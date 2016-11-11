<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;


/**
 * @param $request
 * @param $url
 * @param null $data
 * @return string
 */
function curl_request($request,$url,$data = []){

    $cookie_jar = __DIR__ . '/cookie.txt';

    $ch = curl_init();


    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2");
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
//  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_URL,$url);

    if($request === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);

        $postData = '';

        $i = 0;
        if(is_array($data)) {
            foreach ($data as $key => $value) {
                $postData .= "$key=$value";
                if ($i != count($data) - 1) {
                    $postData .= '&';
                }
                $i++;
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    ob_start();      // prevent any output
    curl_exec ($ch); // execute the curl command
    $result = ob_get_clean();  // stop preventing output


    curl_close ($ch);
    unset($ch);

    return $result;
}


/**
 * Login
 */
function login(){

    if(!isset($_POST['login']) || empty($_POST['login']))
        response(400,['Identifiant Requis']);

    if(!isset($_POST['pass']) || empty($_POST['pass']))
        response(400,['Mot de passe Requis']);

    $result = curl_request('POST','https://www.leonard-de-vinci.net/include/php/ident.php',[
        'front_type' => 'default',
        'login' => $_POST['login'],
        'pass' => $_POST['pass'],
    ]);

    if(strpos($result,'Accès refusé') !== false){
        response(401,['error' => 'Identifiant ou Mot de passe incorrect']);
    }
}


function get_calendar_link(){
    $cal = curl_request('GET','https://www.leonard-de-vinci.net?my=edt');

    $dom = new Dom();

    $dom->load($cal);
    $content = $dom->find('.social-box',1);

    $a = $content->find('a',0);
    response(200,['id' => str_replace('/ical_student/','',$a->getAttribute('href'))]);

}


/**
 * @param $code
 * @param $data
 */
function response($code,$data)
{
    header('Content-Type: application/json; charset=utf-8');
    switch ($code) {
        case 401 :
            header("HTTP/1.1 401 Unauthorized",false,$code);
            break;
        case 200 :
            header("HTTP/1.1 200 OK",false,$code);
            break;
        case 400 :
            header('HTTP/1.0 401 Bad Request',false,$code);
    }

    $data = array_merge($data, ['status' => $code]);
    echo json_encode($data);
    die();

}


/**
 * Get the data from the calendar
 */
function get_calendar_data(){

    if(!isset($_GET['id']) || empty($_GET['id'])){
        response(400,['error' => 'Identifiant requis']);
    }

    $result = curl_request('GET','https://www.leonard-de-vinci.net/ical_student/' . $_GET['id']);

    if(strpos($result,'Undefined') !== false){
        response(404,['error' => 'Calendrier non trouvé']);
    }
    else{
        response(200,['calendrier' => $result]);
    }
}