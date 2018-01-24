<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ConfigService.
 */
class ConfigService {

  /**
   * Skyfish admin form configs.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Currently logged in user.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * Username to connect to Skyfish.
   *
   * @var string
   */
  protected $username;

  /**
   * Skyfish api key.
   *
   * @var string
   */
  protected $key;

  /**
   * Skyfish apie secret key.
   *
   * @var string
   */
  protected $secret;

  /**
   * Skyfish user password.
   *
   * @var string
   */
  protected $password;

  /**
   * ConfigService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration array containing information about the plugin instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('media_skyfish.adminconfig');
    $this->user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $this->initialize();
  }

  /**
   * Initialize function checks if user's or global data should be user.
   */
  private function initialize() {
    $this->key = empty($this->config->get('media_skyfish_api_key')) ?
      $this->user->field_skyfish_api_user->value : $this->config->get('media_skyfish_api_key');
    $this->secret = empty($this->config->get('media_skyfish_api_secret')) ?
      $this->user->field_skyfish_secret_api_key->value : $this->config->get('media_skyfish_api_secret');
    $this->username = empty($this->config->get('media_skyfish_global_user')) ?
      $this->user->field_skyfish_username->value : $this->config->get('media_skyfish_global_user');
    $this->password = empty($this->config->get('media_skyfish_global_password')) ?
      $this->user->field_skyfish_password->value : $this->config->get('media_skyfish_global_password');
  }

  /**
   * Get Skyfish api key.
   *
   * @return string
   *   Skyfish api key.
   */
  public function getKey(): string {
    return $this->key;
  }

  /**
   * Set Skyfish api key.
   *
   * @param string $key
   *   Skyfish api key.
   *
   * @return $this
   */
  public function setKey(string $key): ConfigService {
    $this->key = $key;

    return $this;
  }

  /**
   * Check if key is not empty.
   *
   * @return bool
   *   If not empty return key.
   */
  public function hasKey() {
    return !empty($this->key);
  }

  /**
   * Get Skyfish secret api key.
   *
   * @return string
   *   Skyfish api secret.
   */
  public function getSecret(): string {
    return $this->secret;
  }

  /**
   * Set Skyfish secret api key.
   *
   * @param string $secret
   *   Skifish api secret.
   *
   * @return $this
   */
  public function setSecret(string $secret): ConfigService {
    $this->secret = $secret;

    return $this;
  }

  /**
   * Get Skyfish username.
   *
   * @return string
   *   Username.
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Set username to login to Skyfish.
   *
   * @param string $username
   *   Skyfish username.
   *
   * @return $this
   *   ConfigService.
   */
  public function setUsername(string $username): ConfigService {
    $this->username = $username;

    return $this;
  }

  /**
   * Get password to login to Skyfish.
   *
   * @return string
   *   Password for the Skyfish.
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Set password to login to Skyfish.
   *
   * @param string $password
   *   Skyfish password.
   *
   * @return $this
   *   ConfigService.
   */
  public function setPassword(string $password): ConfigService {
    $this->password = $password;

    return $this;
  }

  /**
   * Get cache time.
   *
   * @return array|mixed|null
   *   Cache time.
   */
  public function getCacheTime() {
    return $this->config->get('media_skyfish_cache');
  }

  /**
   * Get hmac for authentication.
   *
   * @return string
   *   Hmac string.
   */
  public function getHmac() {
    return hash_hmac('sha1', $this->key . ':' . time(), $this->secret);
  }

}
