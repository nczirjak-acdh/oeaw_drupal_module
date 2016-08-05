<?php


namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;


class DetailsForm extends FormBase
{
    
    public function getFormId()
    {
        return "details_form";
    }
    
    public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
        // Do something with $user in the form
       echo "user: ";
        echo "<pre>";
       var_dump($user);
       echo "<pre>";
       echo "form: ";
       echo "<pre>";
       var_dump($form);
       echo "<pre>";
       echo "form state: ";
       echo "<pre>";
       var_dump($form_state);
       echo "<pre>";
       
        
        
        die();
    }
    
     public function submitForm(array &$form, FormStateInterface $form_state) {
         
     }
    

    
}


