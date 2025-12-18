<?php 

namespace Drupal\calendar_generator_nav\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class CalendarNavController extends ControllerBase {

    public function calendar_nav($era_type, $year) {
        $database = \Drupal::database();
        $con = \Drupal\Core\Database\Database::getConnection('calendar');
    
        $thisAM = 1;
        $yearVal = $year;
        if($era_type=="am"){
            $thisAM = $year;
        } else {
            if($era_type=="bc"){
                $yearVal = "-" . $year;
            } 
            $sql = "SELECT * FROM `chart3new` where ";
            if($era_type=="am"){
            $sql  .= 'amyr = ' . $year;
            } else {
            $sql  .= 'gyr = ' . $yearVal;
            }
            $output = $sql;
            $query = $con->query($sql);
            $result = $query->fetchAll();
        
            $res = print_r($result, true);
            $thisAM = $result[0]->amyr;
        }

        // return a redirect to the AM year page
        $redirURL = "/calendar/date/am/" . $thisAM;;
        $url = Url::fromUserInput("/calendar/date/am/" . $thisAM);

        // Simply RETURN the response. Do NOT use ->send();
        //return new RedirectResponse($url->toString());

    	
        return [
            '#type' => 'markup',
            '#markup' => $this->t('<h1 class="page-title">YEAR @name @era</h1>', [
		        '@name' => $year,
		        '@era' => $era_type,
		      ]),
        ];
        
    }

}
