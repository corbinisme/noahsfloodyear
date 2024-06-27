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


class CalendarGeneratorNavBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */


  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

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
      $adSel = " active";
    }
    if($era=="bc"){
      $bcSel = " active";
    }
    if($era=="am"){
      $amSel = " active";
    }
    ?>
    
    <?php
    $html = "<div class='col-md-4 text-right eraSelector'>";
    
    /*$html .= "<select class=\"form-control currentEra\" id=\"GC_Era\" name=\"GC_Era\" style='width: 82px'>";
    $html .= " <option  ". $adSel ."' data-id=\"AD\">AD</option>";
    $html .= " <option  " .$bcSel ." data-id=\"BC\">BC</option>";
    $html .= " <option  ". $amSel . " data-id=\"AM\">AM</option>";
    $html .= "</select>";
    */

    $html .= '<div class="btn-group currentEra" id="GC_Era" name="GC_Era" data-value="' .strtoupper($era) . '">';
    $html .=             '<button type="button" data-id="AD" class="btn btn-primary' . $adSel. '">AD</button> ';
    $html .=             '<button type="button" data-id="BC" class="btn btn-primary' . $bcSel. '">BC</button> ';
    $html .=             '<button type="button" data-id="AM" class="btn btn-primary' . $amSel. '">AM</button>';
    $html .=         '</div></div>';
    
    return $html;
  
    
  }

  private function getYearSelector($year){

    $content = "<div class='col-md-4 yearInput'>" .
    "<div class='input-group'>" . 
    "<a class='input-group-addon yearToggle btn bg-primary prev' data-dir='prev'>Prev</a>" .  
    "<input class='form-control currentyear' type='number' value='" . $year . "' />" . 
    "<a class='input-group-addon yearToggle btn bg-primary next' data-dir='next'>Next</a>" .  
    "</div>" . 
    "</div>";

    return $content;
  }

  public function build() {

  	$thisURL = $_SERVER['REQUEST_URI'];
  	$splits = explode("/",$thisURL);
  	$year = $splits[count($splits)-1];
  	$era = $splits[count($splits)-2];

    // this is not sending data to the template for some reason
    
    $markup = "<div class='container'><div class='calendar_nav row'>";
    
    $markup .= $this::getEraDropdown($era);
    $markup .= $this::getYearSelector($year);
    $markup .="<div class='col-md-4 text-left generateBtn'><button type='button' onClick='calendarnav.generateBtn(this)' class='btn btn-primary generateBtn'>Generate</button></div>";
    $markup .="</div></div>";
    

      return [
      	'#markup' => $this->t($markup),
        //'#theme' => 'calendar_generator_nav_block',
        '#data'=>[
          'year' => $year,
          'era' => strtoupper($era)
 
          
        ],
        '#attached' => [
          'library' => [
            'calendar_generator_nav/calendar',
          ]
        ],
      ];
  }

}