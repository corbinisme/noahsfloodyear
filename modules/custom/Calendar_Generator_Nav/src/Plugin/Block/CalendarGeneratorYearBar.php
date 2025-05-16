<?php

namespace Drupal\calendar_generator_nav\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a 'Calendar Year Bar' Block.
 *
 * @Block(
 *   id = "calendar_year_bar",
 *   admin_label = @Translation("Calendar Year Bar"),
 *   category = @Translation("Calendar Navigation"),
 * )
 */


class CalendarGeneratorYearBar extends BlockBase {

  /**
   * {@inheritdoc}
   */


  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  public function getCacheMaxAge() {
    return 0;
  }



  public function build() {

  	$thisURL = $_SERVER['REQUEST_URI'];
  	$splits = explode("/",$thisURL);
  	$year = $splits[count($splits)-1];
  	$era = $splits[count($splits)-2];

    $markup  = '<div class="calendar-how-to">
    <div class="text-center">
      <a href="#" class="calendar-toggle">How to use the calendar generator <i class="fa fa-chevron-down"></i></a>
    </div>
    <div class="calendar-top-three">
    <div class="row">
      <div class="col-sm-4">
        <div class="iconGrid">
          <i class="fa fa-calendar"></i>
          <span>Type in a year in AD, BC, or the Hebrew Calendar</span>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="iconGrid">
        <i class="fa fa-arrow-up"></i>
        <span>Find significant days throughout history</span>
        </div>
      </div>
      <div class="col-sm-4">
       <div class="iconGrid">
        <i class="fa fa-search"></i>
        <span>View every Sabbath since the creation of man.</span>
      </div>
      </div>
    </div>
   </div>
   </div>';

    $markup .= "<div class='year_bar'>";
    $markup .= "<div class='year_bar_inner'>";

      $markup .= "<div class='row'>";
      $markup .= "<div class='col-sm-1 text-start'><a href='#' class='year_bar_action' data-dir='prev'><i class='fa fa-chevron-left'></i></a></div>";
        $markup .= "<div class='col-sm-10 text-center'>";
        
        $markup .= "<h2 class='yearTitle'>". $year . "</h2>";
        
        $markup .= "</div>";
        $markup .= "<div class='col-sm-1 text-end'><a href='#' class='year_bar_action' data-dir='next'><i class='fa fa-chevron-right'></i></a></div>";
      $markup .= "</div>";

    $markup .=  "</div>";
    $markup .= "</div>";
    

      return [
      	'#markup' => $this->t($markup),
        '#attached' => [
          'library' => [
            'calendar_generator_nav/calendar',
          ]
        ],
      ];
  }

}
