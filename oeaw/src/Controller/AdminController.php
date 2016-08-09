<?php
/**
@file
Contains \Drupal\oeaw\Controller\AdminController.
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



class AdminController extends ControllerBase 
{
    
    /*
     * 
     * Detail view after the user clicked on the details button
     * 
     */
    public function oeaw_detail($uri, Request $request) 
    {
        if(empty($uri)) { return false; }
 

        $uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($uri, 'encode');        
        $data = \Drupal\oeaw\oeawStorage::getPropertyByURI($uri);     
        $table = \Drupal\oeaw\oeawFunctions::generateTable($data, $text = "root");
        $data2 = \Drupal\oeaw\oeawStorage::getChildrenPropertyByRoot($uri);
        $table2 = \Drupal\oeaw\oeawFunctions::generateTable($data2, $text = "child resources");
        
        return array($table, $table2);
    }      
      
    
    /* 
     * 
     *  at kell nevezni mert ez a meta es uri kereso felulet
     */
    public function meta_uri_search()
    {        
        $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\SearchForm');                            
        return $form;                
    }
    
    /*
     *  
     * A mostani all_list utani keresonek az eredmeny oldala
     * 
     */
    public function resource_list()
    {
        $formData = $_SESSION['oeaw_form_result'];
        $formUri = $_SESSION['oeaw_form_result_uri'];        
        $metaKey = $_SESSION['oeaw_form_result_metakey'];
        $metaValue = $_SESSION['oeaw_form_result_metavalue'];
        
        if(!empty($formUri))
        {            
            $result = \Drupal\oeaw\oeawStorage::getDefPropByURI($formUri, $metaKey, $metaValue);        
        }
        else
        {
            $result = \Drupal\oeaw\oeawStorage::getDataByProp($metaKey, $metaValue);
        }
        
        $result2 = \Drupal\oeaw\oeawFunctions::generateTable($result, $metaKey);
        
        if($result2 == false)
        {
            $error_msg = drupal_set_message($this->t('Data is not available, please change your searching criteria!! <br> <a href="/oeaw_all">go back</a>'), 'error');            
            return $error_msg;
        }        
     
        return $result2;        
    }
    
    /*
     * root menu
     */
    public function roots_list()
    {
         $result = \Drupal\oeaw\oeawStorage::getRootFromDB();                      
         $table = \Drupal\oeaw\oeawFunctions::generateTable($result);
         
        
         return array($table);
    }
    
    /*
     * Here will be the new resource uploadin
     */
    public function new_resource()
    {
        die("new_resource");
    }
  
    /*
     * Main Menu
     * 
     */
    
    public function oeaw_menu()
    {
        // Table header.
        $header = array('id' => t('MENU'));        
        $rows = array();
        
        $link = Link::fromTextAndUrl('List All Root Resource',  Url::fromRoute('oeaw_roots'));                   
        $rows[0] = array('data' => array($link));
        
        $link1 = Link::fromTextAndUrl('Search by Meta data And URI', Url::fromRoute('oeaw_meta_uri_search'));
        $rows[1] = array('data' => array( $link1));
        
        $link2 = Link::fromTextAndUrl('Add New', Url::fromRoute('oeaw_new_resource'));
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

    
    
  
}
