<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\user\PrivateTempStoreFactory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\EasyRdfUtil;
use zozlak\util\Config;
use EasyRdf_Graph;
use EasyRdf_Resource;
use Drupal\oeaw\OeawStorage;
use Drupal\oeaw\OeawFunctions;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    private $config;
    private $OeawFunctions;
    private $OeawStorage;
    
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
        $this->config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $this->OeawStorage = new OeawStorage();
        $this->OeawFunctions = new OeawFunctions();
    }

    public static function create(ContainerInterface $container) {
        return new static(
                $container->get('user.private_tempstore'), $container->get('session_manager'), $container->get('current_user')
        );
    }

    public function getFormId() {
        return "edit_form";
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form_state->disableCache();
        //get the hash uri from the url, based on the drupal routing file
        $editHash = \Drupal::request()->get('uri');

        $isImage = false;
        
        if (empty($editHash)) {
            return drupal_set_message($this->t('The uri is not exists!'), 'error');            
        }

        $editUri = $this->OeawFunctions->createDetailsUrl($editHash, 'decode');
      
        // get the digital resource classes where the user must upload binary file
        $digitalResQuery = $this->OeawStorage->getDigitalResources();
        
        $digitalResources = array();

        //we need that ones where the collection is true
        foreach ($digitalResQuery as $dr) {
            if (isset($dr["collection"])) {
                $digitalResources[] = $dr["id"];
            }
        }
        //create and load the data to the graph
        $classGraph = $this->OeawFunctions->makeGraph($editUri);

        $classVal = array();
        //get tge identifier from the graph and convert the easyrdf_resource object to php array
        $classValue = $classGraph->all($editUri, EasyRdfUtil::fixPropName(\Drupal\oeaw\ConnData::$rdfType));
        
        //$metadataQuery = $this->OeawStorage->getClassMeta($class); 
        if(count($classValue) > 0){
            foreach ($classValue as $v) {
                if(!empty($v->getUri())){
                    $classVal[] = $v->getUri();
                }
            }
        } else {
            return drupal_set_message($this->t('The acdh RDF Type is missing'), 'error');
        }

        $fedora = $this->OeawFunctions->initFedora();
        $editUriClass = "";
       
        if (!empty($classVal)) {
            foreach ($classVal as $cval) {
                $res = $fedora->getResourcesByProperty($this->config->get('fedoraIdProp'), $cval);
                // this will contains the onotology uri, what will helps to use to know
                // which fields we need to show in the editing form
                if(count($res) > 0){
                    $editUriClass = $res[0]->getUri();
                    $actualClassUri = $cval;
                    if($cval == \Drupal\oeaw\ConnData::$imageProperty ){ $isImage = true; }
                }                
            }
        } else {
            return drupal_set_message($this->t('ACDH Vocabs missing from the Resource!!'), 'error');
        }

        if (empty($editUriClass)) {            
            return drupal_set_message($this->t('URI Class is empty!!'), 'error');            
        }
        
        //the actual fields for the editing form based on the editUriClass variable
        $editUriClassMetaFields = $this->OeawStorage->getClassMeta($editUriClass);
        
        if(empty($editUriClassMetaFields)){
            return drupal_set_message($this->t('There are no Fields for this URI CLASS'), 'error');            
        }
 
        $attributes = array();        
                
        $resTitle = $classGraph->label($editUri);        
        
        $form['resource_title'] = array(
            '#markup' => '<h2><b><a href="'.$editUri.'" target="_blank">'.$resTitle.'</a></b></h2>',
        );
        $editUriClassMetaFields[] = array("id" => "http://purl.org/dc/terms/identifier", "label" => "");
        $editUriClassMetaFields[] = array("id" => "http://purl.org/dc/terms/contributor", "label" => "");

        //get the propertys which have more than one values
        $duplicates = $this->OeawFunctions->getDuplicatesFromArray($editUriClassMetaFields, "id");
                
        
        if(count($duplicates) > 0){}
        
        
        for ($i = 0; $i < count($editUriClassMetaFields); $i++) {
            
            // get the field values based on the edituri and the metadata uri
            //if the property is not exists then we need to avoid the null error message
            
            $value = $classGraph->get($editUri, EasyRdfUtil::fixPropName($editUriClassMetaFields[$i]["id"]));
            
            $oldLabel = "";
            
            if (!empty($value)) {
                //get the input fields values
                $value = $classGraph->get($editUri, EasyRdfUtil::fixPropName($editUriClassMetaFields[$i]["id"]))->toRdfPhp();
                $value = $value["value"];
                // if the input field value contains the id.acdh... then we check the labels
                // and shows the old Label to the user
                if (strpos($value, $this->config->get('fedoraIdNamespace')) !== false) {                    
                    $resOT = $fedora->getResourcesByProperty($this->config->get('fedoraIdProp'), $value);
                    foreach($resOT as $ot){
                        if(!empty($ot->getMetadata()->label())){
                            $labelURL = (string)$value;
                            $labelTxt = (string)utf8_decode($ot->getMetadata()->label());
                            $oldLabel = "Old Value: <a href='$labelURL' target='_blank'>".$labelTxt."</a>";
                        }else {
                            $oldLabel = "";
                        }
                    }
                }
            } else {
                $value = "";
            }

            // get the field uri s last part to show it as a label title
            $label = explode("/", $editUriClassMetaFields[$i]["id"]);
            $label = end($label);
            $label = str_replace('#', '', $label);

            // if the label is the isPartOf or identifier, then we need to disable the editing
            // to the users, they do not have a permission to change it
            if($editUriClassMetaFields[$i]["id"] === $this->config->get('fedoraRelProp') || $editUriClassMetaFields[$i]["id"] === $this->config->get('fedoraIdProp')){
                $attributes = array('readonly' => 'readonly', 'data-repoid' => $editUriClassMetaFields[$i]["id"]);
            } else {
                $attributes = array('data-repoid' => $editUriClassMetaFields[$i]["id"]);
            }
            
            // generate the form fields
            $form[$label] = array(
                '#type' => 'textfield',
                '#title' => $this->t($label),
                '#default_value' => $value,
                '#attributes' => $attributes,
                '#field_suffix' => $oldLabel,
                //description required a space, in other case the ajax callback will not works....
                '#description' => ' ',
                //define the autocomplete route and values
                '#autocomplete_route_name' => 'oeaw.autocomplete',
                '#autocomplete_route_parameters' => array('prop1' => strtr(base64_encode($editUriClassMetaFields[$i]["id"]), '+/=', '-_,'), 'fieldName' => $label),
                //create the ajax to we can display the selected uri title
                '#ajax' => [
                    // Function to call when event on form element triggered.
                    'callback' => 'Drupal\oeaw\Form\EditForm::fieldValidateCallback',
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
            
            
           
            //create the hidden propertys to the saving methods
            $labelVal = str_replace(' ', '+', $label);
            $form[$labelVal . ':oldValues'] = array(
                '#type' => 'hidden',
                '#value' => $value,
            );

            $property[$label] = $editUriClassMetaFields[$i]["id"];
            $fieldsArray[] = $label;
            $fieldsArrayOldValues[] = $labelVal . ':oldValues';
        }

        $this->store->set('formEditFields', $fieldsArray);
        $this->store->set('formEditOldFields', $fieldsArrayOldValues);
        $this->store->set('propertysArray', $property);
        $this->store->set('resourceUri', $editUri);

        $checkDigRes = in_array($actualClassUri, $digitalResources);

        // if we have a digital resource then the user must upload a binary resource        
        if ($checkDigRes == true || $isImage == true) {
            $form['file'] = array(
                '#type' => 'managed_file',
                '#title' => t('Binary Resource'),                
                '#upload_validators' => array(
                    'file_validate_extensions' => array('xml doc txt simplified docx pdf jpg png tiff gif bmp'),
                 ),
                '#description' => t('Upload a file, allowed extensions: XML, CSV, and images etc....'),
            );            
        }
        
  
     
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit sample'),
        );
       
        return $form;
    }
    
    public function fieldValidateCallback(array &$form, FormStateInterface $form_state) {
        //get the formelements
        $formElements = $form_state->getUserInput();        
        $result = array();
        
        $oeawFunc = new OeawFunctions();
        $result = $oeawFunc->getFieldNewTitle($formElements, "new");
       
        return $result;        
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        //get the form stored values
        $editForm = $this->store->get('formEditFields');        
        //$form_state->getUserInput()        
        $editOldForm = $this->store->get('formEditOldFields');
        $propertysArray = $this->store->get('propertysArray');
        $resourceUri = $this->store->get('resourceUri');

        //get the uploaded files values
        $fileID = $form_state->getValue('file');
        $fileID = $fileID[0];
      
        if (!empty($fileID)) {
            //create the file object
            $fObj = file_load($fileID);
            if (!empty($fObj) || isset($fObj)) {
                //get the temp file uri        
                $fUri = $fObj->getFileUri();
            }
        }
        
        // create array with new form values
        foreach ($editForm as $e) {
            $editFormValues[$e] = $form_state->getValue($e);
        }

        // create array with old form values
        foreach ($editOldForm as $e) {
            $value = $form_state->getValue($e);
            $key = str_replace(':oldValues', '', $e);
            if (!empty($value)) {
                $editFormOldValues[$key] = $value;
            }
        }

        foreach ($propertysArray as $key => $value) {
            //in the editing we need to skip the ispartof
            // because the user cant overwrite the original
            if ($key !== 'isPartOf') {
                $uriAndValue[$value] = $editFormValues[$key];
            }
        }

        $config = new Config($_SERVER["DOCUMENT_ROOT"] . '/modules/oeaw/config.ini');

        $fedora = new Fedora($config);
        $fedora->begin();
        $resourceUri = preg_replace('|^.*/rest/|', '', $resourceUri);

        $fr = $fedora->getResourceByUri($resourceUri);
        //get the existing metadata
        $meta = $fr->getMetadata();

        foreach ($uriAndValue as $key => $value) {
            if (!empty($value)) {
                if (strpos($value, 'http') !== false) {                    
                    $meta->delete($key);
                    //insert the property with the new key
                    $meta->addResource($key, $value);
                } else {                    
                    $meta->delete($key);
                    //insert the property with the new key
                    $meta->addLiteral($key, $value);
                }
            }
        }

        try {
            $fr->setMetadata($meta);
            $fr->updateMetadata();

            if (!empty($fUri)) { $fr->updateContent($fUri); }

            $fedora->commit();
            $this->deleteStore($editForm);            
            $encodeUri = $this->OeawFunctions->createDetailsUrl($resourceUri, 'encode');
            
            if (strpos($encodeUri, 'fcr:metadata') !== false) {
                $encodeUri = $encodeUri.'/fcr:metadata';
            }
            
            $response = new RedirectResponse(\Drupal::url('oeaw_new_success', ['uri' => $encodeUri]));
            $response->send();
            return;
            
        } catch (Exception $ex) {
            $fedora->rollback();
            $this->deleteStore($editForm);
            return drupal_set_message($this->t('Error during the saving process'), 'error');
        }
    }

    /**
     * Helper method that removes all the keys from the store collection used for
     * the multistep form.
     */
    protected function deleteStore($editForm) {
        foreach ($metadata as $key => $value) {
            $this->store->delete($key);
        }
    }

}
