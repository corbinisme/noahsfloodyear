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

  private function getPartialContent($stringy){
	$newString = substr($stringy,strpos($stringy, "container") +19,strlen($stringy));
    $newString = substr($newString, 0, strpos($newString, "Math")-17);
	return $newString;
  }

  private function getLegend(){


	$mar = '<div class="calendar_legend"><div class="key keepThis">
			<div class="GC label">Gregorian Calendar</div>
			<div class="HCC label">Hebrew Calendar</div>
			<div class="SabbathLegend label">Total # of Sabbaths</div>
			<div class="SC label">Solar Calendar</div>
		</div></div>
		<div class="calendarWrapperSub">
		<div class="actions">
			<div class="caption">
			<h3 class="disclaimer_heading">THE BELOW DATES REPRESENT THE SABBATHS OF EACH MONTH</h3>
			</div>
		</div>
		<div class="actions hidden">
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

	//$markup .= "<pre>" . 
	//print_r($result[0], true) . "</pre>";

	//if the filter date is AM, show GC
	if($era=="AM"||$era=="am"){
		$markup .= "<h2 class='subtitle text-center'>" . $result[0]->GC_Year . " " . $result[0]->GC_Era . "</h2>";
	} else {
		$markup .= "<h3 class='subtitle text-center'>" . $result[0]->AM . " AM (After Man)</h2>";
	}
	$markup .= "<input type='hidden' name='gregDate' value='" .$result[0]->GC_Era . $result[0]->GC_Year .  "' />";
	$markup .= "<input type='hidden' name='eraType' value='" .$result[0]->GC_Era . "' />";
	
	/*
	$markup .= "<div class='calendarWrapper'><div class='row'><div class='col-lg-6'>";
	
	$markup .= '<table class="table"><thead><tr><th>Holy Day</th><th>Start Day</th><th nowrap>End</th></tr></thead>';
	if($result[0]->AM=="1" || ($result[0]->GC_Year=="4046" && $era=="BC")){

		 $markup .=
		'<tbody>' .
		'<tr><td class="passover"><span></span> Passover </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "passover") . ', Apr 2</td><td></td></tr>' .
		'<tr><td class="unleavenedbread"><span class="ubbg"></span> Unleavened Bread </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "unleavenedbread") . ', Apr 3</td><td class="end"> Apr 10</td></tr>'.
		'<tr><td class="pentecost"><span></span> Pentecost </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "pentecost") . ', May 22</td><td></td></tr>'.
		'<tr><td class="trumpets"><span></span> Trumpets </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "feastoftrumpets") . ', Sep 13</td><td></td></tr>'.
		'<tr><td class="atonement"><span></span> Atonement </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "dayofatonement") . ', Sep 22</td><td></td></tr>'.
		'<tr><td class="tabernacles"><span></span> Tabernacles </td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "feastoftabernacles") . ', Sep 27</td><td  class="end">Oct 3</td></tr>'.
		'<tr><td class="lastgreatday"><span></span> Last Great Day (8th Day)</td><td class="start">' . CalendarDatesBlock::getDayOfWeek(7, "lastgreatday") . ', Oct 4</td><td></td>'.
		'</tr>';
		
	} else {

		$markup .= "<tr>";
		$markup .= "<tr><td  class='passover'><span></span> Passover </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "passover") . ", " . $result[0]->passover_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='unleavenedbread'><span class='ubbg'></span> Unleavened Bread </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "unleavenedbread") . ", " . $result[0]->unleavened_bread_start . "</td><td class='end'>" . $result[0]->unleavened_bread_end . "</td></tr>";
		$markup .= "<tr><td class='pentecost'><span></span> Pentecost </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "pentecost") . ", " . $result[0]->pentecost_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='trumpets'><span></span> Trumpets </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "feastoftrumpets") . ", " . $result[0]->feast_of_trumpets_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='atonement'><span></span> Atonement </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "dayofatonement") . ", " . $result[0]->day_of_atonement_start . "</td><td></td></tr>";
		$markup .= "<tr><td class='tabernacles'><span></span> Tabernacles </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "feastoftabernacles") . ", " . $result[0]->feast_of_tabernacles_start . "</td><td class='end'>" . $result[0]->feast_of_tabernacles_end . "</td></tr>";
		$markup .= "<tr><td class='lastgreatday'><span></span> Last Great Day (8th Day) </td><td class='start'>" . CalendarDatesBlock::getDayOfWeek($result[0]->HS, "lastgreatday") . ", " . $result[0]->last_great_day_start . "</td><td></td></tr>";
		$markup .= "</tr>";
		
	}
	$markup .='<tr><td colspan="3"><small>THE ABOVE DATES ARE OBSERVED THE PREVIOUS EVENING, AFTER SUNSET</small></td></tr>' .
		'</tbody></table>';

	$markup .= "<input type='hidden' id='hsvalue' value='" . $result[0]->HS . "' />";
	
	$markup .= "</div><div class='col-lg-6'>";
	*/

	$amYear = $result[0]->AM;
	$cycles = $amYear/247;
	$which19base = $amYear/19;
	$which19 = floor($which19base)+1;
	$placeInCycle = $amYear - (floor($amYear/247)*247);
	$where19 = $placeInCycle%19;
	
	$solarHebDiff = $result[0]->HCCYearLength - $result[0]->SC_YearLen;
	// get absolute value of the difference
	$lastYearDiff = (int)$result[0]->D - (int)$solarHebDiff;
	
	//$markup .= "<pre>" . print_r($result[0], true) . "</pre>";
	$markup .= "<input type='hidden' id='AMYear' value='" . $result[0]->AM . "' />";
	// add the other one
	
	/*
	$markup .= "<div class='calendarMarkup'>";

	$markup .= "<table class='table'>
		<thead><tr><th>Question</th><th>Answer</th></tr></thead><tbody>";
	$markup .= "<tr>";
		$markup .= "<td>What 247 year period from creation?</td>";
		$markup .= "<td>" . ceil($cycles) . "</td>";
	$markup .= "</tr>";
	$markup .= "<tr>";
		$markup .= "<td>Which 19 year time cycle of the 13 in the 247 year period?</td>";
		$markup .= "<td>" . $which19 . "</td>";
	$markup .= "</tr>";
	$markup .= "<tr>";
		$markup .= "<td>What year in the 19 year time cycle?</td>";
		$markup .= "<td>" . $where19 . "</td>";
	$markup .= "</tr>";
	$markup .= "<tr>
		<td>
			Hebrew Calendar Days</td>
			<td>" . $result[0]->HCCYearLength . "</td>
		</tr>
		<tr>
			<td>Solar Calendar Days</td>
			<td>" . $result[0]->SC_YearLen . "</td>
		</tr>
		<tr>
			<td>Difference between the solar and Hebrew calendars</td>
			<td>" . $solarHebDiff . "</td>
		</tr>
		<tr>
			<td>Difference between last year and present year</td>
			<td class='diffWithLastYear'>". $result[0]->D . "</td>
		</tr>
		<tr>
			<td>Last Year's Difference</td>
			<td>" . $lastYearDiff .  "</td>
		</tr>";
	$markup .= "</tbody>";
	$markup .= "</table>";

	$markup .= "</div></div></div>";
	*/


	//$markup .= CalendarDatesBlock::getLegend();
	$markup .= "</div><div class='clear'></div>";
	
	
	$markup .= "</div>";


	$fileName = $result[0]->GC_Era . $result[0]->GC_Year . ".html";
	// load the html?
	$path = $_SERVER["DOCUMENT_ROOT"] . "/Content/download/generator/output/" . $fileName;
	$out = file_get_contents($path);
	$out = html_entity_decode($out);
    $out = str_replace("&amp;nbsp;", "&nbsp;", $out);

	$shorten  = substr($out, strpos($out, "id=\"AMYear")-7);
	// newcalendar +22
	$markup .= "<div class='loadhtmlwrapper calendarWrapper'><div class='calendarMarkup'>" . $shorten . "</div></div>";
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
