<?php

namespace Drupal\oeaw;

class oeawStorage {
    
    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#>';
    public static $sparqlEndpoint = 'http://blazegraph:9999/blazegraph/sparql';
    public static $fedoraUrl = 'http://fedora:8080/rest/';
    

    
    /* 
     * 
     * way = code/encode 
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
        
        //creating the header and the rows part
        foreach ($data as $r)
        {               
            // header elements foreach
            foreach($fields as $h)        
            {                        
                $r = (array)$r;
                $header[$h] = t($h);                                                       
                $r4 = $r[$h];                            
                $res4 = $r4->dumpValue('string');                     
                if($h == 'uri') { $details = oeawStorage::createDetailsUrl($res4, 'code'); }
                //if($h == 'uri') { $details = $res4; }
                $finalArray[$i][] = $res4;                
            }
            $finalArray[$i][] = t('<a href="/oeaw_detail/'.$details.'">Details</a>');                
            $i++;
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
                    '#markup' => $text,          
            );            
        }
                
        $ftrTxt = array(
                '#type' => 'markup',
                '#markup' => '<a href="'.$goBackUrl.'">Go Back</a>',          
        );            
        
        return array(
            $hdrTxt,
            $table,
            $ftrTxt,
        );
        
        
    }
    
    /*  
     * get the root elements from fedora
     */
    public function getRootFromDB()
    {    
        $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
      
        $result = $sparql->query(self::$prefixes.' '
                . 'select * WHERE {  '                
                . '?uri dct:title ?title .'                
                . 'FILTER ('
                . '!EXISTS {'
                . '?uri dct:isPartOf ?y .'
                . '}'
                . ')'
                . '}'
                . ''
        );        
        return $result;    
    }
  
   /* get all data by uri */
    public static function getPropertyByURI($uri)
    {      
        if(empty($uri)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE { <'.$uri.'> ?p ?o}');
        
        return $result;
    }
    
    /* get all data by property and URI */
    public static function getDefPropByURI($uri, $property)
    {
        if(empty($uri) && empty($property)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
        
            // the result will be an EasyRdf_Sparql_Result Object
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE { <'.$uri.'> ?property ?value . <'.$uri.'> '.$property.' ?value . }');
        
        return $result;        
    }
    
    /* get all data by property */
    public static function getDataByProp($property)
    {        
        if(empty($property)) { return false; }
        
        $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
        
        // the result will be an EasyRdf_Sparql_Result Object
        $result = $sparql->query(self::$prefixes.' SELECT * WHERE { ?uri '.$property.' ?value . }');
        
        return $result;        
    }
    
    /*
     * get the child resources by Uri
     */
    public static function getPartOfUri($uri)
    {
        if(empty($uri)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
        
        $result = $sparql->query(self::$prefixes.' SELECT * WHERE { ?uri dct:isPartOf <'.$uri.'> .  ?uri dct:title ?title .}');
        
        $res = array();
        
        foreach ($result as $r)
        {
            $r = (array)$r;
            $r1 = $r['uri'];
            $r2 = $r['title'];
            $res['uri'] = $r1->dumpValue('string');        
            $res['title'] = $r2->dumpValue('string');
        }
        
        return $res;                
    }
  
}
