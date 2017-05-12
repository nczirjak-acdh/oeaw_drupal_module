<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


abstract class DepAgreeBaseForm extends FormBase {
   
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
   * Constructs a Multi step form Base.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
    
    public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {    
        $this->tempStoreFactory = $temp_store_factory;
        $this->sessionManager = $session_manager;
        $this->currentUser = $current_user;      
        $this->store = $this->tempStoreFactory->get('deep_agree_form_data');        
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
        //get the values from the forms
        $root = $this->store->get('form1Elements')['root'];        
        $metadata = $this->store->get('form2Elements');        
        $fileName = $this->store->get('fileName');
        $uriAndValue = $this->store->get('uriAndValue');
        $ontologyClassIdentifier = $this->store->get('ontologyClassIdentifier');
  
        
        echo "<pre>";
        var_dump($root);
        echo "</pre>";

        die();
        }
    
}
