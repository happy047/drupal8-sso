<?php

namespace Drupal\simplesamlphp_auth\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to link SimpleSAMLphp authentication with Drupal users.
 */
class SimplesamlphpDrupalAuth {

  use StringTranslationTrait;

  /**
   * SimpleSAMLphp Authentication helper.
   *
   * @var SimplesamlphpAuthManager
   */
  protected $simplesamlAuth;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The ExternalAuth service.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauth;

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   *
   * @param SimplesamlphpAuthManager $simplesaml_auth
   *   The SimpleSAML Authentication helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalauth
   *   The ExternalAuth service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(SimplesamlphpAuthManager $simplesaml_auth, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, ExternalAuthInterface $externalauth, AccountInterface $account, MessengerInterface $messenger, ModuleHandlerInterface $module_handler) {
    $this->simplesamlAuth = $simplesaml_auth;
    $this->config = $config_factory->get('simplesamlphp_auth.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->externalauth = $externalauth;
    $this->currentUser = $account;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Log in and optionally register a user based on the authname provided.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The logged in Drupal user.
   */
  public function externalLoginRegister($authname) {
    $account = $this->externalauth->login($authname, 'simplesamlphp_auth');
    if (!$account) {
      $account = $this->externalRegister($authname);
    }

    if ($account) {
      // Determine if roles should be evaluated upon login.
      if ($this->config->get('role.eval_every_time')) {
        $this->roleMatchSync($account);
      }
    }

    return $account;
  }

  /**
   * Registers a user locally as one authenticated by the SimpleSAML IdP.
   *
   * @param string $authname
   *   The authentication name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   The registered Drupal user.
   *
   * @throws \Exception
   *   An ExternalAuth exception.
   */
  public function externalRegister($authname) {
    $account = FALSE;

    // It's possible that a user with their username set to this authname
    // already exists in the Drupal database.
    $existing_user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $authname]);
    $existing_user = $existing_user ? reset($existing_user) : FALSE;
    if ($existing_user) {
      // If auto-enable SAML is activated, link this user to SAML.
      if ($this->config->get('autoenablesaml')) {
        if ($this->config->get('debug')) {
          $this->logger->debug('Linking authname %authname to existing Drupal user with ID %id because "Automatically enable SAML authentication for existing users upon successful login" setting is activated.', [
            '%authname' => $authname,
            '%id' => $existing_user->id(),
          ]);
        }
        $this->externalauth->linkExistingAccount($authname, 'simplesamlphp_auth', $existing_user);
        $account = $existing_user;
      }
      else {
        if ($this->config->get('debug')) {
          $this->logger->debug('A local Drupal user with username %authname already exists. Aborting the creation of a SAML-enabled Drupal user.', [
            '%authname' => $authname,
          ]);
        }
        // User is not permitted to login to Drupal via SAML.
        // Log out of SAML and redirect to the front page.
        $this->messenger
          ->addMessage($this->t('We are sorry, your user account is not SAML enabled.'), 'status');
        $this->simplesamlAuth->logout(base_path());
        return FALSE;
      }
    }
    else {
      // If auto-enable SAML is activated, take more action to find an existing
      // user.
      if ($this->config->get('autoenablesaml')) {
        // Allow other modules to decide if there is an existing Drupal user,
        // based on the supplied SAML atttributes.
        $attributes = $this->simplesamlAuth->getAttributes();
        foreach ($this->moduleHandler->getImplementations('simplesamlphp_auth_existing_user') as $module) {
          $return_value = $this->moduleHandler->invoke($module, 'simplesamlphp_auth_existing_user', [$attributes]);
          if ($return_value instanceof UserInterface) {
            $account = $return_value;
            if ($this->config->get('debug')) {
              $this->logger->debug('Linking authname %authname to existing Drupal user with ID %id because "Automatically enable SAML authentication for existing users upon successful login" setting is activated.', [
                '%authname' => $authname,
                '%id' => $account->id(),
              ]);
            }
            $this->externalauth->linkExistingAccount($authname, 'simplesamlphp_auth', $account);
          }
        }
      }

      // Check the admin settings for simpleSAMLphp and find out if we
      // are allowed to register users.
      if (!$this->config->get('register_users')) {
        // We're not allowed to register new users on the site through
        // simpleSAML. We let the user know about this and redirect to the
        // user/login page.
        $this->messenger
          ->addMessage($this->t('We are sorry. While you have successfully authenticated, you are not yet entitled to access this site. Please ask the site administrator to provision access for you.'), 'status');
        $this->simplesamlAuth->logout(base_path());

        return FALSE;
      }
    }

    if (!$account) {
      // Create the new user.
      try {
        $account = $this->externalauth->register($authname, 'simplesamlphp_auth');
      }
      catch (\Exception $ex) {
        watchdog_exception('simplesamlphp_auth', $ex);
        $this->messenger
          ->addMessage($this->t('Error registering user: An account with this username already exists.'), 'error');
      }
    }

    if ($account) {
      $this->synchronizeUserAttributes($account, TRUE);
      return $this->externalauth->userLoginFinalize($account, $authname, 'simplesamlphp_auth');
    }
  }

  /**
   * Synchronizes user data if enabled.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The Drupal account to synchronize attributes on.
   * @param bool $force
   *   Define whether to force syncing of the user attributes, regardless of
   *   SimpleSAMLphp settings.
   */
  public function synchronizeUserAttributes(AccountInterface $account, $force = FALSE) {
    $sync_mail = $force || $this->config->get('sync.mail');
    $sync_user_name = $force || $this->config->get('sync.user_name');

    if ($sync_user_name) {
      $name = $this->simplesamlAuth->getDefaultName();
      if ($name) {
        $existing = FALSE;
        $account_search = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
        if ($existing_account = reset($account_search)) {
          if ($account->id() != $existing_account->id()) {
            $existing = TRUE;
            $logger_params = [
              '%username' => $name, '%new_uid' => $this->currentUser->id(),
              '%existing_uid' => $existing_account->id(),
            ];
            $this->logger->critical("Error on synchronizing name attribute for uid %new_uid: an account with the username %username and uid %existing_uid already exists.", $logger_params);
            $this->messenger->addMessage($this->t('Error synchronizing username: an account with this username already exists.'), 'error');
          }
        }

        if (!$existing) {
          $account->setUsername($name);
        }
      }
      else {
        $this->logger->critical("Error on synchronizing name attribute: no username available for Drupal user %id.", ['%id' => $account->id()]);
        $this->messenger->addMessage($this->t('Error synchronizing username: no username is provided by SAML.'), 'error');
      }
    }

    if ($sync_mail && $this->config->get('mail_attr')) {
      $mail = $this->simplesamlAuth->getDefaultEmail();
      if ($mail) {
        $account->setEmail($mail);
      }
      else {
        $this->logger->critical("Error on synchronizing mail attribute: no email address available for Drupal user %id.", ['%id' => $account->id()]);
        $this->messenger->addMessage($this->t('Error synchronizing mail: no email address is provided by SAML.'), 'error');
      }
    }

    if ($sync_mail || $sync_user_name) {
      $account->save();
    }
  }

  /**
   * Synchronizes (adds/removes) user account roles.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to sync roles for.
   */
  public function roleMatchSync(UserInterface $account) {
    // Get matching roles based on retrieved SimpleSAMLphp attributes.
    $matching_roles = $this->getMatchingRoles();
    // Get user's current roles, excluding locked roles (e.g. Authenticated).
    $current_roles = $account->getRoles(TRUE);
    // Set boolean to only update account when needed.
    $account_updated = FALSE;

    // Remove non-locked roles not mapped to the user via SAML.
    foreach (array_diff($current_roles, $matching_roles) as $role_id) {
      if ($this->config->get('debug')) {
        $this->logger->debug('Removing role %role from user %name', [
          '%role' => $role_id,
          '%name' => $account->getAccountName(),
        ]);
      }
      $account->removeRole($role_id);
      $account_updated = TRUE;
    }

    // Add roles mapped to the user via SAML.
    foreach (array_diff($matching_roles, $current_roles) as $role_id) {
      if ($this->config->get('debug')) {
        $this->logger->debug('Adding role %role to user %name', [
          '%role' => $role_id,
          '%name' => $account->getAccountName(),
        ]);
      }
      $account->addRole($role_id);
      $account_updated = TRUE;
    }
    if ($account_updated) {
      $account->save();
    }
  }

  /**
   * Get matching user roles to assign to user.
   *
   * Matching roles are based on retrieved SimpleSAMLphp attributes.
   *
   * @return array
   *   List of matching roles to assign to user.
   */
  public function getMatchingRoles() {
    $roles = [];
    // Obtain the role map stored. The role map is a concatenated string of
    // rules which, when SimpleSAML attributes on the user match, will add
    // roles to the user.
    // The full role map string, when mapped to the variables below, presents
    // itself thus:
    // $role_id:$key,$op,$value;$key,$op,$value;|$role_id:$key,$op,$value etc.
    if ($rolemap = $this->config->get('role.population')) {

      foreach (explode('|', $rolemap) as $rolerule) {
        list($role_id, $role_eval) = explode(':', $rolerule, 2);

        foreach (explode(';', $role_eval) as $role_eval_part) {
          if ($this->evalRoleRule($role_eval_part)) {
            $roles[$role_id] = $role_id;
          }
        }
      }
    }

    $attributes = $this->simplesamlAuth->getAttributes();
    $this->moduleHandler->alter('simplesamlphp_auth_user_roles', $roles, $attributes);
    return $roles;
  }

  /**
   * Determines whether a role should be added to an account.
   *
   * @param string $role_eval_part
   *   Part of the role evaluation rule.
   *
   * @return bool
   *   Whether a role should be added to the Drupal account.
   */
  protected function evalRoleRule($role_eval_part) {
    list($key, $op, $value) = explode(',', $role_eval_part);
    $attributes = $this->simplesamlAuth->getAttributes();

    if ($this->config->get('debug')) {
      $this->logger->debug('Evaluate rule (key=%key,operator=%op,value=%val', [
        '%key' => $key,
        '%op' => $op,
        '%val' => $value,
      ]);
    }

    if (!array_key_exists($key, $attributes)) {
      return FALSE;
    }
    $attribute = $attributes[$key];

    // A '=' requires the $value exactly matches the $attribute, A '@='
    // requires the portion after a '@' in the $attribute to match the
    // $value and a '~=' allows the value to match any part of any
    // element in the $attribute array.
    switch ($op) {
      case '=':
        return in_array($value, $attribute);

      case '@=':
        list($before, $after) = explode('@', array_shift($attribute));
        return ($after == $value);

      case '~=':
        return array_filter($attribute, function ($subattr) use ($value) {
          return strpos($subattr, $value) !== FALSE;
        });
    }
  }

}
