<?php

namespace Drupal\calendar_generator_dates\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\hebrew_calendar_generator\CalendarYearGenerator;


/**
 * Provides a 'Calendar' Block.
 *
 * @Block(
 *   id = "calendar_html_block",
 *   admin_label = @Translation("Calendar HTML Block"),
 *   category = @Translation("Calendar HTML "),
 * )
 */


class CalendarHtmlBlock extends BlockBase implements ContainerFactoryPluginInterface {


	/**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MyCustomNodeBlock object.
   *
   * @param array $configuration
   * A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   * The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   * The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }
  /**
   * {@inheritdoc}
   */

  public function getCacheMaxAge() {
    return 0;
  }




  private function getPartialContent($stringy){
	$newString = substr($stringy,strpos($stringy, "container") +19,strlen($stringy));
    $newString = substr($newString, 0, strpos($newString, "Math")-17);
	return $newString;
  }


  private function getDayOfWeekShort($dow){
    $returnVal = "";
    switch($dow){
      case "Sunday":
        $returnVal = "S";
        break;
      case "Monday":
        $returnVal = "M";
        break;
      case "Tuesday":
        $returnVal = "T";
        break;
      case "Wednesday":
        $returnVal = "W";
        break;
      case "Thursday":
        $returnVal = "Th";
        break;
      case "Friday":
        $returnVal = "F";
        break;
      case "Saturday":
        $returnVal = "Sab";
        break;
    }
    return $returnVal;
  }
  private function getBGClassForFeastType($type){
    $returnClass = "";
    switch($type){
      case "Passover":
        $returnClass = "bg-passover";
        break;
      case "First Day of Unleavened Bread":
        $returnClass = "bg-unleavenedbread";
        break;
      case "Regular Day of Unleavened Bread":
        $returnClass = "bg-unleavenedbread";
        break;
      case "Last Day of Unleavened Bread":
          $returnClass = "bg-unleavenedbread";
          break;
      case 'Pentecost':
        $returnClass = "bg-pentecost";
        break;
      case 'Atonement':
        $returnClass = "bg-atonement";
        break;
      case 'Trumpets':
        $returnClass = "bg-trumpets";
        break;
      case 'First Day of Tabernacles':
        $returnClass = "bg-tabernacles";
        break;
      case 'Regular Day of Tabernacles':
        $returnClass = "bg-tabernacles";
        break;
      case 'Eighth Day':
        $returnClass = "bg-lastgreatday";
        break;
      // Add more cases as needed
    }
    return $returnClass;
  }
  private function listStatsForYear($year){
    $markup = "<div class='stats'>";
    $markup .= "<table class='table table-bordered'>";
    // get cycleId19Year, cycleId247Year, hebrewYearDays, solarYearDays, diffBetweenSolarAndHebrewDay, gregorianYearDays , yearIn19YearCycle from $year in a table
    $markup .= "<tr><th>Property</th><th>Value</th></tr>";
    $markup .= "<tr><td>Cycle ID 19 Year</td><td>" . $year->cycleId19Year . "</td></tr>";
    $markup .= "<tr><td>Cycle ID 247 Year</td><td>" . $year->cycleId247Year . "</td></tr>";
    $markup .= "<tr><td>Hebrew Year Days</td><td>" . $year->hebrewYearDays . "</td></tr>";
    $markup .= "<tr><td>Solar Year Days</td><td>" . $year->solarYearDays . "</td></tr>";
    $markup .= "<tr><td>Difference Between Solar and Hebrew Day</td><td>" . $year->diffBetweenSolarAndHebrewDay . "</td></tr>";
    $markup .= "<tr><td>Gregorian Year Days</td><td>" . $year->gregorianYearDays . "</td></tr>";
    $markup .= "<tr><td>Year in 19 Year Cycle</td><td>" . $year->yearIn19YearCycle . "</td></tr>";
    // add solarYearDaysToFirstGregorianSabbath
    $markup .= "<tr><td>Solar Year Days to First Gregorian Sabbath</td><td>" . $year->solarYearDaysToFirstGregorianSabbath . "</td></tr>";
    // add numDaysInPreviousHebrewYear()
    
    $markup .= "</table>";
    $markup .= "</div>";
    return $markup;
  }
  private function getCalendarYear($yearVal){

    $generator = \Drupal::service('hebrew_calendar_generator.generator');

    $generator->prepareYears();

    $year = $generator->createYear((int)$yearVal);
    $markup = "";
    
    $markup .= "<pre class='hidden'>" . print_r($year, TRUE) . "</pre><h2>New calculations from chart3</h2>";
    $startingSolarFromCreation = $year->solarYearDaysToFirstGregorianSabbath;
    $markup .="<div id='total-calendar-wrapper' class='hidden'><ul class='d-flex flex-wrap mainUl'>";

    $currentMonth = "";
    foreach ($year->enumerateWeeks() as $week) {
      $markup .= '<li class="mb-4 card week">';
      // And you can loop through the days!

      $markup .= "<div class='gregorian'>";
      $counter = 1;
      $markup .= "<div class='month'>";
      $showMonth = false;
      foreach ($week->enumerateDays() as $day) {
        $monthName = $day->gregorianMonth->toString();
        if ($monthName !== $currentMonth) {
          $markup .= " <span class='month-show'>" . $monthName . "\n</span>";
          $showMonth = true;
          $currentMonth = $monthName;
        } 
      }
      if($showMonth === false){
        $markup .= " <span class='month-hide'>&nbsp;</span>";
      }
      $markup .= "</div>";
      $markup .= "<ul class='d-flex daylist'>";

      foreach ($week->enumerateDays() as $day) {
        
        $feastClass = "";
        $feastType = $day->getFeastDayType()->toString();
        if($feastType != "None"){
          
          $feastClass = $this->getBGClassForFeastType($feastType);
          
        }
        $dayofWeekShort = $this->getDayOfWeekShort($day->dayOfWeek->toString());
        $sabClass="";
        if($day->dayOfWeek->toString() === "Saturday") {
          $sabClass = " sabbath";
        } 
        $markup .= "<li class='day feast ". $sabClass ."' 
          data-day='". $day->gregorianDay ."' 
          data-month='". $day->gregorianMonth->toString() ."'>";
          $markup .= "<span class='weekday d-block'>" . $dayofWeekShort . "</span>";

          $markup .= "<span class='weekdate d-block ". $feastClass ."'>" . $day->gregorianDay . "</span>";
        // You can access the public properties and method of $day, e.g.:
        
        
        $markup .= "</li>";
        
        
      }
      $markup .= "</ul></div>";

      $markup .= "<div class='hebrew'>";
      $currentHebrewMonth = "";
      $markup .= "<div class='month'>";

      foreach ($week->enumerateDays() as $day) {
        $hebrewMonthName = $day->hebrewMonth->toString();
        if ($hebrewMonthName !== $currentHebrewMonth) {
          $markup .= " <span class='month-show'>" . $hebrewMonthName . "\n</span>";
          $currentHebrewMonth = $hebrewMonthName;
        } else {
          $markup .= " <span class='month-hide'>&nbsp;</span>";
        }
      }
      $markup .= "</div><ul class='daylist'>";
      foreach ($week->enumerateDays() as $day) {
        $markup .= "<li class='hebrew-day'>" . $day->hebrewDay . "</li>";
      }
      $markup .= "</ul></div>";
      $markup .= "<div class='solar-container'>";
      $markup .= 'Sab: ' . $week->sabbathIdFromCreation . "<div class='solar'>";
      $markup .= 'days: ' . $startingSolarFromCreation;
      $markup .= "</div></div>";
      $markup .= "</li>";
      $startingSolarFromCreation++;
    }
    $markup .= "</ul></div>";

    $markup .= $this->listStatsForYear($year);
    return $markup;
  }


  private function getData(){

	$node = $this->routeMatch->getParameter('node');
	$node_id = "";
	$shorten = "Load here";

	 if ($node instanceof NodeInterface) {
      // You have the node object!
		$node_title = $node->label();
		$node_id = $node->id();
    $amYear = $node->get("field_am_year")->value;

		$adbc = $node->get("field_gc_era")->value;
		$yearVal = $node->get("field_gregorianyear")->value;
		$adbc = strtoupper($adbc);	
		

		$markup = "";

		$fileName = $adbc . $yearVal . ".html";
		// load the html?
		$path = $_SERVER["DOCUMENT_ROOT"] . "/Content/download/generator/output/" . $fileName;
		$out = file_get_contents($path);
		$out = html_entity_decode($out);
		$out = str_replace("&amp;nbsp;", "&nbsp;", $out);

		$shorten  = substr($out, strpos($out, "id=\"AMYear")-7);
		
		
		
		$markup = "<div class='path-calendar'>";
		$markup .= "<input type='hidden' name='gregDate' value='" . $adbc . $yearVal . "' />";
		$markup .= "<input type='hidden' name='eraType' value='" .$adbc . "' />";
    $markup .= "<a href='#' class='mobileToggle btn btn-secondary'><i class='fa fa-chevron-right'></i></a>";
		$markup .="<div class='loadhtmlwrapper calendarWrapper'><div class='calendarMarkup'>" . $shorten . "</div></div></div>";
		
    
    
    $markup .= $this->getCalendarYear($amYear);

    return $markup;
	} else {
		$markup = "Error";
		return $markup;		
	}


  }

  public function build() {


	$markup = $this->getData();


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
