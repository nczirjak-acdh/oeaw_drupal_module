<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\oeawFunctions;
use Drupal\oeaw\connData;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Render\MarkupInterface;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\fedora\metadataQuery\Query;
use acdhOeaw\fedora\metadataQuery\HasTriple;
use acdhOeaw\fedora\metadataQuery\HasValue;
use acdhOeaw\fedora\metadataQuery\HasProperty;
use acdhOeaw\fedora\metadataQuery\QueryParameter;
use acdhOeaw\fedora\metadataQuery\MatchesRegEx;

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
    
    public static $sparqlPref = array(
        'rdfType' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'rdfsLabel' => 'http://www.w3.org/2000/01/rdf-schema#label',
        'foafName' => 'http://xmlns.com/foaf/0.1/name'
    );

    /*
     * Get the root elements from fedora
     * 
     * @return Array
     * +
     */
    public function getRootFromDB(): array {
  
        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');        
        $dcTitle = $config->get('fedoraTitleProp');
        $isPartOf = $config->get('fedoraRelProp');
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
      
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);

        
        try {
          
            $q = new Query();
            $q->addParameter(new HasTriple('?uri', $dcTitle, '?title'));    
            $q2 = new Query();
            $q2->addParameter(new HasTriple('?uri', $isPartOf, '?y'));
            $q2->setJoinClause('filter not exists');
            $q->addSubquery($q2);            
            $q->setSelect(array('?uri', '?title'));
        
            $query= $q->getQuery();
            
            $result = $sparql->query($query);        
            $fields = $result->getFields(); 
            
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
        
            return $getResult;
            
        } catch (Exception $ex) {            
            return drupal_set_message(t('There was an error in the function: getRootFromDB'), 'error');
        }
    }

   
    /* 
     *
     * Get all property for search
     *     
     * @return Array
    */
    public static function getAllPropertyForSearch():array {
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);

        try {
            
            $q = new Query();
            $q->addParameter(new HasTriple('?s', '?p', '?o'));    
            $q->setDistinct(true);            
            $q->setSelect(array('?p'));
        
            $query= $q->getQuery();
            $result = $sparql->query($query);            
            
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
    public static function getDataByProp(string $property, string $value): array {
        
        if (empty($property)) {
            return drupal_set_message(t('Empty values!'), 'error');
        }
      
        if(!filter_var($property, FILTER_VALIDATE_URL)){
            $property = \Drupal\oeaw\oeawFunctions::createUriFromPrefix($property);
           
        }else if(filter_var($property, FILTER_VALIDATE_URL)){            
            $property = '<'. $property .'>';
        }


        if(!filter_var($value, FILTER_VALIDATE_URL)){
            $value = \Drupal\oeaw\oeawFunctions::createUriFromPrefix($value);
           
        }else if(filter_var($value, FILTER_VALIDATE_URL)){            
            $value = '<'. $value .'>';
        }        

        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);        
        
        try {        
            
            $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
            $dcTitle = $config->get('fedoraTitleProp');
            $foafName = self::$sparqlPref["foafName"];
            $rdfsLabel = self::$sparqlPref["rdfsLabel"];
            
            
            $q = new Query();
            $q->addParameter((new HasValue($property, $value))->setSubVar('?uri'));
            $q2 = new Query();
            $q2->addParameter((new HasTriple('?uri', $dcTitle, '?title')));
            $q2->setJoinClause('optional');
            $q->addSubquery($q2);
            $q3 = new Query();
            $q3->addParameter((new HasTriple('?uri', $rdfsLabel, '?label')));
            $q3->setJoinClause('optional');
            $q->addSubquery($q3);
            $q4 = new Query();
            $q4->addParameter((new HasTriple('?uri', $foafName, '?name')));
            $q4->setJoinClause('optional');
            $q->addSubquery($q4);
            
            $q->setSelect(array('?uri', '?title', '?label', '?name'));
            $query = $q->getQuery();
           
            $result = $sparql->query($query);        
   
/*               $result = $sparql->query(
                        self::$prefixes . ' '
                        . 'SELECT '
                            . '?uri ?title ?label ?name '
                        . ' WHERE { '
                            . '?uri ' . $property . ' '.$value.' . '
                            . 'OPTIONAL { ?uri dc:title ?title } .'
                            . 'OPTIONAL { ?uri rdfs:label ?label } . '
                            . 'OPTIONAL { ?uri foaf:name ?name } . '
                        . '}'); 
          
  */       
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
    public static function getClass(): array {
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
            $rdfType = self::$sparqlPref["rdfType"];
            $dcTitle = $config->get('fedoraTitleProp');

            $rdfsLabel = self::$sparqlPref["rdfsLabel"];
            
            /*
             * 
             * SELECT 
                            ?uri ?title 
                        WHERE {
                            ?uri a owl:Class .
                            ?uri rdfs:label ?title .
                          }
             * 
             */
            $q = new Query();
            $q->addParameter((new HasValue($rdfType, 'http://www.w3.org/2002/07/owl#Class'))->setSubVar('?uri'));
            $q->addParameter(new HasTriple('?uri', $rdfsLabel, '?title'));
            $q->setSelect(array('?uri', '?title'));
            $query = $q->getQuery();
           
            $result = $sparql->query($query);   
                        
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
     * +
    */    
    public function getDigitalResources(): array
    {
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
            $rdfType = self::$sparqlPref["rdfType"];
            $dcTitle = $config->get('fedoraTitleProp');
            $isPartOf = $config->get('fedoraRelProp');
          
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
    public static function getClassMeta(string $classURI): array{
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            
            
            $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
            
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
                    } Order BY (?id)           
                ');
            
            $fields = $result->getFields(); 
            $getResult = \Drupal\oeaw\oeawFunctions::createSparqlResult($result, $fields);
            
            return $getResult;    
            
            
        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getClassMeta'), 'error');
        }
      
    }
    
    public function searchForData(string $value, string $property): array{
        
        $sparqlConfig = \Drupal::config('oeaw.settings')->get('sparql_endpoint');
        
        if(empty($sparqlConfig)){
            return drupal_set_message(t('Please set up the fedora values in the Admin!'), 'error');            
        }
        
        $sparql = new \EasyRdf_Sparql_Client($sparqlConfig);
        
        try {
            //if the property is url then
            if(!empty(filter_var($property, FILTER_VALIDATE_URL))){
                $property = "<". $property .">";
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
    
    
    public function getClassesForSideBar():array
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
    
    

} 