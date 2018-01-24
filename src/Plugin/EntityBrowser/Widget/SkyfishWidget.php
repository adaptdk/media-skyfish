<?php

namespace Drupal\media_skyfish\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload;

/**
 * Plugin implementation of the 'skyfish' widget.
 *
 * @EntityBrowserWidget(
 *   id = "skyfishwidget",
 *   label = @Translation("Skyfish"),
 *   description = "Adds Skyfish upload integration.",
 *   auto_select = FALSE
 * )
 */
class SkyfishWidget extends Upload {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    unset($form['upload_location'], $form['extensions'], $form['multiple']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    unset($form['upload']);

    $connect = \Drupal::service('media_skyfish.apiservice');
    $folders = $connect->getFolders();

    if (empty($folders)) {
      $form['message'] = [
        '#type' => 'item',
        '#prefix' => '<p>',
        '#markup' => isset($folders->message) ? $folders->message : $this->t('Error while getting data'),
        '#suffix' => '</p>',
      ];
    }

    $form['skyfish'] = [
      '#type' => 'container',
    ];

    foreach ($folders as $folder) {
      $images = $connect->getImagesInFolder($folder->id);
      if (empty($images)) {
        continue;
      }
      $form['skyfish'][$folder->name] = [
        '#type' => 'details',
        '#group' => 'skyfish',
        '#title' => $folder->name,
      ];

      foreach ($images as $image) {
        $form['skyfish'][$folder->name][$image->unique_media_id] = [
          '#type' => 'checkbox',
          '#title' => '<img src="' . $image->thumbnail_url . '">',
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $connect = \Drupal::service('media_skyfish.apiservice');
    $folders = $connect->getFolders();
    $media = [];

    foreach ($folders as $folder) {
      $images = $connect->getImagesInFolder($folder->id);

      foreach ($images as $image_id => $image) {
        if (isset($form_values[$image->unique_media_id]) && $form_values[$image->unique_media_id] === 1) {
          $media[$image_id] = $image;
        }
      }

    }

    $images_with_metadata = $connect->getImagesMetadata($media);
    $saved_images = $this->saveImages($images_with_metadata);

    $this->selectEntities($saved_images, $form_state);

  }

  /**
   * Save images in array.
   *
   * @param array $images
   *   Skyfish images.
   *
   * @return array
   *   Array of images.
   */
  protected function saveImages(array $images) {

    foreach ($images as $image_id => $image) {
      $images[$image_id] = $this->saveFile($image);
    }

    return $images;
  }

  /**
   * Default system file scheme.
   *
   * @return array|mixed|null
   *   Default scheme.
   */
  public function fileDefaultScheme() {
    return \Drupal::config('system.file')->get('default_scheme');
  }

  /**
   * Save file in the system.
   *
   * @param \stdClass $image
   *   Skyfish image.
   *
   * @return \Drupal\file\FileInterface|false
   *   Saved image.
   */
  protected function saveFile(\stdClass $image) {
    $user = \Drupal::currentUser()->id();
    $destination = $this->fileDefaultScheme() . '://media-skyfish/' . $user . '/' . $image->filename;
    $data = \Drupal::httpClient()->get($image->download_url)->getBody();
    $file = file_save_data($data, $destination);

    if (!$file) {
      // @todo: throw an error;
    }
    return $file;
  }

}