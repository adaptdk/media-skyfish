<?php
/**
 * @file
 * User page callbacks for Media: Skyfish.
 */

/**
 * Callback for usere skyfish settings form.
 *
 * @param string $usr
 *   User id.
 */
function media_skyfish_user_settings_form($form, $form_state, $usr) {
  global $user;

  $prefix = $user->uid . '_';
  $key_name = media_skyfish_variable_name($prefix . 'api_key');
  $secret_key_name = media_skyfish_variable_name($prefix . 'api_secret');

  $form = array();
  $form['api'] = array(
    '#type' => 'fieldset',
    '#title' => t('Skyfish Gloabl API'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['api'][$key_name] = array(
    '#type' => 'textfield',
    '#title' => t('Skyfish API Key'),
    '#default_value' => media_skyfish_variable_get($prefix . 'api_key'),
    '#description' => t('Please enter Skyfish API Key here.'),
  );
  $form['api'][$secret_key_name] = array(
    '#type' => 'textfield',
    '#title' => t('Skyfish API Secret'),
    '#default_value' => media_skyfish_variable_get($prefix . 'api_secret'),
    '#description' => t('Please enter Skyfish API secret key.'),
  );

  return system_settings_form($form);
}
