<?php
/**
 * @file
 * Contains Drupal\timeline_block\Controller\TimelineBlockController.
 */
namespace Drupal\timeline_block\Controller;
class TimelineBlockController {
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World!'),
    );
  }
}
