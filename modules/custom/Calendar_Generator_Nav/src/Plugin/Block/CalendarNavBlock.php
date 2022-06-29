<?php

namespace Drupal\calendar_generator_nav\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a 'Calendar Nav' Block.
 *
 * @Block(
 *   id = "calendar_nav_block",
 *   admin_label = @Translation("Calendar Nav Block"),
 *   category = @Translation("Calendar Navigation"),
 * )
 */


class CalendarNavBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public $globalEra;

  public function set_era($era){
    $this->globalEra = $era;
  }
  public function get_era(){
    return $this->globalEra;
  }

  public function getCacheMaxAge() {
    return 0;
  }



  private function getEraDropdown($era){
    $adSel = "";
    $bcSel = "";
    $amSel = "";
    if($era=="ad"){
      $adSel = "selected";
    }
    if($era=="bc"){
      $bcSel = "selected";
    }
    if($era=="am"){
      $amSel = "selected";
    }
    ?>
    
    <?php
    $html = "<div class='col-sm-4'>";
    
    $html .= "<select class=\"form-control currentEra\" id=\"GC_Era\" name=\"GC_Era\" style='width: 82px'>";
    $html .= " <option  ". $adSel ."' data-id=\"AD\">AD</option>";
    $html .= " <option  " .$bcSel ." data-id=\"BC\">BC</option>";
    $html .= " <option  ". $amSel . " data-id=\"AM\">AM</option>";
    $html .= "</select></div>";
    
    return $html;
  
    
  }

  private function getYearSelector($year){

    $content = "<div class='col-sm-4'>" .
    "<div class='input-group'>" . 
    "<a class='input-group-addon yearToggle prev' data-dir='prev' onClick='calendarnav.updateYear(\"prev\")'>Prev</a>" .  
    "<input class='form-control currentyear' type='number' value='" . $year . "' />" . 
    "<a class='input-group-addon yearToggle next' data-dir='next' onClick='calendarnav.updateYear(\"next\")'>Next</a>" .  
    "</div>" . 
    "</div>";

    return $content;
  }

  public function build() {

  	$thisURL = $_SERVER['REQUEST_URI'];
  	$splits = explode("/",$thisURL);
  	$year = $splits[count($splits)-1];
  	$era = $splits[count($splits)-2];



    $markup = "<div class='container'><div class='calendar_nav row'>";
    
    $markup .= $this::getEraDropdown($era);
    $markup .= $this::getYearSelector($year);
    $markup .="<div class='col-sm-4 text-right'><a href='#' onClick='calendarnav.generateBtn(this)' class='btn btn-primary generateBtn'>Generate</a></div>";
    $markup .="</div></div>";

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