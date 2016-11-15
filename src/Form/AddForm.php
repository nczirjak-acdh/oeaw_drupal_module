<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\oeaw\oeawStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddForm extends FormBase {

    public function getFormId() {
        return "add_form";
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attributes']['enctype'] = "multipart/form-data";
        /* get the root elements */
        //$roots = \Drupal\oeaw\oeawStorage::getRootFromDB();
        /* create the array with the roots for the select menu */
        /*foreach ((array) $roots as $key => $value) {
            $value = (array) $value;

            foreach ($value as $v) {
                $v = (array) $v;
                if (!empty($v["\0*\0" . "uri"])) {
                    $rootURI[$v["\0*\0" . "uri"]] = $v["\0*\0" . "uri"];
                }
            }
        }
        
        $form["roots"] = array(
            "#type" => "select",
            "#title" => t("SELECT YOUR ROOT ELEMENT"),
            "#options" =>
            $rootURI,
            "#description" => t("Select plugin."),
        );
        */
        $form['file'] = array(
            '#type' => 'managed_file',
            '#title' => t('FILE'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('xml doc txt simplified docx pdf xsl rdf owl zip ttl xls xlsx csv sql gz zip tar php css html'),
            ),
            '#description' => t('Upload a file, allowed extensions: xml doc txt simplified docx pdf xsl rdf owl zip ttl xls xlsx csv sql gz zip tar php css html'),
        );

        $form['file_sparql'] = array(
            '#type' => 'managed_file',
            '#title' => t('FILE SPARQL'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('sparql'),
            ),
            '#description' => t('Upload a file, allowed extensions: SPARQL'),
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
        if (empty($form_state->getValue('file_sparql'))) {
            $form_state->setErrorByName('file_sparql', $this->t('Please upload a sparql file'));
        }
/*
        if (empty($form_state->getValue('file'))) {
            $form_state->setErrorByName('file', $this->t('Please upload a file'));
        }
 * 
 */
/*
        if (empty($form_state->getValue('roots'))) {
            $form_state->setErrorByName('roots', $this->t('Please select a root element'));
        } 
 */
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        //get the uploaded files values
        $sparqlFileID = $form_state->getValue('file_sparql');
        $sparqlFileID = $sparqlFileID[0];
        
        $fileID = $form_state->getValue('file');
        
        $fileID = $fileID[0];
        // get the root select value
        //$root = $form_state->getValue('roots');

        //create file object with file data        
        $sfObj = file_load($sparqlFileID);
        $fObj = file_load($fileID);

        //get the temp file uri
        $sfUri = $sfObj->getFileUri();
        $fUri = $fObj->getFileUri();
        $mime = mime_content_type($fUri);

        //get file content
        $sfContent = file_get_contents($sfUri);
        $fContent = file_get_contents($fUri);

        //insert the content with the transaction based curl
        $saveFile = \Drupal\oeaw\oeawStorage::insertDataToFedora($fContent, $sfContent, false, $mime);

        if ($saveFile != false) {
            $_SESSION['newFedoraUri'] = $saveFile;

            $url = Url::fromRoute('oeaw_new_resource_result');
            $form_state->setRedirectUrl($url);
        } else {
            drupal_set_message(t('An error occurred and processing did not complete. Please check your sparql query!'), 'error');
        }
    }

}
