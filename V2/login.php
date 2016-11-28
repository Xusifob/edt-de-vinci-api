<?php

$data = include 'includes/header.php';

RequestFilter::login($data);

switch($_GET['school']) {
    case Utils::SCHOOL_DEVINCI:
        $id = Devinci::login($data['login'],$data['pass']);
        break;
    case Utils::SCHOOL_BCIT:
        $id = BCIT::login($data['login'],$data['pass']);
        break;
}
if(isset($id)){
    Utils::response(200,['id' => $id]);
}else{
    Utils::response(500,['error' => Utils::ERROR_SERVER]);
}