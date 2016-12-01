<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawStorage;
use Drupal\oeaw\connData;

 
class oeawFunctions {
       
    
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
            return false;
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
        
        if (empty($array)) {
            return false;
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
    
    
    /* THIS WILL BE DELETED */    
    public static function generateTable(array $data, string $text = null, string $edit = null) {

        /* get the fields from the sparql query */
        $fields = array_keys($data[0]);
        $i = 0;
        
        $finalArray = array();
        $filename = false;
        
        // it is a special prefix
        $describedby = false;
        $descVal = "";
        
        //creating the header and the rows part
        foreach ($data as $r) {
            
            // header elements foreach
            foreach ($fields as $h) {                
                $header[$h] = t($h);
                $val = $r[$h];
                
                $value = (string)$val;
                
                if (substr($value,0,7) == 'http://')
                {            
                    $asd = \Drupal\oeaw\oeawFunctions::createPrefixesFromString($val);
                }
                    
                $length = strlen($value);

                if (substr($value, $length - 8, 8) == 'filename') {
                    $filename = true;
                }
                if ($h == 'uri') {
                    $ResURL = $value;
                    $details = \Drupal\oeaw\oeawFunctions::createDetailsUrl($value, 'encode');
                    
                }

                if (\Drupal\oeaw\oeawFunctions::generateUrl($value) != false) {
                    $value = \Drupal\oeaw\oeawFunctions::generateUrl($value);
                }
                
                $finalArray[$i][] = $value;
            }

            if (!empty($details)) {
                $finalArray[$i][] = t('<a href="/oeaw_detail/' . $details . '">Details</a>');
            } else {
                $finalArray[$i][] = t('NO Details');
            }
            
            if($edit != null){
                $finalArray[$i][] = t('<a href="/oeaw_editing/' . $details . '">edit</a>');
                $finalArray[$i][] = t('<a href="/oeaw_delete/' . $details . '">delete</a>');
            }

            $i++;
        }

        if (($filename == true) && ($describedby == true)) {
            $current_uri = \Drupal::request()->getRequestUri();
            $downloadURL = \Drupal\oeaw\oeawFunctions::createDetailsUrl($current_uri, 'decode', true);

            $value = t('<a href="' . $downloadURL . '">Download content</a>');

            $downText = array(
                '#type' => 'markup',
                '#markup' => $value
            );
        }

        $header['details'] = t('details');
        if($edit != null){
            $header['edit'] = t('edit');
            $header['delete'] = t('delete');
        }
        
        $rows = $finalArray;

        $table = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'id' => 'oeaw-table',
            ),
        );

        $current_uri = \Drupal::request()->getRequestUri();
        $actualMenu = explode('/', $current_uri);
        
        // we are checking the actual menupoint, if it is the details then we are showing the title and download url 
        if ($actualMenu[1] == 'oeaw_detail') {

            if (empty($ResURL)) {

                $current_uri = \Drupal::request()->getRequestUri();
                                
                /* url for the resource */
                $ResURL = \Drupal\oeaw\oeawFunctions::createDetailsUrl($current_uri, 'decode', true);
                
                /* fedora url for the sparql  */
                $ResURLFedora = \Drupal\oeaw\oeawFunctions::createDetailsUrl($current_uri, 'decode');
                                
                $titleProperty = \Drupal\oeaw\oeawStorage::getDefPropByURI($ResURLFedora, 'dc:title');
                                
                $title = $titleProperty[0]["value"];
                
                $hdrTxt = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="tableHeaderTxt"><h2>' . $title . '</h2><br>' . $ResURL . ' <br><br></div>',
                );
            }
        }

        $ftrTxt = array(
            '#type' => 'markup',
            '#markup' => '<div class="tableFooterTxt" ><a href="/oeaw_menu" class="tableBackTxt">Go Back to the menu</a></br></br></div>',
        );

        return array(
            $hdrTxt,
            $downText,
            $table,
            $ftrTxt,
        );
    }

}
