<?php 

namespace Drupal\Chart_Three\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

class ChartThreeController extends ControllerBase {

    public function index() {
        return new JsonResponse([ 'data' => $this->getChart3(), 'method' => 'GET', 'status'=> 200]);
    }
    public function getChart3(){
        $thisURL = $_SERVER['REQUEST_URI'];
       
        $database = \Drupal::database();
        $con = \Drupal\Core\Database\Database::getConnection('calendar');
    
        $sql = "SELECT * FROM `chart3`";
        
        $query = $con->query($sql);
        $result = $query->fetchAll();

        //return json data
        return $result;
        
        
    }
}