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
   * @return string
   *   Return Hello string.
   */
  public function hello() {

    /**
     * @var \Drupal\media_skyfish\ApiService $connect
     */
    $connect = \Drupal::service('media_skyfish.apiservice');
    $token = $connect->getFolders();
    dpm($token);


    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: hello with parameter(s): ' . $token),
    ];
  }

}
