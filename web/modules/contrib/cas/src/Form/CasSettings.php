<?php

declare(strict_types=1);

namespace Drupal\cas\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cas\Model\CasProtocolVersion;
use Drupal\cas\Model\EmailAssignment;
use Drupal\cas\Model\GatewayMethod;
use Drupal\cas\Model\HttpScheme;
use Drupal\cas\Model\SslCertificateVerification;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides the CAS settings form.
 */
class CasSettings extends ConfigFormBase {

  use AutowireTrait;

  // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint -- strict type the $typedConfigManager param as soon as Drupal 10 support is dropped.
  public function __construct(
    ConfigFactoryInterface $configFactory,
    protected readonly ExecutableManagerInterface $conditionPluginManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'config.typed')]
    $typedConfigManager,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
    // @todo Strict type the param and remove the line when D10 support is gone.
    $this->typedConfigManager = $typedConfigManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cas_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('cas.settings');
    $form['#tree'] = TRUE;

    $form['server'] = [
      '#type' => 'details',
      '#title' => $this->t('CAS server'),
      '#description' => $this->t('Enter the details of your CAS server.'),
      '#open' => TRUE,
    ];
    $form['server']['version'] = [
      '#type' => 'radios',
      '#title' => $this->t('CAS Protocol version'),
      '#description' => $this->t('The CAS protocol version your CAS server supports. If unsure, ask your CAS server administrator.'),
      '#config_target' => 'cas.settings:server.version',
      '#options' => CasProtocolVersion::getAsOptions(),
    ];
    $form['server']['protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('HTTP Protocol'),
      '#description' => $this->t('HTTP protocol type of the CAS server. WARNING: Do not use HTTP on production environments!'),
      '#config_target' => 'cas.settings:server.protocol',
      '#options' => HttpScheme::getAsOptions(),
    ];
    $form['server']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the CAS server.'),
      '#config_target' => 'cas.settings:server.hostname',
      '#size' => 30,
    ];
    $form['server']['port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#description' => $this->t('443 is the standard SSL port. 8443 is the standard non-root port for Tomcat.'),
      '#config_target' => 'cas.settings:server.port',
      '#min' => 0,
      '#max' => 65535,
    ];
    $form['server']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('If the CAS server paths (like /login) are not at the root of the host, specify the base path (e.g., /cas).'),
      '#config_target' => 'cas.settings:server.path',
      '#size' => 30,
    ];
    $form['server']['verify'] = [
      '#type' => 'radios',
      '#title' => 'SSL Verification',
      '#description' => $this->t("Choose an appropriate option for verifying the SSL/TLS certificate of your CAS server."),
      '#config_target' => 'cas.settings:server.verify',
      '#options' => SslCertificateVerification::getAsOptions(),
    ];
    $form['server']['cert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Certificate Authority PEM Certificate'),
      '#description' => $this->t('The PEM certificate of the Certificate Authority that issued the certificate on the CAS server, used only with the custom certificate option above.'),
      '#config_target' => 'cas.settings:server.cert',
      '#states' => [
        'visible' => [
          ':input[name="server[verify]"]' => ['value' => SslCertificateVerification::Custom->value],
        ],
      ],
    ];

    $form['login_link_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place a link to log in via CAS on the standard /user/login form'),
      '#description' => $this->t('Note that even when enabled, the CAS module will not hide the standard Drupal login. If CAS is the primary way your users will log in, it is recommended to alter the login page in a custom module to hide the standard form.'),
      '#config_target' => 'cas.settings:login_link_enabled',
    ];
    $form['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login link text'),
      '#config_target' => 'cas.settings:login_link_label',
      '#states' => [
        'visible' => [
          ':input[name="login_link_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['login_success_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login success message'),
      '#description' => $this->t('The message to output to users upon successful login. Leave blank to output no message.'),
      '#config_target' => 'cas.settings:login_success_message',
    ];

    $form['user_accounts'] = [
      '#type' => 'details',
      '#title' => $this->t('User Account Handling'),
      '#open' => TRUE,
    ];
    $form['user_accounts']['prevent_normal_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent normal login for CAS users (recommended)'),
      '#description' => $this->t('Prevents any user associated with CAS from authenticating using the normal login form. If attempted, users will be presented with an error message and a link to login via CAS instead.'),
      '#config_target' => 'cas.settings:user_accounts.prevent_normal_login',
    ];
    $form['user_accounts']['restrict_password_management'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict password management (recommended)'),
      '#description' => $this->t('Prevents CAS users from changing their Drupal password by removing the password fields on the user profile form and disabling the "forgot password" functionality. Admins will still be able to change Drupal passwords for CAS users.'),
      '#config_target' => 'cas.settings:user_accounts.restrict_password_management',
    ];
    $form['user_accounts']['restrict_email_management'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict email management (recommended)'),
      '#description' => $this->t("Prevents CAS users from changing their email by disabling the email field on the user profile form. Admins will still be able to change email addresses for CAS users. Note that Drupal requires a user enter their current password before changing their email, which your users may not know. Enable the restricted password management feature above to remove this password requirement."),
      '#config_target' => 'cas.settings:user_accounts.restrict_email_management',
    ];
    $form['user_accounts']['auto_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically register users'),
      '#description' => $this->t('Enable to automatically create local Drupal accounts for first-time CAS logins. If disabled, users must be pre-registered before being allowed to log in.'),
      '#config_target' => 'cas.settings:user_accounts.auto_register',
    ];
    $form['user_accounts']['auto_register_follow_registration_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Follow site's account registration policy"),
      '#description' => $this->t('With auto-register on, will follow the user account registration policy. For instance, if the <a href=":url">account settings</a> "%option" is selected under "%field", the auto-created account will wait for administrator approval.', [
        '%option' => $this->t('Visitors, but administrator approval is required'),
        '%field' => $this->t('Who can register accounts?'),
        ':url' => Url::fromRoute('entity.user.admin_form')->toString(),
      ]),
      '#config_target' => 'cas.settings:user_accounts.auto_register_follow_registration_policy',
      '#states' => [
        'visible' => [
          ':input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$this->moduleHandler->moduleExists('cas_attributes')) {
      $form['user_accounts']['cas_attributes_callout'] = [
        '#prefix' => '<p class="messages messages--status">',
        '#markup' => $this->t('If your CAS server supports <a href="@attributes" target="_blank">attributes</a>, you can install the <a href="@module" target="_blank">CAS Attributes</a> module to map them to user fields and roles during login and auto-registration.', [
          '@attributes' => 'https://apereo.github.io/cas/5.1.x/protocol/CAS-Protocol-Specification.html#255-attributes-cas-30',
          '@module' => 'https://drupal.org/project/cas_attributes',
        ]),
        '#suffix' => '</p>',
      ];
    }
    $form['user_accounts']['email_assignment_strategy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Email address assignment'),
      '#description' => $this->t("Drupal requires every user to have an email address. Select how you'd like to assign an email to automatically registered users."),
      '#config_target' => 'cas.settings:user_accounts.email_assignment_strategy',
      '#options' => EmailAssignment::getAsOptions(),
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['user_accounts']['email_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email hostname'),
      '#description' => $this->t("The email domain name used to combine with the username to form the user's email address."),
      '#field_prefix' => $this->t('username@'),
      '#config_target' => 'cas.settings:user_accounts.email_hostname',
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
          'input[name="user_accounts[email_assignment_strategy]"]' => ['value' => EmailAssignment::Standard->value],
        ],
      ],
    ];
    $form['user_accounts']['email_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email attribute'),
      '#description' => $this->t("The CAS attribute name (case sensitive) that contains the user's email address. If unsure, check with your CAS server administrator to see a list of attributes that are returned during login."),
      '#config_target' => 'cas.settings:user_accounts.email_attribute',
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
          'input[name="user_accounts[email_assignment_strategy]"]' => ['value' => EmailAssignment::FromAttribute->value],
        ],
      ],
    ];
    $form['user_accounts']['auto_assigned_roles_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically assign roles on user registration'),
      '#description' => $this->t('To provide role mappings based on CAS attributes, install and configure the optional <a href="@module" target="_blank">CAS Attributes</a> module.', ['@module' => 'https://drupal.org/project/cas_attributes']),
      '#default_value' => count($config->get('user_accounts.auto_assigned_roles')) > 0,
      '#states' => [
        'invisible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $roles = array_map(
      fn(RoleInterface $role): string => $role->label(),
      array_filter(
        $this->entityTypeManager->getStorage('user_role')->loadMultiple(),
        fn(RoleInterface $role): bool => !in_array($role->id(), [
          RoleInterface::ANONYMOUS_ID,
          RoleInterface::AUTHENTICATED_ID,
        ], TRUE),
      ),
    );

    $form['user_accounts']['auto_assigned_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Roles'),
      '#description' => $this->t('The selected roles will be automatically assigned to each CAS user on login. Use this to automatically give CAS users additional privileges or to identify CAS users to other modules.'),
      '#config_target' => 'cas.settings:user_accounts.auto_assigned_roles',
      '#options' => $roles,
      '#states' => [
        'invisible' => [
          'input[name="user_accounts[auto_assigned_roles_enable]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['error_handling'] = [
      '#type' => 'details',
      '#title' => $this->t('Error Handling'),
    ];
    $form['error_handling']['login_failure_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login failure page'),
      '#description' => $this->t('If CAS login fails for any reason (e.g. validation failure or some other module prevents login), redirect the user to this page. If empty, users will be redirected to the homepage or to the original page they were on when initiating a login sequence. If your site is configured to automatically log users in via CAS when accessing a restricted page, you should set this to a page that does not require authentication to view. Otherwise you will create a redirect loop for users that that experience login failures as CAS continuously attempts to log them in as it returns them to the restricted page.'),
      '#config_target' => 'cas.settings:error_handling.login_failure_page',
    ];
    $form['error_handling']['messages_help'] = [
      [
        '#markup' => $this->t('Error messages'),
        '#prefix' => '<h6>',
        '#suffix' => '</h6>',
      ],
      [
        '#markup' => $this->t('Replacement tokens can be used to customize the messages.'),
      ],
    ];
    if (!$this->moduleHandler->moduleExists('token')) {
      $form['error_handling']['messages_help'][] = [
        '#prefix' => ' ',
        '#markup' => $this->t('Install the <a href="https://www.drupal.org/project/token">Token</a> module to see what tokens are available.'),
      ];
    }
    $form['error_handling']['message_validation_failure'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ticket validation failure'),
      '#description' => $this->t('During the CAS authentication process, the CAS server provides Drupal with a "ticket" which is then exchanged for user details (e.g. username and other attributes). This message will be displayed if there is a problem during this process.'),
      '#config_target' => 'cas.settings:error_handling.message_validation_failure',
      '#rows' => 3,
    ];
    $form['error_handling']['message_no_local_account'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Local account does not exist'),
      '#description' => $this->t('Displayed when a new user attempts to login via CAS and automatic registration is disabled.'),
      '#config_target' => 'cas.settings:error_handling.message_no_local_account',
      '#rows' => 3,
    ];
    $form['error_handling']['message_subscriber_denied_reg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Denied registration'),
      '#description' => $this->t('Displayed when some other module (like CAS Attributes) denies automatic registration of a new user.'),
      '#config_target' => 'cas.settings:error_handling.message_subscriber_denied_reg',
      '#rows' => 3,
    ];
    $form['error_handling']['message_subscriber_denied_login'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Denied login'),
      '#description' => $this->t('Displayed when some other module (like CAS Attributes) denies login of a user.'),
      '#config_target' => 'cas.settings:error_handling.message_subscriber_denied_login',
      '#rows' => 3,
    ];
    $form['error_handling']['message_account_blocked'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Local account is blocked'),
      '#description' => $this->t('Displayed when the Drupal user account belonging to the user logging in via CAS is blocked.'),
      '#config_target' => 'cas.settings:error_handling.message_account_blocked',
      '#rows' => 3,
    ];
    $form['error_handling']['message_username_already_exists'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Local account username already exists'),
      '#description' => $this->t('Displayed when automatic registration of new user fails because an existing Drupal user is using the same username.'),
      '#config_target' => 'cas.settings:error_handling.message_username_already_exists',
      '#rows' => 3,
    ];
    $form['error_handling']['message_prevent_normal_login'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Prevent normal login error message'),
      '#description' => $this->t('Displayed when prevent normal login for CAS users is on and a CAS user tries to logon using the normal Drupal login form.'),
      '#config_target' => 'cas.settings:error_handling.message_prevent_normal_login',
      '#states' => [
        'disabled' => [
          ':input[name="user_accounts[prevent_normal_login]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['error_handling']['message_restrict_password_management'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Restrict password management error message'),
      '#description' => $this->t('Displayed when restrict password management is on and a CAS user tries to reset their Drupal password.'),
      '#config_target' => 'cas.settings:error_handling.message_restrict_password_management',
      '#rows' => 3,
      '#states' => [
        'disabled' => [
          ':input[name="user_accounts[restrict_password_management]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['error_handling']['messages_tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['cas'],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }

    $form['gateway'] = [
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login)'),
      '#open' => FALSE,
    ];
    $form['gateway']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Gateway login enabled'),
      '#description' => $this->t(
        'This implements the <a href="@cas-gateway">Gateway feature</a> of the CAS Protocol. When enabled, Drupal will check if a visitor has an active CAS session, and if so, will be automatically log them into Drupal. This is done by quickly redirecting them to the CAS server to perform the active session check, and then redirecting them back to page they initially requested.<br/><br/>If enabled, all pages on your site will trigger this feature by default unless you specify specific pages below.<br/><br/><strong>WARNING:</strong> This feature may disable page caching on pages it is active on. See "Method" below.',
        ['@cas-gateway' => 'https://wiki.jasig.org/display/CAS/gateway']
      ),
      '#config_target' => 'cas.settings:gateway.enabled',
    ];
    $gatewayEnabledStates = [
      'visible' => [
        'input[name="gateway[enabled]"]' => ['checked' => TRUE],
      ],
    ];
    $form['gateway']['recheck_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Recheck time'),
      '#description' => $this->t('After initially checking if the visitor has an active CAS session, this is the amount of time to wait before checking again. Every check redirects the user to the CAS server and then back to the page they were on.'),
      '#config_target' => 'cas.settings:gateway.recheck_time',
      '#options' => [
        -1 => $this->t('Every page request (not recommended)'),
        30 => $this->t('30 minutes'),
        60 => $this->t('1 hour'),
        120 => $this->t('2 hours'),
        180 => $this->t('3 hours'),
        360 => $this->t('6 hours'),
        540 => $this->t('9 hours'),
        720 => $this->t('12 hours'),
        1440 => $this->t('24 hours'),
      ],
      '#states' => $gatewayEnabledStates,
    ];
    $gatewayPaths = $this->conditionPluginManager->createInstance('request_path', $config->get('gateway.paths'));
    $form_state->set('gatewayPaths', $gatewayPaths);
    $form['gateway']['paths'] = $gatewayPaths->buildConfigurationForm([], $form_state);
    $form['gateway']['paths']['id']['#config_target'] = 'cas.settings:gateway.paths.id';
    $form['gateway']['paths']['pages']['#states'] = $gatewayEnabledStates;
    $form['gateway']['paths']['pages']['#config_target'] = 'cas.settings:gateway.paths.pages';
    $form['gateway']['paths']['negate']['#states'] = $gatewayEnabledStates;
    $form['gateway']['paths']['negate']['#config_target'] = 'cas.settings:gateway.paths.negate';

    $form['gateway']['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method'),
      '#description' => $this->t('Configure how the redirect to the CAS server is performed.'),
      '#config_target' => 'cas.settings:gateway.method',
      '#options' => GatewayMethod::getAsOptions(),
      '#states' => $gatewayEnabledStates,
    ];

    $form['forced_login'] = [
      '#type' => 'details',
      '#title' => $this->t('Forced login'),
      '#open' => FALSE,
    ];
    $form['forced_login']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Forced login enabled'),
      '#description' => $this->t('When enabled, anonymous users will be forced to login through CAS on the pages you specify. <strong>If enabled and no specific pages are specified below, it will trigger on every page.</strong>'),
      '#config_target' => 'cas.settings:forced_login.enabled',
    ];
    $forcedLoginEnabledStates = [
      'visible' => [
        'input[name="forced_login[enabled]"]' => ['checked' => TRUE],
      ],
    ];
    $forcedLoginPaths = $this->conditionPluginManager->createInstance('request_path', $config->get('forced_login.paths'));
    $form_state->set('forcedLoginPaths', $forcedLoginPaths);
    $form['forced_login']['paths'] = $forcedLoginPaths->buildConfigurationForm([], $form_state);
    $form['forced_login']['paths']['id']['#config_target'] = 'cas.settings:forced_login.paths.id';
    $form['forced_login']['paths']['pages']['#states'] = $forcedLoginEnabledStates;
    $form['forced_login']['paths']['pages']['#config_target'] = 'cas.settings:forced_login.paths.pages';
    $form['forced_login']['paths']['negate']['#states'] = $forcedLoginEnabledStates;
    $form['forced_login']['paths']['negate']['#config_target'] = 'cas.settings:forced_login.paths.negate';

    $form['logout'] = [
      '#type' => 'details',
      '#title' => $this->t('Log out behavior'),
      '#open' => FALSE,
    ];
    $form['logout']['cas_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Drupal logout triggers CAS logout'),
      '#description' => $this->t('When enabled, users that log out of your Drupal site will then be logged out of your CAS server as well. This is done by redirecting the user to the CAS logout page.'),
      '#config_target' => 'cas.settings:logout.cas_logout',
    ];
    $form['logout']['logout_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log out destination'),
      '#description' => $this->t('Drupal path or URL. Enter a destination if you want the CAS Server to redirect the user after logging out of CAS.'),
      '#config_target' => 'cas.settings:logout.logout_destination',
    ];
    $form['logout']['enable_single_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable single log out?'),
      '#description' => $this->t('If enabled (and your CAS server supports it), users will be logged out of your Drupal site when they log out of your CAS server. <strong>WARNING:</strong> THIS WILL BYPASS A SECURITY HARDENING FEATURE ADDED IN DRUPAL 8, causing session IDs to be stored un-hashed in the database.'),
      '#config_target' => 'cas.settings:logout.enable_single_logout',
    ];
    $form['logout']['single_logout_session_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Max lifetime of session mapping data'),
      '#description' => $this->t("This module stores a mapping of Drupal session IDs to CAS server session IDs to support single logout. Normally this data is cleared automatically when a user is logged out, but not always. To make sure this storage doesn't grow out of control, session mapping data older than the specified amount of days is cleared during cron. This should be a length of time slightly longer than the session lifetime of your Drupal site or CAS server."),
      '#config_target' => 'cas.settings:logout.single_logout_session_lifetime',
      '#field_suffix' => $this->t('days'),
      '#min' => 0,
      '#states' => [
        'visible' => [
          'input[name="logout[enable_single_logout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['proxy'] = [
      '#type' => 'details',
      '#title' => $this->t('Proxy'),
      '#open' => FALSE,
      '#description' => $this->t('These options relate to the proxy feature of the CAS protocol, including configuring this client as a proxy and configuring this client to accept proxied connections from other clients.'),
    ];
    $form['proxy']['initialize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Initialize this client as a proxy?'),
      '#description' => $this->t('Initializing this client as a proxy allows it to access CAS-protected resources from other clients that have been configured to accept it as a proxy.'),
      '#config_target' => 'cas.settings:proxy.initialize',
    ];
    $form['proxy']['can_be_proxied'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this client to be proxied?'),
      '#description' => $this->t("Allow other CAS clients to access this site's resources via the CAS proxy protocol. You will need to configure a list of allowed proxies below."),
      '#config_target' => 'cas.settings:proxy.can_be_proxied',
    ];
    $form['proxy']['proxy_chains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed proxy chains'),
      '#description' => $this->t('A list of proxy chains to allow proxy connections from. Each line is a chain, and each chain is a whitespace delimited list of URLs for an allowed proxy in the chain, listed from most recent (left) to first (right). Each URL in the chain can be either a plain URL or a URL-matching regular expression (delimited only by slashes). Only if the proxy list returned by the CAS Server exactly matches a chain in this list will a proxy connection be allowed.'),
      '#config_target' => 'cas.settings:proxy.proxy_chains',
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    ];
    $form['advanced']['debug_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t('This is not meant for production sites! Enable this to log debug information about the interactions with the CAS Server to the Drupal log.'),
      '#config_target' => 'cas.settings:advanced.debug_log',
    ];
    $form['advanced']['connection_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Connection timeout'),
      '#description' => $this->t('This module makes HTTP requests to your CAS server and, if configured as a proxy, to a proxied service. This value determines the maximum amount of time to wait on those requests before canceling them.'),
      '#config_target' => 'cas.settings:advanced.connection_timeout',
      '#min' => 0,
      '#field_suffix' => $this->t('seconds'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $form_state->get('gatewayPaths')->validateConfigurationForm($form, $condition_values);
    foreach ($condition_values->getErrors() as $error) {
      $form_state->setErrorByName('gateway][paths', $error);
    }
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $form_state->get('forcedLoginPaths')->validateConfigurationForm($form, $condition_values);
    foreach ($condition_values->getErrors() as $error) {
      $form_state->setErrorByName('forced_login][paths', $error);
    }

    $ssl_verification_method = $form_state->getValue(['server', 'verify']);
    $cert_path = $form_state->getValue(['server', 'cert']);
    if ($ssl_verification_method == SslCertificateVerification::Custom->value && !file_exists($cert_path)) {
      $form_state->setErrorByName('server][cert', $this->t('The path you provided to the custom PEM certificate for your CAS server does not exist or is not readable. Verify this path and try again.'));
    }

    if ($form_state->getValue(['user_accounts', 'auto_register'])) {
      $follow_registration_policy = $form_state->getValue([
        'user_accounts',
        'auto_register_follow_registration_policy',
      ]);
      if ($follow_registration_policy && $this->config('user.settings')->get('register') === UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
        $form_state->setErrorByName('user_accounts][auto_register_follow_registration_policy', $this->t('Auto-registering accounts is not possible while following the account registration policy because the policy requires that new accounts to be created only by administrators. Either uncheck <em>Follow site\'s account registration policy</em> or change the policy at <a href=":url">account settings</a>.', [
          ':url' => Url::fromRoute('entity.user.admin_form')->toString(),
        ]));
      }
      $email_assignment_strategy = $form_state->getValue([
        'user_accounts',
        'email_assignment_strategy',
      ]);
      if ($email_assignment_strategy == EmailAssignment::Standard->value && empty($form_state->getValue([
        'user_accounts',
        'email_hostname',
      ]))) {
        $form_state->setErrorByName('user_accounts][email_hostname', $this->t('You must provide a hostname for the auto assigned email address.'));
      }
      elseif ($email_assignment_strategy == EmailAssignment::FromAttribute->value && empty($form_state->getValue([
        'user_accounts',
        'email_attribute',
      ]))) {
        $form_state->setErrorByName('user_accounts][email_attribute', $this->t('You must provide an attribute name for the auto assigned email address.'));
      }

      if ($form_state->getValue(['server', 'version']) == CasProtocolVersion::Version1->value && $email_assignment_strategy == EmailAssignment::FromAttribute->value) {
        $form_state->setErrorByName('user_accounts][email_assignment_strategy', $this->t("The CAS protocol version you've specified does not support attributes, so you cannot assign user emails from a CAS attribute value."));
      }
    }

    $error_page_val = $form_state->getValue([
      'error_handling',
      'login_failure_page',
    ]);
    if ($error_page_val) {
      $error_page_val = trim($error_page_val);
      if (!str_starts_with($error_page_val, '/')) {
        $form_state->setErrorByName('error_handling][login_failure_page', $this->t('Path must begin with a forward slash.'));
      }
    }

    $method = $form_state->getValue(['gateway', 'method']);
    $recheck_time = $form_state->getValue(['gateway', 'recheck_time']);
    if ($method == GatewayMethod::ClientSide->value && $recheck_time == -1) {
      $form_state->setErrorByName('gateway][method', $this->t('The "Every page request" recheck time is not compatible with the "Client-side" method.'));
    }

    $path = ['user_accounts', 'auto_assigned_roles'];
    $form_state->setValue($path, array_keys(array_filter($form_state->getValue($path))));
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cas.settings'];
  }

}
