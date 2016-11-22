<?php

/**
 * @file
 * Contains \Drupal\demo\Form\Multistep\MultistepFormBase.
 */

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\oeaw\oeawFunctions;
use acdhOeaw\fedora\FedoraResource;
use acdhOeaw\util\SparqlEndpoint;
use zozlak\util\Config;


abstract class NewResourceFormBase extends FormBase {
   
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
   * Constructs a \Drupal\demo\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
    
    public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    
        $this->tempStoreFactory = $temp_store_factory;
        $this->sessionManager = $session_manager;
        $this->currentUser = $current_user;
        
        $this->store = $this->tempStoreFactory->get('multistep_data');
    }
    
    public static function create(ContainerInterface $container){
        return new static(
                $container->get('user.private_tempstore'),
                $container->get('session_manager'),
                $container->get('current_user')
        );
    }
    
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        //start a manual session for anonymus user
        if($this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
            $_SESSION['multistep_form_holds_session'] = true;
            $this->sessionManager->start();
        }
        
        $form = array();
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
            '#button_type' => 'primary',
            '#weight' => 10,
        );

        return $form;
        
    }
    
    /*
     * Saves data from the multistep form
    */
    
    protected function saveData()
    {
        $root = $this->store->get('form1Elements')['root'];
        $class = $this->store->get('form1Elements')['class'];
        $metadata = $this->store->get('form2Elements');
        $propertysArray = $this->store->get('propertysArray');
        $valuesArray = $this->store->get('valuesArray');
        $fileMIME = $this->store->get('fileMIME');
        $fileContent = $this->store->get('fileContent');
        $fileName = $this->store->get('fileName');
        $uriAndValue = $this->store->get('uriAndValue');
        $ontologyClassIdentifier = $this->store->get('ontologyClassIdentifier');
  
        $graph = new \EasyRdf_Graph();        
        $meta = $graph->resource('acdh');
        
        foreach($uriAndValue as $key => $value){
        
            if($key == "http://purl.org/dc/terms/isPartOf"){
                $value = $root;
            }
            if (strpos($value, 'http') !== false) {
                //$meta->addResource("http://vocabs.acdh.oeaw.ac.at/#represents", "http://dddd-value2222");
                $meta->addResource($key, $value);
            } else {
                //$meta->addLiteral("http://vocabs.acdh.oeaw.ac.at/#depositor", "dddd-value");
                $meta->addLiteral($key, $value);
            }            
        }
        
        //add the ontologyClass dct:identifier to the new resource rdf:type, to we can
        // recognize the ontologyclass and required fields to the editing form
        $meta->addResource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type", $ontologyClassIdentifier);  
        
        $config = new Config($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');                
        $sparqlEndpoint = new SparqlEndpoint($config->get('sparqlUrl'));
        $init = FedoraResource::init($config);
        FedoraResource::begin();
        
        // ha van fajl akkor a masodik ertek az lesz
        try{
            $res = FedoraResource::factory($meta, $fileName);
            
            FedoraResource::commit();
            $uri = $res->getUri();
            $uriArr = str_replace('http://fedora/rest/', '', $uri);
            $uriArr = explode('/', $uriArr);
            $uri = str_replace($uriArr[0].'/', '', $uri);
            $uri = str_replace('http://fedora/rest/', 'http://fedora.localhost/rest/', $uri);
            $uri = $uri.'/fcr:metadata';
            
            $this->deleteStore($metadata);
            drupal_set_message($this->t('The form has been saved. Your new resource is: <a href="'.$uri.'" target="_blank">'.$uri.'</a>'));
            
            
        } catch (Exception $ex) {
            FedoraResource::rollback();
            $this->deleteStore($metadata);
            drupal_set_message($this->t('Error during the saving process'), 'error');
        }
        
    }
    
    
    /**
    * Helper method that removes all the keys from the store collection used for
    * the multistep form.
    */
    
    protected function deleteStore($metadata) {
        
        foreach($metadata as $key => $value){
            $this->store->delete($key);
        }
    }
    
}