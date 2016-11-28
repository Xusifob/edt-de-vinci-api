<?php

$data = include 'includes/header.php';


RequestFilter::calendar($data);

switch($_GET['school']) {
    case 'devinci':
        $events = Devinci::getCalendarEvents($data['id']);
        break;
    case 'bcit':
        RequestFilter::login($data);
        BCIT::login($data['login'],$data['pass']);
        $events = BCIT::getCalendarEvents();
        break;
}
if(isset($events)){
    Utils::response(200,['data' => $events]);
}else{
    Utils::response(500,['error' => Utils::ERROR_SERVER]);
}