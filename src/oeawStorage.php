<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawFunctions;
use Drupal\oeaw\connData;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Render\MarkupInterface;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\SparqlEndpoint;
use zozlak\util\Config;

class oeawStorage {

    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> '
            . 'PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> '
            . 'PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> '
            . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>'
            . 'PREFIX dc: <http://purl.org/dc/elements/1.1/>'
            . 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>';

    
    /*
     * Get the root elements from fedora
     * 
     * @return Array
     * 
     */
    public function getRootFromDB() {
  
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
      
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);

        try {
            
            $result = $sparql->query(self::$prefixes . ' '
                . 'SELECT '
                    . '?uri ?title '
                    . 'WHERE {  '
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
            return drupal_set_message(t('There was an error in the function: getRootFromDB'), 'error');
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
    
    public static function getAllPropertyByURI(string $uri) {
        
        if (empty($uri)) {
            throw new \Exception('URI empty');
        }
        
        try {
            
            $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
            
            if(empty($sparqlConfig)){
                return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
            }
            
            $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
            
            $result = $sparql->query(
                    self::$prefixes . ' '
                    . 'SELECT '
                        . '?property ?value  '
                    . 'WHERE { '
                        . '<' . $uri . '> ?property ?value'
                    . '}');

            $fields = $result->getFields(); 

            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            
            return $getResult;
            
        } catch (Exception $ex) {            
            return drupal_set_message(t('There was an error in the function: getAllPropertyByURI'), 'error');
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
            throw new \Exception('URI empty');
        }
        
        try {
            
            $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
            
            if(empty($sparqlConfig)){
                return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
            }
            
            $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
            
            $result = $sparql->query(
                    self::$prefixes . ' '
                    . 'SELECT '
                        . '?uri ?title '
                    . 'WHERE { '
                        . '?uri dct:isPartOf <' . $uri . '> . '
                        . 'OPTIONAL { ?uri dc:title ?title . } '
                    . '}');
        
            $fields = $result->getFields(); 
          
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
  
            return $getResult;
            
        } catch (Exception $ex) {            
            return drupal_set_message(t('There was an error in the function: getChildrenPropertyByRoot'), 'error');
        }
    }
    
    
   
    /* 
     *
     * Get all property for search
     *     
     * @return Array
    */
    public static function getAllPropertyForSearch() {
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);

        try {
            
            $result = $sparql->query(
                    self::$prefixes . ' '
                    . 'SELECT '
                        . 'distinct ?p '
                    . 'WHERE {'
                        . ' ?s ?p ?o '
                    . '}');

            $fields = $result->getFields(); 

            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;                
            
        } catch (Exception $ex) {
            
            return drupal_set_message(t('There was an error in the function: getAllPropertyForSearch'), 'error');
        }        
    }
    
    /* 
     *
     * Get all data by property.
     *
     * @param string $property
     * @param string $value
     *
     * @return Array
    */
    public static function getDataByProp(string $property, string $value = null) {
        
        if (empty($property)) {
            return drupal_set_message(t('Empty values!'), 'error');
        }
                
        if(!filter_var($property, FILTER_VALIDATE_URL)){
            $property = \Drupal\oeaw\oeawFunctions::createUriFromPrefix($property);
            $property = '<'. $property .'>';
        }else if(filter_var($property, FILTER_VALIDATE_URL)){            
            $property = '<'. $property .'>';
        }
        
        
        if(!filter_var($value, FILTER_VALIDATE_URL)){
            $value = \Drupal\oeaw\oeawFunctions::createUriFromPrefix($value);
            $value = '<'. $value .'>';
        }else if(filter_var($value, FILTER_VALIDATE_URL)){            
            $value = '<'. $value .'>';
        }        
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);        
        
        try {        
            
            if ($value == null) {                
                $result = $sparql->query(
                        self::$prefixes . ' '
                        . 'SELECT '
                            . '?uri ?value '
                        . ' WHERE {'
                            . ' ?uri ' . $property . ' ?value . '
                        . '}');
            } else {    
               
                $result = $sparql->query(
                        self::$prefixes . ' '
                        . 'SELECT '
                            . '?uri ?title ?label ?name '
                        . ' WHERE { '
                            . '?uri ' . $property . ' '.$value.' . '
                            . 'OPTIONAL { ?uri dc:title ?title } .'
                            . 'OPTIONAL { ?uri rdfs:label ?label } . '
                            . 'OPTIONAL { ?uri foaf:name ?name } . '
                        . '}'); 
            }    
         
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;                
        
        } catch (Exception $ex) {            
            return drupal_set_message(t('There was an error in the function: getDataByProp'), 'error');
        }
    }
    
    /* 
     *
     * Get all class data for the new resource adding form.
     *     
     * @return Array
    */
    public static function getClass() {
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            $result = $sparql->query(
                    self::$prefixes . ' 
                        SELECT 
                            ?uri ?title 
                        WHERE {
                            ?uri a owl:Class .
                            ?uri rdfs:label ?title .
                          }
            ');
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult; 
            
        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getClass'), 'error');
        }    
        
    }
    
    /* 
     *
     * Get the digital rescources to we can know which is needed a file upload
     *     
     *
     * @return Array
    */    
    public function getDigitalResources()
    {
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            $result = $sparql->query(
                self::$prefixes . ' 
                    SELECT 
                        ?id ?collection 
                    WHERE {
                            ?class a owl:Class .
                            ?class dct:identifier ?id .
                            OPTIONAL {
                              {
                                {?class rdfs:subClassOf* <https://vocabs.acdh.oeaw.ac.at/#Collection>}
                                UNION
                                {?class rdfs:subClassOf* <https://vocabs.acdh.oeaw.ac.at/#DigitalCollection>}
                                UNION
                                {?class dct:identifier <https://vocabs.acdh.oeaw.ac.at/#Collection>}
                                UNION
                                {?class dct:identifier <https://vocabs.acdh.oeaw.ac.at/#DigitalCollection>}
                              }
                              VALUES ?collection {true}
                            }
                        }
            ');

            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;
            
        } catch (Exception $ex) {            
             return drupal_set_message(t('There was an error in the function: getDigitalResources'), 'error');
        }
    }
    
    
    /* 
     *
     *  Get the digital rescources Meta data by ResourceUri
     *
     * @param string $classURI 
     *
     * @return Array
    */
    public static function getClassMeta($classURI){
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
          
            $result = $sparql->query(self::$prefixes . ' 
                    SELECT 
                        ?id ?label 
                    WHERE {
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
            return drupal_set_message(t('There was an error in the function: getClassMeta'), 'error');
        }
      
    }
    
    /* 
     *
     * Get the field values to the editing form
     *
     * @param string $uri
     * @param string $resourceProperty
     *
     * @return array
    */
    
    public function getValueByUriProperty($uri, $resourceProperty){
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            //if the property is url then
            if(!empty(filter_var($resourceProperty, FILTER_VALIDATE_URL))){
                $resourceProperty = "<". $resourceProperty .">";
            }

            $result = $sparql->query(
                    self::$prefixes . ' '
                    . 'SELECT '
                        . '?value '
                    . 'WHERE {  '
                        . '<' . $uri . '> '.$resourceProperty.' ?value . '
                    . '} ');

            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;

        } catch (Exception $ex) {
             return drupal_set_message(t('There was an error in the function: getValueByUriProperty'), 'error');
        }
    }
     
    
    public function searchForData(string $value, string $property){
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            //if the property is url then
            if(!empty(filter_var($resourceProperty, FILTER_VALIDATE_URL))){
                $resourceProperty = "<". $resourceProperty .">";
            }
       
            $result = $sparql->query(
                    self::$prefixes . ' SELECT ?uri ?property ?value ?title ?label  '
                    . 'WHERE {'
                        . '?uri '.$property.' ?value . '
                        . ' FILTER (  '
                            . ' regex( str(?value), "' . $value . '", "i")'
                        . ') . '
                        . ' OPTIONAL {?uri dc:title ?title} . '
                        . ' OPTIONAL {?uri rdfs:label ?label} . '
                    . '} ');

            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;

        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: searchForData'), 'error');
        }
        
    }
    
    
    public function getClassesForSideBar()
    {
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            $result = $sparql->query(
                    self::$prefixes . ' 
                        SELECT ?type  
                        WHERE {[] a ?type} GROUP BY ?type ');

            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

            return $getResult;

        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getClassesForSideBar'), 'error');
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
    //////////////////// NOT IN USE \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    public static function getDefPropByURI(string $uri, string $property, string $value=null) {
        
        if (empty($uri) && empty($property)) {
             return drupal_set_message(t('Empty values!'), 'error');
        }

        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        if ($value == null) {
            // the result will be an EasyRdf_Sparql_Result Object
            try {                
                $result = $sparql->query(
                        self::$prefixes . ' '
                        . 'SELECT '
                            . '?property ?value '
                        . 'WHERE { '
                            . '<' . $uri . '> ?property ?value . '
                            . '<' . $uri . '> ' . $property . ' ?value . '
                        . '}');
                
                $fields = $result->getFields(); 

                $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

                return $getResult;
                
            } catch (Exception $ex) {
                return drupal_set_message(t('There was an error in the function: getDefPropByURI'), 'error');
            }
        } else {
            
            try {
                
                $result = $sparql->query(
                        self::$prefixes . ' '
                        . 'SELECT '
                            . '?property ? value '
                        . 'WHERE { '
                            . '<' . $uri . '> ?property ?value . '
                            . '<' . $uri . '> ' . $property . ' ?value . '
                            . 'FILTER (CONTAINS(LCASE(?value), LCASE("' . $value . '"))) . '
                        . '}');
                
                $fields = $result->getFields(); 

                $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);

                return $getResult;                
                
            } catch (Exception $ex) {
                return drupal_set_message(t('There was an error in the function: getDefPropByURI'), 'error');
            }
        }        
    }
            

} 