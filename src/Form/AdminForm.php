<?php

/**
 * @file
 * Contains Drupal\xai\Form\SettingsForm.
 */

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\oeaw\Form
 */
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'oeaw.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state) {
    
        $config = $this->config('oeaw.settings');
                
        $form['intro'] = [
            '#markup' => '<p>' . $this->t('<h2>Oeaw Module Settings</h2><br/>') . '</p>',
        ];
        
        $form['sparql_endpoint'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Sparql Endpoint'),
            '#placeholder' => $this->t('http://blazegraph:9999/blazegraph/sparql'),
            '#default_value' => $config->get('sparql_endpoint'),
        );
        
        $form['fedora_url'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Fedora Url'),
            '#placeholder' => $this->t('http://fedora.localhost/rest/'),
            '#default_value' => $config->get('fedora_url'),
        );
        
        $form['prefixes_intro'] = [
            '#markup' => '<p>' . $this->t('<br/><h2>Prefixes settings</h2><br/>') . '</p>',
        ];
        
        $propertys = \Drupal\oeaw\oeawStorage::getAllPropertyForSearch();
        
        if($propertys == false){
            drupal_set_message($this->t('please provide sparql endpoint and fedora url'), 'error');
        }
        
        $header = array_keys($propertys[0]);
        $header = $header[0];
        
        foreach($propertys as $p){            
            $select .= "<option value='".$p[$header]."' name='myprefix[]'>".$p[$header]."</option>";
;        }
        
        
        /*
         * 
         * <select>
            <option value="volvo">Volvo</option>  
        </select>
         * 
         */
        
        $form['input_fields'] = [
            '#markup' => $this->t('
                <div class="input_fields_wrap">
                    <button class="add_field_button">Add New Property</button>
                    <div><select id="myprefix" name="myprefix[]">'.$select.'</select> : <input type="text" name="myprefixvalue[]"></div>
                </div>'),
        ];
       
        
        
        return parent::buildForm($form, $form_state);
    }

    
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

       

        
        $this->config('oeaw.settings')->set('fedora_url', $form_state->getValue('fedora_url'))->save();
    
        $this->config('oeaw.settings')->set('sparql_endpoint', $form_state->getValue('sparql_endpoint'))->save();
    
    }

} 