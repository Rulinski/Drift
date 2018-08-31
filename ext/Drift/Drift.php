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
    private $users_url = 'https://driftapi.com/users/list/';
    private $conversations_url = 'https://driftapi.com/conversations/';
    
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
    
    public function getUser($id, $options = [])
    {
        $resp = $this->curlGet($this->users_url . $id);
        
        return $resp;
    }
    
    public function getAllConversations($options = [])
    {
        $resp = $this->curlGet($this->conversations_url);
        
        return $resp;
    }
    
    public function getConversation($id, $options = [])
    {
        $resp = $this->curlGet($this->conversations_url . $id);
        
        return $resp;
    }
    
    public function getConversationMessage($id, $options = [])
    {
        $resp = $this->curlGet($this->conversations_url . $id . '/messages');
        $resp = json_decode($resp);
        $body = [];
        foreach ($resp->data->messages as $message) {
            if ($message->body) {
                $body[] = strip_tags($message->body);
            }
            
        }
        
        return json_encode($body);
    }
}