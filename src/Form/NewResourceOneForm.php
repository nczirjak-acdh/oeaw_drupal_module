<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormStateInterface;

class NewResourceOneForm extends NewResourceFormBase {

    /**
     * {@inheritdoc}.
     */
    public function getFormId() {
        return 'newresource_form_one';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);

        $form['#attributes']['enctype'] = "multipart/form-data";

        /* get the root elements */
        $roots = \Drupal\oeaw\oeawStorage::getRootFromDB(true);
        
        $form["roots"] = array(
            "#type" => "select",
            "#title" => t("SELECT YOUR ROOT ELEMENT"),
            '#required' => TRUE,
            "#options" =>
            $roots,
            '#default_value' => $this->store->get('roots') ? $this->store->get('roots') : '',
        );

        
        $classes = \Drupal\oeaw\oeawStorage::getClass();
        

        $form['class'] = array(
            '#type' => 'select',
            '#title' => $this->t('SELECT YOUR CLASS'),
            '#required' => TRUE,
            "#options" =>
            $classes,
            '#default_value' => $this->store->get('class') ? $this->store->get('class') : '',
        );

        $form['actions']['submit']['#value'] = $this->t('Next');

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $form1Elements['root'] = $form_state->getValue('roots');
        $form1Elements['class'] = $form_state->getValue('class');
        $this->store->set('form1Elements', $form1Elements);
        $this->store->set('roots', $form_state->getValue('roots'));
        $this->store->set('class', $form_state->getValue('class'));
        $form_state->setRedirect('oeaw_newresource_two');
    }

}
