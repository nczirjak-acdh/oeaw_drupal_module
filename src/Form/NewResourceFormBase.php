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

abstract class NewResourceFormBase extends FormBase {
    
    public static $prefixes = 'PREFIX dct: <http://purl.org/dc/terms/> PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
        . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
        . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
        . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>';

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
        
        $propWithVal = array();
        // i creating a new array for the propertys
        foreach($propertysArray as $p)
        {             
            $propEnd = explode("/", $p);            
            $propEnd = end($propEnd);
            $identifier = \Drupal\oeaw\oeawStorage::getIdentifier($p);
            
            if(strpos($identifier, 'http://') !== false){            
                $identifier = \Drupal\oeaw\oeawFunctions::createPrefixesFromString($identifier);
            }
            //$propWithVal[$propEnd] = $p; 
            
            if(empty($identifier)){
                $key = $p;
            }else{
                $key = $identifier;
            }
            if(isset($valuesArray[$propEnd])){
                $propWithVal[$key] = $valuesArray[$propEnd];
            }
        }
       
        $sparql = self::$prefixes.' 
                    INSERT {
                      <> 
                         ';     
        
        foreach($propWithVal as $key => $value)
        {         
            if($key !== "dc:isPartOf"){
                $sparql .= $key.' "'.$value.'" ;';            
            }
            
        }        
        $sparql .= '}
                    WHERE {}
                    ';
        
        error_log($sparql);
        $insert = \Drupal\oeaw\oeawStorage::insertDataToFedora($fileContent, $sparql, $root, $fileMIME);         
        
        if($insert == false)
        {
            $this->deleteStore($metadata);
            drupal_set_message($this->t('Error during the saving process'), 'error');
        } else {
            $this->deleteStore($metadata);
            drupal_set_message($this->t('The form has been saved. Your new resource is: '.$insert));
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
