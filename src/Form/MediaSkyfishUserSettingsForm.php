<?php

namespace Drupal\media_skyfish\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MediaSkyfishUserSettingsForm.
 */
class MediaSkyfishUserSettingsForm extends ConfigFormBase {

  /**
   * Base url for service.
   */
  public const API_BASE_URL = 'https://api.colourbox.com';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // Do not save user sensitive configuration
    // in any kind if exportable configs, so we return empty config array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_skyfish_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $form['skyfish_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish Username'),
      '#description' => $this->t('Please enter username to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $user->field_skyfish_username->value,
    ];
    $form['skyfish_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Skyfish Password'),
      '#description' => $this->t('Please enter password to login to Skyfish.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $user->field_skyfish_password->value,
    ];
    $form['skyfish_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Key'),
      '#description' => $this->t('Please enter Skyfish API Key here.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $user->field_skyfish_api_user->value,
    ];
    $form['skyfish_api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skyfish API Secret'),
      '#description' => $this->t('Please enter Skyfish API secret key.'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $user->field_skyfish_secret_api_key->value,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $key = $form_state->getValue('skyfish_api_key');
    $secret = $form_state->getValue('skyfish_api_secret');
    $username = $form_state->getValue('skyfish_user');
    $password = $form_state->getValue('skyfish_password');

    if ($this->validateLogins($key, $secret, $username, $password) === FALSE) {
      $form_state->setError($form, 'Incorrect login information.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());

    if ($user) {
      $user->set('field_skyfish_api_user', $values['skyfish_api_key']);
      $user->set('field_skyfish_secret_api_key', $values['skyfish_api_secret']);
      $user->set('field_skyfish_username', $values['skyfish_user']);
      $user->set('field_skyfish_password', $values['skyfish_password']);
      $user->save();
    }
  }

  /**
   * Validate user logins for Skyfish API.
   *
   * @param string $key
   *   Skyfish API key for user.
   * @param string $secret
   *   Skyfish API secret for user.
   * @param string $username
   *   Skyfish API user username.
   * @param string $password
   *   Skyfish API user password.
   *
   * @return bool|string
   *   Skyfish token or false if request invalid.
   */
  public function validateLogins(string $key, string $secret, string $username, string $password) {
    $client = \Drupal::httpClient();

    $hmac = hash_hmac('sha1', $key . ':' . time(), $secret);

    try {
      $request = $client
        ->request('POST',
          self::API_BASE_URL . '/authenticate/userpasshmac',
          [
            'json' =>
              [
                'username' => $username,
                'password' => $password,
                'key' => $key,
                'ts' => time(),
                'hmac' => $hmac,
              ],
          ]);
      $response = json_decode($request->getBody()->getContents(), TRUE);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response['token'] ?? FALSE;
  }

}
