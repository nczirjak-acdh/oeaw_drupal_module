<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Render\MarkupInterface;

use Drupal\oeaw\oeawStorage;
use Drupal\oeaw\connData;

use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\EasyRdfUtil;
use zozlak\util\Config;
use EasyRdf\Graph;
use EasyRdf\Resource;


 
class oeawFunctions {
    
    private $config;
        
    public function __construct(){        
        $this->config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');        
    }
    
    /*
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
    
    /*
     * 
     * Creates the EasyRdf_Resource by uri
     *      
     * @param string $uri  - resource uri
     * @return EasyRdf_Resource
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
    
    /*
     * Creates the EasyRdf_Graph by uri
     * 
     * @param string $uri - resource uri
     * @return EasyRdf_Graph
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
    
    public static function getFieldNewTitle(array $formElements, string $mode = 'edit'): AjaxResponse {
        
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
        $label = "";
        
        foreach($result as $key => $value){
            
            $resNL = $fedora->getResourcesByProperty("http://purl.org/dc/terms/identifier", (string)$value);            
       
            foreach($resNL as $nl){
                if(!empty($nl->getMetadata()->label())){
                    $label = (string)utf8_decode($nl->getMetadata()->label());                    
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
    
    /*
     * This functions checks the given identifier label/name/title
     * This used for the editForm title generating
     * 
     * @param string $value
     * @return string 
     */
    
    public function getLabelByIdentifier(string $value): string{
        
        $res = $this->oeawStorage->getDataByProp('dct:identifier', (string)$value);
        
        $label = "";
        //the possible titles
        if(!empty($res)){
            for ($i = 0; $i < count($res); $i++) {
                if(!empty($res[$i]["title"])){
                   $label = $res[$i]["title"];
                }else if(!empty($res[$i]["label"])){
                   $label = $res[$i]["label"];
                }else if(!empty($res[$i]["name"])){
                   $label = $res[$i]["name"];
                }else{
                    $label = " ";
                }   
            }
        }

        return $label;
        
    }
    
    /* 
     *
     * Create array from  EasyRdf_Sparql_Result object
     *
     * @param EasyRdf_Sparql_Result object $result  
     * @param array $fields
     *
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
                    
                    if($objClass == "EasyRdf_Resource"){
                        
                        $val = $result[$x]->$f;
                        $val = $val->getUri();
                        $res[$x][$f] = $val;
                        
                    }else if($objClass == "EasyRdf_Literal"){
                                                
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
    
    
    /* 
     *
     * create prefix from string based on the connData.php prefixes     
     * @param string $string : url     
     * @return string
    */     
    public static function createPrefixesFromString(string $string): string{
        
        if (empty($string)) {
           return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
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
        
        
        if(!empty(\Drupal\oeaw\connData::$prefixesToChange[$newString])){
            
            $result = \Drupal\oeaw\connData::$prefixesToChange[$newString].':'.$endValue;
        }
        else {
            $result = $string;
        }
         
        return $result;
        
    }
    
    /*      
     * create prefix from array based on the connData.php prefixes     
     * 
     * @param array $array
     * @param array $header
     * 
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
                 
                if(!empty(\Drupal\oeaw\connData::$prefixesToChange[$newString])){            
                    $result[$key][] = \Drupal\oeaw\connData::$prefixesToChange[$newString].':'.$endValue;
                }else {
                    $result[$key][] = $value;
                }
            }
        }
       
        return $result;
        
    }
    
    
    /* 
     * details button url generating to pass the uri value to the next page     
     *
     * @param string $data :  this is the url
     * @param string $way : encode/decode     
     * 
     * @return string
    */
    
    public static function createDetailsUrl(string $data, string $way = 'encode', string$dl = null): string {
      
        $returnData = "";
        
        if ($way == 'encode') {
            $data = str_replace(\Drupal\oeaw\connData::fedoraUrl(), '', $data);
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
            
            if ($dl == null) {
                $returnData = \Drupal\oeaw\connData::fedoraUrl() . $data;
            } else {
                $returnData = \Drupal\oeaw\connData::fedoraDownloadUrl() . $data;
            }
        }
        return $returnData;
    }
    
   
    /*
     *           
     * @param string $string
     * 
     * @return string
     * 
     */    
    public function isURL(string $string): string{
        
        $res = "";
        
        if (filter_var($string, FILTER_VALIDATE_URL)) { 
            
            if (strpos($string, \Drupal\oeaw\connData::fedoraUrl()) !== false) {
                $res = $this->createDetailsUrl($string, 'encode');                
            }
            return $res;
        } else {
            return false;
        }        
    }

     /*
     * We need to check the URL
     * case 1: if it is starting with http then we creating a LINK
     * case 2: if it is starting with http://fedora:8080/rest/, then we need
     * to change it because users cant reach http://fedora:8080/rest/, only the 
     * http://fedora.localhost/rest/
      * 
      * 
      * @param string $value
      * @param string $dl
      * 
      * @return string
      * 
      * 
     */

    public static function generateUrl(string $value, string $dl = null): string {
        
        if(empty($value)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        $result = "";
        
        if (substr($value, 0, 4) == 'http') {
            if (substr($value, 0, 24) == \Drupal\oeaw\connData::fedoraUrl()) {
                $value = str_replace(\Drupal\oeaw\connData::fedoraUrl(), \Drupal\oeaw\connData::fedoraDownloadUrl(), $value);
                if ($dl == true) {
                    $result = $value;
                    return $result;
                }
                $result = t('<a href="' . $value . '">' . $value . '</a>');
                return $result;
            }
            $result = t('<a href="' . $value . '">' . $value . '</a>');
            return $result;
        }

        return false;
    }
    
    /*
     * 
     * Creates a property uri based on the prefix
     * 
     * @param string $prefix
     *      
     * @return string     
     * 
     */
    
    public function createUriFromPrefix(string $prefix): string{
        
        if(empty($prefix)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $res = "";
        $newValue = array();
        $newPrefix = array();
        $prefixes = array();
        
        $newValue = explode(':', $prefix);        
        $newPrefix = $newValue[0];
        $newValue =  $newValue[1];
        
        $prefixes = \Drupal\oeaw\connData::$prefixesToChange;
        
        foreach ($prefixes as $key => $value){            
            if($value == $newPrefix){
                $res = $key.$newValue;
            }
        }
        
        return $res;
    }

}
