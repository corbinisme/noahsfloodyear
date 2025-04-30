<?php

namespace Drupal\calendar_generator_dates\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Provides a 'Calendar Questions' Block.
 *
 * @Block(
 *   id = "calendar_dates_question_block",
 *   admin_label = @Translation("Calendar Dates Question Block"),
 *   category = @Translation("Calendar Dates"),
 * )
 */


class CalendarDatesQuestionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function getCacheMaxAge() {
    return 0;
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
	$markup .= "<input type='hidden' id='AMYearCopy' value='" . $result[0]->AM . "' />";
	// add the other one
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

	$markup .= "</div>";


	return $markup;

  }

  public function build() {

  
	$markup = CalendarDatesQuestionBlock::getData();
	//$markup = 'hello';
	// reset DB connection
	\Drupal\Core\Database\Database::getConnection();
    return [
    	'#markup' => $this->t($markup)
		
    ];
  }

}
