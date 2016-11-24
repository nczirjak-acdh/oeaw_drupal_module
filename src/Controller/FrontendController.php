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
     * Main Menu     
     */

    public function oeaw_menu() {
        //$perms = array_keys(\Drupal::service('user.permissions')->getPermissions());
        
        //var_dump($perms);
        // Table header.
        $header = array('id' => t('MENU'));
        $rows = array();

        $link = Link::fromTextAndUrl('List All Root Resource', Url::fromRoute('oeaw_roots'));
        $rows[0] = array('data' => array($link));

        $link1 = Link::fromTextAndUrl('Search by Meta data And URI', Url::fromRoute('oeaw_meta_uri_search'));
        $rows[1] = array('data' => array($link1));

        $link2 = Link::fromTextAndUrl('Add New Root Resource', Url::fromRoute('oeaw_new_resource'));
        $rows[2] = array('data' => array($link2));

        $link3 = Link::fromTextAndUrl('Add New Resource', Url::fromRoute('oeaw_newresource_one'));
        $rows[3] = array('data' => array($link3));        
        
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
     * root resources menupoint
     */

    public function roots_list() {
        
        $result = \Drupal\oeaw\oeawStorage::getRootFromDB();
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
        
        $datatable = array(
            '#theme' => 'oeaw_root_dt',
            '#result' => $res,
            '#header' => $header,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        );
        
        
        return $datatable;
        
        /*$result = \Drupal\oeaw\oeawStorage::getRootFromDB();
        $table = \Drupal\oeaw\oeawFunctions::generateTable($result);

        return array($table);*/
    }
    
    
    /*
     * 
     * The detail view after the user clicked on 
     * the details button in the results table
    */
    public function oeaw_detail(string $uri, Request $request) {
        
        if (empty($uri)) {
            return false;
        }
        
        // decode the uri hash
        $uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($uri, 'decode');

        // get the table data by the details uri from the URL
        $result = \Drupal\oeaw\oeawStorage::getPropertyByURI($uri);
        
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
        
        $childrenData = \Drupal\oeaw\oeawStorage::getChildrenPropertyByRoot($uri);
        $childHeader = array_keys($childrenData[0]);
        
        for ($x = 0; $x < count($childrenData); $x++) {
            
            foreach($childrenData[$x] as $keyC => $valueC)
            {
                $decodeUrlC = \Drupal\oeaw\oeawFunctions::isURL($valueC, "decode");
                
                if($decodeUrlC !== false){                             
                     $resC[$x]['detail'] = "/oeaw_detail/".$decodeUrlC;
                     $resC[$x]['edit'] = "/oeaw_editing/".$decodeUrlC;
                }
                $resC[$x][$keyC] = $valueC; 
            }
        }

        $datatable = array(
            '#theme' => 'oeaw_detail_dt',
            '#result' => $res,
            '#header' => $header,
            '#childResult' => $resC,
            '#childHeader' => $childHeader,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        );
        
        
        return $datatable;
        
        
        
        

       
       
        
        
        if (!empty($childrenData)) {
            $table2 = \Drupal\oeaw\oeawFunctions::generateTable($childrenData, $text = "child resources", true);
        }

        return array($newText, $table, $table2);
    }
    
    
    /*
    * Meta and uri search page
    */

    public function meta_uri_search() {
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\SearchForm');
        return $form;
    }
    
    /*
     * The Meta search page Result page
     */

    public function resource_list() {
        
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
    * multi step new resource form
    */

    public function multi_new_resource() {
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\NewResourceOneForm');
        return $form;
    }

    /*
    * the editing form
    */
    
    public function oeaw_editing($uri, Request $request) {
        
        $uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($uri, 'decode');
        $data = \Drupal\oeaw\oeawStorage::getPropertyByURI($uri);

        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\EditForm');
        return $form;
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
////////////////////////////////////////////////////////////////////////////////
/*
 * 
 * 
 * NOT USED
 * 
 * 
 */
   
   

    /*
     * New resource uploading
     */

    public function new_resource() {
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\AddForm', $uri);
        return $form;
    }
   
    
    public function delete_resource() {
        
        $result = \Drupal\oeaw\oeawStorage::getRootFromDB();
        $header = array_keys($result[0]);
       
        
        
        
        $datatable = array(
            '#theme' => 'hello_page',
            '#result' => $result,
            '#header' => $header,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        );
        
        
        return $datatable;
        
        
        //$form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\AddForm');
        //return $form;
    }

    /*
     * New Resource result page
     */

    public function new_resource_result() {
        $msg = drupal_set_message($this->t('Your data saved : ' . $_SESSION['newFedoraUri']), 'information');
        return $msg;
    }

    /*
     * New resource uploading
     */

    public function create_child_resource() {
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\CreateChildForm');
        return $form;
    }

    
}
