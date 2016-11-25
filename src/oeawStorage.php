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
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>'
            . 'PREFIX dc: <http://purl.org/dc/elements/1.1/>';

    
    /*
     * Get the root elements from fedora
     * 
     * @return Array
     * 
     */

    public function getRootFromDB() {
  
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        try {
            
            $result = $sparql->query(self::$prefixes . ' '
                . 'select ?uri ?title WHERE {  '
                    . '?uri dc:title ?title .'
                    . 'FILTER ('
                        . '!EXISTS {'
                            . '?uri dct:isPartOf ?y .'
                            . '}'
                        . ')'
                    . '}'                
            );
        
            $fields = $result->getFields(); 
        
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
        
            return $getResult;
            
        } catch (Exception $ex) {            
            throw new Exception("Error during the getRootFromDB function");
        }
    }
    
    /*
     * Get all property based on the provided uri
     * 
     * @uri Fedora resource uri
     * 
     * @return Array
     * 
    */
    
    public static function getPropertyByURI(string $uri) {
        
        if (empty($uri)) {
            throw new Exception('URI empty');
        }

        try {
            
            $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
            $result = $sparql->query(self::$prefixes . ' SELECT ?p ?o  WHERE { <' . $uri . '> ?p ?o}');

            $fields = $result->getFields(); 

            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;
            
        } catch (Exception $ex) {            
            throw new Exception("Error during the getPropertyByURI function");
        }
    }

    /*
     * Get all uri from the uri child resources
     * 
     * @uri Fedora resource uri
     * 
     * @return Array
     * 
    */
    public static function getChildrenPropertyByRoot(string $uri) {
        
        if (empty($uri)) {
            throw new Exception('URI empty');
        }
        
        try {
            
            $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
            $result = $sparql->query(self::$prefixes . ' SELECT ?uri  WHERE {  ?uri dct:isPartOf <' . $uri . '>}');
        
            $fields = $result->getFields(); 

            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;
            
        } catch (Exception $ex) {            
            throw new Exception("Error during the getChildrenPropertyByRoot function");
        }
    }
    
    
    /*
     * Get all data by, Uri, Property and value
     * 
     * @uri Fedora resource uri
     * @property property what value is intresting for us
     * @value optional
     * 
     * @return Array
     * 
    */
    public static function getDefPropByURI(string $uri, string $property, string $value=null) {
        
        if (empty($uri) && empty($property)) {
            throw new Exception('Property or/and uri is empty.');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        if ($value == null) {
            // the result will be an EasyRdf_Sparql_Result Object
            try {                
                $result = $sparql->query(self::$prefixes . ' SELECT ?property ?value WHERE { <' . $uri . '> ?property ?value . <' . $uri . '> ' . $property . ' ?value . }');
                
                $fields = $result->getFields(); 

                $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

                return $getResult;
                
            } catch (Exception $ex) {
                throw new \Exception('error durong the sparql query!');
            }
        } else {
            
            try {
                
                $result = $sparql->query(self::$prefixes . ' SELECT ?property ? value WHERE { '
                    . '<' . $uri . '> ?property ?value . '
                    . '<' . $uri . '> ' . $property . ' ?value . '
                    . 'FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . '
                    . '}');
                
                $fields = $result->getFields(); 

                $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

                return $getResult;                
                
            } catch (Exception $ex) {
                throw new \Exception('error during the sparql query!');
            }
        }        
    }
    
    /*
     * Get all property for search
     * 
     * @return Array
     * 
    */
    public static function getAllPropertyForSearch() {
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        try {
            
            $result = $sparql->query(self::$prefixes . ' SELECT distinct ?p WHERE { ?s ?p ?o }');

            $fields = $result->getFields(); 

            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;                
            
        } catch (Exception $ex) {
            
            throw new \Exception('error during the sparql query!');
        }        
    }
    
    /* 
     * Get all data by property.
     * @property: fedora:created
     * @valueType = Literal / Resource
     */
    public static function getDataByProp($property, string $value = null) {
        
        if (empty($property)) {
            throw new Exception('Property empty');
        }

        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());

        try {        
            
            if (empty($value)) {
                $result = $sparql->query(self::$prefixes . ' SELECT ?uri ?value WHERE { ?uri ' . $property . ' ?value . }');
            } else {
                $result = $sparql->query(self::$prefixes . ' SELECT ?uri WHERE { ?uri ' . $property . ' <'.$value.'> }'); 
            }    
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;                
        
        } catch (Exception $ex) {
            
            throw new \Exception('error during the sparql query!');
        }
    }
    
    
    /* 
     * 
     * Get all class data for the new resource adding form.
     *      
     */
    
    public static function getClass() {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        try {
            
            $result = $sparql->query(self::$prefixes . ' 
                select ?uri ?title where {
                        ?uri a owl:Class .
                        ?uri rdfs:label ?title .
                          }
            ');
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult; 
            
        } catch (Exception $ex) {
            throw new \Exception('error during the sparql query!');
        }    
        
    }
    
    /* 
     * Get the digital rescources to we can know which is needed a file upload
     */
    public function getDigitalResources()
    {
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        try {
            
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
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;
            
        } catch (Exception $ex) {
            throw new Exception('error during the getDigitalResources function!');
        }
    }
    
    /* 
     * Get the digital rescources Meta data by ResourceUri
    */
    
    public static function getClassMeta($classURI){
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());        
        
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
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
            
            return $getResult;    
            
            
        } catch (Exception $ex) {

             throw new Exception($ex->getMessage());
        }
      
    }
    
    /*
     * Get the field values to the editing form
     * 
     */
    
    public function getFieldValByUriProp($uri, $resourceProperty){
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());                  
        
            try {                
                //$result = $sparql->query(self::$prefixes . ' SELECT ?value WHERE { <' . $uri . '> ?property ?value .  <' . $uri . '> <'. $resourceProperty .'> ?value . } ');
                $result = $sparql->query(self::$prefixes . ' SELECT ?value WHERE {  <' . $uri . '> <'. $resourceProperty .'> ?value . } ');

                $fields = $result->getFields(); 
                $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
                
                return $getResult;

            } catch (Exception $ex) {
                throw new \Exception('error durong the sparql query!');
            }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    //////////////////////////////////////////////////////////////////////////
    /*
     * 
     *  DEPRECATED FUNCTIONS
     * 
     */
    
    
    
    
    
    

    
     /*       
      * !!!!!!!!!!!!!!!!!!!!!!! NOT USED
      * 
     * Get the actual class label 
    */
    
    public function getClassLabel($classUri, $prefix ='rdfs', $property = 'label'){
        
        $sparql = new \EasyRdf_Sparql_Client(\Drupal\oeaw\connData::sparqlEndpoint());
        
        try {
            
            $result = $sparql->query(self::$prefixes . ' 
                    select ?'.$property.' where {		
  			<' . $classUri . '> '.$prefix.':'.$property.'  ?'.$property.' .
                    }');
        
            $res = array();
        
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
            
            return $getResult;    
            
        } catch (Exception $e) {
            
            echo $e->getMessage(); 
        }
    }
    
    

   
    
    
   

    

    /*
     * get the child resources by Uri
     */
/*
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

    */
    
  
    
    /* 
     * Get the Class Values, to we can compare it with the already uploaded
     * resources
     */
    /*
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
*/
    
    
    /* For the multiple form, to get the class meta data */
    /* OLD QUERY  */
    /*
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

   */

    /*
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
    */
    
    
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
      
        
        //$sparqlUpdate = \Drupal\oeaw\oeawFunctions::runCurl('PATCH', $fileInsertingUrl, '', 'application/sparql-update', $sparql);
           /*
        
        if ($sparqlUpdate == false) {
        //    $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, 'fcr:tx/fcr:rollback');        
            return false;
        }
        */
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
       
       
        // if everything was okay then commit the 
        $transactionCommit = \Drupal\oeaw\oeawFunctions::runCurl('POST', $transactionUrl, '/fcr:tx/fcr:commit');        
       
        $finalURL = str_replace($transactionUrl, '', $fileInsertingUrl);
        $finalURL = \Drupal\oeaw\connData::fedoraUrl().$finalURL.'fcr:metadata';
       
        return $finalURL;
        
    }
            
            

}
