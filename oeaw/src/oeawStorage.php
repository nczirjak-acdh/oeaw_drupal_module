<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawFunctions;

class oeawStorage {
    
    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> ';
    public static $sparqlEndpoint = 'http://blazegraph:9999/blazegraph/sparql';
    public static $fedoraUrl = 'http://fedora:8080/rest/';
    public static $fedoraDownloadUrl = 'http://fedora.localhost/rest/';
    
    
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
        if(empty($uri)) {  throw new \RuntimeException('URI empty'); }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE { <'.$uri.'> ?p ?o}');
        
        return $result;
    }
    
    public static function getChildrenPropertyByRoot($uri)
    {      
        if(empty($uri)) {  throw new \RuntimeException('URI empty'); }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE {  ?uri dct:isPartOf <'.$uri.'>}');
        
        return $result;
    }
    
    /* get all data by property and URI */
    public static function getDefPropByURI($uri, $property, $value)
    {
        if(empty($uri) && empty($property)) {  throw new \RuntimeException('Property or/and uri is empty.'); }
        
            $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
            if(empty($value))
            {
                // the result will be an EasyRdf_Sparql_Result Object
                $result = $sparql->query(self::$prefixes.' SELECT * WHERE { <'.$uri.'> ?property ?value . <'.$uri.'> '.$property.' ?value . }');
            }
            else
            {
                $result = $sparql->query(self::$prefixes.' SELECT * WHERE { <'.$uri.'> ?property ?value . <'.$uri.'> '.$property.' ?value . FILTER (CONTAINS(LCASE(?value), LCASE("'.$value.'"))) . }');
            }
        
        return $result;        
    }
    
    /* get all data by property */
    public static function getDataByProp($property, $value = null)
    {        
        if(empty($property)) { throw new \RuntimeException('Property empty'); }
        
        $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
        
        if(empty($value))
        {
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE { ?uri '.$property.' ?value . }');
        }
        else
        {            
            $result = $sparql->query(self::$prefixes.' SELECT * WHERE { ?uri '.$property.' ?value . FILTER (CONTAINS(LCASE(?value), LCASE("'.$value.'"))) . }');            
        }
        // the result will be an EasyRdf_Sparql_Result Object

        
        return $result;        
    }
    
    /*
     * get the child resources by Uri
     */
    public static function getPartOfUri($uri)
    {
        if(empty($uri)) {  throw new \RuntimeException('URI EMPTY.'); }
        
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
    
    public function getAllPropertyForSearch()
    {
        $sparql = new \EasyRdf_Sparql_Client(self::$sparqlEndpoint);
        
        $result = $sparql->query(self::$prefixes.' SELECT distinct ?p WHERE { ?s ?p ?o }');
        
        return $result;
    }
  
}
