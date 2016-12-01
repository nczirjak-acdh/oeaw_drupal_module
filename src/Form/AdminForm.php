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
        
        $form['prefixes'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('prefixes'),
            '#placeholder' => $this->t('http://fedora.localhost/rest/'),
            '#default_value' => $config->get('fedora_url'),
        );
      
        
        
        /*
        <div class="input_fields_wrap">
        <button class="add_field_button">Add More Fields</button>
        <div><input type="text" name="mytext[]"></div>
        </div>
         * 
         * 
         * 
        */
        
        /*     
            $(document).ready(function() {
            var max_fields      = 10; //maximum input boxes allowed
        var wrapper         = $(".input_fields_wrap"); //Fields wrapper
        var add_button      = $(".add_field_button"); //Add button ID

        var x = 1; //initlal text box count
        $(add_button).click(function(e){ //on add input button click
            e.preventDefault();
            if(x < max_fields){ //max input box allowed
                x++; //text box increment
                $(wrapper).append('<div><input type="text" name="mytext[]"/><a href="#" class="remove_field">Remove</a></div>'); //add input box
            }
        });

        $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
            e.preventDefault(); $(this).parent('div').remove(); x--;
        })
    });*/
        
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
