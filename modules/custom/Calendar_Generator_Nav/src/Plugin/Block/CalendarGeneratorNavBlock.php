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

  private function getTheDayOfWeek($firstSabath=7, $day=""){
	  // eventually return from DB?
	  // and calculate the LGD
	   $list = [
		"passover"=>"Sat",
		"unleavenedbread"=> "Sun",
		"pentecost"=> "Sun",
		"feastoftrumpets" =>  "Tues",
		"dayofatonement"=> "Thurs",
		"feastoftabernacles"=>  "Tues",
		"lastgreatday"=> "Tues"
	   ];

		switch($firstSabath){
			case 1: 
				$list['passover'] = "Fri"; 
				$list['unleavenedbread'] = "Sat";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Mon";
				$list['dayofatonement'] = "Wed";
				$list['feastoftabernacles'] = "Mon";
				$list['lastgreatday'] = "Mon";
			break;
			case 3: 
				$list['passover'] = "Wed"; 
				$list['unleavenedbread'] = "Thurs";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Sat";
				$list['dayofatonement'] = "Mon";
				$list['feastoftabernacles'] = "Sat";
				$list['lastgreatday'] = "Sat";
			break;
			case 5: 
				$list['passover'] = "Mon"; 
				$list['unleavenedbread'] = "Tues";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Thurs";
				$list['dayofatonement'] = "Sat";
				$list['feastoftabernacles'] = "Thurs";
				$list['lastgreatday'] = "Thurs";
			break;
			case 7: 
				$list['passover'] = "Sat"; 
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "Tues";

			break;
			default: 
				$list['passover'] = "Sat"; 
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "Tues";

			break;
		}

	
	  return $list[$day];

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

  private function getInteractiveLegend($era, $year, $result){
    $content = "<div class='col-md-12 interactiveLegend'>";

    $markup = "<table class='table calendar-dates'>";
    if($year == "1" && $era =="AM" || ($year=="4046" && $era=="BC")){

		 $markup .=
		'<tbody>' .
		'<tr><td class="passover"><span></span> Passover </td><td class="start">' . $this->getTheDayOfWeek(7, "passover") . ', Apr 2</td><td></td></tr>' .
		'<tr><td class="unleavenedbread"><span class="ubbg"></span> Unleavened Bread </td><td class="start">' . $this->getTheDayOfWeek(7, "unleavenedbread") . ', Apr 3</td><td class="end"> Apr 10</td></tr>'.
		'<tr><td class="pentecost"><span></span> Pentecost </td><td class="start">' . $this->getTheDayOfWeek(7, "pentecost") . ', May 22</td><td></td></tr>'.
		'<tr><td class="trumpets"><span></span> Trumpets </td><td class="start">' . $this->getTheDayOfWeek(7, "feastoftrumpets") . ', Sep 13</td><td></td></tr>'.
		'<tr><td class="atonement"><span></span> Atonement </td><td class="start">' . $this->getTheDayOfWeek(7, "dayofatonement") . ', Sep 22</td><td></td></tr>'.
		'<tr><td class="tabernacles"><span></span> Tabernacles </td><td class="start">' . $this->getTheDayOfWeek(7, "feastoftabernacles") . ', Sep 27</td><td  class="end">Oct 3</td></tr>'.
		'<tr><td class="lastgreatday"><span></span> Last Great Day (8th Day)</td><td class="start">' . $this->getTheDayOfWeek(7, "lastgreatday") . ', Oct 4</td><td></td>'.
		'</tr>';
		
	} else {

		$markup .= "<tr>";
		$markup .= "<tr><td  class='passover'><span></span> Passover </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "passover") . ", " . $result[0]->passover_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='unleavenedbread'><span class='ubbg'></span> Unleavened Bread </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "unleavenedbread") . ", " . $result[0]->unleavened_bread_start . "</td><td class='end'>" . $result[0]->unleavened_bread_end . "</td></tr>";
		$markup .= "<tr><td class='pentecost'><span></span> Pentecost </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "pentecost") . ", " . $result[0]->pentecost_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='trumpets'><span></span> Trumpets </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "feastoftrumpets") . ", " . $result[0]->feast_of_trumpets_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='atonement'><span></span> Atonement </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "dayofatonement") . ", " . $result[0]->day_of_atonement_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='tabernacles'><span></span> Tabernacles </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "feastoftabernacles") . ", " . $result[0]->feast_of_tabernacles_start . "</td><td class='end'>" . $result[0]->feast_of_tabernacles_end . "</td></tr>";
		$markup .= "<tr><td class='lastgreatday'><span></span> Last Great Day (8th Day) </td><td class='start'>" . $this->getTheDayOfWeek($result[0]->HS, "lastgreatday") . ", " . $result[0]->last_great_day_start . "</td><td></td></tr>";
		$markup .= "</tr>";
		
	}
  $markup .= "</table><hr />";
  $content .= $markup;

    $content .= '
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


    $new_key = "chart3 db";
    $database = \Drupal::database();
    $con = \Drupal\Core\Database\Database::getConnection('calendar');
    $sql = "SELECT holydays.*, chart3.*, calendardates.First_Sabbath, calendardates.GC_YearLen, `calendardates`.Nisan_1, calendardates.Tish_1, calendardates.HCCYearLength, calendardates.SC_YearLen, calendardates.GC_Era, calendardates.GC_Year ";
    $sql .=" FROM `holydays` JOIN `chart3` on `holydays`.AM = `chart3`.year JOIN `calendardates` on `calendardates`.AM = `holydays`.AM where ";
    
    if($era=="am"){
      $sql .= "calendardates.AM = " . $year;
    } else if($era=="bc"){
      $sql .= " calendardates.GC_Era = 'BC' and calendardates.GC_Year = " . $year;
    } else if($era=="ad"){
      $sql .= "calendardates.GC_Era = 'AD' and calendardates.GC_Year = " . $year;
    }

    $query = $con->query($sql);
	  $result = $query->fetchAll();
    $hs = $result[0]->HS;

    // this is not sending data to the template for some reason
    
    $markup = "<div class='container'><div class='calendar_nav row'>";
    $markup .= "<div class='mobile-sidebar-toggle-wrapper'><a href='#' class='mobile-sidebar-toggle'><i class='fa fa-bars'></i></a></div>";
    $markup .= $this::getEraDropdown($era, $year);
    $markup .= "<div class='calendar-controls'>";
    $markup .= $this::getToggles();
    $markup .= $this::getInteractiveLegend($era, $year, $result);
    $markup .= "</div>";
    //$markup .="<div class='col-md-12 text-left generateBtn'><button type='button' onClick='calendarnav.generateBtn(this)' class='btn btn-primary generateBtn'>Generate</button></div>";
    $markup .="</div></div>";
    \Drupal\Core\Database\Database::getConnection();

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
