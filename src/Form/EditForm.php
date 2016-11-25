<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\SparqlEndpoint;
use zozlak\util\Config;


class EditForm extends FormBase {

    
    /**
    * @var \Drupal\user\PrivateTempStoreFactory
    */
    protected $tempStoreFactory;

    /**
    * @var \Drupal\Core\Session\SessionManagerInterface
    */
    private $sessionManager;

    /**
    * @var \Drupal\Core\Session\AccountInterface
    */
    private $currentUser;

    /**
    * @var \Drupal\user\PrivateTempStore
    */
    protected $store;    
    
    /**   
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
    
    public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    
        $this->tempStoreFactory = $temp_store_factory;
        $this->sessionManager = $session_manager;
        $this->currentUser = $current_user;
        
        $this->store = $this->tempStoreFactory->get('edit_form');
    }
    
    public static function create(ContainerInterface $container){
        return new static(
                $container->get('user.private_tempstore'),
                $container->get('session_manager'),
                $container->get('current_user')
        );
    }
    
    public function getFormId() {
        return "edit_form";
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        //get the hash uri from the url, based on the drupal routing file
        $editHash= \Drupal::request()->get('uri');
        
        if (empty($editHash)) {  return false; }
        
        $editUri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($editHash, 'decode');

        // get the digital resource classes where the user must upload binary file
        $digitalResQuery = \Drupal\oeaw\oeawStorage::getDigitalResources();
        
        $digitalResources = array();
        
        foreach($digitalResQuery as $dr){            
            if(isset($dr["collection"])){
                $digitalResources[] = $dr["id"];
            }            
        }
        
        // get the actual class dct:identifier to we can compare it with the digResource
        // if this is a binaryResource then we need to show the file upload possibility
        $classValue = \Drupal\oeaw\oeawStorage::getDefPropByURI($editUri, "rdf:type");

        
        foreach($classValue as $cv){            
            if(!empty($cv["value"])){
                if (strpos($cv["value"], 'http://vocabs.acdh.oeaw.ac.at') !== false) {                        
                    $classVal[] = $cv["value"];                   
                } 
            }
        }
        if(count($classVal) == 0){
            $classVal[] = "http://vocabs.acdh.oeaw.ac.at/#DigitalResource";
        }
        
        if(!empty($classVal)){
            foreach($classVal as $cval){            
                $editUriClass = \Drupal\oeaw\oeawStorage::getDataByProp("dct:identifier", $cval);     
                $actualClassUri = $cval;
            }
        }
        
        // this will contains the onotology uri, what will helps to use to know
        // which fields we need to show in the editing form
        $editUriClass = $editUriClass[0]["uri"];

        //the actual fields for the editing form based on the editUriClass variable
        $editUriClassMetadata = \Drupal\oeaw\oeawStorage::getClassMeta($editUriClass);
        
        // temporary fix - Mateusz and Norbert - 25. nov. 2016
        $editUriClassMetadata[] = array("id"=> "http://purl.org/dc/elements/1.1/title");

        $attributes = array();
        
        for($i=0; $i < count($editUriClassMetadata); $i++){
            
            // get the field values based on the edituri and the metadata uri
            $value = \Drupal\oeaw\oeawStorage::getFieldValByUriProp($editUri, $editUriClassMetadata[$i]["id"]);
           

            $value = $value[0]["value"];
            
            // get the field uri s last part to show it as a label title
            $label = explode("/", $editUriClassMetadata[$i]["id"]);                
            $label = end($label);
            $label = str_replace('#', '', $label);
            
            // if the label is the isPartOf, then we need to disable the editing
            // to the users, they do not have a permission to change it
            if($label == "isPartOf"){
                if($value !== null){
                    $titleProperty = \Drupal\oeaw\oeawStorage::getDefPropByURI($value, 'dc:title');
                    $value = $titleProperty[0]["value"];
                }
                $attributes =  array('readonly' => 'readonly');
            }else {
                $attributes =  array();
            }
            
            // generate the form fields
            $form[$label] = array(
                '#type' => 'textfield',
                '#title' => $this->t($label),
                '#default_value' => $value,  
                '#attributes' => $attributes,
            );
            
            //create the hidden propertys to the saving methods
            $labelVal = str_replace(' ', '+', $label);
            $form[$labelVal.':oldValues'] = array(
                '#type' => 'hidden',
                '#value' => $value,
            );
            $property[$label] = $editUriClassMetadata[$i]["id"];
            $fieldsArray[] = $label;
            $fieldsArrayOldValues[] = $labelVal.':oldValues';                          
        }
                
        $this->store->set('formEditFields', $fieldsArray);
        $this->store->set('formEditOldFields', $fieldsArrayOldValues);
        $this->store->set('propertysArray', $property);
        $this->store->set('resourceUri', $editUri);
        
        $checkDigRes = in_array($actualClassUri, $digitalResources);
       
        // if we have a digital resource then the user must upload a binary resource
        if($checkDigRes == true){
             $form['file'] = array(
                '#type' => 'managed_file', 
                '#title' => t('FILE'),                 
                '#upload_validators' => array(
                    'file_validate_extensions' => array('xml doc txt simplified docx'),
                 ),
                '#description' => t('Upload a file, allowed extensions: XML, CSV, etc....'),
            );
        }
        
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit sample'),
        );


        return $form;
        
    }
    

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $editForm = $this->store->get('formEditFields');
        $editOldForm = $this->store->get('formEditOldFields');
        $propertysArray = $this->store->get('propertysArray');
        $resourceUri = $this->store->get('resourceUri');
        
        //get the uploaded files values
        $fileID = $form_state->getValue('file');
        $fileID = $fileID[0];
        
        $fObj = file_load($fileID);
        
        if(!empty($fObj) || isset($fObj))
        {                
            //get the temp file uri        
            $fUri = $fObj->getFileUri();
        }
        
        // create array with new form values
        foreach($editForm as $e){                        
            $editFormValues[$e] = $form_state->getValue($e);        
        }
        
        // create array with old form values
        foreach($editOldForm as $e){            
            $value = $form_state->getValue($e); 
            $key = str_replace(':oldValues', '', $e);
            $editFormOldValues[$key] = $value;
        }
        
        foreach($propertysArray as $key => $value){    
            if($key !== 'isPartOf'){
                $uriAndValue[$value] = $editFormValues[$key];
            }
        }
        
        
        $graph = new \EasyRdf_Graph();        
        $meta = $graph->resource('acdh');
        
        foreach($uriAndValue as $key => $value){
            if (strpos($value, 'http') !== false) {
                //$meta->addResource("http://vocabs.acdh.oeaw.ac.at/#represents", "http://dddd-value2222");
                $meta->addResource($key, $value);
            } else {
                //$meta->addLiteral("http://vocabs.acdh.oeaw.ac.at/#depositor", "dddd-value");
                $meta->addLiteral($key, $value);
            }            
        }
        
        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');                
        $sparqlEndpoint = new SparqlEndpoint($config->get('sparqlUrl'));
        
        $fedora = new Fedora($config);
        $fedora->begin();
        $resourceUri = preg_replace('|^.*/rest/|', '', $resourceUri);
        
        $fr = $fedora->getResourceByUri($resourceUri);
        $fr->getMetadata();

        try {
            
            $fr->setMetadata($meta);
            $fr->updateMetadata();
            
            if(!empty($fUri)){
                $fr->updateContent($fUri);
            }
            
            $fedora->commit();
            $this->deleteStore($editForm);
            drupal_set_message($this->t('The form has been saved and you resource was changed'));
            
        } catch (Exception $ex) {
            
            $fedora->rollback();
            $this->deleteStore($editForm);
            drupal_set_message($this->t('Error during the saving process'), 'error');
        }        
    }
    
    
    /**
    * Helper method that removes all the keys from the store collection used for
    * the multistep form.
    */
    
    protected function deleteStore($editForm) {
        
        foreach($metadata as $key => $value){
            $this->store->delete($key);
        }
    }

}

