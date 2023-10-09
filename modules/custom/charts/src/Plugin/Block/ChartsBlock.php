<?php

namespace Drupal\Charts\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a 'Chart Widget' Block.
 *
 * @Block(
 *   id = "charts_block",
 *   admin_label = @Translation("Charts Block"),
 *   category = @Translation("Charts"),
 * )
 */


class ChartsBlock extends BlockBase {


    /**
     * {@inheritdoc}
     */

     // add build function
    public function build() {
        // add markup
        $markup = "<div id='chartsdatatable'>Chart</div>";
        return [
            '#markup' => $this->t($markup),
            '#attached' => [
                'library' => [
                    'charts/chart3',
                ]
            ],
        
        ];
    }
}