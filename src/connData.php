<?php
namespace Drupal\oeaw;

class connData 
{
    /*      
     * prefixes to sparql querys
     * 
    */    
    public static $prefixes = ''
            . 'PREFIX dct: <http://purl.org/dc/terms/> '
            . 'PREFIX ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> '
            . 'PREFIX premis: <http://www.loc.gov/premis/rdf/v1#> '
            . 'PREFIX acdh: <http://vocabs.acdh.oeaw.ac.at/#> '
            . 'PREFIX fedora: <http://fedora.info/definitions/v4/repository#> '
            . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
            . 'PREFIX owl: <http://www.w3.org/2002/07/owl#>';
    

    
    public function fedoraUrl() {
        if(\Drupal::request()->getHttpHost() == 'drupal.localhost'){            
            $url = 'http://fedora.localhost/rest/';
        }else {
            $url = 'http://fedora.hephaistos.arz.oeaw.ac.at/rest/';
        }        
        return $url;
    }
    
    public function fedoraDownloadUrl() {
        if(\Drupal::request()->getHttpHost() == 'drupal.localhost'){            
            $url = 'http://fedora.localhost/rest/';
        }else {
            $url = 'http://fedora.hephaistos.arz.oeaw.ac.at/rest/';
        }        
        return $url;
    }
    
    public function fedoraUrlwHttp() {
        if(\Drupal::request()->getHttpHost() == 'drupal.localhost'){            
            $url = 'fedora.localhost/rest/';
        }else {
            $url = 'fedora.hephaistos.arz.oeaw.ac.at/rest/';
        }        
        return $url;
    }
    
    
    public static $prefixesToChange = array(        
        "http://fedora.info/definitions/v4/repository#" => "fedora",        
        "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#" => "ebucore",
        "http://www.loc.gov/premis/rdf/v1" => "premis",
        "http://www.loc.gov/premis/rdf/v1#" => "premis",        
        "http://www.jcp.org/jcr/nt/1.0#" => "nt",        
        "http://www.w3.org/2000/01/rdf-schema#" => "rdfs",                
        "http://www.w3.org/ns/ldp#" => "ldp",
        "http://www.iana.org/assignments/relation/" => "iana",
        "https://vocabs.acdh.oeaw.ac.at/#" => "acdh",
        "http://vocabs.acdh.oeaw.ac.at/#" => "acdh",
        "http://vocabs.acdh.ac.at/#" => "acdh",
        "http://purl.org/dc/elements/1.1/" => "dc",
        "http://purl.org/dc/terms/" => "dct",
        "http://www.w3.org/2002/07/owl#" => "owl",
        "http://xmlns.com/foaf/0.1/" => "foaf",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf",
    );
    
    
    
}


