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
            $convs = json_decode($drift->getAllConversations(['next='.$drift->getLastConversationId()]));
            $items = [];
            foreach ($convs->data as $conv){
                $item = [];
                $contact = json_decode($drift->getContact($conv->contactId));
                if ($contact->data->attributes->email){
                    $item['contact'] = $contact;
                    $item['conv'] = $drift->getConversationMessage($conv->id);
                    $items[] = $item;
                }
            }
            var_dump($items);
            break;
    }
} else {
    echo 'Empty ?method= parameter';
}
