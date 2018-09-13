<?php
require_once 'vendor/autoload.php';

$config = json_decode(file_get_contents(__DIR__.'/config/drift.json'));

$drift = new \Drift\Drift($config);
date_default_timezone_set("Europe/Minsk");

if (isset($_GET['method'])) {
    switch ($_GET['method']) {
        case 'getAllConversations':
            $convs = json_decode($drift->getAllConversations(['next='.$_GET['next'], 'limit=100']));
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
        case 'sendToCrm':
            $drift = $drift->sendConversationsToCRM();
            break;
    }
} else {
    echo 'Empty ?method= parameter';
}
