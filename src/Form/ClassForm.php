<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ClassForm extends FormBase
{
    
    
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
        
        $this->store = $this->tempStoreFactory->get('class_search_data');
    }
    
    public static function create(ContainerInterface $container){
        return new static(
                $container->get('user.private_tempstore'),
                $container->get('session_manager'),
                $container->get('current_user')
        );
    }
    
    public function getFormId()
    {
        return "class_form";
    }
    
    /*
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {   
       
        $data = \Drupal\oeaw\oeawStorage::getClassesForSideBar();
        
        /* get the fields from the sparql query */
        $fields = array_keys($data[0]);
        
        $searchTerms = \Drupal\oeaw\oeawFunctions::createPrefixesFromArray($data, $fields);
        
        foreach($searchTerms["type"] as $terms){
            $select[$terms] = t($terms);
        }
        
        $form['classes'] = array (
          '#type' => 'select',
          '#title' => ('Classes'),
          '#required' => TRUE,
          '#options' => 
              $select
        );
               
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Get Childrens'),
          '#button_type' => 'primary',
        );
        
        return $form;
    }
    
    
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
        /*
        if (strlen($form_state->getValue('metavalue')) < 1) {
            $form_state->setErrorByName('metavalue', $this->t(''));
        }*/
        
    }
  
  
  
    public function submitForm(array &$form, FormStateInterface $form_state) {
            
        $classes = $form_state->getValue('classes');
        
        $tempstore = \Drupal::service('user.shared_tempstore')->get('oeaw_module_tempstore')->set('classes_search', $classes);
                
        $class = \Drupal::service('user.shared_tempstore')->get('oeaw_module_tempstore')->get('classes_search');
       
        $url = Url::fromRoute('oeaw_classes_result');        
        //$form_state->setRedirect('oeaw_classes_result', ["search_classes" => $classes]); 
        $form_state->setRedirectUrl($url);
        
    }
  
}

