<?php 

namespace Drupal\calendar_generator_dates\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;


class CalendarController extends ControllerBase {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }
    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }
  
    public function calendar_json($era_type, $year){


      return new JsonResponse([ 'data' => $this->getData($era_type, $year), 'method' => 'GET', 'status'=> 200]);
    }


    public function calendar_legend_json($era_type, $year){


      return new JsonResponse([ 'data' => $this->getLegend($era_type, $year), 'method' => 'GET', 'status'=> 200]);
    }


    public function getLegend($era, $year){
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


      $res = array();
      
      $query = $con->query($sql);
      $result = $query->fetchAll();
      $res['GC_Year'] = $result[0]->GC_Year;
      $res['GC_Era'] = $result[0]->GC_Era;
      $res['AM'] = $result[0]->AM;
      
     
      $res['passoverDay'] = getDayOfWeek($result[0]->HS, "passover");
      $res['passoverStart'] = $result[0]->passover_start;
      $res['unleavenedbreadDay'] = getDayOfWeek($result[0]->HS, "unleavenedbread");
      $res['unleavenedbreadStart'] =  $result[0]->unleavened_bread_start;
      $res['unleavenedbreadEnd'] =  $result[0]->unleavened_bread_end;

      
      $res['pentecostDay'] =  getDayOfWeek($result[0]->HS, "pentecost");
      $res['pentecostStart'] =  $result[0]->pentecost_start;

      $res['trumpetsDay'] = getDayOfWeek($result[0]->HS, "feastoftrumpets");
      $res['trumpetsStart'] = $result[0]->feast_of_trumpets_start;

      $res['atonementDay'] = getDayOfWeek($result[0]->HS, "dayofatonement");
      $res['atonementStart'] = $result[0]->day_of_atonement_start;

      $res['tabernaclesDay'] = getDayOfWeek($result[0]->HS, "feastoftabernacles");
      $res['tabernaclesStart'] = $result[0]->feast_of_tabernacles_start;
      $res['tabernaclesEnd'] = $result[0]->feast_of_tabernacles_end;

      $res['lastgreatdayDay'] = getDayOfWeek($result[0]->HS, "lastgreatday");
      $res['lastgreatdayStart'] = $result[0]->last_great_day_start;


     


      $amYear = $result[0]->AM;
      $cycles = $amYear/247;
      $which19base = $amYear/19;
      $which19 = floor($which19base)+1;
      $placeInCycle = $amYear - (floor($amYear/247)*247);
      $where19 = $placeInCycle%19;

      $res['cycles247'] = ceil($cycles);
      $res['which19'] = $which19;
      $res['where19'] = $where19;
	
      
      $res["we"] = "eeee";
      return $res;

    }

    public function importChartThreeData() {
      $new_key = "chart3 db";
      $database = \Drupal::database();
      $con = \Drupal\Core\Database\Database::getConnection('calendar');
      $output = [];
      // open a csv file in this module directory
      $filePath = DRUPAL_ROOT . '/modules/custom/Calendar_Generator_Dates/data/chart3.csv';
      // get real path of the file
      //$filePath = realpath($filePath);
      $file = fopen($filePath, 'r');
      //return new JsonResponse(['status'=>'success', 'data'=>$filePath]);
    
      
      
      $header = fgetcsv($file);
      $header = array_map('trim', $header);
      $header = array_map('strtolower', $header);
      // loop through each row of the csv file
      $rows = array();
      while (($row = fgetcsv($file)) !== FALSE) {
        $row = array_map('trim', $row);
        $row = array_map('strtolower', $row);
        $rows[] = array_combine($header, $row);
      }
      $counter = 0;
      foreach($rows as $row) {
        if(true) {
          $thisRow['year'] = (int)$row['am yr'];
          $thisRow['d'] = $row['d'];
          
          
          // check if the row already exists in the database
          
          
            // update the row
            $con->update('chart3')
              ->fields([
                'd' => $thisRow['d'],
              ])
              ->condition('year', $thisRow['year'])
              ->execute();

              $output[] = [
                'year' => $thisRow['year'],
                'd' => $thisRow['d'],
                'status' => 'updated'
              ];
          
         
        } else {
          continue;
        }
        $counter++;
      }

      /*$sql = "SELECT * FROM chart3 limit 1000";
      $query = $con->query($sql);
      $result = $query->fetchAll();
      */

      return new JsonResponse(['status'=>'success', 'data'=>$output]);
    }

    private function chartThreeUpdate($year, $dvalue){
      $new_key = "chart3 db";
      $database = \Drupal::database();
      $con = \Drupal\Core\Database\Database::getConnection('calendar');


      $sql = "Update chart3 set D=". $dvalue . " WHERE year=" .$year;
      //$query = $con->query($sql);
      //$result = $query->fetchAll();

      return $sql;
      


    }
    public function updateChartThreeValue() {
      // get am year
      // get dco value
      // update

      $post = $this->requestStack->getCurrentRequest()->getContent();
      $data = json_decode($post, true);
      $year = isset($data['year'])?$data['year']:null;
      $value = isset($data['value'])?$data['value']:null;
      if($year && $value){


        // update in db
        $sql = $this->chartThreeUpdate($year, $value);

        return new JsonResponse(['status'=>'success', "year"=>$year, "value"=>$value, "message"=>$sql]);
      } else {
        return new JsonResponse(['status'=>'error', 'message'=>"invalid params"]);
      }
      
    }


    public function  getData($era, $year) {
      

      
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
      
      $res = array();
      $res['GC_era'] = $result[0]->GC_Era;
      $res['AM'] = $result[0]->AM;
      $res['GC_year'] = $result[0]->GC_Year;
      $res['Nisan_1'] = $result[0]->Nisan_1;

      return $res;
    }
    
}