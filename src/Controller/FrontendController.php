<?php

/**
  @file
  Contains \Drupal\oeaw\Controller\FrontendController.
 */

namespace Drupal\oeaw\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\oeaw\oeawStorage;
use Drupal\oeaw\oeawFunctions;
//ajax
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use zozlak\util\Config;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;

use EasyRdf\Graph;
use EasyRdf\Resource;
use acdhOeaw\util\EasyRdfUtil;
//autocomplete
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class FrontendController extends ControllerBase {
    
    private $oeawStorage;
    private $oeawFunctions;
    
    public function __construct() {  
        $this->oeawStorage = new oeawStorage();
        $this->oeawFunctions = new oeawFunctions();
    }
    
    
    public function oeaw_ac_form(){        
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\AutoCompleteForm');
        return $form;        
    }
    
    /**
     * 
     * The root Resources list     
     * 
     * @return array
     */
    public function roots_list(): array {
        
        // get the root resources
        // sparql result fields - uri, title
        $result = array();
        $datatable = array();
        $res = array();
        $decodeUrl = "";
        
        $result = $this->oeawStorage->getRootFromDB();      

        $uid = \Drupal::currentUser()->id();
        
        if(count($result) > 0){
            $i = 0;            
            foreach($result as $value){
                // check that the value is an Url or not            
                $decodeUrl = $this->oeawFunctions->isURL($value["uri"], "decode");
                
                //create details and editing urls
                if($decodeUrl){
                    $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                    if($uid !== 0){
                        $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                    }
                }
                $res[$i]["uri"] = $value["uri"];
                $res[$i]["title"] = $value["title"];
                $i++;
            }
            $decodeUrl = "";
            
        }else {
            return drupal_set_message(t('You have no root elements!'), 'error');    
        }
      
        $header = array_keys($res[0]);
        //create the datatable values and pass the twig template name what we want to use
        $datatable = array(
            '#theme' => 'oeaw_root_dt',
            '#result' => $res,
            '#header' => $header,
            '#userid' => $uid,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', 
                ]
            ]
        );
        
        return $datatable;
    }
    
    /**
     * 
     * The autocomplete function to the edit and new form
     * 
     * @param \Drupal\oeaw\Controller\request $request
     * @param string $prop1
     * @param string $fieldName
     * @return JsonResponse
     */
    public function autocomplete(request $request, string $prop1, string $fieldName): JsonResponse {
        
        $matches = array();
        $string = $request->query->get('q');
        
        //check the user entered char's
        if(strlen($string) < 3) {            
            return new JsonResponse(array());
        }
        
        //f.e.: depositor
        $propUri = base64_decode(strtr($prop1, '-_,', '+/='));

        // this is the fedora.localhost url
        //$resourceUri = base64_decode(strtr($prop2, '-_,', '+/='));

        if(empty($propUri)){
            return new JsonResponse(array());
        }

        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $fedora = new Fedora($config); 
        //get the property resources
        $rangeRes = null;
        
        try {
            $prop = $fedora->getResourceById($propUri);
            //get the property metadata
            $propMeta = $prop->getMetadata();
            // check the range property in the res metadata
            $rangeRes = $propMeta->getResource(EasyRdfUtil::fixPropName('http://www.w3.org/2000/01/rdf-schema#range'));
        }  catch (\RuntimeException $e){}

        if($rangeRes === null){
            return new JsonResponse(array()); // range property is missing - no autocompletion
        }

        $matchClass = $fedora->getResourcesByProperty('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $rangeRes->getUri());

        // if we want additional properties to be searched, we should add them here:
        $match = array(
            'title'  => $fedora->getResourcesByPropertyRegEx('http://purl.org/dc/elements/1.1/title', $string),
            'name'   => $fedora->getResourcesByPropertyRegEx('http://xmlns.com/foaf/0.1/name', $string),
            'acdhId' => $fedora->getResourcesByPropertyRegEx($config->get('fedoraIdProp'), $string),
        );

        $matchResource = $matchValue = array();
        foreach ($matchClass as $i) {
            $matchResource[] = $i->getUri();
            if (stripos($i->getUri(), $string) !== false) {
                $matchValue[] = $i->getUri();
            }
        }
        foreach ($match as $i) {
            foreach ($i as $j) {
                $matchValue[] = $j->getUri();
            }
        }
        $matchValue = array_unique($matchValue);
        $matchBoth = array_intersect($matchResource, $matchValue);

        foreach ($matchClass as $i) {
            if (!in_array($i->getUri(), $matchBoth)) {
                continue;
            }

            $meta = $i->getMetadata();
            $acdhId = $meta->getResource(EasyRdfUtil::fixPropName($config->get('fedoraIdProp')));
            if(empty($acdhId)){
                continue;
            }
            $acdhId = $acdhId->getUri();

            $label = empty($meta->label()) ? $acdhId : $meta->label();
            $matches[] = ['value' => $acdhId , 'label' => (string)utf8_decode($label)];

            if(count($matches) >= 10){
                 break;
            }
        }

        return new JsonResponse($matches);
    }    
        
    /**
     * 
     * Here the oeaw module menu is generating with the available menupoints
     * 
     * @return array
     */
    public function oeaw_menu(): array {
        $table = array();
        $header = array('id' => t('MENU'));
        $rows = array();
        
        $uid = \Drupal::currentUser()->id();
            
        $link = Link::fromTextAndUrl('List All Root Resource', Url::fromRoute('oeaw_roots'));
        $rows[0] = array('data' => array($link));

        $link1 = Link::fromTextAndUrl('Search by Meta data And URI', Url::fromRoute('oeaw_search'));
        $rows[1] = array('data' => array($link1));
        
        //if the user is anonymus then we hide the add resource menu
        if($uid !== 0){
            $link2 = Link::fromTextAndUrl('Add New Resource', Url::fromRoute('oeaw_multi_new_resource'));
            $rows[2] = array('data' => array($link2));
        }
        
        $table = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'id' => 'oeaw-table',
            ),
        );

        return $table;
    }
        
    
    /**
     * 
     * This will generate the detail view when a user clicked the detail href on a result page
     * 
     * 
     * @param string $uri
     * @param Request $request
     * @return array
     */
    public function oeaw_detail(string $uri, Request $request): array {
        
        if (empty($uri)) {
           return drupal_set_message(t('The uri is missing!'), 'error');
        }
        
        $hasBinary = "";        
       
        // decode the uri hash
        $uri = $this->oeawFunctions->createDetailsUrl($uri, 'decode');
 
        $uid = \Drupal::currentUser()->id();
        
        $rootGraph = $this->oeawFunctions->makeGraph($uri); 
        $rootMeta =  $this->oeawFunctions->makeMetaData($uri);

        if(count($rootMeta) > 0){            
            
            $results = array();
            //get the root table data
            $results = $this->oeawFunctions->createDetailTableData($uri);
            
            if(empty($results)){
                return drupal_set_message(t('The resource has no metadata!'), 'error');
            }
           
        } else {
            return drupal_set_message(t('The resource has no metadata!'), 'error');
        }
      
        //get the childrens
        $fedora = $this->oeawFunctions->initFedora();
        $childF = $fedora->getResourceByUri($uri);
        $childF = $childF->getChildren();

        $childResult = array();
        //get the childrens table data
        if(count($childF) > 0){
            $childResult = $this->oeawFunctions->createChildrenDetailTableData($childF);
        }
        
        $resTitle = $rootGraph->label($uri);
        
        if($resTitle){
            $resTitle->dumpValue('text');
        }else {
            $resTitle = "title is missing";
        }
        
        $editResData = array(
            "editUrl" => $this->oeawFunctions->createDetailsUrl($uri, 'encode'),
            "title" => $resTitle
        );        
      
        $datatable = array(
            '#theme' => 'oeaw_detail_dt',
            '#result' => $results,            
            '#userid' => $uid,            
            '#hasBinary' => $hasBinary,            
            '#childResult' => $childResult,         
            '#editResData' => $editResData,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        );                
        return $datatable;        
    }
    
    
    /**
     * 
     * The searching page FORM
     * 
     * @return type
     */
    public function oeaw_search() {    
       
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\SearchForm');
        return $form;
    }
    
    
    /**
     * 
     * This contains the search page results
     * 
     * @return array
     */
    public function oeaw_resources():array {
        
        $metaKey = $_SESSION['oeaw_form_result_metakey'];
        $metaValue = $_SESSION['oeaw_form_result_metavalue'];
        
        $uid = \Drupal::currentUser()->id();
        //normal string seacrh
       
        $metaKey = $this->oeawFunctions->createUriFromPrefix($metaKey);
        
        $stringSearch = $this->oeawStorage->searchForData($metaValue, $metaKey);
        
        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $fedora = new Fedora($config);
        
        //we will search in the title, name, fedoraid
        $idSearch = array(            
            'title'  => $fedora->getResourcesByPropertyRegEx('http://purl.org/dc/elements/1.1/title', $metaValue),
            'name'   => $fedora->getResourcesByPropertyRegEx(\Drupal\oeaw\connData::$foafName, $metaValue),
            'acdhId' => $fedora->getResourcesByPropertyRegEx($config->get('fedoraIdProp'), $metaValue),
        );        

        $x = 0;
        $data = array();
        $datatable = array();
        
        foreach ($idSearch as $i) {
            
            foreach ($i as $j) {
                //if there is any property which contains the searched value then
                // we get the uri and 
                if(!empty($j->getUri())){
                    //get the resource identifier f.e.: id.acdh.oeaw.ac.at.....                    
                    $identifier = $fedora->getResourceByUri($j->getUri())->getMetadata()->getResource(EasyRdfUtil::fixPropName('http://purl.org/dc/terms/identifier'));
                    
                    if(!empty($identifier)){
                        //get the resources which is part of this identifier
                        $identifier = $identifier->getUri();                        

                        $ids = $this->oeawStorage->searchForData($identifier, $metaKey);
                        
                        //generate the result array
                        foreach($ids as $v){
                            $data[$x]["uri"] = $v["uri"];
                            ;
                            if(empty($v["title"])){                                
                                $v["title"] = "";
                            }
                            $data[$x]["title"] = $v["title"];
                            $x++;
                        }
                    }else {
                        $data[$x]["uri"] = $j->getUri();
                        $data[$x]["value"] = $metaValue;
                        $data[$x]["title"] = $j->getMetadata()->label()->__toString();
                        $x++;
                    }                    
                }                
            }
        }        

        if(!empty($data) && !empty($stringSearch)){            
            $data = array_merge($data, $stringSearch);                        
        }elseif (empty($data)) {            
            $data = $stringSearch;
        }        
   
        if(count($data) > 0){
            $i = 0;            
          
            foreach($data as $value){
                // check that the value is an Url or not
                
                if($value["res"]){
                    $decodeUrl = $this->oeawFunctions->isURL($value["res"], "decode");
                
                    //create details and editing urls
                    if($decodeUrl){
                        $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                        if($uid !== 0){
                            $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                        }
                    }                
                    $res[$i]["uri"] = $value["res"];
                }
                
                $res[$i]["title"] = $value["title"];
                $i++;
            }
             $searchArray = array(
                "metaKey" => $metaKey,
                "metaValue" => $metaValue
            );
            $decodeUrl = "";
            
        }else {
            return drupal_set_message(t('There is no data -> Search'), 'error');    
        }


        $searchArray = array(
            "metaKey" => $metaKey,
            "metaValue" => $metaValue            
        );
        
        $datatable = array(
            '#theme' => 'oeaw_search_res_dt',
            '#result' => $res,
            '#userid' => $uid,
            '#searchedValues' => $searchArray,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', 
                ]
            ]
        );
        
        return $datatable;
       
    }
    
    
    /**
     * 
     * The multi step FORM to create resources based on the 
     * fedora roots and classes      
     * 
     * @return type
     */
    public function multi_new_resource() {        
        return $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\NewResourceOneForm');        
    }
    
    /**
     * 
     *  The editing form, based on the uri resource
     * 
     * @param string $uri
     * @param Request $request
     * @return type
     */
    public function oeaw_editing(string $uri, Request $request) {
        return $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\EditForm');
    }
        
    /**
     * 
     * Get the classes data from the sidebar class list block
     * and display them
     * @return array
     */
    public function oeaw_classes_result(): array{
        
        $datatable = array();
        $data = array();
        $interPathArray = array();
        $classesArr = array();
        $res = array();

        $url = Url::fromRoute('<current>');
        $internalPath = $url->getInternalPath();
        $interPathArray = explode("/", $internalPath);
        
        if($interPathArray[0] == "oeaw_classes_result"){
            
            $searchResult = urldecode($interPathArray[1]);
            $classesArr = explode(":", $searchResult);        
            $property = $classesArr[0];
            $value =  $classesArr[1];
            $uid = \Drupal::currentUser()->id();
        
            if (strpos($value, '(') !== false) {
                $val = explode(' (', $value);
                if(count($val) > 0){
                    $value = $val[0];
                }                
            }
         
            //EasyRdfUtil::fixPropName('http://purl.org/dc/terms/identifier')
            //$data = $this->oeawStorage->getDataByProp("rdf:type", $searchResult);
            $data = $this->oeawStorage->getDataByProp(EasyRdfUtil::fixPropName('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'), $property.':'.$value);
        
            if(count($data) > 0){
                $i = 0;            
                foreach($data as $value){
                    // check that the value is an Url or not            
                    $decodeUrl = $this->oeawFunctions->isURL($value["uri"], "decode");

                    //create details and editing urls
                    if($decodeUrl){
                        $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                        if($uid !== 0){
                            $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                        }
                    }
                    $res[$i]["uri"] = $value["uri"];
                    $res[$i]["title"] = $value["title"];
                    $i++;
                }
                 $searchArray = array(
                    "metaKey" => $classesArr[0],
                    "metaValue" => $classesArr[1]
                );
                $decodeUrl = "";

            }else {
                return drupal_set_message(t('There is no data -> Class List Search'), 'error');    
            }         
            
        }else {
            $searchArray = array();
            $res = array();
        }
        
        $datatable = array(
            '#theme' => 'oeaw_search_class_res_dt',
            '#result' => $res,  
            '#userid' => $uid,
            '#searchedValues' => $searchArray,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', 
                ]
            ]
        );
                
        return $datatable;
       
    } 
    
    
}
