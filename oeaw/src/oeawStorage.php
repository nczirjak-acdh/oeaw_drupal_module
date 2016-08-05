<?php

namespace Drupal\oeaw;

class oeawStorage {
    

    /* way = code/encode */
    public function createDetailsUrl($data, $way = 'code')
    {                
        if($way == 'code')
        {
            $data = str_replace('http://fedora:8080/rest/', '', $data);
            $str = str_replace('/', '_', $data);            
        }
        
        if($way == 'encode')
        {            
            $data = str_replace('_', '/', $data);                        
            $str = 'http://fedora:8080/rest/'.$data;            
        }
        
        return $str;        
    }
    
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
    
    public function getRootFromDB()
    {
    
        $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
      
        $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/>'
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
    
        /*
         * 
         * ez ,mukodik
        $res = array();        
        
        foreach ((array)$result[0] as $d)
        {
            $array = (array)$d;
            $prefix = chr(0).'*'.chr(0);
            
            if(!empty($array[$prefix.'uri']))
            {
                $res['uri']=$array[$prefix.'uri'];
            }
            
            if(!empty($array[$prefix.'value']))
            {
                $res['value']=$array[$prefix.'value'];
            }        
        }
        */
        
        return $res;  
  }
  
  
    /* Get All Data from Fedora 4 DB */
    static function getAllSparqlData()
    {
        $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
        $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/>  SELECT * WHERE { ?s ?p ?o}');    
    
        foreach ($result as $row) {
            //echo "<li>".link_to($row->label, $row->country)."</li>\n";
            print_r($row);
        }
    
        die();

        $returnArray = array();

        $i=0;

        foreach ($result as $d)
        {
            foreach($d as $key => $value)
            {           
                $returnArray[$i][$key] = $value;
            }
            $i++;
        }
    
        return $returnArray;
    
  }
  
    public static function getPropertyByURI($uri)
    {      
        if(empty($uri)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
            $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/>  SELECT * WHERE { <'.$uri.'> ?p ?o}');
        
        return $result;
    }
    
    public static function getDefPropByURI($uri, $property)
    {
        if(empty($uri) && empty($property)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
        
            // the result will be an EasyRdf_Sparql_Result Object
            $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/>  SELECT * WHERE { <'.$uri.'> ?property ?value . <'.$uri.'> '.$property.' ?value . }');
        
        return $result;        
    }
    
    public static function getDataByProp($property)
    {        
        if(empty($property)) { return false; }
        
        $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
        
        // the result will be an EasyRdf_Sparql_Result Object
        $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/>  SELECT * WHERE { ?uri '.$property.' ?value . }');
        
        return $result;        
    }
    
    public static function getPartOfUri($uri)
    {
        if(empty($uri)) { return false; }
        
            $sparql = new \EasyRdf_Sparql_Client('http://blazegraph:9999/blazegraph/sparql');
        
        $result = $sparql->query('PREFIX dct: <http://purl.org/dc/terms/> SELECT * WHERE { ?uri dct:isPartOf <'.$uri.'> .  ?uri dct:title ?title .}');
        
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
