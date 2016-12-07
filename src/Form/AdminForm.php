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
        /*
        foreach($propertys as $p){            
            $select .= "<option value='".$p[$header]."' name='myprefix[]'>".$p[$header]."</option>";
            $select2[] = $p[$header];
        }
       
        
        $form['fields']['modules'] = array(
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => t('sample field'),
            '#description' => t('Explaination about sample field is here, Lorem ipsum dolor sit amet. bla yadda bla yadda. this is a very long description here. Lorem ipsum dolor sit amet. bla yadda bla yadda. this is a very long description here.Lorem ipsum dolor sit amet. bla yadda bla yadda. this is a very long description here.Lorem ipsum dolor sit amet. bla yadda bla yadda. this is a very long description here.Lorem ipsum dolor sit amet. bla yadda bla yadda. this is a very long description here.thank you.'),
            '#prefix' => '<div id="modules-wrapper">',
            '#suffix' => '</div>',
        );

        $max = $form_state->get('fields_count');
        if(is_null($max)) {
            $max = 0;
            $form_state->set('fields_count', $max);
        }

        // Add elements that don't already exist
        for($delta=0; $delta<=$max; $delta++) {
            if (!isset($form['fields']['modules'][$delta])) {
                $element = array(
                    '#type' => 'textfield',
                    '#title' => t('field Name'),            
                );
                
                $form['example_select'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Select element'),
                    '#options' => [
                      '1' => $this->t('One'),
                      '2' => [
                        '2.1' => $this->t('Two point one'),
                        '2.2' => $this->t('Two point two'),
                      ],
                      '3' => $this->t('Three'),
                    ],
                  ];
                
                $form['fields']['modules'][$delta]['prefix'] = $element;
                $element = array('#type' => 'select', '#options' => [
                      '1' => $this->t('One'),
                      '2' => [
                        '2.1' => $this->t('Two point one'),
                        '2.2' => $this->t('Two point two'),
                      ],
                      '3' => $this->t('Three'),
                    ],'#required' => TRUE);
                $form['fields']['modules'][$delta]['value'] = $element;
                $element = array('#type' => 'textfield','#title' => t('sss'),'#required' => FALSE, '#suffix' => '<hr />');
                
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
     */
    
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

     



        $this->config('oeaw.settings')->set('fedora_url', $form_state->getValue('fedora_url'))->save();
    
        $this->config('oeaw.settings')->set('sparql_endpoint', $form_state->getValue('sparql_endpoint'))->save();
    
    }

} 