<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawFunctions;
use Drupal\oeaw\connData;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\SparqlEndpoint;


class oeawStorage {




    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> '
            . 'PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> '
            . 'PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> '
            . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
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

    public static function getDefPropByURI($uri, $property, $value=null) {
        
        if (empty($uri) && empty($property)) {
            throw new \Exception('Property or/and uri is empty.');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        if ($value == null) {
            // the result will be an EasyRdf_Sparql_Result Object
            try {
                
                $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { <' . $uri . '> ?property ?value . <' . $uri . '> ' . $property . ' ?value . }');
                
                for ($x = 0; $x <= count($result)-1; $x++) {
                    
                    $resP = $result[$x]->property;                    
                    $resV = $result[$x]->value;
                    
                    $returnArray[$property][$x]['property'] = $resP;
                    $returnArray[$property][$x]['value'] = $resV;                    
                }
                
                return $returnArray;
                
            } catch (Exception $ex) {
                throw new \Exception('error durong the sparql query!');
            }
            
        } else {
            
            try {
                
                $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { '
                    . '<' . $uri . '> ?property ?value . '
                    . '<' . $uri . '> ' . $property . ' ?value . '
                    . 'FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . '
                    . '}');
                
                foreach($result as $r){
                    
                    $resP = $r->property;
                    $resV = $r->value;
                    
                    if(!empty($resP)){
                        if(get_class($resP) == "EasyRdf_Resource"){
                            $returnArray[$property]['property'] = $resP->getUri();
                        } 
                        if(get_class($resP) == "EasyRdf_Literal"){
                            $returnArray[$property]['property'] = $resP->dumpValue('string');
                        } 
                    }
                    if(!empty($resV)){
                        if(get_class($resP) == "EasyRdf_Resource"){
                            $returnArray[$property]['value'] = $resV->getUri();
                        } 
                        if(get_class($resP) == "EasyRdf_Literal"){
                            $returnArray[$property]['value'] = $resV->dumpValue('string');
                        }
                    }
                }
                
                return $returnArray;
                
            } catch (Exception $ex) {
                throw new \Exception('error durong the sparql query!');
            }
        }
        
    }

    /* 
     * get all data by property.
     * @property: fedora:created
     * @valueType = Literal / Resource
     */
    public static function getDataByProp($property, $value = null, $valueType = "Literal") {
        if (empty($property)) {
            throw new \Exception('Property empty');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        if (empty($value)) {
            
            $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { ?uri ' . $property . ' ?value . }');
            
        } else {
            
            if($valueType == "Resource"){
            
                try {
                    $result = $sparql->query(self::$prefixes . ' SELECT ?uri WHERE { ?uri ' . $property . ' <'.$value.'> }');
                    
                    foreach($result as $r){                        
                        $res = $r->uri->getUri();                    
                    }
                    return $res;
                                    
                } catch (Exception $ex) {
                    throw new \Exception('error durong the sparql query!');
                }
                
            }else {
                
                $result = $sparql->query(self::$prefixes . ' SELECT * WHERE { ?uri ' . $property . ' ?value . FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . }');
                
            }
            
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
        
            $res = array();
        
            foreach ($result as $r) {
                $r = (array) $r;
                $lbl = $r[$property]->dumpValue('string');
                $lblArray = explode('"', $lbl);
                $lbl = $lblArray[1];
                $res[] = $lbl;

            }
        
            return $res;
            
        } catch (Exception $e) {
            
            echo $e->getMessage(); 
        }
    }
    
    /* 
     * Get the Class Values, to we can compare it with the already uploaded
     * resources
     */
    public function getOntologyMeta($classURI){
    
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        $result = $sparql->query(self::$prefixes . ' 
                SELECT ?value WHERE 
                {
                    {
                        { <' . $classURI . '> dct:identifier / ^rdfs:domain ?property . }
                        UNION
                        { <' . $classURI . '> rdfs:subClassOf / (^dct:identifier / rdfs:subClassOf)* / ^rdfs:domain ?property . }                        
                    }
                    ?property dct:identifier ?value  						
                }');
        
        if (!empty($result)) {
            $i=0;
            foreach ((array) $result as $r) {                
                $r = (array) $r;
                if(!empty($r["value"])){
                    $res[$i] = \Drupal\oeaw\oeawFunctions::getProtectedValue($r["value"],"uri");
                }                
                $i++;
            }              
            return $res;            
        } else {
            return false;
        }        
    }            

    public static function getClassMeta($classURI){
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        //$result = $sparql->query(self::$prefixes . ' SELECT distinct ?metadata WHERE {  ?metadata rdfs:domain <' . $classURI . '> . }');
        
        try {
            
            $result = $sparql->query(self::$prefixes . ' 
                    SELECT ?id ?label WHERE {
                      {
                        { <' . $classURI . '> dct:identifier / ^rdfs:domain ?property . }
                        UNION
                        { <' . $classURI . '> rdfs:subClassOf / (^dct:identifier / rdfs:subClassOf)* / ^rdfs:domain ?property . }
                      }
                      ?property dct:identifier ?id
                      OPTIONAL {
                        ?property dct:label ?label .
                      }
                    }            
                ');
            
            foreach($result as $r){            
                $resID = $r->id;
                $returnArray[] = $resID->getUri();
            }
            return $returnArray;            
            
            
        } catch (Exception $ex) {

             throw new Exception($ex->getMessage());
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
    public static function insertDataToFedora($file, $sparql, $root = false, $mime, $resourceID = null) {

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
               
        if($resourceID != null){            
            $transactionUrl = $transactionUrl.$resourceID;
        }
        
        if(!empty($file)){
            
            if($resourceID != null){
                $fileMethod = 'PUT';
            }else {
                $fileMethod = 'POST';
            }
            // insert the file to the actual transaction
            $fileInserting = \Drupal\oeaw\oeawFunctions::runCurl($fileMethod, $transactionUrl, '', $mime, $file);        

            // if there was any problem, during the file inserting then rollback the whole transaction
            if ($fileInserting == false) {
                $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
                return false;
            }
            //get the url from the curl response
            $fileInsertingUrl = \Drupal\oeaw\oeawFunctions::resUrl($fileInserting);
            
        } else {
            
            if($resourceID == null){
                $fileInserting = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl);        
                //get the url from the curl response
                $fileInsertingUrl = \Drupal\oeaw\oeawFunctions::resUrl($fileInserting);            
            }else {
                $fileInsertingUrl = $transactionUrl;
            }
            
        }
        
        $sparql = \Drupal\oeaw\ConnData::$prefixes.'
            DELETE {?s ?p ?o}
                INSERT {<'.$fileInsertingUrl.'> <http://vocabs.acdh.oeaw.ac.at/#depositor> "foo" ;           
                }
                WHERE  { ?s ?p ?o . 
                    FILTER (?p =<http://vocabs.acdh.oeaw.ac.at/#represents>) 
                }';
      
        error_log($sparql);
        $sparqlUpdate = \Drupal\oeaw\oeawFunctions::runCurl('PATCH', $fileInsertingUrl, '', 'application/sparql-update', $sparql);
           
        
        if ($sparqlUpdate == false) {
        //    $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
            return false;
        }
        
        if($root != false || $resourceID == null)
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
        $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, '/fcr:tx/fcr:commit');        
       
        $finalURL = str_replace($transactionUrl, '', $fileInsertingUrl);
        $finalURL = \Drupal\oeaw\connData::fedoraUrl().$finalURL.'fcr:metadata';
       
        return $finalURL;
        
    }
    
    
    
    /*  Delete Sparqls */
    public function deleteTriple()
    {
        
        try {
            $sparql = 'DELETE DATA {
                <http://blazegraph.localhost/blazegraph/namespace/kb/sparql> <http://vocabs.acdh.oeaw.ac.at/#depositor> "utzutz" 
            }';
            
            $query = 'DELETE DATA {
                <http://blazegraph.localhost/blazegraph/namespace/kb/sparql> <http://vocabs.acdh.oeaw.ac.at/#represents> "tzutzu" ;
                                                                     dcterm:isPartOf "amc - austrian media corpus" .
            }';
            
        } catch (Exception $ex) {
            echo 'Message: ' .$ex->getMessage();
        }
        
        
    }
    
    
    /* add sparqls */
    public function addTriple(){
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        try {
            
            $query = ' INSERT DATA {
                    <http://blazegraph.localhost/blazegraph/namespace/kb/sparql> <http://vocabs.acdh.oeaw.ac.at/#represents> "a new vocab" ;
                                                               <http://vocabs.acdh.oeaw.ac.at/#depositor> "acdh depositor new".
                }';
            
        } catch (Exception $ex) {

            echo 'Message: ' .$ex->getMessage();
        }
        
    }
    
    public function createResource($uri){
         
        echo file_get_contents($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $config = new zozlak\util\Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        acdhOeaw\util\SparqlEndpoint::init($config->get('sparqlUrl'));
        acdhOeaw\redmine\Redmine::init($config);
        acdhOeaw\FedoraResource\FedoraResource::init($config);
        acdhOeaw\storage\Indexer::init($config);
        
        
        acdhOeaw\fedora\FedoraResource::begin();

        $res = new acdhOeaw\fedora\FedoraResource('http://fedora.localhost/rest/0c/c3/d0/ba/0cc3d0ba-2836-41d2-aa97-9c1d56907068');
        $ind = new acdhOeaw\storage\Indexer($res);
        $ind->index(1000, 2, false, true);
        
        acdhOeaw\fedora\FedoraResource::commit();
        
    }
            
            
            

}
