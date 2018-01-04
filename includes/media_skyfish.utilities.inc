<?php
/**
 * @file
 * Utility functions for Media: Skyfish.
 */

/**
 * Skyfish media browser form.
 */
function media_skyfish_add($form, &$form_state) {
  // *TODO maybe we can preload this one somehow?
  $folders = media_skyfish_get_user_folders();

  if (empty($folders)) {
    $form['message'] = array(
      '#type' => 'item',
      '#prefix' => '<p>',
      '#markup' => isset($folders->message) ? $folders->message : t('Error while getting data'),
      '#suffix' => '</p>',
    );
    return $form;
  }

  $form = array(
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'media_skyfish') . '/js/media_skyfish.browser.js',
      ),
      'css' => array(
        drupal_get_path('module', 'media_skyfish') . '/css/media_skyfish.browser.css',
      ),
    ),
    '#attributes' => array(
      'id' => 'media-skyfish-form',
    ),
  );

  $form['skyfish'] = array(
    '#type' => 'vertical_tabs',
  );

  $form['skyfish']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'submit',
    '#attributes' => array(
      'id' => 'media-skyfish-form-submit',
    ),
    '#submit' => array(
      'media_skyfish_upload_image',
    ),
  );

  foreach ($folders as $folder) {
    // *TODO if there is more than 500 images in folder,
    // we need to do more request using media _offset parameter,
    // to get all images.
    // *TODO do loading just then when tab is active.
    // We want to avoid loading it every time when media popup is loaded.
    // *TODO load images just for first active folder.
    // For others load them when they are active.
    $images = media_skyfish_get_user_folder_images($folder->id);
    if (empty($images)) {
      continue;
    }

    // *TODO do pagination just for active folder.
    $pagination = media_skyfish_do_pagination($images, 49);

    $form['skyfish'][$folder->id] = array(
      '#type' => 'fieldset',
      '#title' => $folder->name,
      '#description' => isset($pagination['pagination']) ? $pagination['pagination'] : '',
    );

    foreach ($pagination['current'] as $image) {
      if (!isset($image->unique_media_id) || !isset($image->thumbnail_url)) {
        continue;
      }

      $output = theme('image', array(
        'path' => $image->thumbnail_url,
        'title' => 'title',
        'alt' => 'alt',
        'attributes' => array(
          'width' => 200,
          'hspace' => 10,
          'vspace' => 10,
          'data-image' => $image->unique_media_id,
        ),
        'getsize' => FALSE,
      ));

      $form['skyfish'][$folder->id][$image->unique_media_id] = array(
        '#type' => 'checkbox',
        '#title' => empty($image->title) ? $image->unique_media_id : $image->title,
        '#prefix' => '<div class="media-skyfish-list">' . $output,
        '#suffix' => '</div>',
        '#attributes' => array(
          'class' => array(
            'media-skyfish-checkbox',
          ),
        ),
      );
    }
  }

  return $form;
}

/**
 * Form media skyfish image upload handler.
 */
function media_skyfish_upload_image($form, &$form_state) {
  // *TODO at the moment just single file upload works.
  // Maybe we should do multiple.
  foreach ($form_state['values'] as $key => $value) {
    if (is_numeric($key)) {
      if ($key == TRUE) {
        $upload = $key;
        break;
      }
    }
  }

  if (!isset($upload)) {
    form_set_error('skyfish', t('Error while trying to upload image. Please try again later.'));
    return $form;
  }

  // We need to get image name.
  $image_data = media_skyfish_do_request('/media/' . $upload);
  // Get download link for image.
  $image = media_skyfish_do_request('/media/' . $upload . '/download_location');

  if (empty($image_data) || empty($image)) {
    form_set_error('skyfish', t('Error while trying to get image. Please try again later.'));
    return $form;
  }

  global $user;
  // We store it to skyfish user folder.
  $folder = file_default_scheme() . '://media-skyfish/' . $user->uid . '/';
  $uri = $folder . $image_data->filename;
  file_prepare_directory($folder, FILE_CREATE_DIRECTORY);
  $file = system_retrieve_file($image->url, $uri, TRUE, FILE_EXISTS_RENAME);

  // *TODO maybe we can add image title from image_data object.
  $url = 'media/browser';
  $parameters = array(
    'query' => array(
      'render' => 'media-popup',
      'fid' => $file->fid,
    ),
  );

  drupal_goto($url, $parameters);
}

/**
 * Get user folders from skyfish. Set it to cache.
 *
 * @return bool|mixed
 *   Data received from service.
 */
function media_skyfish_get_user_folders() {
  global $user;

  $cache_name = MEDIA_SKYFISH_NAMESPACE . 'folders_' . $user->uid;
  $folders = cache_get($cache_name);
  if (isset($folders->data) && time() < $folders->expire) {
    return $folders->data;
  }

  $folders = media_skyfish_do_request('/folder');
  if (empty($folders)) {
    return FALSE;
  }

  // Set cache for 60 minutes.
  cache_set($cache_name, $folders, 'cache', time() + 60 * 60);
  return $folders;
}

/**
 * Get all folder images from skyfish.
 *
 * @param string $folder_id
 *   Folder id.
 *
 * @return bool|mixed
 *   Array of images.
 */
function media_skyfish_get_user_folder_images($folder_id) {
  global $user;
  $cache_name = MEDIA_SKYFISH_NAMESPACE . 'images_' . $user->uid . '_' . $folder_id;
  $images = cache_get($cache_name);
  if (isset($images->data) && time() < $images->expire) {
    return $images->data;
  }

  $images = media_skyfish_do_request('/search?&return_values=title+unique_media_id+thumbnail_url&folder_ids=' . $folder_id);

  if (empty($images->response->media)) {
    return FALSE;
  }

  // Set cache for 60 minutes.
  cache_set($cache_name, $images->response->media, 'cache', time() + 60 * 60);
  return $images->response->media;
}

/**
 * Do pagination for provided array.
 *
 * @param array $data
 *   Array containing data.
 * @param int $per_page
 *   How many items per page should be.
 *
 * @return array
 *   Array containing pagination html and current page array elements.
 */
function media_skyfish_do_pagination(array $data, $per_page = 25) {
  $total_array = count($data);
  if ($total_array <= $per_page) {
    return array('current' => $data);
  }

  $current_page = pager_default_initialize($total_array, $per_page);
  $chunks = array_chunk($data, $per_page, TRUE);
  $current = $chunks[$current_page];
  $pagination = theme('pager', array('quantity', count($current)));

  return array(
    'current' => $current,
    'pagination' => $pagination,
  );
}

/**
 * Do curl request to skyfish for provided url.
 *
 * @param string $url
 *   Request url.
 *
 * @return bool|mixed
 *   Object containing received data.
 */
function media_skyfish_do_request($url) {
  $api = media_skyfish_get_api();
  if (empty($api['api_key']) || empty($api['api_secret'])) {
    return FALSE;
  }

  $hash = hash_hmac('sha1', $api['api_key'] . ':' . time(), $api['api_secret']);

  $headers = array(
    'headers' => array(
      'Content-length' => '0',
      'Content-type' => 'application/json',
      'Authorization' => 'CBX-HMAC Key=' . $api['api_key'] . ' HMAC=' . $hash . ' TS=' . time(),
    ),
  );

  $return = drupal_http_request(MEDIA_SKYFISH_API_BASE_URL . $url, $headers);
  if (isset($return->code) && $return->code == 400) {
    return FALSE;
  }

  return json_decode($return->data);
}
