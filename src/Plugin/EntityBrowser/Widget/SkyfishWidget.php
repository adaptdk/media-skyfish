<?php

namespace Drupal\media_skyfish\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_skyfish\ApiService;
use Drupal\media_skyfish\ConfigService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * Drupal logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Skyfish api service.
   *
   * @var \Drupal\media_skyfish\ApiService
   */
  protected $connect;

  /**
   * SkyfishWidget constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher, \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager, \Drupal\entity_browser\WidgetValidationManager $validation_manager, \Drupal\Core\Extension\ModuleHandlerInterface $module_handler, \Drupal\Core\Utility\Token $token, LoggerInterface $logger, ApiService $api_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $module_handler, $token);

    $this->logger = $logger;
    $this->connect = $api_service;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('logger.channel.media_skyfish'),
      $container->get('media_skyfish.apiservice')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Since we extend default entity_browser Upload widget,
    // it contains unused configuration fields, so we remove them.
    unset($form['upload_location'], $form['extensions'], $form['multiple']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    // Since we extend default entity_browser Upload widget,
    // it contains unused upload field, so we remove it.
    unset($form['upload']);

    $folders = $this->connect->getFolders();

    // Show message if there is an error while getting folders.
    if (!is_array($folders) || empty($folders)) {
      $error_message = $folders->message ? $this->t($folders->message) : $this->t('Error while getting data');
      drupal_set_message($error_message, 'error');

      return $form;
    }

    $form['skyfish'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => str_replace('_', '-', 'edit_folder_' . $folders[0]->id),
      '#attributes' => [
        'class' => [
          'skyfish',
        ],
      ],
    ];

    foreach ($folders as $folder) {
      $images = $this->connect->getImagesInFolder($folder->id);

      if (empty($images)) {
        continue;
      }

      $form['folder_' . $folder->id] = [
        '#type' => 'details',
        '#group' => 'skyfish',
        '#title' => $folder->name,
        '#attributes' => [
          'class' => [
            'skyfish__folder',
            'folder',
          ],
        ],
      ];

      foreach ($images as $image) {
        $form['folder_' . $folder->id][$image->unique_media_id] = [
          '#type' => 'checkbox',
          '#title' => '<img src="' . $image->thumbnail_url . '" class="image__thumbnail">',
          '#attributes' => [
            'class' => [
              'folder__image',
              'image',
            ],
          ],
        ];
      }
    }

    $form['#attached']['library'][] = 'media_skyfish/pager';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $folders = $this->connect->getFolders();
    $media = [];

    foreach ($folders as $folder) {
      $images = $this->connect->getImagesInFolder($folder->id);

      foreach ($images as $image_id => $image) {
        if (isset($form_values[$image->unique_media_id]) && $form_values[$image->unique_media_id] === 1) {
          $media[$image_id] = $image;
        }
      }

    }

    $images_with_metadata = $this->connect->getImagesMetadata($media);
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
      $this->logger->error('Unable to save file for @image', ['@image' => $image->filename]);
    }
    return $file;
  }

}
