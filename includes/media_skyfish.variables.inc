<?php
///**
// * @file
// * Variable defaults for Media: skyfish.
// */
//
///**
// * Define our constants.
// */
//define('MEDIA_SKYFISH_API_BASE_URL', 'https://api.colourbox.com');
//define('MEDIA_SKYFISH_NAMESPACE', 'media_skyfish__');
//
///**
// * Get variable for skyfish.
// *
// * @param string $name
// *   String variable name.
// * @param string $default
// *   String default variable value.
// *
// * @return string
// *   Data pulled from variable.
// */
//function media_skyfish_variable_get($name, $default = NULL) {
//  $variable_name = media_skyfish_variable_name($name);
//  return variable_get($variable_name, $default);
//}
//
///**
// * Get skyfish variable name.
// *
// * @param string $name
// *   Variable name without prefix.
// *
// * @return string string
// *   String for real variable name.
// */
//function media_skyfish_variable_name($name) {
//  return MEDIA_SKYFISH_NAMESPACE . $name;
//}
//
///**
// * Get variable for user.
// *
// * @param string $name
// *   Variable name.
// *
// * @return null
// *   Data stored in variable.
// */
//function media_skyfish_get_user_variables($name) {
//  global $user;
//  return media_skyfish_variable_get($user->uid . '_' . $name);
//}
//
///**
// * Return skyfish api key and secret key.
// *
// * If user did not provide api keys, use defaults.
// *
// * @return array $api
// *   Array containing api key and api secrect.
// */
//function media_skyfish_get_api() {
//  $api = array();
//  $api['api_key'] = media_skyfish_get_user_variables('api_key');
//  $api['api_secret'] = media_skyfish_get_user_variables('api_secret');
//  if (empty($api['api_key']) || empty($api['api_secret'])) {
//    // User has no skyfech api set, use default one.
//    $api['api_key'] = media_skyfish_variable_get('api_key');
//    $api['api_secret'] = media_skyfish_variable_get('api_secret');
//  }
//
//  return $api;
//}
