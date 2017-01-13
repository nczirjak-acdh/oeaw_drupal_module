<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class AutoCompleteForm extends FormBase
{
    
    public function getFormId()
    {
        return "autocomplete_form";
    }
    
    /*
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {   
        
       $form['input_fields']['nid'] = array(
            '#type' => 'textfield',
            '#title' => t('example autocomplete field'),
            '#autocomplete_route_name' => 'oeaw.autocomplete',            
        );

       
        return $form;
    }
    
    
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
        /*
        if (strlen($form_state->getValue('metavalue')) < 1) {
            $form_state->setErrorByName('metavalue', $this->t(''));
        }*/
        
    }
  
  
  
    public function submitForm(array &$form, FormStateInterface $form_state) {
      
    }
  
}

