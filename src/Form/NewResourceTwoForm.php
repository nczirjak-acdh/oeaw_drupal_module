<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class NewResourceTwoForm extends NewResourceFormBase {

    /* 
     *
     * drupal core formid
     *     
     * @return string : form id
    */
    public function getFormId() {
        return 'multistep_form_two';
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
        
        // get form page 1 stored values
        $formVal = $this->store->get('form1Elements');
        $class = $formVal['class'];
        $root =  $formVal['root'];
        
        // get the digital resource classes where the user must upload binary file
        $digitalResQuery = \Drupal\oeaw\oeawStorage::getDigitalResources();

        //create the digitalResources array
        $digitalResources = array();
        foreach($digitalResQuery as $dr){            
            if(isset($dr["collection"])){
                $digitalResources[] = $dr["id"];
            }            
        }
        echo $class;
        // get the actual class dct:identifier to we can compare it with the digResource
        $classValue = \Drupal\oeaw\oeawStorage::getValueByUriProperty($class, "dct:identifier"); 
        
        foreach($classValue as $cv){
            
            if(!empty($cv["value"])){
                $classValue = $cv["value"];
            }
        }
        
        //we store the ontology identifier for the saving process
        $this->store->set('ontologyClassIdentifier', $classValue);
        
        // compare the digRes and the actual class, because if it is a DigColl then 
        // we need to show the fileupload option
        $checkDigRes = in_array($classValue, $digitalResources);
      
        // get the actual class metadata
        $metadataQuery = \Drupal\oeaw\oeawStorage::getClassMeta($class);
        
        foreach($metadataQuery as $m){            
            $metadata[] = $m["id"];
        }
        
        $rootTitle = \Drupal\oeaw\oeawStorage::getDefPropByURI($root, "dc:title");
        if(!empty($rootTitle)){
            $rootTitle = $rootTitle[0]["value"];
        } else {
            $rootTitle = "";
        }
                
        $fieldsArray = array();
        
        foreach ($metadata as $m) {
            
            $expProp = explode("/", $m);            
            $expProp = end($expProp);
            if (strpos($expProp, '#') !== false) {
               $expProp = str_replace('#', '', $expProp);
            }
            
            if($expProp == 'isPartOf'){
                $defaultValue = $rootTitle;        
                $attributes =  array('readonly' => 'readonly');
            }else{
                $defaultValue = $this->store->get($m) ? $this->store->get($m) : '';
                $attributes = "";
            }

            if(empty($m['label']) || !isset($m['label'])){          
                $label = $expProp;
            }else{
                $label = $m['label'];
            }
            
            $form[$label] = array(
                '#type' => 'textfield',
                '#title' => $this->t($label.' *'),                
                '#default_value' => $defaultValue,                
                '#attributes' => $attributes,
            );

            $labelVal = str_replace(' ', '+', $label);
            $form[$labelVal.':prop'] = array(
                '#type' => 'hidden',                
                '#value' => $m,
            );

            $fieldsArray[] = $label;
            $fieldsArray[] = $labelVal.':prop';                
            
        }
               
             
        $this->store->set('form2Fields', $fieldsArray);
        // if we have a digital resource then the user must upload a binary resource
        if($checkDigRes == true){
            $form['file'] = array(
                '#type' => 'managed_file', 
                '#title' => t('FILE'), 
                '#required' => TRUE,
                '#upload_validators' => array(
                    'file_validate_extensions' => array('xml doc txt simplified docx'),
                 ),
                '#description' => t('Upload a file, allowed extensions: XML, CSV, etc....'),
            );
        }
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('button'),
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_newresource_one'),
        );

        return $form;
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {    
        
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
         
        /* get the form 1 elements */
        $formVal = $this->store->get('form1Elements');
        /* get the form2 autogenerated fields name */
        $form2Fields = $this->store->get('form2Fields');
        $ontologyClassIdentifier = $this->store->get('ontologyClassIdentifier');
        
        //get the uploaded file Drupal number
        $fileID = $form_state->getValue('file');
        $fileID = $fileID[0];
        //create the file objectt
        $fObj = file_load($fileID);
        
        if(!empty($fObj) || isset($fObj)){
            //get the temp file uri
            $fUri = $fObj->getFileUri();
        }
        
        foreach($form2Fields as $f){
            
            $fVal = $form_state->getValue($f);
          
            // check the field is the value or some hidden input field 
            if (strpos($f, ':') !== false) {
            
                $fe = explode(':', $f);
                if($fe[1] == 'prop'){
                    $property[] = $fVal;
                    $propUrls[$fe[0]] = $fVal;                    
                }
                
            } else {
                // not hidden input fields values
                $valuesArray[$f] = $fVal;                
            }            
        }
 
        foreach($propUrls as $key => $value){            
            $uriAndValue[$value] = $valuesArray[$key];            
        }       

        $this->store->set('propertysArray', $property);
        $this->store->set('valuesArray', $valuesArray);        
        $this->store->set('fileName', $fUri);
        $this->store->set('uriAndValue', $uriAndValue);
        
        // Save the data
        parent::saveData();
    }

}
