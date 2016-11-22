<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;

class EditForm extends FormBase {

    public function getFormId() {
        return "edit_form";
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        
        $form['fields']['modules'] = array(
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => t('Add triples'),
            '#description' => t('If you want to add new triples then you can do it here:'),
            '#prefix' => '<div id="modules-wrapper">',
            '#suffix' => '</div>',
        );

        error_log("itt: ");
        error_log($form_state->get('fields_count'));
        $max = $form_state->get('fields_count');
        
        
        if(is_null($max)) {
            $max = 0;
            $form_state->set('fields_count', $max);
        }
        
        
        $propertys = \Drupal\oeaw\oeawStorage::getAllPropertyForSearch();        
        //modify the prefixes
        $searchTerms = \Drupal\oeaw\oeawFunctions::createPrefixesFromObject($propertys);    
        
        // Add elements that don't already exist
        for($delta=0; $delta<=$max; $delta++) {
            if (!isset($form['fields']['modules'][$delta])) {
                
                $element = array(
                    '#type' => 'select',
                    '#title' => $this->t('Select Triple Class'),
                    '#required' => TRUE,
                    "#options" =>
                        $searchTerms,                    
                );                                
                $form['fields']['modules'][$delta]['trpClass_'.$delta] = $element;
                
                $element = array(
                    '#type' => 'textfield',
                    '#title' => t('Triple value'),                    
                    '#required' => TRUE,
                    '#suffix' => '<hr />',
                );                
                $form['fields']['modules'][$delta]['trpValue_'.$delta] = $element;                
            }
        }

        $form['fields']['modules']['add'] = array(
            '#type' => 'submit',
            '#name' => 'addfield',
            '#value' => t('Add one more field'),
            '#submit' => array(array($this, 'addfieldsubmit')),
            '#ajax' => array(
                'callback' => array($this, 'addfieldCallback'),
                'wrapper' => 'modules-wrapper',
                'effect' => 'fade',
            ),
        );
        
        $form['fields']['modules']['remove'] = array(
            '#type' => 'submit',
            '#name' => 'removefield',
            '#value' => t('Remove last field'),
            '#submit' => array(array($this, 'removefieldsubmit')),
            '#ajax' => array(
                'callback' => array($this, 'removefieldCallback'),
                'wrapper' => 'modules-wrapper',
                'effect' => 'fade',
            ),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit sample'),
        );


        return $form;
    }
    
    /**
    * Ajax submit to add new field.
    */
    public function addfieldsubmit(array &$form, FormStateInterface &$form_state) {
        $max = $form_state->get('fields_count') + 1;        
        $form_state->set('fields_count',$max);
        $form_state->setRebuild(TRUE);
    }
    
    public function removefieldsubmit(array &$form, FormStateInterface &$form_state) {
        
        $max = $form_state->get('fields_count');
        unset($form['fields']['modules'][$max]['trpClass_'.$max]);
        unset($form['fields']['modules'][$max]['trpValue_'.$max]);
        
        $max = $form_state->get('fields_count') - 1;            
        $form_state->set('fields_count',$max);        
        $form_state->setRebuild(TRUE);
    }

   /**
    * Ajax callback to add new field.
    */
    public function addfieldCallback(array &$form, FormStateInterface &$form_state) {
        return $form['fields']['modules'];
    }
    
    public function removefieldCallback(array &$form, FormStateInterface &$form_state) {
        return $form['fields']['modules'];
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
      /*  if (empty($form_state->getValue('file_sparql'))) {
            $form_state->setErrorByName('file_sparql', $this->t('Please upload a sparql file'));
        }*/
/*
        if (empty($form_state->getValue('file'))) {
            $form_state->setErrorByName('file', $this->t('Please upload a file'));
        }
 * 
 */
/*
        if (empty($form_state->getValue('roots'))) {
            $form_state->setErrorByName('roots', $this->t('Please select a root element'));
        } 
 */
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $sbmtNewVal = $form_state->get('fields_count');

        //new triples added by the user
        $newTriples = array();
        
        for ($x = 0; $x <= $sbmtNewVal; $x++) {            
            $class = $form_state->getValue('trpClass_'.$x);
            $value = $form_state->getValue('trpValue_'.$x);
            $newTriples[$class] = $value;
        }     
        
        //
        
        echo "<pre>";
        var_dump($newTriples);
        echo "</pre>";

        die();



        //get the uploaded files values
        $sparqlFileID = $form_state->getValue('file_sparql');
        $sparqlFileID = $sparqlFileID[0];
        
        $fileID = $form_state->getValue('file');
        
        $fileID = $fileID[0];
        // get the root select value
        //$root = $form_state->getValue('roots');

        //create file object with file data        
        $sfObj = file_load($sparqlFileID);
        $fObj = file_load($fileID);

        //get the temp file uri
        $sfUri = $sfObj->getFileUri();
        $fUri = $fObj->getFileUri();
        $mime = mime_content_type($fUri);

        //get file content
        $sfContent = file_get_contents($sfUri);
        $fContent = file_get_contents($fUri);

        //insert the content with the transaction based curl
        $saveFile = \Drupal\oeaw\oeawStorage::insertDataToFedora($fContent, $sfContent, false, $mime);

        if ($saveFile != false) {
            $_SESSION['newFedoraUri'] = $saveFile;

            $url = Url::fromRoute('oeaw_new_resource_result');
            $form_state->setRedirectUrl($url);
        } else {
            drupal_set_message(t('An error occurred and processing did not complete. Please check your sparql query!'), 'error');
        }
    }

}
