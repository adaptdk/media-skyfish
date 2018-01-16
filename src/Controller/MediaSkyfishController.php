<?php

namespace Drupal\media_skyfish\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MediaSkyfishController.
 */
class MediaSkyfishController extends ControllerBase {

  /**
   * Hello.
   *
   * @return array
   *   Return Hello string.
   */
  public function hello() {

    /**
     * @var \Drupal\media_skyfish\ApiService $connect
     */
    $connect = \Drupal::service('media_skyfish.apiservice');

    return [
      '#type' => 'markup',
      '#markup' => 'Test folders id: 946499 and 949137',
    ];
  }

}
