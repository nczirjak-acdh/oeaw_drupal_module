<?php

namespace Drupal\oeaw;

use Drupal\Core\Url;
use Drupal\oeaw\OeawFunctions;
use Drupal\oeaw\ConnData;
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


class OeawStorage {

    private static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> '
            . 'PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> '
            . 'PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> '
            . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>'
            . 'PREFIX dc: <http://purl.org/dc/elements/1.1/>'
            . 'PREFIX foaf: <http://xmlns.com/foaf/0.1/>';
    
    private static $sparqlPref = array(
        'rdfType' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'rdfsLabel' => 'http://www.w3.org/2000/01/rdf-schema#label',
        'foafName' => 'http://xmlns.com/foaf/0.1/name',
        'rdfsSubClass' => 'http://www.w3.org/2000/01/rdf-schema#subClassOf',
        'owlClass' => 'http://www.w3.org/2002/07/owl#Class',
        'rdfsDomain' => 'http://www.w3.org/2000/01/rdf-schema#domain',
        'dctLabel' => 'http://purl.org/dc/terms/label',
    );
    
    private $apiUrl;
    
    private $idProp;
    
    private $relProp;
    
    private $titleProp;
    
    private $OeawFunctions;
    
    private $fedora;
    
    
    
    public function __construct() {  
        
        $cfg = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $this->apiUrl = preg_replace('|/$|', '', $cfg->get('fedoraApiUrl'));
        $this->idProp = $cfg->get('fedoraIdProp');
        $this->relProp = $cfg->get('fedoraRelProp');
        $this->titleProp = $cfg->get('fedoraTitleProp');
        $this->OeawFunctions = new OeawFunctions();
        $this->fedora = new Fedora($cfg);
        
        //blazegraph bugfix. Add missing namespace
        $blazeGraphNamespaces = \EasyRdf\RdfNamespace::namespaces();
        $localNamespaces =  \Drupal\oeaw\ConnData::$prefixesToBlazegraph;
                
        foreach($localNamespaces as $key => $val){
            if(!array_key_exists($val, $blazeGraphNamespaces)){
                \EasyRdf\RdfNamespace::set($key, $val);
            }
        }
    }


    /*
     * Get the root elements from fedora
     * 
     * @return Array     
     */
    public function getRootFromDB(): array {
  
        $dcTitle = $this->titleProp;
        $isPartOf = $this->relProp;
        $getResult = array();
        
        try {
          
            $q = new Query();
            $q->addParameter(new HasTriple('?uri', $dcTitle, '?title'));    
            $q->addParameter((new HasValue(self::$sparqlPref["rdfType"], 'https://vocabs.acdh.oeaw.ac.at/#Project' ))->setSubVar('?uri'));
            $q2 = new Query();
            $q2->addParameter(new HasTriple('?uri', $isPartOf, '?y'));
            $q2->setJoinClause('filter not exists');
            $q->addSubquery($q2);            
            $q->setSelect(array('?uri', '?title'));
        
            $query= $q->getQuery();
        
            $result = $this->fedora->runSparql($query);
            $fields = $result->getFields(); 
            
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);
        
            return $getResult;
            
        } catch (Exception $ex) {            
            return drupal_set_message(t('There was an error in the function: getRootFromDB'), 'error');
        }
    }

    /**
     * 
     * Get all property for search
     * 
     * @return array
     */
    public function getAllPropertyForSearch():array {
        
        $getResult = array();
        
        try {
            
            $q = new Query();
            $q->addParameter(new HasTriple('?s', '?p', '?o'));    
            $q->setDistinct(true);            
            $q->setSelect(array('?p'));
        
            $query= $q->getQuery();
            
            $result = $this->fedora->runSparql($query);
            
            $fields = $result->getFields(); 

            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

            return $getResult;                
            
        } catch (Exception $ex) {
            
            return drupal_set_message(t('There was an error in the function: getAllPropertyForSearch'), 'error');
        }        
    }
       
    
    /**
     * 
     * Get all data by property.
     * 
     * @param string $property
     * @param string $value
     * @return array
     */
    public function getDataByProp(string $property, string $value): array {
        
        if (empty($value) || empty($property)) {
            return drupal_set_message(t('Empty values! -->'.__FUNCTION__), 'error');
        }
       
        if(!filter_var($property, FILTER_VALIDATE_URL)){
            $property = $this->OeawFunctions->createUriFromPrefix($property);
            if($property === false){
                return drupal_set_message(t('Error in function: createUriFromPrefix '), 'error'); 
            }
           
        }else if(filter_var($property, FILTER_VALIDATE_URL)){            
            $property = '<'. $property .'>';
        }


        if(!filter_var($value, FILTER_VALIDATE_URL)){
            $value = $this->OeawFunctions->createUriFromPrefix($value);
            if($value === false){
                return drupal_set_message(t('Error in function: createUriFromPrefix '), 'error'); 
            }
           
        }else if(filter_var($value, FILTER_VALIDATE_URL)){            
            $value = '<'. $value .'>';
        }        

        $getResult = array();


        try {        
            
            $dcTitle = $this->titleProp;        
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
            
            $result = $this->fedora->runSparql($query);
            
   
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
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

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
    public function getClass(): array {
        
        $getResult = array();
        
        try {
        
            $rdfType = self::$sparqlPref["rdfType"];
            $dcTitle = $this->titleProp;        
            $rdfsLabel = self::$sparqlPref["rdfsLabel"];
             $owlClass = self::$sparqlPref["owlClass"];
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
            $q->addParameter((new HasValue($rdfType, $owlClass))->setSubVar('?uri'));
            $q->addParameter(new HasTriple('?uri', $rdfsLabel, '?title'));
            $q->setSelect(array('?uri', '?title'));
            $query = $q->getQuery();
            $result = $this->fedora->runSparql($query);
                        
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

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
    public function getDigitalResources(): array
    {
        
        $getResult = array();
        
        try {
            
            $rdfType = self::$sparqlPref["rdfType"];
            $dcID = $this->idProp;
            $rdfsSubClass = self::$sparqlPref["rdfsSubClass"];
            $owlClass = self::$sparqlPref["owlClass"];
            
            $q = new Query();            
            $q->setSelect(array('?id', '?collection'));            
            
            $q->addParameter((new HasValue($rdfType, $owlClass))->setSubVar('?class'));
            $q->addParameter(new HasTriple('?class', $dcID, '?id'));
            
            $q2 = new Query();
            $q2->setJoinClause('optional');
            
            
            $q->addSubquery($q2);
            
            /*
            
            $q3 = new Query();            
            $q3->addParameter((new HasValue($rdfsSubClass, 'https://vocabs.acdh.oeaw.ac.at/#Collection'))->setSubVar('?class'));            
            $q2->addSubquery($q3);
            
            $q4 = new Query();
            $q4->setJoinClause('union');
            $q4->addParameter((new HasValue($rdfsSubClass, 'https://vocabs.acdh.oeaw.ac.at/#DigitalCollection'))->setSubVar('?class'));
            $q2->addSubquery($q4);
            
            $q5 = new Query();
            $q5->setJoinClause('union');
            $q5->addParameter((new HasValue($dcID, 'https://vocabs.acdh.oeaw.ac.at/#Collection'))->setSubVar('?class'));
            $q2->addSubquery($q5);
            
            
            $q6 = new Query();
            $q6->setJoinClause('union');
            $q6->addParameter((new HasValue($dcID, 'https://vocabs.acdh.oeaw.ac.at/#DigitalCollection'))->setSubVar('?class'));
            $q2->addSubquery($q6);
            //VALUES ?collection {true}
            $q2->addParameter((new HasValue('?collection' '{true}'))->setSubVar('VALUES'));;
            $query = $q->getQuery();
            
             * 
             * 
        */

            $query=
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
            ';
      
            $result = $this->fedora->runSparql($query);
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

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
    public function getClassMeta(string $classURI): array{
       
        if (empty($classURI)) {
            return drupal_set_message(t('Empty values! -->'.__FUNCTION__), 'error');
        }
        
        $getResult = array();        
        
        try {
            
            $rdfType = self::$sparqlPref["rdfType"];
            $dcTitle = $this->titleProp;        
            $rdfsLabel = self::$sparqlPref["rdfsLabel"];
            $idProp = $this->idProp;
            $rdfsSubClass = self::$sparqlPref['rdfsSubClass'];
            $rdfsDomain = self::$sparqlPref["rdfsDomain"];
            $dctLabel = self::$sparqlPref["dctLabel"];
            
            $q = new Query();            
            $q->setSelect(array('?id', '?label'));            
            
            $q3 = new Query();
            $q3->addParameter(new HasTriple($classURI, array( $idProp, '/', '^', $rdfsDomain, ), '?property'));
            
            $q2 = new Query();
            
            $q4 = new Query();
            $q4->addParameter(new HasTriple($classURI, array( $rdfsSubClass, '/', '(', '^', $idProp, '/', $rdfsSubClass,')', '*', '/', '^', $rdfsDomain, ), '?property'));           
            $q4->setJoinClause('union');
            
            $q5 = new Query();
            $q5->addParameter(new HasTriple('?property', $idProp, '?id'));            
            
            $q6 = new Query();
            $q6->addParameter(new HasTriple('?property', $dctLabel, '?label'));            
            $q6->setJoinClause('optional');
            
            $q2->addSubquery($q3);
            $q2->addSubquery($q4);
            $q->addSubquery($q2);
            $q->addSubquery($q5);
            $q->addSubquery($q6);
            
            $q->setOrderBy(array('?id'));
            $query = $q->getQuery();
            
            
        //HasTriple('?class', array('(', 'rdfs:subClassOf', '/', '^', 'dct:identifier', ')', '*'), 'acdh:DigitalCollection')

/*
            $query = self::$prefixes . ' 
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
                ';*/
            $result = $this->fedora->runSparql($query);
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);
            
            return $getResult;    
            
            
        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getClassMeta'), 'error');
        }
      
    }
    
    /*
     * 
     * Get the resource thumbnail image
     * 
     * @param string $value -> the property value 
     * @param string $property -> the property
     * 
     * @return string
     * 
     */
    
    public function getImage(string $value, string $property = null ): array
    {         
        
        if (empty($value)) {
            return drupal_set_message(t('Empty values! -->'.__FUNCTION__), 'error');
        }
        
        if($property == null){
            $property = $this->idProp;
        }
        
        $result = "";
        $res = array();

        try{
            
            $q = new Query();
            $q->setSelect(array('?res'));
            $q->addParameter((new HasValue($property, $value)));
            $query = $q->getQuery();
            $result = $this->fedora->runSparql($query);
            
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);
           
            if(count($getResult) > 0){
                $res[] = $getResult[0]["res"];
            }
            
            return $res;            
         
        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getImage'), 'error');
        }
    }
    
    /*
     * 
     * Search function
     * 
     * @param string $value -> the property value 
     * @param string $property -> the property
     * 
     * @return array
     * 
     */
    
    public function searchForData(string $value, string $property): array{
        
        $rdfType = self::$sparqlPref["rdfType"];
        $dcTitle = $this->titleProp;        
        $rdfsLabel = self::$sparqlPref["rdfsLabel"];
        
        if (empty($value) || empty($property)) {
            return drupal_set_message(t('Empty values! -->'.__FUNCTION__), 'error');
        }
        
        $getResult = array();
        
        try {
           
            $q = new Query();            
            $q->setSelect(array('?res', '?property', '?value', '?title', '?label'));            
            //$q->addParameter(new HasTriple('?uri', $property, '?value'));            
            $q->addParameter(new MatchesRegEx($property, $value), 'i');
            
            $q2 = new Query();
            $q2->addParameter((new HasTriple('?res', $dcTitle, '?title')));
            $q2->setJoinClause('optional');
            $q->addSubquery($q2);

            $q3 = new Query();
            $q3->addParameter((new HasTriple('?res', $rdfsLabel, '?label')));
            $q3->setJoinClause('optional');
            $q->addSubquery($q3);
            
            $query = $q->getQuery();
            
            /*
            $query =
                    self::$prefixes . ' SELECT ?uri ?property ?value ?title ?label  '
                    . 'WHERE {'
                        . '?uri '.$property.' ?value . '
                        . ' FILTER (  '
                            . ' regex( str(?value), "' . $value . '", "i")'
                        . ') . '
                        . ' OPTIONAL {?uri dc:title ?title} . '
                        . ' OPTIONAL {?uri rdfs:label ?label} . '
                    . '} ';
*/
            $result = $this->fedora->runSparql($query);
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

            return $getResult;

        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: searchForData'), 'error');
        }
        
    }
    /*
     * 
     * Get the actual classes for the SideBar block
     * 
     * @return array
     * 
     */
    
    public function getClassesForSideBar():array
    {        
        $rdfType = self::$sparqlPref["rdfType"];
        
        $getResult = array();
        
        try {
            
            $q = new Query();
            $q->addParameter(new HasTriple('?aaa', $rdfType, '?type'));
            $q->setSelect(array('?type', '(COUNT(?type) as ?typeCount)'));
            $q->setOrderBy(array('?aaa'));
            $q->setGroupBy(array('?type'));
            $query = $q->getQuery();
         
            /*   $query =
                    self::$prefixes . ' 
                        SELECT ?type  
                        WHERE {[] a ?type} GROUP BY ?type ';
*/
            $result = $this->fedora->runSparql($query);
            $fields = $result->getFields(); 
            $getResult = $this->OeawFunctions->createSparqlResult($result, $fields);

            return $getResult;

        } catch (Exception $ex) {
            return drupal_set_message(t('There was an error in the function: getClassesForSideBar'), 'error');
        }
        
    }

} 