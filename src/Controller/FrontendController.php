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
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\SafeMarkup;

class FrontendController extends ControllerBase {
    
        
    /* 
     *
     * Here the oeaw module menu is generating with the availbale menuopints
     *
     * @return array with drupal core table values
    */
    public function oeaw_menu() {
        
        $header = array('id' => t('MENU'));
        $rows = array();

        $link = Link::fromTextAndUrl('List All Root Resource', Url::fromRoute('oeaw_roots'));
        $rows[0] = array('data' => array($link));

        $link1 = Link::fromTextAndUrl('Search by Meta data And URI', Url::fromRoute('oeaw_search'));
        $rows[1] = array('data' => array($link1));

        $link2 = Link::fromTextAndUrl('Add New Resource', Url::fromRoute('oeaw_newresource_one'));
        $rows[2] = array('data' => array($link2));        
        
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
     * The root Resources list     
     *
     * @return array with datatable values and template name
    */

    public function roots_list() {
        
        // get the root resources
        $result = \Drupal\oeaw\oeawStorage::getRootFromDB();
        //generate the header values
        $header = array_keys($result[0]);
       
        for ($i = 0; $i < count($result); $i++) {            
            foreach($result[$i] as $key => $value){
                // check that the value is an Url or not
                $decodeUrl = \Drupal\oeaw\oeawFunctions::isURL($value, "decode");
                
                //create details and editing urls
                if($decodeUrl !== false){                             
                     $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                     $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                }
                $res[$i][$key] = $value; 
            }
        }
        //create the datatable values and pass the twig template name what we want to use
        $datatable = array(
            '#theme' => 'oeaw_root_dt',
            '#result' => $res,
            '#header' => $header,
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
     * this generates the detail view when a user clicked the detail href on a reuslt page
     *
     * @param string $uri : the encoded uri from the url, to we can identify the selected resource
     * 
     * @param Request $request : drupal core function
     *
     * @return array with datatable values and template name
    */
    public function oeaw_detail(string $uri, Request $request) {
        
        if (empty($uri)) {
            return false;
        }
        
        // decode the uri hash
        $uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($uri, 'decode');

        // get the table data by the details uri from the URL
        $result = \Drupal\oeaw\oeawStorage::getAllPropertyByURI($uri);
        
        $header = array_keys($result[0]);
       
        for ($i = 0; $i < count($result); $i++) {
            
            foreach($result[$i] as $key => $value)
            {
                $decodeUrl = \Drupal\oeaw\oeawFunctions::isURL($value, "decode");
                
                if($decodeUrl !== false){                             
                     $res[$i]['detail'] = "/oeaw_detail/".$decodeUrl;
                     $res[$i]['edit'] = "/oeaw_editing/".$decodeUrl;
                }
                $res[$i][$key] = $value; 
            }
        }
        
        //get the root identifier to i can get the children elements
        $rootIdentifier = \Drupal\oeaw\oeawStorage::getValueByUriProperty($uri, 'dct:identifier');

        if(!empty($rootIdentifier)){
            //get the childrens data by the root 
           $childrenData = \Drupal\oeaw\oeawStorage::getChildrenPropertyByRoot($rootIdentifier[0]["value"]);
        
            $childHeader = array_keys($childrenData[0]);

            for ($x = 0; $x < count($childrenData); $x++) {

                foreach($childrenData[$x] as $keyC => $valueC)
                {
                    $decodeUrlC = \Drupal\oeaw\oeawFunctions::isURL($valueC, "decode");

                    if($decodeUrlC !== false){                             
                         $childResult[$x]['detail'] = "/oeaw_detail/".$decodeUrlC;
                         $childResult[$x]['edit'] = "/oeaw_editing/".$decodeUrlC;
                    }
                    $childResult[$x][$keyC] = $valueC; 
                }
            }
        } else {
            $childResult = "";
            $childHeader = "";
        }
        
       
        $datatable = array(
            '#theme' => 'oeaw_detail_dt',
            '#result' => $result,
            '#header' => $header,
            '#childResult' => $childResult,
            '#childHeader' => $childHeader,
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
     * This will contains the search page results
     *
     * @return array with drupal core table generating
    */

    public function oeaw_resources() {
        
        $formData = $_SESSION['oeaw_form_result'];
        $formUri = $_SESSION['oeaw_form_result_uri'];
        $metaKey = $_SESSION['oeaw_form_result_metakey'];
        $metaValue = $_SESSION['oeaw_form_result_metavalue'];

        if (!empty($formUri)) {
            $result = \Drupal\oeaw\oeawStorage::getDefPropByURI($formUri, $metaKey, $metaValue);
        } else {
            $result = \Drupal\oeaw\oeawStorage::getDataByProp($metaKey, $metaValue);
        }

        $tableResult = \Drupal\oeaw\oeawFunctions::generateTable($result, $metaKey, true);

        if ($tableResult == false) {
            $error_msg = drupal_set_message($this->t('Data is not available, please change your searching criteria!! <br> <a href="/oeaw_all">go back</a>'), 'error');
            return $error_msg;
        }

        return $tableResult;
    }
    
    /* 
     *
     * The multi step FORM to create resources based on the 
     * fedora roots and classes      
     *
     * @return Drupal Form 
    */

    public function multi_new_resource() {
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\NewResourceOneForm');
        
        return $form;
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
    
    public function oeaw_editing($uri, Request $request) {
        
        $uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($uri, 'decode');
        $data = \Drupal\oeaw\oeawStorage::getAllPropertyByURI($uri);

        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\EditForm');
        return $form;
        
    }
    
    
}
