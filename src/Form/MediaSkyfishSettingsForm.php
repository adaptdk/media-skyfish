<?php

namespace Drupal\media_skyfish\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MediaSkyfishSettingsForm.
 */
class MediaSkyfishSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'media_skyfish.adminconfig'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_skyfish_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('media_skyfish.adminconfig');
    $form['skyfish_global_api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Skyfish Global API'),
    ];
    $form['media_skyfish__api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Key'),
      '#description' => $this->t('Please enter Skyfish API Key here.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish__api_key'),
    ];
    $form['media_skyfish__api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Secret'),
      '#description' => $this->t('Please enter Skyfish API secret key.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish__api_secret'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    parent::submitForm($form, $form_state);

    $this->config('media_skyfish.adminconfig')
      ->set('media_skyfish__api_key', $form_state->getValue('media_skyfish__api_key'))
      ->set('media_skyfish__api_secret', $form_state->getValue('media_skyfish__api_secret'))
      ->save();
  }

}
