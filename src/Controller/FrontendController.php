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
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use zozlak\util\Config;
use EasyRdf_Graph;
use EasyRdf_Resource;
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
    
    
    /* 
     *
     * The root Resources list     
     *
     * @return array with datatable values and template name
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
            for ($i = 0; $i < count($result); $i++) {
                foreach($result[$i] as $key => $value){
                    // check that the value is an Url or not
                    
                    $decodeUrl = $this->oeawFunctions->isURL($value, "decode");
                    
                    //create details and editing urls
                    if($decodeUrl){ 
                        
                        $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                        if($uid !== 0){
                            $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                        } 
                    }
                    $res[$i][$key] = $value; 
                }
                $decodeUrl = "";
            }
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
    
    /* 
     *
     * Here the oeaw module menu is generating with the available menupoints
     *
     * @return array with drupal core table values
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
            $link2 = Link::fromTextAndUrl('Add New Resource', Url::fromRoute('oeaw_newresource_one'));
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
    
    
    /* 
     *
     * this generates the detail view when a user clicked the detail href on a reuslt page
     *
     * @param string $uri : the encoded uri from the url, to we can identify the selected resource
     * 
     * @param Request $request : drupal core function
     *
     * @return array with datatable values and template name
    */
    public function oeaw_detail(string $uri, Request $request): array {
        
        if (empty($uri)) {
           return drupal_set_message(t('The uri is missing!'), 'error');
        }
       
        // decode the uri hash
        $uri = $this->oeawFunctions->createDetailsUrl($uri, 'decode');

        $uid = \Drupal::currentUser()->id();
        
        $rootGraph = $this->oeawFunctions->makeGraph($uri);
        
        /*
        $graphSerial = $rootGraph->serialise('json');
        $decodedGraph = json_decode($graphSerial);
        $jGraph = $decodedGraph->{$uri};
        $jsonArray = array();
        $i = 1;
        $jsonArray[0] = array(
                "id" => $uri,
                "name" => $uri);
        
        foreach($jGraph as $key => $value){
            
            $jsonArray[$i] = array("id" => $key,
            "name" => $key);

            if(is_array($value)){

                foreach($value as $v){
                    $jsonArray[$i]["adjacencies"][] = 
                            array(
                                "nodeTo" => $v->value,
                                "nodeFrom" => $key,
                                "data" => array(
                                    '$color' => "#557EAA"
                                )
                            );
                }
                
                $jsonArray[0]["adjacencies"][] = 
                    array(
                        "nodeTo" => $key,
                        "nodeFrom" => $uri,
                        "data" => array(
                            '$color' => "#557EAA"
                        )
                    );
            }
            $i++;
        }
        */
        //$rootMetaAll =  \Drupal\oeaw\oeawFunctions::makeMetaData($uri)->all(EasyRdfUtil::fixPropName("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"));
        
        $rootMeta =  $this->oeawFunctions->makeMetaData($uri);
        
        if(count($rootMeta) > 0){
            // get the table data by the details uri from the URL
            $i = 0;
            $results = array();
            $hasBinary = "";
            $hasImage = "";
            foreach($rootMeta->propertyUris($uri) as $v){

                foreach($rootMeta->all(EasyRdfUtil::fixPropName($v)) as $item){
                    
                    // if there is a thumbnail
                    if($v == "http://xmlns.com/foaf/spec/thumbnail"){ 
                        if($item){
                            $imgData = $this->oeawStorage->getImage($item);                            
                            if(count($imgData) > 0){
                                $hasImage = $imgData;
                            }
                        }                        
                    }
                    
                    if(get_class($item) == "EasyRdf_Resource"){
                        $results[$i]["property"] = $v;
                        $results[$i]["value"][] = $item->getUri();
                        if($item->getUri() == "http://fedora.info/definitions/v4/repository#Binary"){ $hasBinary = $uri;}
                        
                    }else if(get_class($item) == "EasyRdf_Literal"){
                        $results[$i]["property"] = $v;
                        $results[$i]["value"] = $item->__toString();
                    }else {
                        $results[$i]["property"] = $v;
                        $results[$i]["value"] = $item;
                    }
                }
                $i++;
            }
        } else {
            return drupal_set_message(t('The resource has no metadata!'), 'error');
        }
        
        $header = array_keys($results[0]);     
        
        //get the childrens
        $fedora = $this->oeawFunctions->initFedora();
        $childF = $fedora->getResourceByUri($uri);
        $childF = $childF->getChildren();
        
        $i = 0;
        if($childF){
            foreach($childF as $r){
                
                $childResult[$i]['uri']= $r->getUri();
                $childResult[$i]['title']= $r->getMetadata()->label();
                $decUrlChild = $this->oeawFunctions->isURL($r->getUri(), "decode");
                
                $childResult[$i]['detail'] = "/oeaw_detail/".$decUrlChild;
                if($uid !== 0){
                    $childResult[$i]['edit'] = "/oeaw_editing/".$decUrlChild;                            
                } 
                $i++;
            }
        }else {
            $childResult = "";
            $childHeader = "";
        }
        
        
        $resEditUrl = $this->oeawFunctions->createDetailsUrl($uri, 'encode');
        
        $resTitle = $rootGraph->label($uri);
        
        if($resTitle){
            $resTitle->dumpValue('text');
        }else {
            $resTitle = "title is missing";
        }
        
        $editResData = array(
            "editUrl" => $resEditUrl, 
            "title" => $resTitle
        );        
      

        $datatable = array(
            '#theme' => 'oeaw_detail_dt',
            '#result' => $results,
            '#header' => $header,
            '#userid' => $uid,
//            '#jsonGraph' => $oJson,
            '#jsonGraph' => NULL,
            '#hasBinary' => $hasBinary,
            '#hasImage' => $hasImage,
            '#childResult' => $childResult,
            '#childHeader' => $childHeader,
            '#editResData' => $editResData,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        );                
        return $datatable;        
    }
    
    
    /* 
     *
     * The searching page FORM
     *
     * @return Drupal Form 
    */

    public function oeaw_search() {    
       
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\SearchForm');
        return $form;
    }
    
    /* 
     *
     * This contains the search page results
     *
     * @return array with drupal core table generating
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
            'name'   => $fedora->getResourcesByPropertyRegEx('http://xmlns.com/foaf/0.1/name', $metaValue),
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
        
        for ($i = 0; $i < count($data); $i++) {            
            foreach($data[$i] as $key => $value){
                // check that the value is an Url or not
                $decodeUrl = $this->oeawFunctions->isURL($value, "decode");
                
                //create details and editing urls
                if($decodeUrl !== false){                             
                    $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                    if($uid !== 0){
                       $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                    } 
                }
                $res[$i][$key] = $value; 
            }
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
    
    /* 
     *
     * The multi step FORM to create resources based on the 
     * fedora roots and classes      
     *
     * @return Drupal Form 
    */

    public function multi_new_resource() {
        
        $uid = \Drupal::currentUser()->id();
        
        if($uid !== 0){
            $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\NewResourceOneForm');        
            return $form;
        }else {
            return drupal_set_message(t('You have no rights for this page!'), 'error');    
        }
        
    }

    /* 
     *
     * The editing form, based on the uri resource
     *
     * @param string $uri : the encoded uri from the url, to we can identify the selected resource
     * 
     * @param Request $request : drupal core function
     *
     *
     * @return Drupal Form 
    */
    
    public function oeaw_editing(string $uri, Request $request) {
     
        $uid = \Drupal::currentUser()->id();
        if($uid !== 0){            
            $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\EditForm');            
            return $form;
            
        } else {
            return drupal_set_message(t('You have no rights for this page!'), 'error');    
        }
    }
    
    
    /* 
     * Get the classes data from the sidebar class list block
     * and display them
     *     
    */
    public function oeaw_classes_result(): array{
        

        $url = Url::fromRoute('<current>');
        $internalpath = $url->getInternalPath();
        $internalpath = explode("/", $internalpath);
        
        $datatable = array();
        $data = array();
        
        if($internalpath[0] == "oeaw_classes_result"){
            
            $searchResult = urldecode($internalpath[1]);
            $classesArr = explode(":", $searchResult);        
            $property = $classesArr[0];
            $value =  $classesArr[1];
            $uid = \Drupal::currentUser()->id();
            
            $data = $this->oeawStorage->getDataByProp("rdf:type", $searchResult);
            
            if(count($data) > 0){
                $res = array();
                
                for ($i = 0; $i < count($data); $i++) {            
                    foreach($data[$i] as $key => $value){
                        // check that the value is an Url or not                    
                        $decodeUrl = $this->oeawFunctions->isURL($value, "decode");

                        //create details and editing urls
                        if(!empty($decodeUrl)){                            
                            $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                            if($uid !== 0){
                               $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                            } 
                        }
                        $res[$i][$key] = $value; 
                    }
                }

                $searchArray = array(
                    "metaKey" => $classesArr[0],
                    "metaValue" => $classesArr[1]
                );
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
