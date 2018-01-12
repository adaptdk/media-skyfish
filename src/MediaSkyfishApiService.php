<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class MediaSkyfishApiService.
 */
class MediaSkyfishApiService {

  const MEDIA_SKYFISH_API_BASE_URL = 'https://api.colourbox.com';

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * MediaSkyfishApiService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $client) {
    $config = $config_factory->get('media_skyfish.adminconfig');
    $this->skyfish_global_user = $config->get('media_skyfish_global_user');
    $this->skyfish_global_psw = $config->get('media_skyfish_global_password');
    $this->skyfish_api_key = $config->get('media_skyfish_api_key');
    $this->skyfish_secret_api_key = $config->get('media_skyfish_api_secret');
    $this->client = $client;
    //TODO these two goes to construct
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $this->skyfish_user_api_key = $user->field_skyfish_api_user->value;
    $this->skyfish_user_secret_api_key = $user->field_skyfish_secret_api_key->value;
    $this->skyfish_username = $user->field_skyfish_username->value;
    $this->skyfish_psw = $user->field_skyfish_password->value;
  }

  /**
   * @return array
   */
  public function getApi() {

    $api = array();
    $api['skyfish_api_key'] = $this->skyfish_user_api_key;
    $api['skyfish_api_secret'] = $this->skyfish_user_secret_api_key;
    $api['skyfish_user'] = $this->skyfish_username;
    $api['skyfish_psw'] = $this->skyfish_psw;
    if (empty($api['skyfish_api_key']) || empty($api['skyfish_api_secret'])) {
      $api['skyfish_api_key'] = $this->skyfish_api_key;
      $api['skyfish_api_secret'] = $this->skyfish_secret_api_key;
      $api['skyfish_user'] = $this->skyfish_global_user;
      $api['skyfish_psw'] = $this->skyfish_global_psw;
    }
    return $api;
  }

  public function doRequest() {

    $api = $this->getApi();
    if (empty($api['skyfish_api_key']) || empty($api['skyfish_api_secret'])) {
      return FALSE;
    }
    //TODO replace username and password values from fields && check if getHmac returns proper value
    $request = $this
      ->client
      ->request('POST',
        self::MEDIA_SKYFISH_API_BASE_URL . '/authenticate/userpasshmac',
        [
          'json' =>
            [
              'username' => 'karolis.bernotas@adapt.dk',
              'password' => 'vovwued9',
              'key' => $api['skyfish_api_key'],
              'ts' => time(),
              'hmac' => $this->getHmac(),
            ]
        ]);

    $response = json_decode($request->getBody()->getContents(), TRUE);

    return $response['token'] ?? FALSE;

  }

  public function getHmac() {
    $api = $this->getApi();

    return hash_hmac('sha1', $api['skyfish_api_key'] . ':' .time(), $api['skyfish_api_secret']);
  }

  public function getHeader() {
    $token = $this->doRequest();

    if (!$token) {
      return FALSE;
    }

    return 'CBX-SIMPLE-TOKEN Token=' . $token;
  }

  public function getFolders() {

    $header = $this->getHeader();
    if (!$header) {
      return FALSE;
    }

    $request_folders = $this
      ->client
      ->request(
        'GET',
        self::MEDIA_SKYFISH_API_BASE_URL . '/folders',
        [
          'headers' => [
            'Authorization' => $header,
          ]
        ]
      );

    return json_decode($request_folders->getBody()->getContents(), TRUE);

  }


}
