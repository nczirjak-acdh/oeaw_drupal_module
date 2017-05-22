<?php
namespace Drupal\oeaw;

class ConnData 
{
    /*      
     * prefixes to sparql querys
     * 
    */    
    public static $prefixes = ''
            . 'PREFIX dct: <http://purl.org/dc/terms/> '
            . 'PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> '
            . 'PREFIX acdh: <https://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> '
            . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>';
    
  
    public static $prefixesToBlazegraph = array(
        "dct" => "http://purl.org/dc/terms/"        
    );
    
    public static $prefixesToChange = array(        
        "http://fedora.info/definitions/v4/repository#" => "fedora",
        "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#" => "ebucore",
        "http://www.loc.gov/premis/rdf/v1#" => "premis",
        "http://www.jcp.org/jcr/nt/1.0#" => "nt",
        "http://www.w3.org/2000/01/rdf-schema#" => "rdfs",
        "http://www.w3.org/ns/ldp#" => "ldp",
        "http://www.iana.org/assignments/relation/" => "iana",
        "https://vocabs.acdh.oeaw.ac.at/#" => "acdh",
        "http://purl.org/dc/elements/1.1/" => "dc",
        "http://purl.org/dc/terms/" => "dct",
        "http://purl.org/dc/terms/" => "dcterms",
        "http://purl.org/dc/terms/" => "dcterm",
        "http://www.w3.org/2002/07/owl#" => "owl",
        "http://xmlns.com/foaf/0.1/" => "foaf",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf",
        "http://www.w3.org/2004/02/skos/core#" => "skos",
        //"http://xmlns.com/foaf/spec/" => "foaf"
    );
    
    public static $imageProperty = "http://xmlns.com/foaf/0.1/Image";
    public static $imageThumbnail = "http://xmlns.com/foaf/0.1/thumbnail";
    public static $rdfType = "http://www.w3.org/1999/02/22-rdf-syntax-ns#type";
    public static $fedoraBinary = "http://fedora.info/definitions/v4/repository#Binary";
    public static $foafName = "http://xmlns.com/foaf/0.1/name";
    
    public static function getPDFLng(string $lng): string {
        

        $lngData = array(
            'title' => 'Title',
            'l_name' => 'Last Name',
            'f_name' => 'First Name',
            'institution' => 'Institution',
            'city' => 'City',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'email' => 'Email',
            'phone' => 'Phone',
            'material_acdh_repo_id' => 'Material ACDH RepoID',
            'material_title' => 'Material Title',
            'material_ipr' => 'Material IPR',
            'material_metadata' => 'Metadata',
            'material_metadata_file' => 'Metadata file',
            'material_preview' => 'Preview',
            'material_mat_licence' => 'Material Licence',
            'material_scope_content_statement' => 'Scope Content Statement',
            'material_file_size_byte' => 'File size byte',
            'material_file_number' => 'File Number',
            'material_folder_number' => 'Folder Number',
            'material_soft_req' => 'Software Req.',
            'material_arrangement' => 'Arrangement',
            'material_name_scheme' => 'Name Scheme',
            'material_other_file_type' => 'Other File Type',
            'material_other_file_formats' => 'Other File Formats',
            'material_file_formats' => 'File Formats',
            'material_file_types' => 'File types',
            'folder_name' => 'Folder Name',
            'transfer_date' => 'Transfer Date',
            'transfer_method' => 'Transfer Method',
            'data_validation' => 'Data Validation',
            'creator_title_' => 'Creator Title',
            'creator_l_name_' => 'Creator Last Name',
            'creator_f_name_' => 'Creator First Name',
            'creator_institution_' => 'Creator Institution_',
            'creator_city_' => 'Creator City_',
            'creator_address_' => 'Creator Address',
            'creator_zipcode_' => 'Creator Zipcode',
            'creator_phone_' => 'Creator Phone',
            'creator_email_' => 'Creator Email',
            'fields_count_' => 'The number of the Creators'
        );
        
        if(array_key_exists($lng, $lngData)){
            return $lngData[$lng];
        }else{
            foreach($lngData as $key => $val){
                $lngE = explode("_", $lng);
                $lngE = end($lngE);
                $lngN = str_replace('_'.$lngE, '', $lng);
                if(strpos($key, $lngN) !== false){
                    return $val;
                }
            }
        }
        return false;        
    }
    
    
    public static function getMaterialLicences(){
        $licenes = array( 
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
            'Copyright Not Evaluated' => t('Copyright Not Evaluated')
        );
        return $licenes;
    }
    
    public static function getFileTypes(){        
        $fileTypes = array();
        $fileTypes["3DVirtual"] = "3D Data and Virtual Reality";
        $fileTypes["AudioFiles"] = "Audio Files";
        $fileTypes["Database"] = "DataBase";        
        $fileTypes["Images"] = "Images (raster)";        
        $fileTypes["PDFDocuments"] = "PDF Documents";
        $fileTypes["Spreadsheets"] = "Spreadsheets";
        $fileTypes["StructFiles"] = "Structured text files (e. g. XML files)";
        $fileTypes["TextDocuments"] = "Text Documents";
        $fileTypes["VectorImages"] = "Vector Images";
        $fileTypes["VideoFiles"] = "Video Files";
        $fileTypes["Websites"] = "Websites";
        return $fileTypes;             
    }
    
    public static function getFileFormats(){
        $fileFormats = array();
        $fileFormats["AAC_MP4"]="AAC/MP4";
        $fileFormats["AI"]="AI";
        $fileFormats["AIFF"]="AIFF";
        $fileFormats["ASF_WMV"]="ASF/WMV";
        $fileFormats["AVI"]="AVI";
        $fileFormats["BAK"]="BAK";
        $fileFormats["BMP"]="BMP";
        $fileFormats["BWF"]="BWF";
        $fileFormats["CGM"]="CGM";
        $fileFormats["COLLADA"]="COLLADA";
        $fileFormats["CPT"]="CPT";
        $fileFormats["CSV"]="CSV";
        $fileFormats["DBF"]="DBF";
        $fileFormats["DNG"]="DNG";
        $fileFormats["DOC"]="DOC";
        $fileFormats["DOCX"]="DOCX";
        $fileFormats["DTD"]="DTD";
        $fileFormats["DWF"]="DWF";
        $fileFormats["DWG"]="DWG";
        $fileFormats["DXF"]="DXF";
        $fileFormats["FLAC"]="FLAC";
        $fileFormats["FLV"]="FLV";
        $fileFormats["FMP"]="FMP";
        $fileFormats["GIF"]="GIF";
        $fileFormats["HTML"]="HTML";
        $fileFormats["JPEG"]="JPEG";
        $fileFormats["JPEG2000"]="JPEG2000";
        $fileFormats["JSON"]="JSON";
        $fileFormats["MAFF"]="MAFF";
        $fileFormats["MDB"]="MDB";
        $fileFormats["MHTML"]="MHTML";
        $fileFormats["MJ2"]="MJ2";
        $fileFormats["MKV"]="MKV";
        $fileFormats["MOV"]="MOV";
        $fileFormats["MP3"]="MP3";
        $fileFormats["MP4"]="MP4";
        $fileFormats["MPEG"]="MPEG";
        $fileFormats["MXF"]="MXF";
        $fileFormats["OBJ"]="OBJ";
        $fileFormats["ODB"]="ODB";
        $fileFormats["ODS"]="ODS";
        $fileFormats["ODT"]="ODT";
        $fileFormats["OGG"]="OGG";
        $fileFormats["PDF (other)"]="PDF (other)";
        $fileFormats["PDF_A-1"]="PDF/A-1";
        $fileFormats["PDF_A-2"]="PDF/A-2";
        $fileFormats["PDF_A-3"]="PDF/A-3";
        $fileFormats["PLY"]="PLY";
        $fileFormats["PNG"]="PNG";
        $fileFormats["PostScript"]="PostScript";
        $fileFormats["PSD"]="PSD";
        $fileFormats["RF64_MBWF"]="RF64/MBWF";
        $fileFormats["RTF"]="RTF";
        $fileFormats["SGML"]="SGML";
        $fileFormats["SIARD"]="SIARD";
        $fileFormats["SQL"]="SQL";
        $fileFormats["STL"]="STL";
        $fileFormats["SVG"]="SVG";
        $fileFormats["SXC"]="SXC";
        $fileFormats["SXW"]="SXW";
        $fileFormats["TIFF"]="TIFF";
        $fileFormats["TSV"]="TSV";
        $fileFormats["TXT"]="TXT";
        $fileFormats["U3D"]="U3D";
        $fileFormats["VRML"]="VRML";
        $fileFormats["WARC"]="WARC";
        $fileFormats["WAV"]="WAV";
        $fileFormats["WMA"]="WMA";
        $fileFormats["X3D"]="X3D";
        $fileFormats["XHTML"]="XHTML";
        $fileFormats["XLS"]="XLS";
        $fileFormats["XLSX"]="XLSX";
        $fileFormats["XML"]="XML";
        $fileFormats["XSD"]="XSD";
        
        return $fileFormats;
    }
    
    public static function getTransferMedium(){
        $transferMeth = array();
        $transferMeth["CD"] = "CD";
        $transferMeth["DVD"] = "DVD";
        $transferMeth["HDD"] = "Hard Drive";
        $transferMeth["NETWORK"] = "Network Transfer";
        $transferMeth["USB"] = "USB";
        return $transferMeth;
    }
    
    public static function getDataValidation(){
        $dataValidation = array();
        $dataValidation[0] = "The donor/depository has provided a tab-delimited text file providing full object paths and filenames for the all objects being submitted, with an MD5 checksum for each object.  The repository will perform automated validation.";
        $dataValidation[1] = "Based on incomplete information supplied by the depositor/donor prior to transfer, the repository will carry out selected content and completeness checks to verify that the transmitted data is what is expected, and that it is complete.";
        $dataValidation[2] = "No data validation will be performed on objects submitted.";        
        return $dataValidation;
    }
    
}


