<?php 

namespace Drupal\bcp_create_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class BcpCreateCalendarController extends ControllerBase implements ContainerInjectionInterface {
    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager')
        );
    }
    public function fillChart3(){
        
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
        // remove spaces from the header titles
        $header = array_map(function($value) {
            return str_replace(' ', '', $value);
        }, $header);
        // if the header value is "as" replace with "sa"
        $header = array_map(function($value) {
            return $value === 'as' ? 'sa' : $value;
        }, $header);
        // loop through each row of the csv file
        $rows = array();
        while (($row = fgetcsv($file)) !== FALSE) {
            $row = array_map('trim', $row);
            $row = array_map('strtolower', $row);
            // if there is a "-" at the end of the value, move it to the front
            $row = array_map(function($value) {
                if (substr($value, -1) === '-') {
                    return '-' . substr($value, 0, -1);
                }
                return $value;
            }, $row);
            // if value is "n/a", replace with 0
            $row = array_map(function($value) {
                return $value === 'n/a' ? 0 : $value;
            }, $row);
            // remove spaces from the row titles
            $rows[] = array_combine($header, $row);
        }
        $counter = 0;

        // sql query to insert data into chart3 table
        
        $database = \Drupal::database();
        $con = \Drupal\Core\Database\Database::getConnection('calendar');
        // Use Drupal's database API to insert data into chart3 table.
        foreach($rows as $row) {
            if(true) {
                $fields = [
                  "amyr"=>$row['amyr'],
                    "days"=> $row['days'],
                    "dco"=> $row['dco'],
                    "mly"=> (int)$row['mly'],
                    "sa"=> (int)$row['as'],
                    "hs"=> (int)$row['hs'],
                    "d"=> $row['d'],
                    "c19"=> $row['c19'],
                    "c247"=> (int)$row['c247'],
                    "a2g"=> $row['a2g'],
                    "gs"=> $row['gs'],     
                    "gyr"=> $row['gyr']
                    ];
                    
                    
                    
                // insert into chart3 table
                $con->insert('chart3new')
                    ->fields($fields)
                    ->execute();
                $counter++;
            }
        }


        
            
        return new JsonResponse(['status'=>'success', 'data'=>$rows]);
    }

    private function getCalendarData($era, $year) {
        // This function would contain the logic to retrieve calendar data
        // based on the era and year. For now, it returns a placeholder array.
        $database = \Drupal::database();
        $con = \Drupal\Core\Database\Database::getConnection('calendar');
        $sql = "SELECT holydays.*, chart3.*, calendardates.First_Sabbath, calendardates.GC_YearLen, `calendardates`.Nisan_1, calendardates.Tish_1, calendardates.HCCYearLength, calendardates.SC_YearLen, calendardates.GC_Era, calendardates.GC_Year ";
        $sql .=" FROM `holydays` JOIN `chart3` on `holydays`.AM = `chart3`.year JOIN `calendardates` on `calendardates`.AM = `holydays`.AM where ";
      
        $era = strtolower($era);
        if($era=="bc"){
            $sql .= " calendardates.GC_Era = 'BC' and calendardates.GC_Year = " . $year;
        } else if($era=="ad"){
            $sql .= "calendardates.GC_Era = 'AD' and calendardates.GC_Year = " . $year;
        }

        // Execute the query
        $query = $con->query($sql);
        $result = $query->fetchAll();
        $res = [];
        foreach ($result as $row) {
            $res[] = [
                'AM' => $row->AM,
                'GC_Era' => $row->GC_Era,
                'GC_Year' => $row->GC_Year,
                'First_Sabbath' => $row->First_Sabbath,
                'GC_YearLen' => $row->GC_YearLen,
                'Nisan_1' => $row->Nisan_1,
                'Tish_1' => $row->Tish_1,

                'HCCYearLength' => $row->HCCYearLength,
                'SC_YearLen' => $row->SC_YearLen,
                'year' => $row->year,
                'passover_start' => $row->passover_start,
                'unleavened_bread_start' => $row->unleavened_bread_start,
                'unleavened_bread_end' => $row->unleavened_bread_end,
                'pentecost_start' => $row->pentecost_start,
                'feast_of_trumpets_start' => $row->feast_of_trumpets_start,
                'day_of_atonement_start' => $row->day_of_atonement_start,
                'feast_of_tabernacles_start' => $row->feast_of_tabernacles_start,
                'feast_of_tabernacles_end' => $row->feast_of_tabernacles_end,
                'last_great_day_start' => $row->last_great_day_start,
    
            ];
        }

        
        return $res;
    }

    private function setData($node, $dataArray) {
        
        $data = $dataArray;
        $node->set('field_am_year', $data['AM']);
        $node->set('field_gc_era', $data['GC_Era']);
        // Set other fields as needed
        $node->save();
        return $node->id;
    }

    public function createCalendarNode($era, $year) {

        $nid = 0;
        $calData = $this->getCalendarData($era, $year)[0];
        // check if this node already exists
        $nodeStorage = $this->entityTypeManager->getStorage('node');
        $query = $nodeStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', 'calendar')
            ->condition('field_am_year', $calData['AM'])
            ->execute();
        if (!empty($query)) {
            // update existing node
            
            $updateNode = $nodeStorage->load(reset($query));
            $nid = $updateNode->id();
        } else {
            // create a new node
            $node = $nodeStorage->create([
                'type' => 'calendar',
                'title' => "AM Year: " . $calData['AM'] . " - " . $era . " " . $year,
                'field_gregorianyear' => $year,
                'field_gc_era' => $era,
            ]);
            $node->save();
            $nid = $node->id();

        }

        

        $nodeUpdate = $nodeStorage->load($nid);
        $updateId = $this->setData($nodeUpdate, $calData);
        

        $thisData = [
            'gc_era' => $era,
            'gc_year' => $year,
            'calData' => $calData,
            "html" => "load file?",
            "nid" => $nid,
        ];
        // return json response
        return new JsonResponse([
            'message' => 'Creating calendar node',
            'data' => $thisData,
            'status' => 200,
        ]);
    }

    /**
     * Returns a renderable array for the calendar list.
     */
    public function calendarList() {

        // loop through files in the /Content directory in the web root
        $markup = '';
        $markup .= '<h2>Files in Content Directory</h2><div class="file-list">';
        $directory = DRUPAL_ROOT . '/Content/download/generator/output';
        $files = scandir($directory);
        $file_list = [];
        $counter = 0;

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($directory . '/' . $file)) {
                if($counter<10){
                    $file_list[] = $file;
                }
                $counter++;
            }

        }

        // display links

        foreach($file_list as  $file) {
            // get the first two characters of the file name
            $prefixEra = substr($file, 0, 2);
            // get the rest of the file name
            // remove extension
            $file = pathinfo($file, PATHINFO_FILENAME);
            $year = substr($file, 2);
            if($year === '0') {
                continue;
            }

            
            $markup .= "<a href='/api/calendarcreate/create/$prefixEra/$year' class='btn btn-primary'>" . $prefixEra . " - " . $year . "</a>";
               
        }
        $markup .= '</div>';
       
        return [
            '#type' => 'markup',
            '#markup' => $markup,
            '#title' => $this->t('Files in Content Directory: @directory', ['@directory' => $directory]),
            '#attached' => [
                'library' => [
                    'bcp_create_calendar/create_calendar',
                ],
            ],
            
        ];
        
    }

}
