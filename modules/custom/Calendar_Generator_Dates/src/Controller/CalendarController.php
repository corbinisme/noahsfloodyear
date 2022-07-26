<?php 

namespace Drupal\calendar_generator_dates\Controller;

use Drupal\Core\Controller\ControllerBase;

class CalendarController extends ControllerBase {

    public function calendar_dates($era_type, $year) {
        return [
            '#type' => 'markup',
            '#markup' => $this->t('<h1>This AM YEAR @name</h1>', [
		        '@name' => $year,
		      ]),
        ];
    }
    public function getCal(){
        $thisURL = $_SERVER['REQUEST_URI'];
        $splits = explode("/",$thisURL);
        $year = $splits[count($splits)-1];
        $era = $splits[count($splits)-2];
    
    
        $database = \Drupal::database();
        $con = \Drupal\Core\Database\Database::getConnection('calendar');
    
        $sql = "SELECT * FROM `chart3` JOIN `generator` on AM_Year = year where ";
        if($era=="AM"){
          $sql  = 'SELECT * from `generator` WHERE AM_Year = "' . $year . '"';
        } else {
          $sql  = 'SELECT * from `generator` WHERE GC_Year = "' . $year . '" AND GC_ERA = "' . $era .'"';
        }
    
        
        /*
        if($era=="am"){
          $sql .= "calendardates.AM = " . $year;
        } else if($era=="bc"){
          $sql .= " calendardates.GC_Era = 'BC' and calendardates.GC_Year = " . $year;
        } else if($era=="ad"){
          $sql .= "calendardates.GC_Era = 'AD' and calendardates.GC_Year = " . $year;
        }
        */
    
        $query = $con->query($sql);
        $result = $query->fetchAll();
    
        $res = print_r($result, true);
        return [
            '#type' => 'markup',
            '#markup' => $this->t('<h1>This AM YEAR @name</h1>', [
		        '@name' => $year,
		      ]),
        ];
    }
}