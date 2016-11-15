<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawFunctions;
use Drupal\oeaw\connData;


class oeawStorage {

    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>';

    
    /*
     * get the root elements from fedora
     * @selectList: generate key => value array from the result
     */

    public function getRootFromDB($selectList = false) {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        $result = $sparql->query(self::$prefixes . ' '
                . 'select * WHERE {  '
                    . '?uri dct:title ?title .'
                    . 'FILTER ('
                        . '!EXISTS {'
                            . '?uri dct:isPartOf ?y .'
                            . '}'
                        . ')'
                    . '}'                
        );
        

        if ($selectList != false) {
            /* create the array with the roots for the select menu */
            foreach ((array) $result as $key => $value) {
                $value = (array) $value;
                $uri = (array) $value["uri"];
                $title = (array) $value["title"];

                if (!empty($uri["\0*\0" . "uri"]) && !empty($title["\0*\0" . "value"])) {
                    $rootURI[$uri["\0*\0" . "uri"]] = $title["\0*\0" . "value"];
                }
            }

            return $rootURI;
        } else {
            return $result;
        }
    }
    
    

    /* 
     * Get the digital rescources to we can know which is needed a file upload
     */
    public function getDigitalResources()
    {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        $result = $sparql->query(
                self::$prefixes . ' SELECT ?id ?collection WHERE {
                                        ?class a owl:Class .
                                        ?class dct:identifier ?id .
                                        OPTIONAL {
                                          {
                                            {?class rdfs:subClassOf* <http://vocabs.acdh.oeaw.ac.at/#Collection>}
                                            UNION
                                            {?class rdfs:subClassOf* <http://vocabs.acdh.oeaw.ac.at/#DigitalCollection>}
                                            UNION
                                            {?class dct:identifier <http://vocabs.acdh.oeaw.ac.at/#Collection>}
                                            UNION
                                            {?class dct:identifier <http://vocabs.acdh.oeaw.ac.at/#DigitalCollection>}
                                          }
                                          VALUES ?collection {true}
                                        }
                                    }
                                ');
        
        $res = array();

        $i = 0;
        foreach ($result as $r) {
            $r = (array) $r;
            $r1 = $r['id'];
            $r2 = $r['collection'];
            if($r2 == true) 
            {
                $res[$i] = $r1->dumpValue('string');                
                $i++;
            }
            
        }

        return $res;
    }
    
    /* get all data by uri */
    public static function getPropertyByURI($uri) {
        if (empty($uri)) {
            throw new \RuntimeException('URI empty');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { <' . $uri . '> ?p ?o}');

        return $result;
    }

    public static function getChildrenPropertyByRoot($uri) {
        if (empty($uri)) {
            throw new \Exception('URI empty');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        $result = $sparql->query(self::$prefixes . ' SELECT * WHERE {  ?uri dct:isPartOf <' . $uri . '>}');

        return $result;
    }

    /* get all data by property and URI */

    public static function getDefPropByURI($uri, $property, $value) {
        if (empty($uri) && empty($property)) {
            throw new \Exception('Property or/and uri is empty.');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        if (empty($value)) {
            // the result will be an EasyRdf_Sparql_Result Object
            $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { <' . $uri . '> ?property ?value . <' . $uri . '> ' . $property . ' ?value . }');
        } else {
            $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { '
                    . '<' . $uri . '> ?property ?value . '
                    . '<' . $uri . '> ' . $property . ' ?value . '
                    . 'FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . '
                    . '}');
        }

        return $result;
    }

    /* 
     * get all data by property.
     * @property: fedora:created
     */
    public static function getDataByProp($property, $value = null) {
        if (empty($property)) {
            throw new \Exception('Property empty');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        if (empty($value)) {
            $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { ?uri ' . $property . ' ?value . }');
        } else {
            $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { ?uri ' . $property . ' ?value . FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . }');
        }
        // the result will be an EasyRdf_Sparql_Result Object


        return $result;
    }

    /*
     * get the child resources by Uri
     */

    public static function getPartOfUri($uri) {
        if (empty($uri)) {
            throw new \Exception('URI EMPTY.');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { ?uri dct:isPartOf <' . $uri . '> .  ?uri dct:title ?title .}');

        $res = array();

        foreach ($result as $r) {
            $r = (array) $r;
            $r1 = $r['uri'];
            $r2 = $r['title'];
            $res['uri'] = $r1->dumpValue('string');
            $res['title'] = $r2->dumpValue('string');
        }

        return $res;
    }

    public static function getAllPropertyForSearch() {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        $result = $sparql->query(self::$prefixes . ' SELECT distinct ?p WHERE { ?s ?p ?o }');

        return $result;
    }
    
    public function getClassLabel($classUri, $prefix ='rdfs', $property = 'label'){
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        try {
            
            $result = $sparql->query(self::$prefixes . ' 
                    select ?'.$property.' where {		
  			<' . $classUri . '> '.$prefix.':'.$property.'  ?'.$property.' .
                    }');
        
            if($result == false){
                 throw new Exception("sparql error!"); 
            }
            
            $res = array();
        
            foreach ($result as $r) {
                $r = (array) $r;
                $lbl = $r[$property]->dumpValue('string');
                $lblArray = explode('"', $lbl);
                $lbl = $lblArray[1];
                $res[] = $lbl;

            }
        
            return $res;
            
        } catch (Exception $ex) {
            echo "sss";
            echo $e->getMessage(); 
        }
    }

    public static function getClassMeta($classURI){
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        //$result = $sparql->query(self::$prefixes . ' SELECT distinct ?metadata WHERE {  ?metadata rdfs:domain <' . $classURI . '> . }');
        
        $result = $sparql->query(self::$prefixes . ' 
                    SELECT ?property ?label WHERE {
                      {
                        { <' . $classURI . '> dct:identifier / ^rdfs:domain ?property . }
                        UNION
                        { <' . $classURI . '> rdfs:subClassOf / (^dct:identifier / rdfs:subClassOf)* / ^rdfs:domain ?property . }
                      }
                      OPTIONAL {
                        ?property dct:label ?label .
                      }
                    }            
                ');
       
        if (!empty($result)) {
            $i=0;
            foreach ((array) $result as $r) {
                
                $r = (array) $r;
                if(empty($r['property']->dumpValue('string'))) { return false;}                
                $res[$i]['property'] = $r['property']->dumpValue('string');       
                if(!empty($r['label'])) { $res[$i]['label'] = $r['label']->dumpValue('string');}                       
                $i++;
            }
            
            return $res;
            
        } else {
            return false;
        }
    }
    
    /* For the multiple form, to get the class meta data */
    /* OLD QUERY  */
    public static function getClassMetadata($classURI, $createPrefix = true) {

        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        //$result = $sparql->query(self::$prefixes . ' SELECT distinct ?metadata WHERE {  ?metadata rdfs:domain <' . $classURI . '> . }');
        
        $result = $sparql->query(self::$prefixes . ' select ?p where {  
                <' . $classURI . '> dct:identifier ?s .
                ?p rdfs:domain ?s .  
                }
                ');
        
        /*
        $result = $sparql->query(self::$prefixes . ' SELECT ?uri ?title WHERE {  
                    ?metadata rdfs:domain <' . $classURI . '> .
                    ?metadata rdfs:label ?title .
                    ?metadata rdfs:isDefinedBy ?uri . }
                ');
        */

        if (!empty($result) && $createPrefix == true) {            
            $metadata = \Drupal\oeaw\oeawFunctions::createPrefixesFromObject($result);
            return $metadata;
            
        }else if (!empty($result)) {
            
            foreach ((array) $result as $r) {
                $r = (array) $r;
                $res[] = $r['p']->dumpValue('string');                
            }
            
            return $res;
            
        } else {
            return false;
        }
    }

    public static function getClass() {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        //$result = $sparql->query(self::$prefixes . ' select ?uri where {?uri a owl:Class}');
        $result = $sparql->query(self::$prefixes . ' 
        select ?uri ?title where {
  		?uri a owl:Class .
                ?uri rdfs:label ?title .
                  }
        ');
        
        foreach ((array) $result as $r) {
            $r = (array) $r;
            $r1 = $r['uri'];
            $r2 = $r['title'];
            $res['uri'] = $r1->dumpValue('string');
            $res['title'] = $r2->dumpValue('string');            
            $title = explode('"', $res["title"]);
            $title = $title[1];
            $class[$res["uri"]] = $title;
        }

        return $class;
    }

    public function getIdentifier($class){
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        //$result = $sparql->query(self::$prefixes . ' select ?uri where {?uri a owl:Class}');
        $result = $sparql->query(self::$prefixes . ' 
        select ?identifier {
  		<'.$class.'> dct:identifier ?identifier .                
                  }
        ');
        
        if (!empty($result)) {
            foreach ((array) $result as $r) {                
                $r = (array) $r;
                if(empty($r['identifier']->dumpValue('string'))) { return false;}                
                $res = $r['identifier']->dumpValue('string');                                      
            }
            
            return $res;
            
        } else {
            return false;
        }
    }
    
    
    
    /*
     *  Data inserting to fedora with transactions
     * @file : content of the file what we want to upload
     * @sparql: sparql file content
     * @root: the root uri for the isPartOf
     * @mime: mime type of the uploaded file
     */
    public static function insertDataToFedora($file, $sparql, $root = false, $mime) {

        if (empty($sparql) ) {
            return false;
        }

        // start the transaction and get the url
        $transactionRequest = \Drupal\oeaw\oeawFunctions::runCurl('POST', \Drupal\oeaw\connData::fedoraUrl(), 'fcr:tx');                
        $transactionUrl = \Drupal\oeaw\oeawFunctions::resUrl($transactionRequest);
       
        if(empty($transactionUrl)){
            $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
            return false;
        }
               
        if(!empty($file)){
            // insert the file to the actual transaction
            $fileInserting = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, '', $mime, $file);        

            // if there was any problem, during the file inserting then rollback the whole transaction
            if ($fileInserting == false) {
                $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
                return false;
            }
            //get the url from the curl response
            $fileInsertingUrl = \Drupal\oeaw\oeawFunctions::resUrl($fileInserting);
            
        } else {
            
            $fileInserting = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl);        
            //get the url from the curl response
            $fileInsertingUrl = \Drupal\oeaw\oeawFunctions::resUrl($fileInserting);            
        }
        error_log($sparql);
        $sparqlUpdate = \Drupal\oeaw\oeawFunctions::runCurl('PATCH', $fileInsertingUrl, 'fcr:metadata', 'application/sparql-update', $sparql);
                
        if ($sparqlUpdate == false) {
            $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
            return false;
        }
        
        if($root != false)
        {            
            // create sparql with the isPartOf
            $isPartOf = self::$prefixes . ' 
                    INSERT {
                        <>       
                        dct:isPartOf	<' . $root . '> ;                             
                    }
                    WHERE {}
                    ';
            
            $isPartOfRequest = \Drupal\oeaw\oeawFunctions::runCurl('PATCH', $fileInsertingUrl, 'fcr:metadata', 'application/sparql-update', $isPartOf);
       
            if($isPartOfRequest == false) {
                $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
                return false;
            }
        }
       
       
        /* if everything was okay then commit the */
        $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:commit');        
       
        $finalURL = str_replace($transactionUrl, '', $fileInsertingUrl);
        $finalURL = \Drupal\oeaw\connData::fedoraUrl().$finalURL.'fcr:metadata';
       
        return $finalURL;
        
    }

}
