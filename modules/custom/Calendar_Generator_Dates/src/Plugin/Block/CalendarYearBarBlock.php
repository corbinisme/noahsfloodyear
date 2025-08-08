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

/**
 * Provides a 'Year Bar' Block.
 *
 * @Block(
 *   id = "calendar_year_bar",
 *   admin_label = @Translation("Calendar Year Bar Block"),
 *   category = @Translation("Calendar Year Bar "),
 * )
 */


class CalendarYearBarBlock extends BlockBase implements ContainerFactoryPluginInterface {


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


  private function getData(){

	$node = $this->routeMatch->getParameter('node');
	$node_id = "";
	$shorten = "Load here";

	 if ($node instanceof NodeInterface) {
      // You have the node object!
		$node_title = $node->label();
		$node_id = $node->id();

    $amyear = $node->get("field_am_year")->value;
		$adbc = $node->get("field_gc_era")->value;
		$yearVal = $node->get("field_gregorianyear")->value;
		$adbc = strtoupper($adbc);	
		

		$markup = '<div id="yearBar">
        <div class="d-flex yearBarInner">
            <a class="yearAction" href="#" data-dir="-1">←</a> 
            <h2>AM ' . $amyear . ' - ' . $yearVal . ' ' . $adbc . '</h2> 
            <a class="yearAction" href="#" data-dir="1">→</a>
        </div>
    </div>';

		
		
		return $markup;
	} else {
		$markup = "No information available.";
		return $markup;		
	}


  }

  public function build() {


	$markup = $this->getData();
	// reset DB connection

    return [
    	'#markup' => $this->t($markup),
  

    ];
  }

}
