<?php

$request_body = file_get_contents('php://input');
$data = json_decode($request_body,true);

if(!isset($data['dev']) && !isset($_GET['dev']) && !isset($_POST['dev'])) {
	ini_set( 'error_reporting', 0 );
}

include __DIR__ .  '/../../vendor/autoload.php';


include __DIR__ .  '/../src/controller/Devinci.php';
include __DIR__ .  '/../src/controller/BCIT.php';

include __DIR__ .  '/../src/service/Utils.php';
include __DIR__ .  '/../src/service/RequestFilter.php';

include __DIR__ .  '/../src/model/Event.php';



return $data;