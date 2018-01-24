<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;

/**
 * Class ApiService.
 *
 * @package Drupal\media_skyfish
 */
class ApiService {

  /**
   * Base url for service.
   */
  public const API_BASE_URL = 'https://api.colourbox.com';

  /**
   * Folders uri.
   */
  public const API_URL_FOLDER = '/folder';

  /**
   * Uri for searching folders.
   */
  public const API_URL_SEARCH = '/search?&return_values=title+unique_media_id+thumbnail_url&folder_ids=';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Header for authorization.
   *
   * @var bool|string
   */
  protected $header;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Construct ApiService.
   *
   * @param \GuzzleHttp\Client $client
   *   Http client.
   * @param \Drupal\media_skyfish\ConfigService $config_service
   *   Config service for Skyfish API authorization and settings.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Drupal user account interface.
   */
  public function __construct(Client $client, ConfigService $config_service, AccountInterface $account) {
    $this->config = $config_service;
    $this->client = $client;
    $this->header = $this->getHeader();
    $this->user = $account;
  }

  /**
   * Get token from a Skyfish.
   *
   * @return string|bool
   *   Authorization token string or false if there was an error.
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
   * Get authorization header.
   *
   * @return bool|string
   *   Authorization header for further communication, or false if error.
   */
  public function getHeader() {
    $token = $this->getToken();

    if (!$token) {
      return FALSE;
    }

    return 'CBX-SIMPLE-TOKEN Token=' . $token;
  }

  /**
   * Make request to Skyfish API.
   *
   * @param string $uri
   *   Request URL.
   *
   * @return array|null
   *   Response body content.
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
    $cache_time = $this->config->getCacheTime();

    $cache = \Drupal::cache()->get($cache_id);
    if (empty($cache->data)) {
      $folders = $this->getFoldersWithoutCache();

      if (!empty($folders)) {
        // @TODO set timestamp for cache
        \Drupal::cache()->set($cache_id, $folders, $cache_time);
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
   * Get all images in a Skyfish folder.
   *
   * @return array|null
   *   Array of images in a folder.
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
   *
   * @param int $folder_id
   *   Id of the folder.
   *
   * @return array
   *   Array of images in a folder.
   */
  public function getImagesInFolderWithoutCache(int $folder_id) {
    $response = $this->doRequest(self::API_URL_SEARCH . $folder_id);
    $images = $response->response->media ?? [];

    return $images;
  }

  /**
   * Store images with metadata.
   *
   * @param array $images
   *   Loaded images.
   *
   * @return array
   *   Array of images with metadata.
   */
  public function getImagesMetadata(array $images) {
    foreach ($images as $image_id => $image) {
      if (FALSE === $metadata = $this->getImageMetadata($image)) {
        unset($images[$image_id]);
      }

      $images[$image_id] = $metadata;
    }

    return $images;
  }

  /**
   * Set metadata for the image.
   *
   * @param $image
   *   Skyfish image.
   *
   * @return bool
   *   If image title empty display Skyfish id.
   */
  public function getImageMetadata($image) {
    $image->title = $this->getImageTitle($image->unique_media_id);
    $image->download_url = $this->getImageDownloadUrl($image->unique_media_id);
    $image->filename = $this->getFilename($image->unique_media_id);

    if ($image->download_url === FALSE) {
      // @todo: if no image/download link - throw an error.

      return FALSE;
    }

    if ($image->title === FALSE) {
      $image->title = $image->unique_media_id;
    }

    return $image;
  }

  /**
   * Get image title.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string
   *   Filename of the image.
   */
  public function getImageTitle($img_id) {
    $request = $this->doRequest('/media/' . $img_id);
    $full_filename = $request->filename;
    $filename = substr($full_filename, 0, (strrpos($full_filename, ".")));

    return $filename;
  }

  /**
   * Get filename.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string
   *   Filename.
   */
  public function getFilename($img_id) {
    $request = $this->doRequest('/media/' . $img_id);

    return $request->filename;
  }

  /**
   * Get image download url.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string
   *   Download url.
   */
  public function getImageDownloadUrl($img_id) {
    $request = $this->doRequest('/media/' . $img_id . '/download_location');

    return $request->url;
  }

}
