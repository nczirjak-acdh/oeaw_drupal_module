<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;



class AddForm extends FormBase
{
     
    
    public function getFormId()
    {
        return "add_form";
    }
    
 /**
   * {@inheritdoc}.
   */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {      
        
        $form['root_sparql'] = array(
            '#type' => 'file', 
            '#title' => t('ROOT SPARQL'), 
            '#description' => t('Upload a file, allowed extensions: SPARQL'),
        );
   
        $form['child_sparql'] = array(
            '#type' => 'file', 
            '#title' => t('CHILD SPARQL'), 
            '#description' => t('Upload a file, allowed extensions: SPARQL'),
        );
        $form['file_sparql'] = array(
            '#type' => 'file', 
            '#title' => t('FILE SPARQL'), 
            '#description' => t('Upload a file, allowed extensions: SPARQL'),
        );
        $form['file'] = array(
            '#type' => 'file', 
            '#title' => t('FILE'), 
            '#description' => t('Upload a file, allowed extensions: XML, CSV, etc....'),
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
        
        /*if (strlen($form_state->getValue('candidate_number')) < 10) {
            $form_state->setErrorByName('candidate_number', $this->t('Mobile number is too short.'));
        }*/
        
    }
  
  
  
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        //file_get_contents($filename);
        
        echo "<pre>";
        var_dump($form);
        echo "</pre>";
        echo "----------------------------------------------------------";
        

        die();



        // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
        foreach ($form_state->getValues() as $key => $value) {
        //  drupal_set_message($key . ': ' . $value);
        
            $_SESSION['oeaw_form_result_'.$key] = $value;            
            $url = Url::fromRoute('oeaw_resource_list');
            $form_state->setRedirectUrl($url);           
           
        }
    }
    
    
    private function getRequestFile(Request $request) {
        $file = $request->files->get('file');
        if (empty($file)) {
            throw new \RuntimeException('No file provided or file upload failed.');
        }
        return array(
            'path' => $file->getPathName(),
            'name' => $file->getFileName(),
            'mime' => $file->getClientMimeType()
        );
    }

  
}

