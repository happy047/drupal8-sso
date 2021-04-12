<?php

namespace Drupal\drupalauth4ssp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure DrupalAuth for SimpleSAMLphp settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupalauth4ssp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drupalauth4ssp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['authsource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authsource'),
      '#default_value' => $this
        ->config('drupalauth4ssp.settings')
        ->get('authsource'),
      '#description' => $this->t('The machine name of the authsource used in SimpleSAMLphp.'),
      '#required' => TRUE,
    ];

    $form['returnto_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed list of URLs for ReturnTo Parameter'),
      '#default_value' => $this
        ->config('drupalauth4ssp.settings')
        ->get('returnto_list'),
      '#description' => $this->t('Enter one URL per line. The "*"(wildcard) character is allowed. Example URLs are www.example.com/specific-path for a certain path and www.example.com* for all the URLs for www.example.com domain (like www.example.com; www.example.com/path1; www.example.com/path2/path3 etc.) and *example.com* for all subdomain paths (like a.example.com; a.example.com/path etc.)'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('drupalauth4ssp.settings')
      ->set('authsource', $form_state->getValue('authsource'))
      ->set('returnto_list', $form_state->getValue('returnto_list'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
