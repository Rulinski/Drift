<?php
require_once 'vendor/autoload.php';
//ini_set('display_errors', 1);
//error_reporting(E_ALL^E_NOTICE);

$config = json_decode(file_get_contents(__DIR__.'/config/drift.json'));

$drift = new \Drift\Drift($config);

if (isset($_GET['method'])) {
    switch ($_GET['method']) {
        case 'getAllConversations':
            $convs = json_decode($drift->getAllConversations());
            ob_start();
            include 'templates/get_all_conversations.phtml';
            echo ob_get_clean();
            break;
        case 'getConversation':
            echo $drift->getConversation($_GET['id']);
            break;
        case 'getConversationMessage':
            echo json_encode($drift->getConversationMessage($_GET['id']));
            break;
        case 'getAllContacts':
            echo $drift->getAllContacts();
            break;
        case 'getContact':
            echo $drift->getContact($_GET['id']);
            break;
        case 'sentToCrm':
//            $convs = json_decode($drift->getAllConversations());
            $conversation_id = $drift->getLastConversationId();
            $convs = json_decode($drift->getAllConversations(['next='.$conversation_id]));

            $new_data = array_chunk($convs->data, 10);

            var_dump($new_data[0]);
//            var_dump($convs);die;
            $items = [];
            foreach ($new_data[0] as $conv){
                if ($conv->id == $conversation_id) continue;
                
                $item = [];
                $contact = json_decode($drift->getContact($conv->contactId));
//                $drift->saveLastConversationId($conv->id);
                if ($contact->data->attributes->email && !in_array($contact->data->attributes->_classification,['Bad Lead'])){
                    $item['conv_id'] = $conv->id;
                    $item['contact']['email'] = $contact->data->attributes->email;
                    $item['contact']['first_name'] = $contact->data->attributes->first_name;
                    $item['contact']['last_name'] = $contact->data->attributes->last_name;
                    $item['contact']['title'] = $contact->data->attributes->employment_title;
                    $item['contact']['url_page'] = $contact->data->attributes->recent_entrance_page_url;
                    $item['contact']['country'] = $contact->data->attributes->display_location;
                    $item['contact']['lead_source'] = 'Drift Chat';
                    $item['contact']['lead_source_description'] = $contact->data->attributes->original_entrance_page_title;
                    $item['contact']['company'] = $contact->data->attributes->employment_name;
                    $item['contact']['drift_id'] = $contact->data->id;
                    $item['conv']['message'] = $drift->getConversationMessage($conv->id);
                    $item['conv']['time'] = date('d-m-Y H:i:s', ceil($conv->createdAt / 1000));
                    $conversation_id = $conv->id;
                    $items[] = $item;
                    $drift->saveLastConversationId($conv->id);
                }
            }
    
//            var_dump($items);
//            die;
            $curl = new \Curl\Curl();

            $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_USERAGENT, 'request');
            //        $curl->setHeader('Content-Type', 'application/json');

            $curl->post('https://crm.altoros.com/vital_api.php?method=driftData', ['data' => $items]);
            $curl->close();

            var_dump($curl->response);
            break;
    }
} else {
    echo 'Empty ?method= parameter';
}
