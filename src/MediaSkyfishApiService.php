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
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('media_skyfish.adminconfig');
    $this->skyfish_api_key = $config->get('media_skyfish_api_key');
    $this->skyfish_secret_api_key = $config->get('media_skyfish_api_secret');
    $this->client = new Client();
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $this->skyfish_user_api_key = $user->field_skyfish_api_user->value;
    $this->skyfish_user_secret_api_key = $user->field_skyfish_secret_api_key->value;
  }

  /**
   * @return array
   */
  public function getApi() {

    $api = array();
    $api['skyfish_api_key'] = $this->skyfish_user_api_key;
    $api['skyfish_api_secret'] = $this->skyfish_user_secret_api_key;
    if (empty($api['skyfish_api_key']) || empty($api['skyfish_api_secret'])) {
      $api['skyfish_api_key'] = $this->skyfish_api_key;
      $api['skyfish_api_secret'] = $this->skyfish_secret_api_key;
    }
    return $api;
  }

  public function doRequest($url) {

    $api=$this->getApi();
    if (empty($api['skyfish_api_key']) || empty($api['skyfish_api_secret'])) {
      return FALSE;
    }

    $hash = hash_hmac('sha1', $api['skyfish_api_key'] . ':' .time(), $api['skyfish_api_secret']);

    $response = $this->client->request('POST', 'https://api.colourbox.com/authenticate/userpasshmac', [
      'json' => ['username' => 'karolis.bernotas@adapt.dk', 'password' => 'vovwued9', 'key' => $api['skyfish_api_key'], 'ts' => time(), 'hmac' => $hash]
    ]);

    $content = json_decode($response->getBody()->getContents(), TRUE);

    $authorization = 'CBX-SIMPLE-TOKEN Token=' . $content['token'];

    $request_folders = $this->client->request('GET', 'https://api.colourbox.com/folder', [
      'headers' => [
        'Authorization' => $authorization,
      ]
    ]);

    $folders = json_decode($request_folders->getBody()->getContents(), TRUE);

    dpm($folders);



    return true;

    
  }


}
