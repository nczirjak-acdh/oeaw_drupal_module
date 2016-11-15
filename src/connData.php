<?php
namespace Drupal\oeaw;

class connData 
{
   

    public function sparqlEndpoint() {
        if(\Drupal::request()->getHttpHost() == 'drupal.localhost'){            
            $url = 'http://blazegraph:9999/blazegraph/sparql';
        }else {
            $url = 'http://blazegraph/blazegraph/sparql';
        }        
        return $url;
    }
    
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
    
    
    
    
}

