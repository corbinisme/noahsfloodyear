<?php
namespace Drupal\bibleCalImport\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use \Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

class BibleCalImportController extends ControllerBase {


    private function uploadPDF($url){

        $file_data = file_get_contents($url);
        $file_namefull = explode("/",$url);
        $file_name = $file_namefull[count($file_namefull)-1];
        $file_name = str_replace('%20', '_', $file_name);
        
        $file = file_save_data($file_data, 'public://' . $file_name . '.pdf', FileSystemInterface::EXISTS_REPLACE);
        

        $media = Media::create([
            'bundle'=> 'document',
            'uid' => \Drupal::currentUser()->id(),
            'field_media_document' => [
                'target_id' => $file->id(),
            ],
        ]);

        $media->setName($file_name)
        ->setPublished(TRUE)
        ->save();
        $mediaId = $media->get('mid')->value;

        return $mediaId;

    }
    public function import(){

        
        $ret =  '<h3>Let us import some articles!</h3>';
        if(isset($_REQUEST['importSubmit'])){
            
        
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            


            if($_REQUEST['feed']=="https://www.biblicalcalendarproof.net/articleExport"){
            $xml = simplexml_load_file("https://www.biblicalcalendarproof.net/articleExport");
        
            foreach($xml as $node){

                $title = $node->title;
                $summary = "";
                $body = "";
                if($node->body!=null){
                    $body = $node->body;
                }
                if($node->summary!=null){
                    $summary = $node->summary;
                }
                $pdf = "";
                if($node->pdf!=null){
                    $pdf = $node->pdf; 
                }

                $nodes = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties([
                    'title' => $title,
                    ]);

                if(count($nodes)>0){
                    // it already exists

                    $keys = array_keys($nodes);
                    $nodearr = print_r($keys, true);

                    $ret.= "Node with the title '<strong><u><em>" . $title . "</em></u></strong>' already exists. ";
                    
                    // check if we should overwrite/update or just skip

                    $mediaId = "";
                    $filenameenter = "/media";

                    if(isset($_REQUEST["updateExisting"])){
                    
                        if($pdf!=""){

                            $mediaId = $this->uploadPDF($pdf);

                        }

                        $body = "";
                        

                        $nodeload = Node::load($keys[0]);
                        $nodeload->body = [
                            'summary' => $summary,
                            'value' => $body,
                            'format' => 'full_html'
                        ];
                        $nodeload->field_pdf->target_id = $mediaId;
                        $nodeload->field_pdf_reference = $filenameenter;
                        /*
                        $loaded_entity->get('body')->setValue(array(
                            'summary' => $summary,
                            'value' => $body,
                            'format' => 'full_html',
                            ));
                        $loaded_entity->field_pdf->target_id = $mediaId;
                        $loaded_entity->get("field_pdf_reference")->setValue($pdf);
                        */

                        $ret.= "<h3>Updating</h3>";
                        //$ret.= "<pre>" . $loaded_obj . "</pre><br /><hr />";
                    } else {
                        $ret.= "<h3>Skipping</h3>";
                    }
                    $ret .= "<hr />";

                } else {

                    $node = Node::create(array(
                        'type' => 'article',
                        'title' => $title,
                        'langcode' => 'en',
                        //'uid' => $node->post_id,
                        'status' => 1,
                        'body' => array(
                            'value' => $body,
                            'summary' => $summary,
                            'format' => 'full_html',
                        ),
                        "field_pdf_reference" => $pdf
                    ));

                    if($pdf!=""){
                        $mediaId = $this->uploadPDF($pdf);
                        $node->field_pdf->target_id = $mediaId;
                    }
                    
                    
                    //$ret .= "title . " . $title . "<br />";
                    $node->save();

                    $nid = $node->id();

                    $ret .= "Node Created: " . $nid . "<br />";

                }
            }

            } else if($_REQUEST['feed'] == "https://redesign.biblicalcalendarproof.com/timelineRest"){
                $ret .= "Timeline<br />";

                $json = file_get_contents($_REQUEST['feed']);
                $read = json_decode($json, true);
               

                foreach($read as $entry){
                    $title = trim($entry['title'][0]['value']);
                    $body = "";
                    if(count($entry['body'])>0){
                        $body = trim($entry['body'][0]['processed']);
                    } 
                    $amyear = trim($entry['field_am_year'][0]['value']);

                    $gcera = "";
                    if(count($entry['field_gc_era'])>0){
                        $gcera = trim($entry['field_gc_era'][0]['value']);
                    }
                    $gcyear = null;
                    if(count($entry['field_gc_era'])>0){
                        $gcyear =trim($entry['field_gc_year'][0]['value']);
                    }
                    $related = null;
                    if(count($entry['field_related']) >0){
                        $related = $entry['field_related'][0]['value'];
                    }
                    //$ret .= "<pre>";
                    //$ret .= print_r($entry, true);
                    //$ret .="</pre>";

                    $lang = $entry['langcode'][0]['value'];
                    
                    $nodes = \Drupal::entityTypeManager()
                        ->getStorage('node')
                        ->loadByProperties([
                    'title' => $title,
                    ]);

                    if(count($nodes)>0){
                        $ret .= "skipping " . $title . "<hr />";
                    } else {
                        $ret .= "!adding! " . $title . "<br />";

                        
                        $node = Node::create(array(
                            'type' => 'timeline_entry',
                            'title' => $title,
                            'langcode' => $lang,
                            'field_am_year'=>$amyear,
                            'field_gc_era'=>$gcera,
                            'field_gc_year'=>$gcyear,
                            'field_relate'=>$related,
                            'status' => 1,
                            'body' => array(
                                'value' => $body,
                                'format' => 'full_html',
                            ),
                            
                        ));

                        $node->save();

                        $nid = $node->id();
    
                        $ret .= "Node Created: " . $nid . "<hr />";
                        
                    }

                    //$ret .= $title . " <br /> " . $amyear . " <br />";
                    //$ret .= $gcyear . " " .$gcera . " | " . $related;
                    //$ret .= "<textarea>" . $body . "</textarea><hr />";
                }

            } else {
                $ret .= "Other<br />";
            }
            $ret .= "<hr /><a href='/bibleCalImport/import'>Start Over</a>";
            

        } else {
             

            $sources = array(
                "Article"=>"https://www.biblicalcalendarproof.net/articleExport",
                "Timeline Entry"=>"https://redesign.biblicalcalendarproof.com/timelineRest",
                "Chart"=>""
            );

            $ret .="<br /><form>
            <label>Type <select class='form-control' name='feed'>";
            
            foreach($sources as $name=>$url){
                $isDisabled = "";
                if($url==""){
                    $isDisabled = " disabled";
                }

                $ret .="<option value='" . $url . "'  " . $isDisabled . ">" . $name . "</option>";
            }
            $ret .="</select></label><br />
            <label><input type=\"checkbox\" value=\"yes\" name=\"updateExisting\" /> Update existing records?</label><br />
                <br /><input type=\"submit\" class=\"btn btn-default\" name=\"importSubmit\" value=\"Run Import\" />
            </form>";
            
        }
        
       
        return [
            '#markup' => t($ret),
        ];
    }
}
