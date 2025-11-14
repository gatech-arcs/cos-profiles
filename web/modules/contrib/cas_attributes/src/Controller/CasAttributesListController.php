<?php

declare(strict_types=1);

namespace Drupal\cas_attributes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for 'cas_attributes.available_attributes' route.
 */
class CasAttributesListController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Lists all currently loaded CAS attributes.
   */
  public function content(Request $request): array {
    $build = [
      '#cache' => [
        'contexts' => [
          'session',
        ],
      ],
    ];

    $session = $request->getSession();

    if (!$this->config('cas_attributes.settings')->get('sitewide_token_support')) {
      $this->messenger()->addError($this->t('You must <a href="@link">enable sitewide token support</a> to view the list of available attributes for the currently logged in user. Note that enabling that feature is not required to define user field mappings, but it is required if you want to use this page.', ['@link' => Url::fromRoute('cas_attributes.settings')->toString()]));
    }
    else {
      if (!$session->get('is_cas_user')) {
        $this->messenger()->addError($this->t('You must login through CAS view available CAS attributes.'));
      }
      else {
        $attributes = $request->getSession()->get('cas_attributes', []);

        $table = [
          '#type' => 'table',
          '#header' => ['Name', 'Token', 'Value'],
          '#empty' => $this->t('There are no CAS attributes associated with your session.'),
          '#caption' => $this->t('This table contains all attributes returned from your CAS server for the currently logged in user.'),
        ];

        $row = 0;
        foreach ($attributes as $attrName => $attrValue) {
          $table[$row] = [
            'name' => ['#plain_text' => $attrName],
            'token' => ['#plain_text' => '[cas:attribute:' . mb_strtolower($attrName . ']')],
            'value' => ['#plain_text' => var_export($attrValue, TRUE)],
          ];
          $row++;
        }

        $build['table'] = $table;
      }
    }

    return $build;
  }

}
