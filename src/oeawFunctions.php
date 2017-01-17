<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawStorage;
use Drupal\oeaw\connData;
use Drupal\Component\Render\MarkupInterface;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use zozlak\util\Config;
use EasyRdf_Graph;
use EasyRdf_Resource;
use acdhOeaw\util\EasyRdfUtil;
 
class oeawFunctions {
       
    
    public function makeGraph($uri){
     
        // setup fedora
        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $fedora = new Fedora($config);
        //create and load the data to the graph
        $res = $fedora->getResourceByUri($uri);
        $meta = $res->getMetadata();
        $graph = $meta->getGraph();
        
        return $graph;
    }
    
    
    /* 
     *
     * Simple function to create array from sparql object, to we can pass data to the datatables     
     *
     * @param EasyRdf_Sparql_Result object $result  
     * @param array $fields
     *
     * @return array
    */
    
    public function createSparqlResult($result, array $fields){
        
        if(empty($result) && empty($fields)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }

        $resCount = count($result)-1;
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
     *
     * @param string $string : url     
     *
     * @return string
    */     
    public function createPrefixesFromString(string $string){
        
        if (empty($string)) {
           return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $endValue = explode('/', $string);
        $endValue = end($endValue);
        if (strpos($endValue, '#') !== false) {
            $endValue = explode('#', $string);
            $endValue = end($endValue);
        }
        
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
     *
     * create prefix from string based on the connData.php prefixes
     *
     * @param string $string : url     
     *
     * @return string
    */     
    public function createPrefixesFromArray(array $array, array $header){
        
        if (empty($array) && empty($header)) {
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        $result = array();
        
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
     * 
     * @return string
    */
    
    public static function createDetailsUrl($data, $way = 'encode', $dl = null) {
      
        if ($way == 'encode') {
            $data = str_replace(\Drupal\oeaw\connData::fedoraUrl(), '', $data);
            $data = base64_encode($data);
            $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        }

        if ($way == 'decode') {
            $data = str_replace('oeaw_detail/', '', $data);
            $data = str_replace('/', '', $data);
            $data = str_replace(array('-', '_'), array('+', '/'), $data);
            $mod4 = strlen($data) % 4;
            
            if ($mod4) {
                $data .= substr('====', $mod4);
            }
            
            $data = base64_decode($data);
            
            if ($dl == null) {
                $data = \Drupal\oeaw\connData::fedoraUrl() . $data;
            } else {
                $data = \Drupal\oeaw\connData::fedoraDownloadUrl() . $data;
            }
        }
        return $data;
    }
    
    /* 
     *
     * create detail or edit url from array
     *
     * @param string $string : the url 
     *
     * @return void
    */
        
    public function isURL(string $string){
        
        if (filter_var($string, FILTER_VALIDATE_URL)) { 
            
            if (strpos($string, \Drupal\oeaw\connData::fedoraUrl()) !== false) {
                $res = \Drupal\oeaw\oeawFunctions::createDetailsUrl($string, 'encode');                
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
     */

    public function generateUrl($value, $dl = null) {
        
        if(empty($value)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
        if (substr($value, 0, 4) == 'http') {
            if (substr($value, 0, 24) == \Drupal\oeaw\connData::fedoraUrl()) {
                $value = str_replace(\Drupal\oeaw\connData::fedoraUrl(), \Drupal\oeaw\connData::fedoraDownloadUrl(), $value);
                if ($dl == true) {
                    return $value;
                }
                $value = t('<a href="' . $value . '">' . $value . '</a>');
                return $value;
            }
            $value = t('<a href="' . $value . '">' . $value . '</a>');
            return $value;
        }

        return false;
    }
    
    public function createUriFromPrefix(string $prefix){
        
        if(empty($prefix)){
            return drupal_set_message(t('Error in function: '.__FUNCTION__), 'error');
        }
        
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
