<?php

namespace Drupal\timeline_block\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a 'Timeline' Block.
 *
 * @Block(
 *   id = "timeline_block",
 *   admin_label = @Translation("Timeline Block"),
 *   category = @Translation("Timeline"),
 * )
 */


class TimelineBlock extends BlockBase {

 
  public function build() {
      $markup = "Timeline block";
      return [
        '#theme' => 'custom-list',
        '#attached' => [
          'library' => [
            'timeline_block/timeline',
          ]
        ],
        
      ];
  }

}