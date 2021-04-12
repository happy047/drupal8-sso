<?php

/**
 * @file
 * Post update hooks for drupalauth4ssp.
 */

/**
 * Add the authsource configuration parameter.
 */
function drupalauth4ssp_post_update_add_authsource_param() {
  $config = \Drupal::service('config.factory')
    ->getEditable('drupalauth4ssp.settings');
  if (!$config->get('authsource')) {
    $config->set('authsource', 'drupal-userpass')
      ->clear('cookie_name')
      ->save();
  }
}
