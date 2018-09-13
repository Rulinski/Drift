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
    private $contact = null;
    
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
    
    private function setContact($contact)
    {
        $this->contact = $contact;
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
        
        $this->contact = json_decode($resp);
        
        return $resp;
    }
    
    public function getUser($id, $options = [])
    {
        $resp = $this->curlGet($this->users_url . $id);
        
        return $resp;
    }
    
    public function getAllConversations($options = [])
    {
        if (!empty($options)){
            $request = '?';
            $request .= implode('&', $options);
        }
        
        $resp = $this->curlGet($this->conversations_url . $request);
        
        return $resp;
    }
    
    public function saveLastConversationId($conversationId)
    {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/config/conversation.json', $conversationId);
    }
    
    public function getLastConversationId()
    {
        return file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/config/conversation.json');
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
            if ($message->body){
                $body[] = str_replace([';'], '', strip_tags(html_entity_decode($message->body,ENT_QUOTES)));
            }
        }
        
        return $body;
    }
    
    /**
     * @return string
     * @throws \ErrorException
     * https://devdocs.drift.com/docs/authentication-and-scopes
     */
    private function oauth()
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_USERAGENT, 'request');
        $curl->setHeader('Content-Type', 'application/json');
        $options = [
            "clientId"     => $this->clientId,
            "clientSecret" => $this->clientSecret,
            "code"         => $this->code,
            'grantType'    => 'authorization_code',
        ];
        $curl->post('https://driftapi.com/oauth2/token', json_encode($options));
        $curl->close();
        
        return $curl->response;
    }
    
    private function refreshToken()
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_USERAGENT, 'request');
        $curl->setHeader('Content-Type', 'application/json');
        $options = [
            "clientId"     => $this->clientId,
            "clientSecret" => $this->clientSecret,
            "refreshToken" => $this->refreshToken,
            'grantType'    => 'refresh_token',
        ];
        $curl->post('https://driftapi.com/oauth2/token', json_encode($options));
        $curl->close();
        
        return $curl->response;
    }
    
    public function getContactAttr($name)
    {
        if (!empty($this->contact)){
            return $this->contact->data->attributes->{$name};
        }
    }
    
    public function sendConversationsToCRM()
    {
        $conversation_id = $this->getLastConversationId();
        $convs = json_decode($this->getAllConversations(['next=' . $conversation_id]));
        
        $new_data = array_chunk($convs->data, 20);

        $items = [];
        foreach ($new_data[0] as $conv) {
            if ($conv->id == $conversation_id){
                continue;
            }
            
            $item = [];
            $this->getContact($conv->contactId);
            
            $this->saveLastConversationId($conv->id);
            if ($this->getContactAttr('email') && !in_array($this->getContactAttr('_classification'), ['Bad Lead'])){
                $item['conv_id'] = $conv->id;
                $item['contact']['email'] = $this->getContactAttr('email');
                $item['contact']['first_name'] = ($this->getContactAttr('first_name')) ? $this->getContactAttr('first_name') :
                    ($this->getContactAttr('name')) ? $this->getContactAttr('name') : 'empty_name';
                $item['contact']['last_name'] = $this->getContactAttr('last_name');
                $item['contact']['title'] = $this->getContactAttr('employment_title');
                $item['contact']['url_page'] = $this->getContactAttr('recent_entrance_page_url');
                $item['contact']['country'] = $this->getContactAttr('display_location');
                $item['contact']['lead_source'] = 'Drift Chat';
                $item['contact']['lead_source_description'] = $this->getContactAttr('original_entrance_page_title');
                $item['contact']['company'] = $this->getContactAttr('employment_name');
                $item['contact']['drift_id'] = $this->contact->data->id;
                $item['conv']['message'] = $this->getConversationMessage($conv->id);
                $item['conv']['time'] = date('d-m-Y H:i:s', ceil($conv->createdAt / 1000));
                $items[] = $item;
                $this->saveLastConversationId($conv->id);
            }
        }
        
        $curl = new Curl();
        
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_USERAGENT, 'request');
        
        $curl->post($this->crmURL, ['data' => $items]);
        $curl->close();
        
        var_dump($curl->response);
    }
}