<?php

namespace Drift;

use Curl\Curl as Curl;

/**
 * Class Drift
 * @package Drift
 */
class Drift
{
    private $contacts_url = 'https://driftapi.com/contacts/';
    
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }
    
    public function getAllContacts()
    {
        $resp = $this->curlGet($this->contacts_url);
        
        return $resp;
    }
    
    private function curlGet($url, $options = [])
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_USERAGENT, 'request');
        $curl->setHeader('Authorization', 'Bearer ' . $this->accessToken);
        
        $curl->get($url);
        $curl->close();
        
        return $curl->response;
    }
    
    public function getContact($id, $options = [])
    {
        $resp = $this->curlGet($this->contacts_url . $id);
        
        return $resp;
    }
}