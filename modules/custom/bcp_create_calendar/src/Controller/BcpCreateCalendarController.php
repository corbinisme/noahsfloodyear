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
    
            ];
        }
        return $res;
    }

    public function createCalendarNode($era, $year) {

        $calData = $this->getCalendarData($era, $year);
        $thisData = [
            'gc_era' => $era,
            'gc_year' => $year,
            'calData' => $calData,
            "html" => "load file?"
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
