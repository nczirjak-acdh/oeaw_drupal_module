<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DepAgreeOneForm extends DepAgreeBaseForm{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
      
        $form['depositor'] = array(
            '#type' => 'fieldset',
            '#title' => t('<b>Depositor</b>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );
              
        
        $form['depositor']['title'] = array(
            '#type' => 'textfield',
            '#title' => t('Name Title:'),
            
        );
        
        $form['depositor']['l_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Last Name:'),
            
        );
        
        $form['depositor']['f_name'] = array(
            '#type' => 'textfield',
            '#title' => t('First Name:'),
            
        );
        
        $form['depositor']['institution'] = array(
            '#type' => 'textfield',
            '#title' => t('Institution:'),
            
        );
        
        $form['depositor']['city'] = array(
            '#type' => 'textfield',
            '#title' => t('City:'),
            
        );
        
        $form['depositor']['address'] = array(
            '#type' => 'textfield',
            '#title' => t('Address:'),
            
        );
        
        $form['depositor']['zipcode'] = array(
            '#type' => 'textfield',
            '#title' => t('Zipcode:'),
            
        );
        
        $form['depositor']['email'] = array(
            '#type' => 'email',
            '#title' => t('Email:'),
            
        );
        
        $form['depositor']['phone'] = array (
            '#type' => 'tel',
            '#title' => t('Phone'),
            
        );
       
        
        //create the next button to the form second page
        $form['actions']['submit']['#value'] = $this->t('Next');
        
        return $form;
  }
  
   public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
    
    
    $form_state->setRedirect('oeaw_depagree_two');
   }
    
}
