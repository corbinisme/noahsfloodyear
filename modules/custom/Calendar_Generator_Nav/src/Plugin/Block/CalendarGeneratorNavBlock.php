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



  private function getEraDropdown($era, $year){
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
    $html = "<div class='col-md-12 eraSelector'>";
    $html .= "<div class='omniSelector'>";
    $html .= "<input type='text' class='form-control currentyear' name='yearSelect' id='yearSelect' value='" . $year . "' placeholder='Type Year' />";
    $html .= "<select class=\"form-select currentEra\" id=\"GC_Era\" name=\"GC_Era\" style='width: 82px'>";
    $html .= " <option  ". $adSel ."' data-id=\"AD\">AD</option>";
    $html .= " <option  " .$bcSel ." data-id=\"BC\">BC</option>";
    $html .= " <option  ". $amSel . " data-id=\"AM\">AM</option>";
    $html .= "</select>";
    $html .= "<a class='btn btn-outline-primary' id='generateBtn' onClick='calendarnav.generateBtn(this)'><i class='fa fa-arrow-right'></i></a>";
    $html .= "</div>";
    

    /*$html .= '<div class="btn-group currentEra" id="GC_Era" name="GC_Era" data-value="' .strtoupper($era) . '">';
    $html .=             '<button type="button" data-id="AD" class="btn btn-primary' . $adSel. '">AD</button> ';
    $html .=             '<button type="button" data-id="BC" class="btn btn-primary' . $bcSel. '">BC</button> ';
    $html .=             '<button type="button" data-id="AM" class="btn btn-primary' . $amSel. '">AM</button>';
    */
    $html .=         '</div>';
    
    return $html;
  
    
  }
  private function getToggles(){
    $content = "<div class='calendar-toggles'>";
    $content .= '<div class="form-check form-switch">
  <input class="form-check-input" type="checkbox" id="toggleGC" checked>
  <label class="form-check-label" for="toggleGC">Gregorian</label>
</div>
<div class="form-check form-switch">
  <input class="form-check-input" type="checkbox" id="toggleHC" checked>
  <label class="form-check-label" for="toggleHC">Hebrew</label>
</div>
<div class="form-check form-switch">
  <input class="form-check-input" type="checkbox" id="toggleSC" checked>
  <label class="form-check-label" for="toggleSC">Solar</label>
</div>
';
    $content .= "</div>";
    return $content;
  }

  private function getInteractiveLegend(){
    $content = "<div class='col-md-12 interactiveLegend'>";
    $content .= '
    <table class="table">
      <tbody>
      <tr><td class="passover"><span></span> Passover </td></tr>
      <tr><td class="unleavenedbread"><span class="ubbg"></span> Unleavened Bread </td></tr>
      <tr><td class="pentecost"><span></span> Pentecost </td></tr>
      <tr><td class="trumpets"><span></span> Trumpets </td></tr>
      <tr><td class="atonement"><span></span> Atonement </td></tr>
      <tr><td class="tabernacles"><span></span> Tabernacles </td></tr>
      <tr><td class="lastgreatday"><span></span> Last Great Day (8th Day)</td></tr>
      </tbody></table>
      <small>THE ABOVE DATES ARE OBSERVED THE PREVIOUS EVENING, AFTER SUNSET</small>';
    $content .= "<div class='legend'></div></div>";
    
    return $content;
  }

  private function getYearSelector($year){

    $content = "<div class='col-md-12 yearInput'>" .
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
    $markup .= "<div class='mobile-sidebar-toggle-wrapper'><a href='#' class='mobile-sidebar-toggle'><i class='fa fa-bars'></i></a></div>";
    $markup .= $this::getEraDropdown($era, $year);
    $markup .= "<div class='calendar-controls'>";
    $markup .= $this::getToggles();
    $markup .= $this::getInteractiveLegend();
    $markup .= "</div>";
    //$markup .="<div class='col-md-12 text-left generateBtn'><button type='button' onClick='calendarnav.generateBtn(this)' class='btn btn-primary generateBtn'>Generate</button></div>";
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
