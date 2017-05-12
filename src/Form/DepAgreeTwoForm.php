<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DepAgreeTwoForm extends DepAgreeBaseForm{
    
    public function getFormId() {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form = parent::buildForm($form, $form_state);
        
        $form['depositor_agreement_title'] = array(
            '#markup' => '<h1><b>Deposition agreement</b></h1>',
        );
        
        $form['material'] = array(
            '#type' => 'fieldset',
            '#title' => t('<h2><b>Description Of Material</b></h2>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );
        
        $form['material']['acdh_repo_id'] = array(
            '#type' => 'textfield',
            '#title' => t('ACDH-repo ID:'),
            
            '#default_value' => substr( md5(rand()), 0, 20),
            '#attributes' => array("readonly" => TRUE),
            '#description' => $this->t('string used as an internal identifier for the deposited resources'),
        );
        
        $form['material']['title'] = array(
            '#type' => 'textfield',
            '#title' => t('Title:'),
            
            '#description' => $this->t(''),
        );
        
        $form['material']['ipr'] = array(
            '#type' => 'textarea',
            '#title' => t('Intellectual Property Rights (IPR):'),
            
            '#description' => $this->t('Intellectual property rights including, but not limited to copyrights, related (or neighbouring) rights and database rights'),
        );
        
        $form['material']['metadata'] = array(
            '#type' => 'textarea',
            '#title' => t('Metadata:'),
            
            '#description' => $this->t('is the information that may serve to identify, discover, interpret, manage, and describe content and structure.'),
        );
        
        $form['material']['file'] = array(
            '#type' => 'managed_file',
            '#title' => t('Metadata Resource:'),                
            '#upload_validators' => array(
                'file_validate_extensions' => array('xml doc txt simplified docx pdf jpg png tiff gif bmp'),
             ),
            '#description' => $this->t(''),
        );
        
        $form['material']['preview'] = array(
            '#type' => 'managed_file',
            '#title' => t('Preview:'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('xml doc txt simplified docx pdf jpg png tiff gif bmp'),
             ),
            '#description' => $this->t('A reduced size or length audio and/or visual representation of Content, in the form of one or more images, text files, audio files and/or moving image files.'),
        );    
        
        $form['material']['licence'] = array(
            '#type' => 'select',
            '#default_value' => 'CC-BY',
            '#options' => array(
                'Public Domain Mark' => t('Public Domain Mark'),
                'No Copyright - non commercial re-use only' => t('No Copyright - non commercial re-use only'),
                'No Copyright - other known legal restrictions ' => t('No Copyright - other known legal restrictions '),
                'CC0' => t('CC0'),
                'CC-BY' => t('CC-BY'),
                'CC-BY-SA' => t('CC-BY-SA'),
                'CC-BY-ND' => t('CC-BY-ND'),
                'CC-BY-NC' => t('CC-BY-NC'),
                'CC-BY-NC-SA' => t('CC-BY-NC-SA'),
                'CC-BY-NC-ND' => t('CC-BY-NC-ND'),
                'In Copyright' => t('In Copyright'),
                'In Copyright - Educational Use Permitted' => t('In Copyright - Educational Use Permitted'),
                'In Copyright - EU Orphan Work' => t('In Copyright - EU Orphan Work'),
                'Copyright Not Evaluated' => t('Copyright Not Evaluated'),                
            ),
            '#title' => t('Licence:'),
            
            '#description' => $this->t(''),
        );
        
        $form['material']['scope_content_statement'] = array(
            '#type' => 'textarea',
            '#title' => t('Scope and content statement:'),
            
            '#description' => $this->t('Provide a description of genres, purpose, and content of the resources being deposited.'),
        );
        
        
        
        $form['creators_title3'] = array(
            '#markup' => '<br><br>',
        );
        
        
        $form['extent'] = array(
            '#type' => 'fieldset',
            '#title' => t('<h2><b>Extent</b></h2>'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,  
        );
              
        $form['extent']['file_size_byte'] = array(
            '#type' => 'textfield',
            '#title' => t('Overall file size in bytes:'),
            
            '#description' => $this->t(''),
        );
        
        $form['extent']['file_number'] = array(
            '#type' => 'textfield',
            '#title' => t('Number of files:'),
            
            '#description' => $this->t(''),
        );
        
        $form['extent']['folder_number'] = array(
            '#type' => 'textfield',
            '#title' => t('Number of folders:'),
            
            '#description' => $this->t(''),
        );
        
        
        $fileTypes = array();
        $fileTypes["Database"] = "DataBase";
        $fileTypes["Spreadsheets"] = "Spreadsheets";
        $fileTypes["Images"] = "Images";
        $fileTypes["TextDocument"] = "Text Documents";
        
        $form['file_types'] = array(
            '#type' => 'checkboxes',
            '#title' => t('List of file types included:'),            
            '#options' => $fileTypes,
            '#description' => $this->t(''),
        );
        
        $fileFormats = array();
        $fileFormats["PDF_A-1"]="PDF/A-1";
        $fileFormats["PDF_A-2"]="PDF/A-2";
        $fileFormats["PDF_A-3"]="PDF/A-3";
        $fileFormats["PDF (other)"]="PDF (other)";
        $fileFormats["ODT"]="ODT";
        $fileFormats["DOCX"]="DOCX";
        $fileFormats["DOC"]="DOC";
        $fileFormats["RTF"]="RTF";
        $fileFormats["SXW"]="SXW";
        $fileFormats["TXT"]="TXT";
        $fileFormats["XML"]="XML";
        $fileFormats["SGML"]="SGML";
        $fileFormats["HTML"]="HTML";
        $fileFormats["DTD"]="DTD";
        $fileFormats["XSD"]="XSD";
        $fileFormats["TIFF"]="TIFF";
        $fileFormats["DNG"]="DNG";
        $fileFormats["PNG"]="PNG";
        $fileFormats["JPEG"]="JPEG";
        $fileFormats["GIF"]="GIF";
        $fileFormats["BMP"]="BMP";
        $fileFormats["PSD"]="PSD";
        $fileFormats["CPT"]="CPT";
        $fileFormats["JPEG2000"]="JPEG2000";
        $fileFormats["SVG"]="SVG";
        $fileFormats["CGM"]="CGM";
        $fileFormats["DXF"]="DXF";
        $fileFormats["DWG"]="DWG";
        $fileFormats["PostScript"]="PostScript";
        $fileFormats["AI"]="AI";
        $fileFormats["DWF"]="DWF";
        $fileFormats["CSV"]="CSV";
        $fileFormats["TSV"]="TSV";
        $fileFormats["ODS"]="ODS";
        $fileFormats["XLSX"]="XLSX";
        $fileFormats["SXC"]="SXC";
        $fileFormats["XLS"]="XLS";
        $fileFormats["SIARD"]="SIARD";
        $fileFormats["SQL"]="SQL";
        $fileFormats["JSON"]="JSON";
        $fileFormats["MDB"]="MDB";
        $fileFormats["FMP"]="FMP";
        $fileFormats["DBF"]="DBF";
        $fileFormats["BAK"]="BAK";
        $fileFormats["ODB"]="ODB";
        $fileFormats["MKV"]="MKV";
        $fileFormats["MJ2"]="MJ2";
        $fileFormats["MP4"]="MP4";
        $fileFormats["MXF"]="MXF";
        $fileFormats["MPEG"]="MPEG";
        $fileFormats["AVI"]="AVI";
        $fileFormats["MOV"]="MOV";
        $fileFormats["ASF_WMV"]="ASF/WMV";
        $fileFormats["OGG"]="OGG";
        $fileFormats["FLV"]="FLV";
        $fileFormats["FLAC"]="FLAC";
        $fileFormats["WAV"]="WAV";
        $fileFormats["BWF"]="BWF";
        $fileFormats["RF64_MBWF"]="RF64/MBWF";
        $fileFormats["AAC_MP4"]="AAC/MP4";
        $fileFormats["MP3"]="MP3";
        $fileFormats["AIFF"]="AIFF";
        $fileFormats["WMA"]="WMA";
        $fileFormats["X3D"]="X3D";
        $fileFormats["COLLADA"]="COLLADA";
        $fileFormats["OBJ"]="OBJ";
        $fileFormats["PLY"]="PLY";
        $fileFormats["VRML"]="VRML";
        $fileFormats["U3D"]="U3D";
        $fileFormats["STL"]="STL";
        $fileFormats["XHTML"]="XHTML";
        $fileFormats["MHTML"]="MHTML";
        $fileFormats["WARC"]="WARC";
        $fileFormats["MAFF"]="MAFF";
        
        $form['file_formats'] = array(
            '#type' => 'checkboxes',
            '#title' => t('List of file formats included:'),
            
            '#options' => $fileFormats,
            '#description' => $this->t(''),
        );
        
        
        $form['extent']['soft_req'] = array(
            '#type' => 'textfield',
            '#title' => t('Software requirements:'),            
            '#description' => $this->t('list any software programs formats that are not typically used in a standard office environment, that are required to access content being transferred'),
        );
        
        $form['extent']['arrangement'] = array(
            '#type' => 'textfield',
            '#title' => t('Arrangement:'),            
            '#description' => $this->t('The aim is to give a logical and coherent overall view of the whole set of objects, describe folder structure, nature of relationship between objects and metadata, etc.  If necessary, attach diagrams or screenshots from the original system'),
        );
        
        $form['extent']['name_scheme'] = array(
            '#type' => 'textfield',
            '#title' => t('Naming scheme:'),            
            '#description' => $this->t('Provide if one exists'),
        );
        
        
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('button'),
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_depagree_one'),
        );
        
        //create the next button to the form second page
        $form['actions']['submit']['#value'] = $this->t('Next');
        
        return $form;
    } 
  
    public function submitForm(array &$form, FormStateInterface $form_state) {   
        $form_state->setRedirect('oeaw_depagree_three');
    }
    
}
