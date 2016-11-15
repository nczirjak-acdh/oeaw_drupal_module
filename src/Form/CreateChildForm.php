<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\oeaw\oeawStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class CreateChildForm extends FormBase
{
    public function getFormId()
    {
        return "create_child_form";
    }
    
    /**
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {   
        
        $current_uri = \Drupal::request()->getRequestUri();
        $current_uri = str_replace('oeaw_create_child/', '', $current_uri);
        
        $current_uri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($current_uri, "encode");
        
        var_dump($current_uri);
        
        $form['#attributes']['enctype'] = "multipart/form-data";
   
        
        $form["roots"] = array(
            "#type" => "select", 
            "#title" => t("SELECT YOUR ROOT ELEMENT"),
            "#options" =>array($array[$current_uri] = $current_uri)
        );
        
        $form['file'] = array(
            '#type' => 'managed_file', 
            '#title' => t('FILE'), 
            '#upload_validators' => array(
                'file_validate_extensions' => array('xml doc txt simplified docx'),
             ),
            '#description' => t('Upload a file, allowed extensions: XML, CSV, etc....'),
        );
        
        $form['file_sparql'] = array(
            '#type' => 'managed_file', 
            '#title' => t('FILE SPARQL'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('sparql'),
             ),
            '#description' => t('Upload a file, allowed extensions: SPARQL'),
        );
        
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Save'),
          '#button_type' => 'primary',
        );
        
        return $form;
    }
    
    
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {        
        if (empty($form_state->getValue('file_sparql'))) {
            $form_state->setErrorByName('file_sparql', $this->t('Please upload a file'));
        }
        
        if (empty($form_state->getValue('file'))) {
            $form_state->setErrorByName('file', $this->t('Please upload a file'));
        }
        
        if (empty($form_state->getValue('roots'))) {
            $form_state->setErrorByName('roots', $this->t('Please select a root element'));
        }                
    }
  
    public function submitForm(array &$form, FormStateInterface $form_state) {        
        
        //get the uploaded files values
        $sparqlFileID = $form_state->getValue('file_sparql');
        $sparqlFileID = $sparqlFileID[0];
        $fileID = $form_state->getValue('file');
        $fileID = $fileID[0];
        // get the root select value
        $root = $form_state->getValue('roots');
        
        //create file object with file data        
        $sfObj = file_load($sparqlFileID);
        $fObj = file_load($fileID);
        
        //get the temp file uri
        $sfUri = $sfObj->getFileUri(); 
        $fUri = $fObj->getFileUri(); 
        
        //get file content
        $sfContent = file_get_contents($sfUri);
        $fContent = file_get_contents($fUri);
        
        //insert the content
        $saveFile = \Drupal\oeaw\oeawStorage::insertDataToFedora($fContent, $sfContent, $root);
        
        if($saveFile != false){
            $_SESSION['newFedoraUri'] = $saveFile;
            
            $url = Url::fromRoute('oeaw_new_resource_result');            
            $form_state->setRedirectUrl($url);           
        } else {
            throw new \Exception('Error during the saving');                         
        }
    }
  
}

