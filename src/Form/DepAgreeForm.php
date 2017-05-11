<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DepAgreeForm extends FormBase{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form['depositor_agreement_title'] = array(
            '#markup' => '<h1><b>Deposition agreement</b></h1>',
        );
        
        $form['depositor_title'] = array(
            '#markup' => '<h2><b>Depositor</b></h2>',
        );
        
        $form['title'] = array(
            '#type' => 'textfield',
            '#title' => t('Title:'),
            '#required' => TRUE,
        );
        
        $form['l_name_f_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Last Name, First Name:'),
            '#required' => TRUE,
        );
        
        $form['institution'] = array(
            '#type' => 'textfield',
            '#title' => t('Institution:'),
            '#required' => TRUE,
        );
        
        $form['city'] = array(
            '#type' => 'textfield',
            '#title' => t('City:'),
            '#required' => TRUE,
        );
        
        $form['address'] = array(
            '#type' => 'textfield',
            '#title' => t('Address:'),
            '#required' => TRUE,
        );
        
        $form['zipcode'] = array(
            '#type' => 'textfield',
            '#title' => t('Zipcode:'),
            '#required' => TRUE,
        );
        
        $form['email'] = array(
            '#type' => 'email',
            '#title' => t('Email:'),
            '#required' => TRUE,
        );
        
        $form['phone'] = array (
            '#type' => 'tel',
            '#title' => t('Phone'),
        );
        
        
        $form['definitions_title'] = array(
            '#markup' => '<h2><b>Definitions</b></h2>',
        );
        
        
        $form['acdh_repo_id'] = array(
            '#type' => 'textfield',
            '#title' => t('ACDH-repo ID:'),
            '#required' => TRUE,
            '#description' => $this->t('string used as an internal identifier for the deposited resources'),
        );
        
        $form['ipr'] = array(
            '#type' => 'textfield',
            '#title' => t('Intellectual Property Rights (IPR):'),
            '#required' => TRUE,
            '#description' => $this->t('Intellectual property rights including, but not limited to copyrights, related (or neighbouring) rights and database rights'),
        );
        
        $form['metadata'] = array(
            '#type' => 'textarea',
            '#title' => t('Metadata:'),
            '#required' => TRUE,
            '#description' => $this->t('is the information that may serve to identify, discover, interpret, manage, and describe content and structure.'),
        );
        $form['preview'] = array(
            '#type' => 'textfield',
            '#title' => t('Preview:'),
            '#required' => TRUE,
            '#description' => $this->t('A reduced size or length audio and/or visual representation of Content, in the form of one or more images, text files, audio files and/or moving image files.'),
        );
        
        $form['public_domain'] = array(
            '#type' => 'select',
            '#options' => array(
                'Content' => t('Content'),
                'Metadata' => t('Metadata'),
                'Other' => t('Other'),
            ),
            '#title' => t('Public Domain:'),
            '#required' => TRUE,
            '#description' => $this->t(''),
        );
        
         $form['resource'] = array(
                '#type' => 'managed_file',
                '#title' => t('Resource'),                
                '#upload_validators' => array(
                    'file_validate_extensions' => array('xml doc txt simplified docx pdf jpg png tiff gif bmp'),
                 ),
                '#description' => t('Upload a file, allowed extensions: XML, CSV, and images etc....'),
            );    
        
        $form['Third Party'] = array(
            '#type' => 'textarea',
            '#title' => t('Third Party:'),
            '#required' => TRUE,
            '#description' => $this->t('Any natural or legal person who is not party to this agreement'),
        );
        
        $form['candidate_confirmation'] = array (
            '#type' => 'radios',
            '#required' => TRUE,
            '#title' => ('I read and agree the ....'),
            '#options' => array(
                'Yes' =>t('Yes'),
                'No' =>t('No')
            ),
        );
        
        
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );
        
        return $form;
  }
  
   public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
   }
    
}
