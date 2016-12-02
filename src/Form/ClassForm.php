<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;


class ClassForm extends FormBase
{
    
    public function getFormId()
    {
        return "class_form";
    }
    
    /*
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {   
        $data = \Drupal\oeaw\oeawStorage::getClassesForSideBar();
        
        /* get the fields from the sparql query */
        $fields = array_keys($data[0]);
        
        $searchTerms = \Drupal\oeaw\oeawFunctions::createPrefixesFromArray($data, $fields);
        
        foreach($searchTerms["type"] as $terms){
            $select[$terms] = t($terms);
        }
        
        $form['classes'] = array (
          '#type' => 'select',
          '#title' => ('Classes'),
          '#required' => TRUE,
          '#options' => 
              $select
        );
               
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Get Childrens'),
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
            
        $classes = $form_state->getValue('classes');
        $_SESSION['oeaw_form_result_classes'] = $classes;
        $url = Url::fromRoute('oeaw_classes_result');
        $form_state->setRedirectUrl($url);
        
    }
  
}

