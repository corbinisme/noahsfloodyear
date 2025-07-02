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


  private function getData(){

	$node = $this->routeMatch->getParameter('node');
	$node_id = "";
	$shorten = "Load here";

	 if ($node instanceof NodeInterface) {
      // You have the node object!
		$node_title = $node->label();
		$node_id = $node->id();

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
		
		
		
		$markup = "<div class='path-calendar'><div class='loadhtmlwrapper calendarWrapper'><div class='calendarMarkup'>" . $shorten . "</div></div></div>";
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
