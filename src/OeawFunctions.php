<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Render\MarkupInterface;

use Drupal\oeaw\OeawStorage;
use Drupal\oeaw\ConnData;

use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\EasyRdfUtil;
use zozlak\util\Config;
use EasyRdf\Graph;
use EasyRdf\Resource;


 
class OeawFunctions {
    
    private $config;
            
    public function __construct(){        
        $this->config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');     
    }
        
    /**
     * 
     * Creates the Fedora instance
     * 
     * @return Fedora
     */   
    public function initFedora(): Fedora{
        // setup fedora
        $fedora = array();        
        $fedora = new Fedora($this->config);
        
        return $fedora;
    }
        
    /**
     * 
     * Creates the EasyRdf_Resource by uri
     * 
     * @param string $uri
     * @return \EasyRdf\Resource
     */
    public function makeMetaData(string $uri): \EasyRdf\Resource{
        
        if(empty($uri)){
            return drupal_set_message(t('The uri is missing!'), 'error');
        }
        
        $fedora = array();
        $meta = array();
       // setup fedora        
        $fedora = new Fedora($this->config);
        $res = $fedora->getResourceByUri($uri);
        $meta = $res->getMetadata();
        return $meta;
    }
    
    /**
     * Creates the EasyRdf_Graph by uri
     * 
     * @param string $uri - resource uri
     * @return  \EasyRdf\Graph
     * 
     */
    public function makeGraph(string $uri): \EasyRdf\Graph{
     
        $graph = array();
        // setup fedora        
        $fedora = new Fedora($this->config);
        //create and load the data to the graph
        
        $res = $fedora->getResourceByUri($uri);        
        $meta = $res->getMetadata();
        
        $graph = $meta->getGraph();
        
        return $graph;
    }
    
    
     /**
     * Get the title by the property
     * This is a static method because the Edit/Add form will use it
     * over their callback method.
     *     
     * 
     * @param array $formElements -> the actual form input
     * @param string $mode -> edit/new form.
     * @return AjaxResponse
     * 
     */    
    public function getFieldNewTitle(array $formElements, string $mode = 'edit'): AjaxResponse {
        
        $ajax_response = array();
        $fedora = array();
        
        $cfg = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $fedora = new Fedora($cfg);
        
        if($mode == "edit"){
            //create the old values and the new values arrays with the user inputs
            foreach($formElements as $key => $value){
                if (strpos($key, ':oldValues') !== false) {
                    $newKey = str_replace(':oldValues', "", $key);
                    $oldValues[$newKey] = $value;
                }else {
                    $newValues[$key] = $value;
                }
            }
            //get the differences
            $result = array_diff_assoc($newValues, $oldValues);
            
        }else if($mode == "new"){
                                   
            foreach($formElements as $key => $value){
                if(strpos($key, ':prop') !== false) {
                    unset($formElements[$key]);
                }elseif (strpos($value, 'http') !== false) {
                    $result[$key] = $value;
                }
            }
        }
        
        $ajax_response = new AjaxResponse();
        
        if(empty($result)){
            return $ajax_response;
        }
       
        $color = 'green';
        
        $resNL = array();        
        
        foreach($result as $key => $value){
            
            $resNL = $fedora->getResourcesByProperty($this->config->get('fedoraIdProp'), (string)$value);
       
            foreach($resNL as $nl){
                if(!empty($nl->getMetadata()->label())){
                    //$label = (string)utf8_decode($nl->getMetadata()->label());
                    $label = htmlentities($nl->getMetadata()->label(), ENT_QUOTES, "UTF-8");
                }else {
                    $label = "";
                }
            }

            if(!empty($label)){
                $ajax_response->addCommand(new HtmlCommand('#edit-'.$key.'--description', "New Value: <a href='".(string)$value."' target='_blank'>".(string)$label."</a>"));
                $ajax_response->addCommand(new InvokeCommand('#edit-'.$key.'--description', 'css', array('color', $color)));
            }
        }
        // Return the AjaxResponse Object.
        return $ajax_response;        
    }
  
           
    /**
     * 
     * Create array from  EasyRdf_Sparql_Result object
     * 
     * @param \EasyRdf\Sparql\Result $result
     * @param array $fields
     * @return array
     */
    public function createSparqlResult(\EasyRdf\Sparql\Result $result, array $fields): array{
        
        if(empty($result) && empty($fields)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        $res = array();
        $resCount = count($result)-1;
        $objClass = array();
        $val = "";
        
        for ($x = 0; $x <= $resCount; $x++) {
        
            foreach($fields as $f){                
                
                if(!empty($result[$x]->$f)){
                    
                    $objClass = get_class($result[$x]->$f);
                    
                    if($objClass == "EasyRdf\Resource"){                        
                        $val = $result[$x]->$f;
                        $val = $val->getUri();
                        $res[$x][$f] = $val;                        
                    }else if($objClass == "EasyRdf\Literal"){                                                
                        $val = $result[$x]->$f;
                        $val = $val->__toString();
                        $res[$x][$f] = $val;                        
                    } else {
                        $res[$x][$f] = $result[$x]->$f->__toString();
                    } 
                }
                else{
                    $res[$x][$f] = "";
                }
            }
        }
        return $res;
    }
    
    /**
     * 
     * create prefix from string based on the connData.php prefixes     
     * 
     * @param string $string
     * @return string
     */
    public static function createPrefixesFromString(string $string): string{
        
        if (empty($string)) {
           return false;
        }
        
        $result = array();
        $endValue = array();
        
        $endValue = explode('/', $string);
        $endValue = end($endValue);
        
        if (strpos($endValue, '#') !== false) {
            $endValue = explode('#', $string);
            $endValue = end($endValue);
        }
        
        $newString = array();
        $newString = explode($endValue, $string);
        $newString = $newString[0];
                
        if(!empty(\Drupal\oeaw\ConnData::$prefixesToChange[$newString])){
            
            $result = \Drupal\oeaw\ConnData::$prefixesToChange[$newString].':'.$endValue;
        }
        else {
            $result = $string;
        }         
        return $result;        
    }
 
    /**
     * 
     * create prefix from array based on the connData.php prefixes     
     * 
     * @param array $array
     * @param array $header
     * @return array
     */
    public function createPrefixesFromArray(array $array, array $header): array{
        
        if (empty($array) && empty($header)) {
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $result = array();
        $endValue= array();
        $newString = array();        
        
        for ($index = 0; $index < count($header); $index++) {
            
            $key = $header[$index];
            foreach($array as $a){
                $value = $a[$key];
                $endValue = explode('/', $value);
                $endValue = end($endValue);
                
                if (strpos($endValue, '#') !== false) {
                    $endValue = explode('#', $value);
                    $endValue = end($endValue);
                }
                
                $newString = explode($endValue, $value);
                $newString = $newString[0];
                 
                if(!empty(\Drupal\oeaw\ConnData::$prefixesToChange[$newString])){            
                    $result[$key][] = \Drupal\oeaw\ConnData::$prefixesToChange[$newString].':'.$endValue;
                }else {
                    $result[$key][] = $value;
                }
            }
        }       
        return $result;        
    }
    
    /**
     * 
     * details button url generating to pass the uri value to the next page     
     * 
     * @param string $data
     * @param string $way
     * @param string $dl
     * @return string
     */
    public function createDetailsUrl(string $data, string $way = 'encode', string$dl = null): string {
      
        $returnData = "";
        
        if ($way == 'encode') {
            $data = str_replace($this->config->get('fedoraApiUrl').'/', '', $data);
            $data = base64_encode($data);
            $returnData = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        }

        if ($way == 'decode') {
            $data = str_replace('oeaw_detail/', '', $data);
            $data = str_replace('/', '', $data);
            $data = str_replace(array('-', '_'), array('+', '/'), $data);
            $mod4 = strlen($data) % 4;
            
            if ($mod4) { $data .= substr('====', $mod4); }
            
            $data = base64_decode($data);
                        
            $returnData = $this->config->get('fedoraApiUrl').'/' . $data;
            
        }
        return $returnData;
    }
    
    /**
     * 
     * create the data for the children resource in the detail view
     * 
     * @param array $data
     * @return array
     */
    public function createChildrenDetailTableData(array $data): array{
        
        if(empty($data)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $i = 0;
        $childResult = array();
        
        foreach($data as $r){
            $childResult[$i]['uri']= $r->getUri();                
            $childResult[$i]['title']= $r->getMetadata()->label();
                
            $imageThumbnail = $r->getMetadata()->get(EasyRdfUtil::fixPropName(\Drupal\oeaw\ConnData::$imageThumbnail));
            $imageRdfType = $r->getMetadata()->all(EasyRdfUtil::fixPropName(\Drupal\oeaw\ConnData::$rdfType));
                        
            //check the thumbnail
            if($imageThumbnail){
                $imgUri = $imageThumbnail->getUri();
                if(!empty($imgUri)){
                    $OeawStorage = new OeawStorage();
                    $childThumb = $OeawStorage->getImage($imgUri);
                    
                    if(count($childThumb) > 0){
                        $childResult[$i]['thumbnail'] = $childThumb[0];
                    }
                }
            }
                
            //if there is an rdf type with foaf image property, then the resource is an image
            if(!empty($imageRdfType)){
                foreach($imageRdfType as $rdfVal){
                    if($rdfVal->getUri() == \Drupal\oeaw\ConnData::$imageProperty){
                        $childResult[$i]['thumbnail'] = $r->getUri();
                    }
                }
            }
              
            $decUrlChild = $this->isURL($r->getUri(), "decode");

            $childResult[$i]['detail'] = "/oeaw_detail/".$decUrlChild;
            if($uid !== 0){
                $childResult[$i]['edit'] = "/oeaw_editing/".$decUrlChild;
            } 
            $i++;
        }
        return $childResult;
    }
    
    /**
     * 
     * create table data for the root resource in the detail view.
     * changes the uris to prefixes
     * 
     * @param string $uri
     * @return array
     */
    public function createDetailTableData(string $uri): array{
        
        if(empty($uri)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $results = array();
        $rootMeta =  $this->makeMetaData($uri);
        if(count($rootMeta) > 0){
            $i = 0;
            foreach($rootMeta->propertyUris($uri) as $v){
            
                foreach($rootMeta->all(EasyRdfUtil::fixPropName($v)) as $item){

                    // if there is a thumbnail
                    if($v == \Drupal\oeaw\ConnData::$imageThumbnail){                        
                        if($item){
                            $OeawStorage = new OeawStorage();                            
                            $imgData = $OeawStorage->getImage($item);
                            if(count($imgData) > 0){
                                $hasImage = $imgData[0];
                                $results[$i]["image"] = $imgData[0];
                            }
                        }
                    }

                    if($v == \Drupal\oeaw\ConnData::$rdfType){
                        if($item == \Drupal\oeaw\ConnData::$imageProperty){
                            $hasImage = $uri;
                            $results[$i]["image"] = $uri;
                        }
                    }

                    if(get_class($item) == "EasyRdf\Resource"){
                        
                        if($this->createPrefixesFromString($v) === false){
                            return drupal_set_message(t('Error in function: createPrefixesFromString'), 'error');
                        }                        
                        $results[$i]["property"] = $this->createPrefixesFromString($v);
                        $results[$i]["value"][] = $item->getUri();
                        if($item->getUri() == \Drupal\oeaw\ConnData::$fedoraBinary){ $hasBinary = $uri;}

                    }else if(get_class($item) == "EasyRdf\Literal"){
                        
                        if($this->createPrefixesFromString($v) === false){
                            return drupal_set_message(t('Error in function: createPrefixesFromString'), 'error');
                        }
                        $results[$i]["property"] = $this->createPrefixesFromString($v);
                        $results[$i]["value"] = $item->__toString();
                    }else {
                        if($this->createPrefixesFromString($v) === false){
                            return drupal_set_message(t('Error in function: createPrefixesFromString'), 'error');
                        }
                        $results[$i]["property"] = $this->createPrefixesFromString($v);
                        $results[$i]["value"] = $item;
                    }
                }
                $i++;
            } 
        }

        return $results;
    }
    
       
    /**
     * 
     * check that the string is URL
     * 
     * @param string $string
     * @return string
     */
    public function isURL(string $string): string{
        
        $res = "";
        
        if (filter_var($string, FILTER_VALIDATE_URL)) { 
            
            if (strpos($string, $this->config->get('fedoraApiUrl')) !== false) {
                $res = $this->createDetailsUrl($string, 'encode');                
            }
            return $res;
        } else {
            return false;
        }        
    }

    
        
    /**
     * 
     * Creates a property uri based on the prefix
     * 
     * @param string $prefix
     * @return string
     */
    public function createUriFromPrefix(string $prefix): string{
        
        if(empty($prefix)){
           return false;
        }
        
        $res = "";
        $newValue = array();
        $newPrefix = array();
        $prefixes = array();
        
        $newValue = explode(':', $prefix);        
        $newPrefix = $newValue[0];
        $newValue =  $newValue[1];
        
        $prefixes = \Drupal\oeaw\ConnData::$prefixesToChange;
        
        foreach ($prefixes as $key => $value){            
            if($value == $newPrefix){
                $res = $key.$newValue;
            }
        }
        
        return $res;
    }
    
}