<?php

namespace Drupal\calendar_generator_page\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Provides a 'Calendar' Block.
 *
 * @Block(
 *   id = "calendar_block",
 *   admin_label = @Translation("Calendar Block"),
 *   category = @Translation("Calendar content"),
 * )
 */


class CalendarBlock extends BlockBase {

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
		"lastgreatday"=> ""
	   ];

		switch($firstSabath){
			case 1: 
				$list['passover'] = "Fri"; 
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "-";
			break;
			case 3: 
				$list['passover'] = "Wed"; 
				$list['unleavenedbread'] = "Thurs";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Sat";
				$list['dayofatonement'] = "Mon";
				$list['feastoftabernacles'] = "Sat";
				$list['lastgreatday'] = "-";
			break;
			case 5: 
				$list['passover'] = "Mon"; 
				$list['unleavenedbread'] = "Tues";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Thurs";
				$list['dayofatonement'] = "Sat";
				$list['feastoftabernacles'] = "Thurs";
				$list['lastgreatday'] = "-";
			break;
			case 7: 
				$list['passover'] = "Sat"; 
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "-";

			break;
			default: 
				$list['passover'] = "Sat"; 
				$list['unleavenedbread'] = "Sun";
				$list['pentecost'] = "Sun";
				$list['feastoftrumpets'] = "Tues";
				$list['dayofatonement'] = "Thurs";
				$list['feastoftabernacles'] = "Tues";
				$list['lastgreatday'] = "-";

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


	$mar = '<hr /><div class="key">
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

  private function addGorSabbath($start, $max, $dateArr, $monthNum){
	
	$gorMonths = CalendarBlock::getGorMonths();
	$canRun = ($max - $start)/7;
	for($i=1;$i<=$canRun;$i++){
		$multiplier = $start + (7 * $i);
		$dateArr[$monthNum]["gor"]["sabbaths"][] = $multiplier;
	}
	$dateArr[$monthNum]["gor"]["name"] = $gorMonths[$monthNum]["name"];
	return $dateArr;
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
	$markup .= "<h2 class='text-center'>" . $result[0]->GC_Year . " " . $result[0]->GC_Era . "</h2>";
	

	$markup .= "<code>" . $sql ."</code><hr />";
	
	$markup .= "<table class='table'><thead><tr><th>Holy Day</th><th>Day</th><th>Start</th><th>End</th></thead>";
	$markup .= "<tr>";
	$markup .= "<tr><td>Passover</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "passover") . "</td><td>" . $result[0]->passover_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Unleavened Bread</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "unleavenedbread") . "</td><td>" . $result[0]->unleavened_bread_start . "</td><td>" . $result[0]->unleavened_bread_end . "</td></tr>";
	$markup .= "<tr><td>Pentecost</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "pentecost") . "</td><td>" . $result[0]->pentecost_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Trumpets</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "feastoftrumpets") . "</td><td>" . $result[0]->feast_of_trumpets_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Atonement</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "dayofatonement") . "</td><td>" . $result[0]->day_of_atonement_start . "</td><td></td></tr>";
	$markup .= "<tr><td>Tabernacles</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "feastoftabernacles") . "</td><td>" . $result[0]->feast_of_tabernacles_start . "</td><td>" . $result[0]->feast_of_tabernacles_end . "</td></tr>";
	$markup .= "<tr><td>Last Great Day (8th Day)</td><td>" . CalendarBlock::getDayOfWeek($result[0]->HS, "lastgreatday") . "</td><td>" . $result[0]->last_great_day_start . "</td><td></td></tr>";
	$markup .= "</tr>";
	$markup .= "</table>";

	$markup .= "<hr /><h3 class='text-center'>THE ABOVE DATES ARE OBSERVED THE PREVIOUS EVENING, AFTER SUNSET</h3>";
	

	$amYear = $result[0]->AM;
	$cycles = $amYear/247;
	$which19base = $amYear/19;
	$which19 = floor($which19base)+1;
	$placeInCycle = $amYear - (floor($amYear/247)*247);
	$where19 = $placeInCycle%19;
	
	$markup .= CalendarBlock::getLegend();
	

	$markup .="<pre style='display: none'>" . print_r($result[0], true) . "</pre>";
	$markup .= "<input type='hidden' id='AMYear' value='" . $result[0]->AM . "' />";
	// add the other one
	$markup .= "<div class='calendarWrapper'><div class='calendarMarkup'>";

	$markup .= "<hr /><h3>Info</h3><div class='calendarMetrics' style='background: rgba(0,0,0,0.4); border-radius: 10px; padding: 2em;'><div class='row'>
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

	$markup .= "<ul>";
	$markup .= "<li>AM Year: " . $result[0]->AM . "</li>";
	$markup .= "<li>Days: " . $result[0]->days . "</li>";
	$markup .= "<li>DCO: " . $result[0]->DCO . "</li>";
	$markup .= "<li>Type: " . $result[0]->Type . "</li>";
	//$markup .= "<li>AM S: " . $result[0]->AM%20%S . "</li>";
	$markup .= "<li>HS: " . $result[0]->HS . "</li>";
	$markup .= "<li>D: " . $result[0]->D . "</li>";
	$markup .= "<li>Cyc: " . $result[0]->Cyc . "</li>";
	//$markup .= "<li>Gor S: " . $result[0]->`Gor%20%S . "</li>";


	$markup .= "</ul><hr />";

	$markup .= "<textarea id='loadGenerator' data-year='" .  $result[0]->GC_Year . "' data-era='" . $result[0]->GC_Era . "'>Load</textarea>";
	
	$markup .= CalendarBlock::getLegend();
	$gorMonths = CalendarBlock::getGorMonths();
	$gor = "Gor. S";
	$ams = "AM S";
	$dateArr = array();
	$markup .="<div id='NewCalendarContainer'>Get api for # of days in month for a year? So, we have <ul>
		<li>first Sabbath of Gregorian as " . $result[0]->$gor . "</li>
		<li>first Sabbath of Solar as " . $result[0]->$ams . "</li>
		<li>first Sabbath of Hebrew as " . $result[0]->HS . " which is where nisan 1 is</li>
	</ul>
	</div><hr />";
	$markup .= "<div id='loadCal'></div>";
	

	$counter = 1;

	$dateArr[$counter]["gor"]["start"] = $result[0]->$gor;
	$dateArr[$counter]["gor"]["sabbaths"] = array();

	$dateArr = CalendarBlock::addGorSabbath($result[0]->$gor,$gorMonths[$counter]["days"], $dateArr, $counter);

	foreach($gorMonths as $index=>$month){
		$start = 0;
		$maxDays = $month["days"];
		$dateArr[$counter]["gor"]["name"] = $month["name"];
		$dateArr[$counter]["gor"]["sabbaths"] = array();

		if($counter>1){
			if(!isset($dateArr[$counter]["gor"]["start"])){
				
			
				$prev = $counter-1;
				$len=count($dateArr[$prev]["gor"]["sabbaths"]);
				if($len>1 && $dateArr[$prev]){
					$start = $dateArr[$prev]["gor"]["sabbaths"][$len-1];
				} else {
					$start = 1;
				}
				$start += 7;

				if($start > $maxDays){
					$diff = ($start+7)-$maxDays;
					$start=$diff;
				}
				$dateArr[$counter]["gor"]["start"] = $start;
			} else {
				
			}
			
			

			
			$canRun = ($maxDays - $start)/7;
			for($i=1;$i<=$canRun;$i++){
				$multiplier = $start + (7 * $i);
				$dateArr[$counter]["gor"]["sabbaths"][] = $multiplier;
			}

		}
		

		$counter++;
	}
	//$markup .="<pre>" . print_r($dateArr, true) . "</pre>";
	//$markup .= "<ul>";
	foreach($result[0] as $key=> $re){
		//$markup .= "<li> " . $key . ": " . $re . "</li>";
	}
	//$markup .= "</ul>";

	
	
	$markup .= "</div><div class='clear'></div>";
	
	
	$markup .= "</div>";

	return $markup;

  }

  public function build() {

  
	$markup = CalendarBlock::getData();
	//$markup = 'hello';
	// reset DB connection
	\Drupal\Core\Database\Database::getConnection();
    return [
    	'#markup' => $this->t($markup),
		'#attached' => [
			'library' => [
			  'calendar_generator_page/calendar',
			]
		],
    
      

    ];
  }

}