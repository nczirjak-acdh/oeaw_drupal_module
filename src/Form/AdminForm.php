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
        $prefNum = $this->config('oeaw.settings')->get('prefNum');
        $pref = $this->config('oeaw.settings')->get('prefix_0');
        $val = $this->config('oeaw.settings')->get('value_0');
        $pref2 = $this->config('oeaw.settings')->get('prefix_1');
      /*  echo "<br>";
        echo "<pre>";
        var_dump($this->config('oeaw.settings')->getRawData());
        echo "</pre>";
        */
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
            '#markup' => '<p>' . $this->t('<br/><h2>Prefix settings</h2><br/>') . '</p>',
        ];
        
        $propertys = \Drupal\oeaw\oeawStorage::getAllPropertyForSearch();
        
        if($propertys == false){
            drupal_set_message($this->t('please provide sparql endpoint and fedora url'), 'error');
        }

        $header = array_keys($propertys[0]);
        $header = $header[0];

        // get the uris without the property
        foreach($propertys as $p){                        
           
            if (strpos($p[$header], '#') !== false) {
                $val = explode("#", $p[$header]);
                $lastVal = $val[count($val)-1];                
            } else {
                $val = explode("/", $p[$header]);
                $lastVal = $val[count($val)-1];                
            }
            $val = str_replace($lastVal, "", $p[$header]);
            $select[urlencode($val)] = t($val);
        }
        
        $form['fields']['modules'] = array(
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => t('PREFIXES'),            
            '#prefix' => '<div id="modules-wrapper">',
            '#suffix' => '</div>',
        );

        //$max = $prefNum;
        if(is_null($max)) {
            $max = 0;
            $form_state->set('prefixes_num', $max);
        }
        
        // Add elements that don't already exist
        for($delta=0; $delta<=$max; $delta++) {
            if (!isset($form['fields']['modules'][$delta])) {
                
                $element = array(
                    '#type' => 'select',
                    '#title' => $this->t('Select prefix'),
                    '#options' => $select, 
                    '#default_value' => urlencode('http://purl.org/dc/terms/'),
                );
                
                $form['fields']['modules'][$delta]['prefix_'.$delta] = $element;
                $element = array('#type' => 'textfield','#title' => t('prefix value'),'#required' => FALSE);
                $form['fields']['modules'][$delta]['value_'.$delta] = $element;
            }
        }

        $form['fields']['modules']['add'] = array(
            '#type' => 'submit',
            '#name' => 'addfield',
            '#value' => t('Add more field'),
            '#submit' => array(array($this, 'addfieldsubmit')),
            '#ajax' => array(
                'callback' => array($this, 'addfieldCallback'),
                'wrapper' => 'modules-wrapper',
                'effect' => 'fade',
            ),
        );
        
        $form['fields']['modules']['remove'] = array(
            '#type' => 'submit',
            '#name' => 'removefield',
            '#value' => t('Remove field'),
            '#submit' => array(array($this, 'removefieldsubmit')),
            '#ajax' => array(
                'callback' => array($this, 'addfieldCallback'),
                'wrapper' => 'modules-wrapper',
                'effect' => 'fade',
            ),
        );
        
        $form['prefixes_num'] = array('#type' => 'hidden', '#value' => $max);
        
        return parent::buildForm($form, $form_state);
    }

   
   
    
     /**
    * Ajax submit to add new field.
    */
    public function addfieldsubmit(array &$form, FormStateInterface &$form_state) {
        $max = $form_state->get('fields_count') + 1;
        $form_state->set('fields_count',$max);
        $form_state->setRebuild(TRUE);
    }
    
    /**
    * Ajax submit to remove field.
    */
    public function removefieldsubmit(array &$form, FormStateInterface &$form_state) {
        $max = $form_state->get('fields_count') -1;
        $form_state->set('fields_count',$max);
        $form_state->setRebuild(TRUE);
    }

    /**
    * Ajax callback to add new field.
    */
    public function addfieldCallback(array &$form, FormStateInterface &$form_state) {
        return $form['fields']['modules'];
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

     
        $prefNum = $form_state->getValue('prefixes_num');
        
        for ($index = 0; $index < count($prefNum) + 1; $index++) {
            $val = $form_state->getValue('value_'.$index);
            $url = urldecode($form_state->getValue('prefix_'.$index)); 
            //$prefixes[$val] = urldecode($form_state->getValue('prefix_'.$index));            
            $this->config('oeaw.settings')->set($url, $val )->save();
            $this->config('oeaw.settings')->set('prefix_'.$index, $url )->save();
            $this->config('oeaw.settings')->set('value_'.$index, $val )->save();
        }
        
        $this->config('oeaw.settings')->set('prefNum', $prefNum)->save();

        $this->config('oeaw.settings')->set('fedora_url', $form_state->getValue('fedora_url'))->save();
    
        $this->config('oeaw.settings')->set('sparql_endpoint', $form_state->getValue('sparql_endpoint'))->save();
    
    }

} 