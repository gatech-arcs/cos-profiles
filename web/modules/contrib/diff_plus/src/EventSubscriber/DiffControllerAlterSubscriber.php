<?php

namespace Drupal\diff_plus\EventSubscriber;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\diff\Controller\PluginRevisionController;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Class ControllerAlterSubscriber.
 */
class DiffControllerAlterSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface|null
   */
  protected ?ModerationInformationInterface $moderationInformation = NULL;

  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected UserDataInterface $userData,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::VIEW][] = ['onView', 50];
    return $events;
  }

  /**
   * Sets the moderation information service if available.
   *
   * This method is called internally by the Symfony container.  It's set up
   * this way so that the content moderation module is an optional dependency.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function setModerationInformation(ModerationInformationInterface $moderation_information) {
    $this->moderationInformation = $moderation_information;
  }

  /**
   * Does the provided request have all required diff parameters?
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to check.
   *
   * @return bool
   *   TRUE if all required parameters exist, otherwise FALSE.
   */
  protected function hasRequiredDiffParams(Request $request) {
    $params = $request->attributes->get('_route_params');
    return isset($params['left_revision'], $params['right_revision']);
  }

  /**
   * Is the controller whose output is being altered derived from a diff base?
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to check.
   *
   * @return bool
   *   TRUE if the controller is derived from a diff base, otherwise FALSE.
   */
  protected function isDiffControllerBase(Request $request) {
    $is_diff_controller_base = FALSE;
    $route_object = $request->attributes->get('_route_object');
    if ($route_object instanceof Route) {
      $controller = $route_object->getDefault('_controller');
      if (is_string($controller)) {
        $parts = explode('::', $controller);
        if ($parts) {
          $is_diff_controller_base = \is_subclass_of(
            $parts[0],
            PluginRevisionController::class
          );
        }
      }
    }
    return $is_diff_controller_base;
  }

  /**
   * Is the provided event firing on a diff route?
   *
   * This method dives into some pretty sketchy internal implementation
   * details. It's pretty fragile, so be sure to enable weekly testing...
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The view event to check.
   *
   * @return bool
   *   TRUE if the event is firing on a diff route, otherwise FALSE.
   */
  protected function isDiffRoute(ViewEvent $event) {
    $request = $event->getRequest();
    return $this->isDiffControllerBase($request) &&
      $this->hasRequiredDiffParams($request);
  }

  /**
   * Should this diff route be enhanced?
   *
   * This method checks both default and personalized configuration options
   * in order to provide an optionally customized per-user experience.
   *
   * @return bool
   *   TRUE if the diff route should be enhanced, otherwise FALSE.
   */
  protected function shouldEnhance() {
    $settings = $this->configFactory->get('diff_plus.settings')->get();
    if ($this->currentUser->hasPermission('personalize diff plus settings')) {

      $personalized_settings = $this->userData->get(
        'diff_plus',
        $this->currentUser->id(),
        'settings'
      ) ?: [];

      $settings = array_replace(
        $settings,
        $personalized_settings
      );
    }

    return $settings['enhance_diff_ui'];
  }

  /**
   * Gets the entity that this diff is running on.
   *
   * This method finds the first content entity that is present in the route
   * match. Pedantically, this is somewhat fragile, but should be fine for the
   * foreseeable future.  If not, we'll have to ask the maintainers of the Diff
   * module for a more entity-agnostic solution that is currently available.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity that this diff is running on, or NULL if unknown.
   */
  protected function getDiffEntity() {
    $entity = NULL;
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        $entity = $parameter;
        break;
      }
    }
    return $entity;
  }

  /**
   * Gets the moderation state for the provided revision if available.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to get the moderation state of.
   *
   * @return string|null
   *   The moderation state, or NULL if no moderation state is set.
   */
  protected function getModerationStatus(ContentEntityInterface $revision) {
    $status = NULL;
    if ($this->moderationInformation && $this->moderationInformation->shouldModerateEntitiesOfBundle($revision->getEntityType(), $revision->bundle())) {
      $content_moderation_state = ContentModerationState::loadFromModeratedEntity($revision);
      if ($content_moderation_state) {
        $state_name = $content_moderation_state->get('moderation_state')->value;
        $workflow = $content_moderation_state->get('workflow')->entity;
        $status = $workflow->get('type_settings')['states'][$state_name]['label'];
      }
    }
    return $status;
  }

  /**
   * Gets the published state of the provided revision.
   *
   * This method is used as a fall-back in-case the content moderation module
   * is not installed, or if the entity being reviewed is not enrolled in
   * a content moderation workflow.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to get the published state of.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The published state, or NULL if unknown.
   */
  protected function getPublishedStatus(ContentEntityInterface $revision) {
    $status = NULL;
    if ($revision instanceof EntityPublishedInterface) {
      $status = $revision->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    }
    return $status;
  }

  /**
   * Gets a revision status for the provided revision.
   *
   * This method runs through a series of graceful degradation calls depending
   * on the state of the system and the revision that is passed through.  If
   * the content moderation module applies, the moderation state string is
   * returned, else if the entity type implements the EntityPublishedTrait, the
   * published state ('Published' or 'Unpublished') is returned, else the
   * string 'Unknown' is returned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to check.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The revision status, or the string 'Unknown' if unknown.
   */
  protected function getRevisionStatus(ContentEntityInterface $revision) {
    return $this->getModerationStatus($revision) ?:
      $this->getPublishedStatus($revision) ?:
        $this->t('Unknown');
  }

  /**
   * Gets the revision author if available.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to get the author of.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user that authored the revision, or NULL if permissions deny it.
   */
  protected function getRevisionAuthor(ContentEntityInterface $revision) {
    $user = NULL;
    if ($revision instanceof RevisionLogInterface) {
      $author = $revision->getRevisionUser();
      if ($this->currentUser->id() === $author->id() ||
          $this->currentUser->hasPermission('access user profiles')) {
        $user = $author;
      }
    }
    return $user;
  }

  /**
   * Gets all revision ids for the provided entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get revisions ids for.
   *
   * @return array
   *   An array of all revision ids for the provided entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Should never happen.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Should never happen.
   */
  protected function getAllRevisionIds(ContentEntityInterface $entity) {
    return array_keys($this->entityTypeManager
      ->getStorage($entity->getEntityTypeId())
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition($entity->getEntityType()->getKey('id'), $entity->id())
      ->allRevisions()
      ->sort($entity->getEntityType()->getKey('revision'), 'DESC')
      ->execute());
  }

  /**
   * Generates a contextually aware diff route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is being reviewed.
   * @param string $left_id
   *   The ID of the "left" revision.
   * @param string $right_id
   *   The ID of the "right" revision.
   *
   * @return string
   *   The generated URL.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Should never happen.
   */
  protected function generateDiffRoute(ContentEntityInterface $entity, $left_id, $right_id) {
    $base_url = sprintf(
      '%s/view/%s/%s/%s',
      $entity->toUrl('version-history')->toString(),
      $left_id,
      $right_id,
      $this->routeMatch->getParameter('filter'),
    );

    return Url::fromUserInput($base_url)
      ->setOption(
        'query',
        $this->requestStack->getCurrentRequest()->getQueryString()
      )->toString();
  }

  /**
   * Gets the previous revision URL (if applicable).
   *
   * If the user is viewing consecutive revisions, this method will offer
   * a URL to step backward a single step.  For example, if revisions exist
   * at "1", "2", and "3", and the user is reviewing "2" and "3", then the url
   * generated by this method will be for reviewing "1" and "2". If there is no
   * previous revision, or if the revisions being reviewed are not consecutive,
   * then NULL is returned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being reviewed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $left
   *   The "left" revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right
   *   The "right" revision.
   *
   * @return string|null
   *   The previous revision URL if applicable, otherwise NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Should never happen.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Should never happen.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Should never happen.
   */
  protected function getPreviousRevisionUrl(ContentEntityInterface $entity, ContentEntityInterface $left, ContentEntityInterface $right) {
    $previous_revision_url = NULL;
    if ($this->revisionsConsecutive($entity, $left, $right)) {
      $all_revision_ids = $this->getAllRevisionIds($left);
      $position = array_search($left->getRevisionId(), $all_revision_ids);
      if (isset($all_revision_ids[$position + 1])) {
        $previous_revision_url = $this->generateDiffRoute($entity, $all_revision_ids[$position + 1], $left->getRevisionId());
      }
    }
    return $previous_revision_url;
  }

  /**
   * Gets the next revision URL (if applicable).
   *
   * If the user is viewing consecutive revisions, this method will offer
   * a URL to step forward a single step.  For example, if revisions exist
   * at "1", "2", and "3", and the user is reviewing "1" and "2", then the url
   * generated by this method will be for reviewing "2" and "3". If there is no
   * next revision, or if the revisions being reviewed are not consecutive,
   * then NULL is returned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being reviewed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $left
   *   The "left" revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right
   *   The "right" revision.
   *
   * @return string|null
   *   The next revision URL if applicable, otherwise NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Should never happen.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Should never happen.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Should never happen.
   */
  protected function getNextRevisionUrl(ContentEntityInterface $entity, ContentEntityInterface $left, ContentEntityInterface $right) {
    $next_revision_url = NULL;
    if ($this->revisionsConsecutive($entity, $left, $right)) {
      $all_revision_ids = $this->getAllRevisionIds($right);
      $position = array_search($right->getRevisionId(), $all_revision_ids);
      if (isset($all_revision_ids[$position - 1])) {
        $next_revision_url = $this->generateDiffRoute($entity, $right->getRevisionId(), $all_revision_ids[$position - 1]);
      }
    }
    return $next_revision_url;
  }

  /**
   * Are the "left" and "right" revisions consecutive?
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being reviewed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $left
   *   The "left" revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right
   *   The "right" revision.
   *
   * @return bool
   *   TRUE if the two revisions on consecutive, otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Should never happen.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Should never happen.
   */
  protected function revisionsConsecutive(ContentEntityInterface $entity, ContentEntityInterface $left, ContentEntityInterface $right) {
    $all_revision_ids = $this->getAllRevisionIds($entity);
    $left_position = array_search($left->getRevisionId(), $all_revision_ids);
    $right_position = array_search($right->getRevisionId(), $all_revision_ids);

    return abs($left_position - $right_position) === 1;
  }

  /**
   * Alters the output of the diff controller.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Should never happen.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Should never happen.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Should never happen.
   */
  public function onView(ViewEvent $event) {

    if (!$this->isDiffRoute($event) || !$this->shouldEnhance()) {
      return;
    }

    $entity = $this->getDiffEntity();

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $left */
    $left = $storage->loadRevision($this->routeMatch->getParameter('left_revision'));

    /** @var \Drupal\Core\Entity\ContentEntityInterface $right */
    $right = $storage->loadRevision($this->routeMatch->getParameter('right_revision'));

    $build = $event->getControllerResult();

    $build['ui'] = [
      '#theme' => 'diff_plus_ui',
      '#entity' => $entity,
      '#from_revision' => $left,
      '#to_revision' => $right,
      '#from_revision_url' => $left->toUrl('revision')->toString(),
      '#to_revision_url' => $right->toUrl('revision')->toString(),
      '#from_status' => $this->getRevisionStatus($left),
      '#to_status' => $this->getRevisionStatus($right),
      '#from_revision_author' => $this->getRevisionAuthor($left),
      '#to_revision_author' => $this->getRevisionAuthor($right),
      '#previous_revision_url' => $this->getPreviousRevisionUrl($entity, $left, $right),
      '#next_revision_url' => $this->getNextRevisionUrl($entity, $left, $right),
      '#version_history_url' => $entity->toUrl('version-history')->toString(),
      '#settings' => $build['controls'],
      '#weight' => -10000,
    ];

    // Remove the stock header and controls.
    unset($build['header'], $build['controls']);

    // Attach the CSS required to drive the customized UI.
    $build['#attached']['library'][] = 'diff_plus/diff-ui';

    $event->setControllerResult($build);
  }

}
