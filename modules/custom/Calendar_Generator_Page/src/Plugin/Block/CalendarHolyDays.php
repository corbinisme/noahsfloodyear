<?php

namespace Drupal\calendar_generator_page\Plugin\Block;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Provides a 'Calendar Ajax' Block.
 *
 * @Block(
 *   id = "calendar_ajax_block",
 *   admin_label = @Translation("Calendar Ajax Block"),
 *   category = @Translation("Calendar Ajax Block"),
 * )
 */



class CalendarHolyDays extends BlockBase {



  /**

   * {@inheritdoc}

   */



  public function getCacheMaxAge() {

    return 0;

}



  public function build() {

    $thisURL = $_SERVER['REQUEST_URI'];
    $splits = explode("/",$thisURL);
    $year = $splits[count($splits)-1];
    $era = $splits[count($splits)-2];

    /*
    $database = \Drupal::database();
    $con = \Drupal\Core\Database\Database::getConnection('calendar');

    $sql = "SELECT * FROM `chart3` JOIN `generator` on AM_Year = year where ";
    if($era=="AM"){
      $sql  = 'SELECT * from `generator` WHERE AM_Year = "' . $year . '"';
    } else {
      $sql  = 'SELECT * from `generator` WHERE GC_Year = "' . $year . '" AND GC_ERA = "' . $era .'"';
    }

  

    $query = $con->query($sql);
    $result = $query->fetchAll();

    $res = print_r($result, true);

    $marky = '<div class=""><pre>@output</pre></div>';

  	//Database::setActiveConnection();

    \Drupal\Core\Database\Database::getConnection();

    */
    $marky = "<div id='loadGenerator2' data-year='" . $year . "' data-era='" . $era . "'>Load</div>";

    return [

    	//'#markup' => $output
     
    	

      '#markup' => $this->t($marky, [

   



      ]),

      



    ];

  }



}