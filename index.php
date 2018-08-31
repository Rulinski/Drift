<?php
require_once 'vendor/autoload.php';
//ini_set('display_errors', 1);
//error_reporting(E_ALL^E_NOTICE);

$config = json_decode(file_get_contents(__DIR__.'/config/drift.json'));

$drift = new \Drift\Drift($config);

if (isset($_GET['method'])) {
    switch ($_GET['method']) {
        case 'getAllContacts':
            echo $drift->getAllContacts();
            break;
        case 'getContact':
            echo $drift->getContact($_GET['id']);
            break;
    }
} else {
    echo 'Empty ?method= parameter';
}
