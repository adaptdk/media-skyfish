<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class ConfigService.
 */
class ConfigService {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(ConfigFactoryInterface $configFactory, Client $client) {
    $this->client = $client;
    $this->config = $configFactory->get('media_skyfish.adminconfig');
    $this->user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $this->initialize();
  }

  private function initialize(){
    $this->key = $this->config->get('media_skyfish_api_key')->isEmpty() ?
      $this->user->field_skyfish_api_user->value : $this->config->get('media_skyfish_api_key');
    $this->secret = $this->config->get('media_skyfish_api_secret')->isEmpty() ?
      $this->user->field_skyfish_secret_api_key->value : $this->config->get('media_skyfish_api_secret');
    $this->username = $this->config->get('media_skyfish_global_user')->isEmpty() ?
      $this->user->field_skyfish_username->value : $this->config->get('media_skyfish_global_user');
    $this->key = $this->config->get('media_skyfish_global_password')->isEmpty() ?
      $this->user->field_skyfish_password->value : $this->config->get('media_skyfish_global_password');
  }

  /**
   * Get Skyfish api key.
   *
   * @return string
   */
  public function getKey(): string {
    return $this->key;
  }

  /**
   * Set Skyfish api key.
   *
   * @param string $key
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
   */
  public function hasKey() {
    return !empty($this->key);
  }

  /**
   * Get Skyfish secret api key.
   *
   * @return string
   */
  public function getSecret(): string {
    return $this->secret;
  }

  /**
   * Set Skyfish secret api key.
   *
   * @param string $secret
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
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Set username to login to Skyfish.
   *
   * @param string $username
   *  Skyfish username.
   *
   * @return $this
   *  ConfigService.
   */
  public function setUsername(string $username): ConfigService {
    $this->username = $username;

    return $this;
  }

  /**
   * Get password to login to Skyfish.
   *
   * @return string
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Set password to login to Skyfish.
   *
   * @param string $password
   *  Skyfish password.
   *
   * @return $this
   *  ConfigService.
   */
  public function setPassword(string $password): ConfigService {
    $this->password = $password;

    return $this;
  }

}
