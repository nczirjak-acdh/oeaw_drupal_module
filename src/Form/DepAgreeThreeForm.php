<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DepAgreeThreeForm extends DepAgreeBaseForm{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
       
        $form['transfer'] = array(
            '#type' => 'fieldset',
            '#title' => t('<b>Transfer Procedures</b>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );       
        
        $form['transfer']['folder_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Folder name or BagIt name:'),            
        );
        
        $form['transfer']['transfer_date'] = array(
            '#type' => 'textfield',
            '#title' => t('Transfer date:'),
            '#attributes' => array("readonly" => TRUE),
            '#default_value' => date("d-m-Y")            
        );
       
        
        $transferMeth = array();
        $transferMeth["CD"] = "CD";
        $transferMeth["DVD"] = "DVD";
        $transferMeth["HDD"] = "Hard Drive";
        $transferMeth["NETWORK"] = "Network Transfer";
        $transferMeth["USB"] = "USB";
        
        $form['transfer']['transfer_method'] = array(
            '#type' => 'radios',
            '#title' => t('Transfer medium and method:'),
            '#options' => $transferMeth,
            '#description' => $this->t('e.g. hard drive, CD, DVD, USB stick, network transfer'),    
        );
        
        
        $dataValidation = array();
        $dataValidation[0] = "The donor/depository has provided a tab-delimited text file providing full object paths and filenames for the all objects being submitted, with an MD5 checksum for each object.  The repository will perform automated validation.";
        $dataValidation[1] = "Based on incomplete information supplied by the depositor/donor prior to transfer, the repository will carry out selected content and completeness checks to verify that the transmitted data is what is expected, and that it is complete.";
        $dataValidation[2] = "No data validation will be performed on objects submitted.";        
        
        $form['transfer']['data_validation'] = array(
            '#type' => 'radios',
            '#title' => t('Data Validation:'),
            '#options' => $dataValidation,
            '#description' => $this->t(''),
        );
       
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('button'),
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_depagree_two'),
        );
        
        $form['actions']['submit']['#value'] = $this->t('Next');       
        
        
        return $form;
  }
  
   public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
    $form_state->setRedirect('oeaw_depagree_four');
   }
    
}
