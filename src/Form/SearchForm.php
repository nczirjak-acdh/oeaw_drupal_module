<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class SearchForm extends FormBase
{
    
    public function getFormId()
    {
        return "search_form";
    }
    
    /*
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {   
        $propertys = \Drupal\oeaw\oeawStorage::getAllPropertyForSearch();
        
        /* get the fields from the sparql query */
        $fields = array_keys($propertys[0]);
        
        $searchTerms = \Drupal\oeaw\oeawFunctions::createPrefixesFromArray($propertys, $fields);
        
        foreach($searchTerms["p"] as $terms){
            $select[$terms] = t($terms);
        }
        
        $form['metakey'] = array (
          '#type' => 'select',
          '#title' => ('MetaKey'),
          '#required' => TRUE,
          '#options' => 
              $select
        );
       
        $form['metavalue'] = array(
          '#type' => 'textfield',
          '#title' => ('MetaValue'),          
          '#required' => TRUE,
        );
     
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Search'),
          '#button_type' => 'primary',
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
        
    
        foreach ($form_state->getValues() as $key => $value) {
        
            // I pass the values with the session to the redirected url where i generating the tables
            $_SESSION['oeaw_form_result_'.$key] = $value;            
            $url = Url::fromRoute('oeaw_resources');
            $form_state->setRedirectUrl($url);           
           
        }
    }
  
}

