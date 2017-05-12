<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DepAgreeFourForm extends DepAgreeBaseForm{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
        
        $form['depositor_agreement_title'] = array(
            '#markup' => '<h1><b>Deposition agreement</b></h1>',
        );
      
        $form['creators'] = array(
            '#type' => 'fieldset',
            '#title' => t('<h2><b>Creators</b></h2>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );       
        
        $form['creators']['title'] = array(
            '#type' => 'textfield',
            '#title' => t('Name Title:'),
            
        );
        
        $form['creators']['l_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Last Name:'),
            
        );
        
        $form['creators']['f_name'] = array(
            '#type' => 'textfield',
            '#title' => t('First Name:'),
            
        );
        
        $form['creators']['institution'] = array(
            '#type' => 'textfield',
            '#title' => t('Institution:'),
            
        );
        
        $form['creators']['city'] = array(
            '#type' => 'textfield',
            '#title' => t('City:'),
            
        );
        
        $form['creators']['address'] = array(
            '#type' => 'textfield',
            '#title' => t('Address:'),
            
        );
        
        $form['creators']['zipcode'] = array(
            '#type' => 'textfield',
            '#title' => t('Zipcode:'),
            
        );
        
        $form['creators']['email'] = array(
            '#type' => 'email',
            '#title' => t('Email:'),
            
        );
        
        $form['creators']['phone'] = array (
            '#type' => 'tel',
            '#title' => t('Phone'),
            
        );
        
         $form['creators_add'] = array(
            '#markup' => '<a href="#">Add more creators</a>',
        );
         
        
         
        $form['creators_title2'] = array(
            '#markup' => '<br><br>',
        );
         
        $form['candidate_confirmation'] = array (
            '#type' => 'radios',
            
            '#title' => ('I read and agree the ....'),
            '#options' => array(
                'Yes' =>t('Yes'),
                'No' =>t('No')
            ),
        );
        
       
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('button'),
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_depagree_three'),
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
