<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\file\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use TCPDF;

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
        if(!isset($_SESSION['deep_agree_form_form_holds_session'])) {
            $_SESSION['deep_agree_form_form_holds_session'] = true;
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
        $form1 = $this->store->get('form1Val');
        $form2 = $this->store->get('form2Val');
        $form3 = $this->store->get('form3Val');
        $form4 = $this->store->get('form4Val');
     
        $fileMetaData = $this->store->get('material_metadata_file');
        $fileNameScheme = $this->store->get('material_name_scheme');
        $filePreview = $this->store->get('material_preview');
        
        $fileMetaData = $fileMetaData[0];
        $fileNameScheme = $fileNameScheme[0];
        $filePreview = $filePreview[0];
        
        $fmdObj = file_load($fileMetaData);
        $form2['material_metadata_file'] = $_SERVER['HTTP_HOST'].'/sites/default/files/'.$form2['material_acdh_repo_id'].'/'.$fmdObj->getFilename();        
        
        $fnsObj = file_load($fileNameScheme);
        $form2['material_name_scheme'] = $_SERVER['HTTP_HOST'].'/sites/default/files/'.$form2['material_acdh_repo_id'].'/'.$fnsObj->getFilename();
        
        $fpObj = file_load($filePreview);
        $form2['material_preview'] = $_SERVER['HTTP_HOST'].'/sites/default/files/'.$form2['material_acdh_repo_id'].'/'.$fpObj->getFilename();
        
        $dv = \Drupal\oeaw\ConnData::getDataValidation();
        $form3['data_validation'] = $dv[$form3['data_validation']];
        
        
        
        $tcpdf = new TCPDF();
        
        $tcpdf->SetCreator(PDF_CREATOR);
        $tcpdf->SetAuthor('ACDH');
        $tcpdf->SetTitle('Deposition Agreement');
        $tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // remove default header/footer
        $tcpdf->setPrintHeader(true);
        $tcpdf->setPrintFooter(false);
        $tcpdf->SetFont('times', 'r', 14);
        $PDF_HEADER_LOGO = "";
        $PDF_HEADER_LOGO_WIDTH = "20";
        $PDF_HEADER_TITLE = "ACDH  - DEPOSITION AGREEMENT FORM";
        $PDF_HEADER_STRING = "";
        
        $tcpdf->SetHeaderData($PDF_HEADER_LOGO, $PDF_HEADER_LOGO_WIDTH, $PDF_HEADER_TITLE, $PDF_HEADER_STRING);
        
       //generate the pages
        $this->generatePdfPage($tcpdf, $form1, "Depositor");
        $this->generatePdfPage($tcpdf, $form2, "Description Of Material, Extent, Files");
        $this->generatePdfPage($tcpdf, $form3, "Transfer Procedures");
        $this->generatePdfPage($tcpdf, $form4, "Creators");       
 
         //Close and output PDF document
        $tcpdf->Output($_SERVER['DOCUMENT_ROOT'].'/sites/default/files/'.$form2['material_acdh_repo_id'].'/'.$form2['material_acdh_repo_id'].'.pdf', 'F');
        
        $this->deleteStore($form1);
        $this->deleteStore($form2);
        $this->deleteStore($form3);
        $this->deleteStore($form4);

        $this->store->delete('form1Val');
        $this->store->delete('form2Val');
        $this->store->delete('form3Val');
        $this->store->delete('form4Val');
        $response = new RedirectResponse(\Drupal::url('oeaw_form_success', ['url' => $form2['material_acdh_repo_id']]));
        $response->send();
        return;
    }
    
    public function generatePdfPage(TCPDF $tcpdf, array $formData, string $title ): TCPDF{
        
         // add a page
        $tcpdf->AddPage();
        // set some text to print
        $txt = "<h1>".$title."</h1><br/>";
       
        foreach($formData as $k => $v){
            
            if(\Drupal\oeaw\ConnData::getPDFLng($k)){
                $text = \Drupal\oeaw\ConnData::getPDFLng($k);
            }else {
                $text = $k;
            }
            
            if($k === "candidate_confirmation" || $k === "fields_count"){
                continue;
            }
            
            if(is_array($v)){
                $txt .= '<table cellspacing="0" cellpadding="1" border="1">
                    <tr>
                        <td>'.$text.'</td><td>';
                        foreach($v as $key => $val) {
                            if($val){
                                $txt .= $key.'<br />';
                            }
                        }
                    $txt .= '</td></tr>
                    </table>';
            }else {
                $txt .= '
                <table cellspacing="0" cellpadding="1" border="1">
                    <tr>
                        <td>'.$text.'</td>
                        <td>'.$v.'</td>        
                    </tr>
                </table>';
            }      
        }
        $tcpdf->writeHTML($txt, true, false, false, false, '');
        // print a block of text using Write()
        
        return $tcpdf;
    }
    
    protected function deleteStore(array $array) {
                
        foreach ($array as $key => $value) {
            $this->store->delete($key);            
        }
    }
    
    
    
    
}
