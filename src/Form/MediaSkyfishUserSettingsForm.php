<?php

namespace Drupal\media_skyfish\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MediaSkyfishUserSettingsForm.
 */
class MediaSkyfishUserSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
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
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
     if($user){
       $user->set('field_skyfish_api_user', $values['skyfish_api_key']);
       $user->set('field_skyfish_secret_api_key', $values['skyfish_api_secret']);
       $user->save();
     }
  }

}
