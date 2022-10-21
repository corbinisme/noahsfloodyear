<?php

namespace Drupal\calendar_generator_dates\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Provides a 'Calendar' Block.
 *
 * @Block(
 *   id = "calendar_dates_block",
 *   admin_label = @Translation("Calendar Dates Block"),
 *   category = @Translation("Calendar Dates "),
 * )
 */


class CalendarDatesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function getCacheMaxAge() {
    return 0;
  }
  public function getGorMonths(){

	$dat = array();
	$dat[1] = array("name"=> "January", "days"=>31);
	$dat[2] = array("name"=> "February", "days"=>28);
	$dat[3] = array("name"=> "March", "days"=>31);
	$dat[4] = array("name"=> "April", "days"=>30);
	$dat[5] = array("name"=> "May", "days"=>31);
	$dat[6] = array("name"=> "June", "days"=>30);
	$dat[7] = array("name"=> "July", "days"=>31);
	$dat[8] = array("name"=> "August", "days"=>31);
	$dat[9] = array("name"=> "September", "days"=>30);
	$dat[10] = array("name"=> "October", "days"=>31);
	$dat[11] = array("name"=> "November", "days"=>30);
	$dat[12] = array("name"=> "December", "days"=>31);
	
	return $dat;


  }

  private function getDayOfWeek($firstSabath=7, $day=""){
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
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "Tues";
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

  private function getPartialContent($stringy){
	$newString = substr($stringy,strpos($stringy, "container") +19,strlen($stringy));
    $newString = substr($newString, 0, strpos($newString, "Math")-17);
	return $newString;
  }

  private function getLegend(){


	$mar = '<div class="key">
			<div class="GC label">Gregorian Calendar</div>
			<div class="HCC label">Hebrew Calendar</div>
			<div class="SC label">Solar Calendar</div>
		</div>
		<div class="calendarWrapper">
		<div class="actions">
			<div class="caption">THE BELOW DATES REPRESENT THE SABBATHS OF EACH MONTH</div>
			<div class="yearToggle prev"><a href="#">Previous Year</a></div>
			<div class="yearToggle next"><a href="#">Next Year</a></div>
		</div>
		</div>';

	return $mar;
  }


  private function getData(){

	$markup = "";


	
	$thisURL = $_SERVER['REQUEST_URI'];
	$splits = explode("/",$thisURL);
	$year = $splits[count($splits)-1];
	$era = $splits[count($splits)-2];

	$new_key = "chart3 db";
	$database = \Drupal::database();
	$con = \Drupal\Core\Database\Database::getConnection('calendar');

	
	//$sql = "SELECT holydays.*, chart3.*, `generator`.html, calendardates.First_Sabbath, calendardates.GC_YearLen, `calendardates`.Nisan_1, calendardates.Tish_1, calendardates.HCCYearLength, calendardates.SC_YearLen ";
	//$sql .=" FROM `holydays` JOIN `chart3` on `holydays`.AM = `chart3`.year JOIN `calendardates` on `calendardates`.AM = `holydays`.AM JOIN `generator` on `generator`.AM_Year = `holydays`.AM where ";
	
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

	$markup .= "<pre>" . print_r($result[0], true) . "</pre>";
	$markup .= "<h2 class='text-center'>" . $result[0]->GC_Year . " " . $result[0]->GC_Era . "</h2>";
	$markup .= "<input type='hidden' name='gregDate' value='" .$result[0]->GC_Era . $result[0]->GC_Year .  "' />";
	
	$markup .= "<table class='table'><thead><tr><th>Holy Day</th><th>Start Day</th><th>End</th></thead>";
	$markup .= "<tr>";
	$markup .= "<tr><td>Passover</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "passover") . ", " . $result[0]->passover_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Unleavened Bread</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "unleavenedbread") . ", " . $result[0]->unleavened_bread_start . "</td><td>" . $result[0]->unleavened_bread_end . "</td></tr>";
	$markup .= "<tr><td>Pentecost</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "pentecost") . ", " . $result[0]->pentecost_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Trumpets</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "feastoftrumpets") . ", " . $result[0]->feast_of_trumpets_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Atonement</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "dayofatonement") . ", " . $result[0]->day_of_atonement_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Tabernacles</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "feastoftabernacles") . ", " . $result[0]->feast_of_tabernacles_start . "</td><td>" . $result[0]->feast_of_tabernacles_end . "</td></tr>";
	$markup .= "<tr><td>Last Great Day (8th Day)</td><td>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "lastgreatday") . ", " . $result[0]->last_great_day_start . "</td><td></td></tr>";
	$markup .= "</tr>";
	$markup .= "</table>";

	$markup .= "<h3 class='text-center'>THE ABOVE DATES ARE OBSERVED THE PREVIOUS EVENING, AFTER SUNSET</h3>";
	

	$amYear = $result[0]->AM;
	$cycles = $amYear/247;
	$which19base = $amYear/19;
	$which19 = floor($which19base)+1;
	$placeInCycle = $amYear - (floor($amYear/247)*247);
	$where19 = $placeInCycle%19;
	
	
	
	$markup .= "<input type='hidden' id='AMYear' value='" . $result[0]->AM . "' />";
	// add the other one
	$markup .= "<div class='calendarWrapper'><div class='calendarMarkup'>";

	$markup .= "<div class='calendarMetrics' style='background: rgba(0,0,0,0.4); border-radius: 10px; padding: 2em;'><div class='row'>
		<div class='col-sm-4'>How many 247 Year Cycles: " . ceil($cycles) . " </div>
		<div class='col-sm-4'>Which 19 Year Cycle: " . $which19 . " </div>
		<div class='col-sm-4'>Where in 19 Year Cycle: " . $where19 . " </div>
	</div>
	<div class='row'>
		<div class='col-sm-4'>
		<hr />
			Hebrew Calendar Days: " . $result[0]->HCCYearLength . "<br />
			
		</div>
		<div class='col-sm-4'>
			<hr />
			Solar Calendar Days: " . $result[0]->SC_YearLen . " 
			
			
		</div>
		<div class='col-sm-4'>
			<hr />
			Difference: ". $result[0]->D . "
		</div>
	</div>

	</div>";

	$markup .= CalendarDatesBlock::getLegend();
	$markup .= "</div><div class='clear'></div>";
	
	
	$markup .= "</div>";


	$fileName = $result[0]->GC_Era . $result[0]->GC_Year . ".html";
	// load the html?
	$path = $_SERVER["DOCUMENT_ROOT"] . "/Content/download/generator/output/" . $fileName;
	$out = file_get_contents($path);
	$out = html_entity_decode($out);
    $out = str_replace("&amp;nbsp;", "&nbsp;", $out);

	$shorten  = substr($out, strpos($out, "NewCalendarContainer")+22);
	$markup .= "<div class='loadhtmlwrapper'>" . $shorten . "</div>";
	return $markup;

  }

  public function build() {

  
	$markup = CalendarDatesBlock::getData();
	//$markup = 'hello';
	// reset DB connection
	\Drupal\Core\Database\Database::getConnection();
    return [
    	'#markup' => $this->t($markup),
		'#attached' => [
			'library' => [
			  'calendar_generator_dates/dates',
			]
		],
    
      

    ];
  }

}