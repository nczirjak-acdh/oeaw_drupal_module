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
    
 /**
   * {@inheritdoc}.
   */
    public function buildForm(array $form, FormStateInterface $form_state) 
    { 
        /*
        $form['uri'] = array(
             '#type' => 'hidden',
            '#value' => $my_field_value,
        );*/
        
        $form['metakey'] = array (
          '#type' => 'select',
          '#title' => ('MetaKey'),
          '#options' => array(
            '' => t(''),
            'dct:title' => t('dct:title'),
            'dct:description' => t('dct:description'),
            'dct:created' => t('dct:created'),            
            'dct:isPartOf' => t('dct:isPartOf'),              
            'rdf:type' => t('rdf:type'),
            'ebucore:filename' => t('ebucore:filename'),
            'ebucore:hasMimeType' => t('ebucore:hasMimeType'),
          ),
        );
        
        $form['uri'] = array(
          '#type' => 'textfield',
          '#title' => ('URI'),          
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
        
    // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
        foreach ($form_state->getValues() as $key => $value) {
        //  drupal_set_message($key . ': ' . $value);
        
            $_SESSION['oeaw_form_result_'.$key] = $value;            
            $url = Url::fromRoute('oeaw_resource_list');
            $form_state->setRedirectUrl($url);
           
            /*if($key == 'uri')              
            {
                if(!empty($value)) 
                {                         
                    
                }            
            }
          
            if($key == 'metakey')
            {                
                if(!empty($value)) 
                {                                                          
                    $_SESSION['oeaw_form_result_'.$key] = $value;            
                    $url = Url::fromRoute('oeaw_resource_list');
                    $form_state->setRedirectUrl($url);
                }                    
            } */         
        }
    }
  
}

