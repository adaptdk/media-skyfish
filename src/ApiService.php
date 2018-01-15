<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;
use GuzzleHttp\Client;

/**
 * Class ApiService
 *
 * @package Drupal\media_skyfish
 */
class ApiService {

  public const API_BASE_URL = 'https://api.colourbox.com';

  public const API_URL_FOLDER = '/folder';

  public const API_URL_IMAGES = '';

  /**
   * Http client used for connection.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var bool|string
   */
  protected $header;

  protected $account;

  /**
   * ApiService constructor.
   *
   * @param \GuzzleHttp\Client $client
   * @param \Drupal\media_skyfish\ConfigService $config_service
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(Client $client, ConfigService $config_service, AccountInterface $account) {
    $this->config = $config_service;
    $this->client = $client;
    $this->header = $this->getHeader();
    $this->user = $account;
  }

  /**
   * Getting token from a Skyfish.
   *
   * @return bool
   */
  public function getToken() {

    $request = $this
      ->client
      ->request('POST',
        self::API_BASE_URL . '/authenticate/userpasshmac',
        [
          'json' =>
          [
            'username' => $this->config->getUsername(),
            'password' => $this->config->getPassword(),
            'key' => $this->config->getKey(),
            'ts' => time(),
            'hmac' => $this->config->getHmac(),
          ]
        ]);

    $response = json_decode($request->getBody()->getContents(), TRUE);

    return $response['token'] ?? FALSE;
  }

  /**
   * Forming the header for authorization.
   *
   * @return bool|string
   */
  public function getHeader() {
    $token = $this->getToken();

    if (!$token) {
      return FALSE;
    }

    return 'CBX-SIMPLE-TOKEN Token=' . $token;
  }

  /**
   * Make request to Skyfish with uri.
   *
   * @param $uri
   *
   * @return array|null
   */
  protected function doRequest($uri) {

    $make_request = $this
      ->client
      ->request(
        'GET',
        self::API_BASE_URL . $uri,
        [
          'headers' => [
            'Authorization' => $this->header,
          ]
        ]
      );

    return json_decode($make_request->getBody());
  }

  public function getFolders(){
    return $this->doRequest(self::API_URL_FOLDER);
  }

  public function getImages(){
    return $this->doRequest(self::API_URL_IMAGES);
  }
}
