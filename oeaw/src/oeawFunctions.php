<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawStorage;


class oeawFunctions {
   
    public static $fedoraUrl = 'http://fedora:8080/rest/';
    public static $fedoraUrlwHttp = 'fedora:8080/rest/';
    public static $fedoraDownloadUrl = 'http://fedora.localhost/rest/';

    /*
     * We need to check the URL
     * case 1: if it is starting with http then we creating a LINK
     * case 2: if it is starting with http://fedora:8080/rest/, then we need
     * to change it because users cant reach http://fedora:8080/rest/, only the 
     * http://fedora.localhost/rest/
     */
    
    public function generateUrl($value, $dl = null)
    {
        if(substr($value, 0,4) == 'http')
        {                
            if(substr($value, 0,24) == self::$fedoraUrl )
            {                
                $value = str_replace(self::$fedoraUrl, self::$fedoraDownloadUrl, $value);
                if($dl == true)
                {
                    return $value;
                }
                $value = t('<a href="'.$value.'">'.$value.'</a>');            
                return $value;
            }            
            /*elseif(substr($value, 0,17) == self::$fedoraUrlwHttp)
            {
                $value = str_replace(self::$fedoraUrlwHttp, self::$fedoraDownloadUrl, $value);
                $value = t('<a href="'.$value.'">'.$value.'</a>');            
                return $value;
            }*/
            $value = t('<a href="'.$value.'">'.$value.'</a>');            
            return $value;
        }
        
        return false;
    }
    
    
    /* 
     * 
     * way = code/encode 
     * details button url generating to pass the uri value to the next page
    */
    public function createDetailsUrl($data, $way = 'code')
    {                
        if($way == 'code')
        {
            $data = str_replace(self::$fedoraUrl, '', $data);
            $str = str_replace('/', '_', $data);            
        }
        
        if($way == 'encode')
        {            
            $data = str_replace('_', '/', $data);                        
            $str = self::$fedoraUrl.$data;            
        }        
        return $str;        
    }
    
    
    /*  
     * generating the table to show the results
     */
    public function generateTable($data, $text = null, $goBackUrl = '/oeaw_menu')
    {        
        $fields = $data->getFields();
        $i =0;
        $finalArray = array();
        $filename = false;
        $describedby = false;
        $descVal = "";
        
        //creating the header and the rows part
        foreach ($data as $r)
        {               
            // header elements foreach
            foreach($fields as $h)        
            {                        
                $r = (array)$r;
                $header[$h] = t($h);                                                                       
                $val = $r[$h];                                            
                $value = $val->dumpValue('string'); 
               
                /* get the length for the file checking */
                $length = strlen($value);
                if(substr($value, $length-8, 8) == 'filename'){$filename = true; }
                
                if(substr($value, $length-11, 11) == 'describedby'){ $describedby = true; $descVal = $value;  }
                
                if($h == 'uri') { $details = \Drupal\oeaw\oeawFunctions::createDetailsUrl($value, 'code'); }
                
                if(\Drupal\oeaw\oeawFunctions::generateUrl($value) !=  false)
                {
                    $value = \Drupal\oeaw\oeawFunctions::generateUrl($value);
                }             
                
                $finalArray[$i][] = $value;                                
            }
            
            if(!empty($details))
            {
                $finalArray[$i][] = t('<a href="/oeaw_detail/'.$details.'">Details</a>');                
            }
            $i++;
        } 
               
        if(($filename == true) && ($describedby == true))
        {            
            $current_uri = \Drupal::request()->getRequestUri();
            $current_uri = str_replace('oeaw_detail/', '', $current_uri);
            
            $downloadURL = \Drupal\oeaw\oeawFunctions::createDetailsUrl($current_uri, 'encode');
            $downloadURL = \Drupal\oeaw\oeawFunctions::generateUrl($downloadURL, true);
                        
            $downText = array(
                '#type' => 'markup',
                '#markup' => '<div></br><a href="'.$downloadURL.'" target="_blank">Download Content</a></br></br></div>'
                );
        }
      
        $header['details'] = t('details');        
        $rows = $finalArray;
        
        $table = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
            'id' => 'oeaw-table',
            ),
        );        
        
        if(empty($rows))
        {
            return false;
        }
        
        if(!empty($text))
        {
            $hdrTxt = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="tableHeaderTxt">'.$text.'</div>',          
            );            
        }
                
        $ftrTxt = array(
                '#type' => 'markup',
                '#markup' => '<a href="'.$goBackUrl.'" class="tableBackTxt">Go Back</a>',          
        );            
        
        return array(
            $downText,
            $hdrTxt,
            $table,            
            $ftrTxt,
           
        );
    }
    
}    
    
