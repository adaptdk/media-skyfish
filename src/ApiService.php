<?php

namespace Drupal\media_skyfish;

use GuzzleHttp\Client;

/**
 * Class ApiService.
 */
class ApiService {

  const MEDIA_SKYFISH_API_BASE_URL = 'https://api.colourbox.com';

  /**
   * Http client used for connection.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * ApiService constructor.
   *
   * @param \Drupal\media_skyfish\ConfigService $config_service
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(Client $client, ConfigService $config_service) {
    $this->config = $config_service;
    $this->client = $client;

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
        self::MEDIA_SKYFISH_API_BASE_URL . '/authenticate/userpasshmac',
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
   * @return mixed
   */
  public function doRequest($uri) {

    $make_request = $this
      ->client
      ->request(
        'GET',
        self::MEDIA_SKYFISH_API_BASE_URL . $uri,
        [
          'headers' => [
            'Authorization' => $this->getHeader(),
          ]
        ]
      );

    return json_decode($make_request->getBody());
  }

}
