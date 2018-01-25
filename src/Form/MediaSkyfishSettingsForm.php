<?php

namespace Drupal\media_skyfish\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Password;

/**
 * Class MediaSkyfishSettingsForm.
 */
class MediaSkyfishSettingsForm extends ConfigFormBase {

  /**
   * Skyfish configs.
   *
   * @return array
   *   Array of Skyfish configs.
   */
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
    $form['skyfish_global_api']['media_skyfish_global_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish Username'),
      '#description' => $this->t('Please enter username to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish_global_user'),
    ];
    $form['skyfish_global_api']['media_skyfish_global_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish Password'),
      '#description' => $this->t('Please enter password to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish_global_password'),
    ];
    $form['skyfish_global_api']['media_skyfish_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Key'),
      '#description' => $this->t('Please enter Skyfish API Key here.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish_api_key'),
    ];
    $form['skyfish_global_api']['media_skyfish_api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Secret'),
      '#description' => $this->t('Please enter Skyfish API secret key.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('media_skyfish_api_secret'),
    ];
    $form['skyfish_global_api']['media_skyfish_cache'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
        ' min' => 0,
        ' max' => 999,
        ' step' => 1,
      ],
      '#title' => $this->t('Cache time in minutes'),
      '#description' => $this->t('Set how long images will be saved in cache.'),
      '#maxlength' => 3,
      '#default_value' => $config->get('media_skyfish_cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('media_skyfish.adminconfig')
      ->set('media_skyfish_api_key', $form_state->getValue('media_skyfish_api_key'))
      ->set('media_skyfish_api_secret', $form_state->getValue('media_skyfish_api_secret'))
      ->set('media_skyfish_global_user', $form_state->getValue('media_skyfish_global_user'))
      ->set('media_skyfish_global_password', $form_state->getValue('media_skyfish_global_password'))
      ->set('media_skyfish_cache', $form_state->getValue('media_skyfish_cache'))
      ->save();
  }

}
