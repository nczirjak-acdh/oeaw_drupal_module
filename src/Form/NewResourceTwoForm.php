<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use zozlak\util\Config;
use EasyRdf_Graph;
use EasyRdf_Resource;
use acdhOeaw\util\EasyRdfUtil;
use InvalidArgumentException;
use RuntimeException;
use Drupal\oeaw\oeawFunctions;


class NewResourceTwoForm extends NewResourceFormBase  {

    
    
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
        $form_state->disableCache();
        // get form page 1 stored values
        $formVal = $this->store->get('form1Elements');
        $class = $formVal['class'];
        $root =  $formVal['root'];
        
        // get the digital resource classes where the user must upload binary file
        $digitalResQuery = $this->oeawStorage->getDigitalResources();

        //create the digitalResources array
        $digitalResources = array();
        foreach($digitalResQuery as $dr){            
            if(isset($dr["collection"])){
                $digitalResources[] = $dr["id"];
            }            
        }
        
        $classGraph = $this->oeawFunctions->makeGraph($class);
        $classID = $classGraph->get($class,EasyRdfUtil::fixPropName('http://purl.org/dc/terms/identifier'))->toRdfPhp();
        if(!empty($classID)){
            $classValue = $classID["value"];
        }
           
        //we store the ontology identifier for the saving process
        $this->store->set('ontologyClassIdentifier', $classValue);
        
        // compare the digRes and the actual class, because if it is a DigColl then 
        // we need to show the fileupload option
        $checkDigRes = in_array($classValue, $digitalResources);
              
        // get the actual class metadata
        $metadataQuery = $this->oeawStorage->getClassMeta($class);  
        $metadata = array();
        if(count($metadataQuery) > 0){
            foreach($metadataQuery as $m){            
                $metadata[] = $m["id"];
            }
        }else {
            return drupal_set_message($this->t('There is no metadata for this class'), 'error');
        }
        
        
        $rootGraph = $this->oeawFunctions->makeGraph($root);
        //get tge identifier from the graph and convert the easyrdf_resource object to php array
        $rootID = array();
        $rootID = $rootGraph->get($root,EasyRdfUtil::fixPropName('http://purl.org/dc/terms/identifier'))->toRdfPhp();
        
        //get the value of the property
        if(count($rootID) > 0 ){
            $rootIdentifier = $rootID["value"];
        }else {
            return drupal_set_message($this->t('Your root element is missing! You cant add new resource without a root element!'), 'error');
        }
        
        
        //old solution
        //$rootIdentifier = \Drupal\oeaw\oeawStorage::getDefPropByURI($root, "dct:identifier");
        //$rootIdentifier = $rootIdentifier[0]["value"];
        
        $fieldsArray = array();       
        $expProp = "";
        $defaultValue = "";
        $label = "";
        $attributes = array();
        $labelVal = "";
        foreach ($metadata as $m) {            

            //we dont need the identifier, because doorkeeper will generate it automatically
            if($m === "http://purl.org/dc/terms/identifier"){
               continue; 
            }
            
            $expProp = explode("/", $m);            
            $expProp = end($expProp);
            if (strpos($expProp, '#') !== false) {
               $expProp = str_replace('#', '', $expProp);
            }
            
            //|| $editUriClassMetaFields[$i]["id"] ===
            if($m === "http://purl.org/dc/terms/isPartOf" ){
                $defaultValue = $rootIdentifier;
                $attributes = array('readonly' => 'readonly');
            } else {
                $defaultValue = $this->store->get($m) ? $this->store->get($m) : '';
                $attributes = array();
            }            

            if(empty($m['label']) || !isset($m['label'])){          
                $label = $expProp;
            }else{
                $label = $m['label'];
            }
            
            $form[$label] = array(
                '#type' => 'textfield',
                '#title' => $this->t($label),                
                '#default_value' => $defaultValue,                
                '#attributes' => $attributes,
                '#description' => ' ',
                '#autocomplete_route_name' => 'oeaw.autocomplete',
                '#autocomplete_route_parameters' => array('prop1' => strtr(base64_encode($m), '+/=', '-_,'), 'fieldName' => $label),
                //create the ajax to we can display the selected uri title
                '#ajax' => [
                    // Function to call when event on form element triggered.
                    'callback' => 'Drupal\oeaw\Form\NewResourceTwoForm::fieldValidateCallback',
                    'effect' => 'fade',
                    // Javascript event to trigger Ajax. Currently for: 'onchange'.
                    //we need to wait the end of the autocomplete
                    'event' => 'autocompleteclose',
                    'progress' => array(
                        // Graphic shown to indicate ajax. Options: 'throbber' (default), 'bar'.
                        'type' => 'throbber',
                        // Message to show along progress graphic. Default: 'Please wait...'.
                        'message' => NULL,
                    ),                    
                  ],
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
                    'file_validate_extensions' => array('xml doc txt simplified docx pdf jpg png tiff gif bmp'),
                 ),
                '#description' => t('Upload a file, allowed extensions: XML, CSV, and images etc....'),
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
    
    public function fieldValidateCallback(array &$form, FormStateInterface $form_state) {

        //get the formelements
        $formElements = $form_state->getUserInput();        
        $result = array();
        $result = \Drupal\oeaw\oeawFunctions::getFieldNewTitle($formElements, "new");
       
        return $result;        
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
