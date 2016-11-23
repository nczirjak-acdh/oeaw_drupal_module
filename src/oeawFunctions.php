<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawStorage;
use Drupal\oeaw\connData;

 
class oeawFunctions {
   
    
    /*
     * Simple function to create array from sparql object
     *      
     * $result: EasyRdf_Sparql_Result 
     * $fields: Array of the sparql query fields
     */
    
    public function createSparqlResult($result, array $fields){
        
        $resCount = count($result) -1;
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
            }
        }
       
        return $res;        
    }
    
    
     /*
     * generating the table to showing the results
     * 
     * $data = array with result of the sparql query
     * $text = 
     * $esit = 
     *  
     */

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
    
    
     /*
     * create prefix from string based on the connData.php prefixes
     * 
     * $string 
     * $text = 
     * $esit = 
     *  
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
     * Creates an array from the $prefixes obj
     * and from the propertys from fedora      
    */
    public function createPrefixesFromObject($propertys) {
        
        if (empty($propertys)) {
            return false;
        }

        $fields = $propertys->getFields();
        $propArr = array();
        /* the Object always has two important property. uri -> fedora uri
         * value which is the name/title of the uri */
        $i = 0;
        foreach ($propertys as $p) {
            foreach ($fields as $f) {
                $p = (array) $p;
                $val = (array)$p[$f];
                
                if(!empty($val["\0*\0" . "uri"])) {
                    $uri = $val["\0*\0" . "uri"];
                    /* get the property name from the end of the uri */
                    $parts = explode('/', $uri);
                    $value = end($parts);
                    
                    if (strpos($uri, '#')) {
                        $arr = explode("#", $uri, 2);
                        // the repo uri. F.e.: http://fedora.info/definitions/v4/repository
                        $uri = $arr[0];
                        $value = end($arr);
                    }else {
                        $uri = str_replace($value, '', $uri);
                    }
                }
                /* only one variable from the sparql query */
                if(!empty($val["\0*\0" . "value"])) {
                    $value = $val["\0*\0" . "value"]; 
                }
                
                if(!empty($uri) && !empty($value)) { 
                    $propArr[$i] = array($uri => $value);
                } else {
                    $propArr[$i] = array($uri => $uri);
                }
            }
            $i++;
        }
        /* change the prefixes */
        foreach($propArr as $p){
            /* check whoch property has no shortcut in our system */
            $diff = array_diff_key($p, self::$prefixesToChange);
            
            foreach($p as $key => $value){            
                if(!empty($diff)){
                    $newProp[$key.'/'.$value] = $key.'/'.$value;
                }else {
                    foreach(self::$prefixesToChange as $pkey => $pvalue){
                        if($pkey == $key){
                            $newProp[$pvalue . ':' . $value] = $pvalue . ':' . $value;
                        }
                    }
                }
            }
        }
        
        return $newProp;
     
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

    /*     
     * way = encode/decode 
     * details button url generating to pass the uri value to the next page
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

   

    
    
    
    public static function resUrl($data) {
        $data = str_replace(array("\r", "\n"), ' ', $data);
        $data = preg_replace('/^.*Location: */', '', $data);
        $data = preg_replace('/ .*$/', '', $data) . '/';
        return $data;
    }
    
    
    /*
     * 
     *  RUN CURL with Transactions
     *  
     */
    function runCurl($method, $url, $transaction = null, $contentType = null, $file = null ){
        
        $username = "admin";
        $password= "admin";
        
        /* 
         * because of the docker file alias and the login method 
         * now we need to use the docker file alias         
         */
        if($url == \Drupal\oeaw\connData::fedoraUrl())
        {
            $url = 'http://fedora/rest/';
        }   
        
        if($transaction != null){
            $url = $url.$transaction;
        }
        
        $h = curl_init();
        $opts = array(
            CURLOPT_USERPWD => "admin:admin",
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: ' . $contentType),
            CURLOPT_POSTFIELDS => $file
        );
        
        
        /*
        if(is_file($file) && $file != null){
            $opts[CURLOPT_POSTFIELDS] = null;
            $opts[CURLOPT_INFILE] = fopen($file, 'r');
        }
        
        */

        curl_setopt_array($h, $opts);
        $res = curl_exec($h);
        $code = curl_getinfo($h, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($h);
        
        if(substr($code, 0, 1) !== '2'){
            echo "error:";
            echo curl_errno($h);
            echo "__";
            echo curl_error($h);
        echo $url;
        echo "----|||";
        echo $code = curl_getinfo($h, CURLINFO_HTTP_CODE);
            

            die();
            //throw new Exception(sprintf("Request failed %d %s\n  %s\n", $code, curl_error($h), $res));
             return false;
        }
        return $res;
    }
    

    /* CURL Function to insert file and sparql */
    /*
     *  $file = the file content what we want to insert / the sparql file content
     *  $requestType = 'POST' / 'PATCH'
     *  $contentType = 'text/csv' / 'application/sparql-update' ....
     *  $url = fedora url / the url where we want to run the sparql query but with the fcr:metadata info
     */

    public static function createCurlRequest($file = null, $requestType, $contentType, $url) {
       
        $username = "admin";
        $password= "admin";
        
        /* 
         * because of the docker file alias and the login method 
         * now we need to use the docker file alias         
         */
        if($url == \Drupal\oeaw\connData::fedoraUrl())
        {
            $url = 'http://fedora/rest/';
        }        
         
        $r = curl_init();
        $opts = array(                        
            CURLOPT_USERPWD => "admin:admin",
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CUSTOMREQUEST => $requestType,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type:' . $contentType),
            CURLOPT_POSTFIELDS => $file,
            CURLOPT_RETURNTRANSFER => true            
        );
        
        /*if ($file == null) {
            $opts[CURLOPT_POSTFIELDS] = null;
        }*/
        
        curl_setopt_array($r, $opts);
        $data = curl_exec($r);
        $code = curl_getinfo($r, CURLINFO_HTTP_CODE);
        curl_close($r);
                
        if ($code >= 200 && $code < 300) {
            return array("data" => $data, "response" => $code);
        } else {
            return false;
        }
    }

    /*
     * 
     * CLASS checkings
     * 
     */

    /**
     * Fetches class definitions from the repository
     * 
     * Here and now hardcoded as there are not available in the repository
     * 
     * @return array
     */
    private function getClassDefs() {
        return array(
            'TEI' => array("metadata1", "metadata2", "metadata3"),
            'Collection' => array("metadata1", "metadata2"),
            'Other' => array("metadata1", "metadata4")
        );
    }

    /**
     * Returns array of classes matched by a given set of metadata
     * 
     * @param array $metadata metadata with property names as array keys and their values as array values
     * 
     * @return array
     */
    public static function getMatchingClasses($metadata) {

        /* Symphony replace the . to _  in input data names */
        $matching = array();
        $classes = \Drupal\oeaw\oeawFunctions::getClassDefs();

        foreach ($classes as $class => $properties) {

            $missing = array_diff($properties, array_keys($metadata));

            if (empty($missing)) {
                $matching[] = $class;
            }
        }

        return $matching;
    }
    
    public static function createFormattedString($string)
    {
        $str = explode('"', $string);
        $string = $str[1];
        
        return $string;
    }
    
    /* get the protected value from the object */
    function getProtectedValue($obj,$name) {
        
            $array = (array)$obj;
            $prefix = chr(0).'*'.chr(0);
            
        return $array[$prefix.$name];
    }

}
