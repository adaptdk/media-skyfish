<?php

namespace Drupal\media_skyfish\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MediaSkyfishController.
 */
class MediaSkyfishController extends ControllerBase {

  public function hello() {
    /**
     * @var \Drupal\media_skyfish\MediaSkyfishApiService $connection
     */
    $connection = \Drupal::service('media_skyfish.apiservice');
    $connection->doRequest('/folder');

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: hello with parameter(s): $name'),
    ];
  }

}
