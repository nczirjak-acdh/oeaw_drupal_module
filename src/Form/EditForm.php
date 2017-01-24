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

        if (empty($editHash)) {
            drupal_set_message($this->t('the uri is not exists!'), 'error');
        }

        $editUri = \Drupal\oeaw\oeawFunctions::createDetailsUrl($editHash, 'decode');

        // get the digital resource classes where the user must upload binary file
        $digitalResQuery = \Drupal\oeaw\oeawStorage::getDigitalResources();

        $digitalResources = array();

        foreach ($digitalResQuery as $dr) {
            if (isset($dr["collection"])) {
                $digitalResources[] = $dr["id"];
            }
        }

        $classGraph = \Drupal\oeaw\oeawFunctions::makeGraph($editUri);

        //get tge identifier from the graph and convert the easyrdf_resource object to php array
        $classValue = $classGraph->all($editUri, EasyRdfUtil::fixPropName('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'));

        foreach ($classValue as $v) {
            if (strpos($v->getUri(), 'vocabs.acdh.oeaw.ac.at') !== false) {
                $classVal[] = $v->getUri();
            }
        }

        if (strpos($classValue["value"], 'vocabs.acdh.oeaw.ac.at') !== false) {
            $classVal[] = $classValue["value"];
        }

        if (!empty($classVal)) {
            foreach ($classVal as $cval) {
                $editUriClass = \Drupal\oeaw\oeawStorage::getDataByProp("dct:identifier", $cval);
                $actualClassUri = $cval;
            }
        } else {
            drupal_set_message($this->t('ACDH Vocabs missing from the Resource!!'), 'error');
        }

        if (empty($editUriClass)) {
            drupal_set_message($this->t('URI Class is empty!!'), 'error');
        }
        
        // this will contains the onotology uri, what will helps to use to know
        // which fields we need to show in the editing form
        $editUriClass = $editUriClass[0]["uri"];

        //the actual fields for the editing form based on the editUriClass variable
        $editUriClassMetaFields = \Drupal\oeaw\oeawStorage::getClassMeta($editUriClass);
        
        $attributes = array();
        
        //create and load the data to the graph
        $classGraph = \Drupal\oeaw\oeawFunctions::makeGraph($editUri);

        $page['#attached']['library'][] = 'oeaw/oeaw_edit';       
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
                if (strpos($value, 'https://id.acdh.oeaw.ac.at') !== false) {
                    $oldLabel = \Drupal\oeaw\oeawFunctions::getLabelByIdentifier((string)$value);
                    $oldLabel = "Old Value: ".$oldLabel;
                }
            } else {
                $value = "";                
            }

            // get the field uri s last part to show it as a label title
            $label = explode("/", $editUriClassMetaFields[$i]["id"]);
            $label = end($label);
            $label = str_replace('#', '', $label);

            // if the label is the isPartOf, then we need to disable the editing
            // to the users, they do not have a permission to change it
            if ($label == "isPartOf") {
                $attributes = array('readonly' => 'readonly');
            } else {
                $attributes = array();
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
        if ($checkDigRes == true) {
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

    
    public function fieldValidateCallback(array &$form, FormStateInterface $form_state) {
               
         
        //get the formelements
        $formElements = $form_state->getUserInput();        
        $result = \Drupal\oeaw\oeawFunctions::getFieldNewTitle($formElements, "edit");        
        
        return $result;        
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        //get the form stored values
        $editForm = $this->store->get('formEditFields');
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
                    //$meta->addResource("http://vocabs.acdh.oeaw.ac.at/#represents", "http://dddd-value2222");
                    //remove the property
                    $meta->delete($key);
                    //insert the property with the new key
                    $meta->addResource($key, $value);
                } else {
                    //$meta->addLiteral("http://vocabs.acdh.oeaw.ac.at/#depositor", "dddd-value");
                    //remove the property
                    $meta->delete($key);
                    //insert the property with the new key
                    $meta->addLiteral($key, $value);
                }
            }
        }

        try {

            $fr->setMetadata($meta);
            $fr->updateMetadata();

            if (!empty($fUri)) {
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

        foreach ($metadata as $key => $value) {
            $this->store->delete($key);
        }
    }

}
