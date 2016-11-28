<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormStateInterface;

class NewResourceOneForm extends NewResourceFormBase {

    /* 
     *
     * drupal core formid
     *     
     * @return string : form id
    */
    public function getFormId() {
        return 'newresource_form_one';
    }

    /* 
     *
     * drupal core buildForm function, to create the form what the user will see
     *
     * @param array $form : it will contains the form elements
     * @param FormStateInterface $form_state : form object
     *
     * @return void
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
        // we need to add this attribute because of the file uploading
        $form['#attributes']['enctype'] = "multipart/form-data";
        
        // get the root resources to we can show it on the select element
        $roots = \Drupal\oeaw\oeawStorage::getRootFromDB();
        
        //create the root option values
        foreach($roots as $r){
           $rootSelect[$r["uri"]] = t($r["title"]);
        }
       
        // create the root form element with the values        
        $form["roots"] = array(
            "#type" => "select",
            "#title" => t("SELECT YOUR ROOT ELEMENT"),
            '#required' => TRUE,
            "#options" =>
            $rootSelect,
            '#default_value' => $this->store->get('roots') ? $this->store->get('roots') : '',
        );

        //get the class resources to we can show it on the select element
        $classes = \Drupal\oeaw\oeawStorage::getClass();
        
        foreach($classes as $c){
           $classesSelect[$c["uri"]] = t($c["title"]);
        }
        //create the class form element with the values
        $form['class'] = array(
            '#type' => 'select',
            '#title' => $this->t('SELECT YOUR CLASS'),
            '#required' => TRUE,
            "#options" =>
            $classesSelect,
            '#default_value' => $this->store->get('class') ? $this->store->get('class') : '',
        );
        //create the next button to the form second page
        $form['actions']['submit']['#value'] = $this->t('Next');

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        //get the class and root values from the form
        $form1Elements['root'] = $form_state->getValue('roots');
        $form1Elements['class'] = $form_state->getValue('class');
        
        //store the required data from this page to the saving page
        $this->store->set('form1Elements', $form1Elements);
        $this->store->set('roots', $form_state->getValue('roots'));
        $this->store->set('class', $form_state->getValue('class'));
        //go to the next page
        $form_state->setRedirect('oeaw_newresource_two');
    }

}
