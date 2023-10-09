<?php 

namespace Drupal\Chart_Three\Plugin\Block;
use Drupal\Core\Block\BlockBase;


/**
 * Provides a 'Chart Three Block' Block.
 *
 * @Block(
 *   id = "chart_three_block",
 *   admin_label = @Translation("Chart Three Block"),
 *   category = @Translation("Charts"),
 * )
 */


 class ChartThreeBlock extends BlockBase {
    /**
     * {@inheritdoc}
     */


    public function build() {
    
        $markup = "<div id='chart3datatable'></div>";
        return [
            '#markup' => $this->t($markup),
            '#attached' => [
                'library' => [
                    'chart_three/chart3',
                ]
            ],
    
      

    ];
    }
 }

