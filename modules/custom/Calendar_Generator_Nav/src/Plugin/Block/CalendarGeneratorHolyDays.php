<?php

namespace Drupal\calendar_generator_nav\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\hebrew_calendar_generator\CalendarYearGenerator;

/**
 * Provides a 'Calendar holy days' Block.
 *
 * @Block(
 *   id = "calendar_holy_days_block",
 *   admin_label = @Translation("Calendar Holy Days Block"),
 *   category = @Translation("Calendar Navigation"),
 * )
 */


class CalendarGeneratorHolyDays extends BlockBase {

  /**
   * {@inheritdoc}

    */

  

  public function build() {

  	$thisURL = $_SERVER['REQUEST_URI'];
  	$splits = explode("/",$thisURL);
  	$year = $splits[count($splits)-1];
  	$era = $splits[count($splits)-2];
    $holyDays = [];
    $generator = \Drupal::service('hebrew_calendar_generator.generator');

    $generator->prepareYears();
    $markup = "<div class='block-calendar-generator-dates'>";
    $year = $generator->createYear((int)$year);
    foreach ($year->enumerateWeeks() as $week) {
      foreach ($week->enumerateDays() as $day) {
        $feastType = $day->getFeastDayType()->toString();
        if ($feastType != "None") {
         
          $holyDays[$feastType] = $day;
        }
      }
    }

    //$markup .= "<div><strong>Holy Days</strong></div>";
    //$markup .= "<pre>" . print_r($holyDays, true) . "</pre>";
    $markup .='<table class="table ">
    <thead>
    <tr>
    <th>Holy Day</th>
    <th>Start Day</th>
    </tr>
    </thead>
    <tbody>';

    foreach ($holyDays as $type =>$day) {
      if(strpos($type, "Regular") === false){
        $startDate = "";
        $feastClass = "";
        $label = "";
        switch ($type) {
          case "Passover":
            $feastClass = "passover";
            $label = "Passover";
            break;
          case "First Day of Unleavened Bread":
            $feastClass = "unleavenedbread";
            $label = "Unleavened Bread";
            break;
          case "Last Day of Unleavened Bread":
            $feastClass = "unleavenedbread";
            $label = "Unleavened Bread End";
            break;
          case "Pentecost":
            $feastClass = "pentecost";
            $label = "Pentecost";
            break;
          case "Trumpets":
            $feastClass = "trumpets";
            $label = "Trumpets";
            break;
          case "Atonement":
            $feastClass = "atonement";
            $label = "Atonement";
            break;
          case "First Day of Tabernacles":
            $feastClass = "tabernacles";
            $label = "Tabernacles";
            break;
          case "Eighth Day":
            $feastClass = "lastgreatday";
            $label = "Eighth Day";
            break;
          default:
            $feastClass = "";
        }
        $markup .='<tr>';
        $markup .='<td class="' . $feastClass . '"><span></span>' . $label . '</td><td>';
        $markup .= $day->gregorianMonth->name . ' ' ;
        $markup .= $day->gregorianDay;
        // if it is a multi-day feast, show the end date
        $endDate = "";
        $endMonth = "";
        if($type == "First Day of Unleavened Bread"){
          // show the last day of unleavened bread 
          $item = $holyDays["Regular Day of Unleavened Bread"];
          $endDate = $item->gregorianDay;
          $endMonth = $item->gregorianMonth->name;
        } elseif ($type == "First Day of Tabernacles") {
          $item = $holyDays["Regular Day of Tabernacles"];
          $endDate = $item->gregorianDay;
          $endMonth = $item->gregorianMonth->name;
        }
        if ($endDate != "") {
          // IF the end month is different, show it
          if ($endMonth != $day->gregorianMonth->name) {
            $markup .=' - ' . $endMonth . ' ';
          }
          $markup .='- ' . $endDate;
        } 
        $markup .='</td>';
        $markup .='</tr>';
        
      }
    }
    $markup .='</tbody>
    </table></div>';
    // this is not sending data to the template for some reason
    
    
    

      return [
      	'#markup' => $markup,
        
        
      ];
  }

}
