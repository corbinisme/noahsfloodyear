<?php 

namespace Drupal\calendar_generator_nav\Controller;

use Drupal\Core\Controller\ControllerBase;

class CalendarNavController extends ControllerBase {

    public function calendar_nav($era_type, $year) {

    	
        return [
            '#type' => 'markup',
            '#markup' => $this->t('<h1>@era YEAR @name</h1>', [
		        '@name' => $year,
		        '@era' => $era_type
		      ]),
        ];
    }

}