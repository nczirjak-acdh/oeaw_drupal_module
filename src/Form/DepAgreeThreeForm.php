<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DepAgreeThreeForm extends DepAgreeBaseForm{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
        
        $form['depositor_agreement_title'] = array(
            '#markup' => '<h1><b>Deposition agreement</b></h1>',
        );
      
        $form['transfer'] = array(
            '#type' => 'fieldset',
            '#title' => t('<b>Transfer Procedures</b>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );       
        
        $form['transfer']['folder_name'] = array(
            '#type' => 'textarea',
            '#title' => t('Folder name or BagIt name:'),            
        );
        
        $form['transfer']['transfer_date'] = array(
            '#type' => 'textfield',
            '#title' => t('Transfer date:'),
            '#attributes' => array("readonly" => TRUE),
            '#default_value' => date("d-m-Y")            
        );
        
        $form['transfer']['transfer_method'] = array(
            '#type' => 'textfield',
            '#title' => t('Transfer medium and method:'),
            '#description' => $this->t('e.g. hard drive, CD, DVD, USB stick, network transfer'),    
        );
        
        $accMode = array();
        $accMode["PUB"] = "Public content (PUB): free access to the general public without any restriction. The classification of a resource as public content does not mean that the resources may be used for any purpose. The permissible types of use are further detailed by the license accompanying every resource";
        $accMode["ACA"] = "Academic content (ACA): to access the resource the user has to register as an academic user. This is accomplished by authentication with the home identity provider by means of the Identity Federation.";
        $accMode["RES"] = "Restricted content (RES): includes resources with a special access mode. Special authorization rules apply that are detailed in the accompanying metadata record";        
        
        $form['transfer']['access_mode'] = array(
            '#type' => 'radios',
            '#title' => t('Access mode:'),
            '#options' => $accMode,
            '#description' => $this->t(''),
        );
        
        
        $dataValidation = array();
        $dataValidation[0] = "The donor/depository has provided a tab-delimited text file providing full object paths and filenames for the all objects being submitted, with an MD5 checksum for each object.  The repository will perform automated validation.";
        $dataValidation[1] = "Based on incomplete information supplied by the depositor/donor prior to transfer, the repository will carry out selected content and completeness checks to verify that the transmitted data is what is expected, and that it is complete.";
        $dataValidation[2] = "No data validation will be performed on objects submitted.";        
        
        $form['transfer']['data_validation'] = array(
            '#type' => 'radios',
            '#title' => t('Data Validation:'),
            '#options' => $dataValidation,
            '#description' => $this->t(''),
        );
       
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('button'),
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_depagree_two'),
        );
        
        $form['actions']['submit']['#value'] = $this->t('Next');       
        
        
        return $form;
  }
  
   public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
    $form_state->setRedirect('oeaw_depagree_four');
   }
    
}
