<?php 
namespace Drupal\biblicalcalendarproof_cats\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;


/**
 * Provides a 'biblicalcalendarproof_cats' block.
 *
 * @Block(
 *  id = "biblicalcalendarproof_cats_block",
 *  label = "Biblical Calendar Proof Categories",
 *  admin_label = @Translation("Biblical Calendar Proof Categories"),
 * )
 */
class BiblicalcalendarproofCatsBlock extends BlockBase  {


    private function getTax(){
            $query = \Drupal::entityQuery('taxonomy_term');
            $query->condition('vid', "topics");
            $query->sort("weight");
            //$query->condition('parent', $tid);
            $tids = $query->execute();
            $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);

            return $terms;
    }
    
    private function getContent($nids, $termIds, $heirarchyTerms, $tid){
        $ret = [];
        //dpm($termIds);

        //\Drupal::entityManager()->getStorage('node')->resetCache($nids);
        $nodes = Node::loadMultiple($nids);
        foreach($nodes as $node){
    
            // if video, get that
            
            $thisNode = array(
                "title"=>$node->get("title")->value,
                "body"=>$node->get("body")->summary,
                "url"=>$node->toUrl()
            );

            if($node->hasField("field_video_source")){
                $thisNode['field_video_source'] = $node->get("field_video_source")->value;
            }


            $ret[] = $thisNode;
        }


        return $ret;
    }
   
    public function getCacheMaxAge() {
        return 0;
    }

    public function build() {
        \Drupal::service('page_cache_kill_switch')->trigger();
        $thisURL = $_SERVER['REQUEST_URI'];

        $master = [];
        $data = [];
        $view = [];
        $termIds = [];
        $heirarchyTerms = [];

        $terms = $this->getTax();
        //$data = $terms;
        
        foreach($terms as $term){
            //$markup .= $term->name[0]->value . "| " . $term->tid[0]->value . "<br />";
            //$markup .= $term->parent[0]->target_id . "<hr />";
            
            $testtid = $term->tid[0]->value;
            $termIds[$testtid] = $term->name[0]->value;

            // what about children of the main?
            $master[] = array("name"=>$term->name[0]->value, "termid" => $term->tid[0]->value, "desc" => $term->description);
            $view[] = array("name"=>$term->name[0]->value, "termid" => $testtid);
            $heirarchyTerms[] = $term->tid[0]->value;

            $dups = [];
           
            foreach($master as $entry) {

                
                $thistid = $entry["termid"];
                $thisname = $entry["name"];
                $thisDesc = $entry["desc"];

                //$data[] = $thisname;
                
                if(!in_array($thistid, $dups)){
                    $dups[] = $thistid;
                
                    // new query of bios with this tag
                    if($thistid!=null){
                        $query = \Drupal::entityQuery('node');
                        //$query->condition('type', "article");
                        $query->condition('field_topic.entity.tid',$thistid, "IN");
                        $tids = $query->execute();

                        // dedup?
                        $bioData = $this->getContent($tids, $termIds, $heirarchyTerms, $thistid);
                        $dataArr = array(
                            "tid" => $thistid,
                            "desc"=> $thisDesc,
                            "entities" => $bioData
                        );
                        
                        $data[$thisname] = $dataArr;
                    }
                
                }
                
            }
            
            //$data[] = $master;
        }

        //dpm($data);
        
        
        return [
            '#theme' => 'biblicalcalendarproof_cats_block',
            '#data' => $data,
            '#attached' => [
                'library' => [
                    'biblicalcalendarproof_cats/biblicalcalendarproofcats',
                ],
            ]

        ];
    }
}