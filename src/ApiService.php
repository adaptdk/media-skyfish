<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;

/**
 * Class ApiService
 *
 * @package Drupal\media_skyfish
 */
class ApiService {

  public const API_BASE_URL = 'https://api.colourbox.com';

  public const API_URL_FOLDER = '/folder';

  public const API_URL_SEARCH = '/search?&return_values=title+unique_media_id+thumbnail_url&folder_ids=';

  /**
   * Http client used for connection.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Short Description.
   *
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
          ],
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
   *   Array of folders.
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
          ],
        ]
      );

    return json_decode($make_request->getBody());
  }

  /**
   * Get media cached folders from Skyfish API.
   *
   * @return array
   *   Array of Skyfish folders.
   */
  public function getFolders() {
    $cache_id = 'folders_' . $this->user->id();

    $cache = \Drupal::cache()->get($cache_id);
    if (empty($cache->data)) {
      $folders = $this->getFoldersWithoutCache();

      if (!empty($folders)) {
        // @TODO set timestamp for cache
        \Drupal::cache()->set($cache_id, $folders);
      }

      return $folders;
    }

    return $cache->data ?? [];
  }

  /**
   * Get folders from Skyfish API.
   *
   * @return array
   *   Array of Skyfish folders.
   */
  public function getFoldersWithoutCache() {
    $folders = $this->doRequest(self::API_URL_FOLDER);
    return $folders;
  }

  /**
   * @return array|null
   */
  public function getImagesInFolder(int $folder_id) {
    $cache_id = 'images_' . $folder_id . '_' . $this->user->id();
    $cache = \Drupal::cache()->get($cache_id);
    if (empty($cache->data)) {
      $images = $this->getImagesInFolderWithoutCache($folder_id);

      if (!empty($images)) {
        // @TODO set timestamp for cache
        \Drupal::cache()->set($cache_id, $images);
      }

      return $images;

    }

    return $cache->data ?? [];
  }

  /**
   * Get images from Skyfish API.
   */
  public function getImagesInFolderWithoutCache(int $folder_id) {
    $response = $this->doRequest(self::API_URL_SEARCH . $folder_id);
    $images = $response->response->media ?? [];

    return $images;
  }

  public function getImagesMetadata(array $images) {
    foreach ($images as $image_id => $image) {
      if (FALSE === $metadata = $this->getImageMetadata($image)) {
        unset($images[$image_id]);
      }

      $images[$image_id] = $metadata;
    }

    return $images;
  }

  public function getImageMetadata(object $image) {
    $image->title = ''; // @todo: get image name from skyfish api.
    $image->download_url = ''; // @todo:get image download link from skyfish api.


    if ($image->download_url === FALSE) {
      // @todo: if no image/download link - throw an error.

      return FALSE;
    }

    if($image->title === FALSE) {
      $image->title = $image->unique_media_id;
    }

    return $image;
  }

}
