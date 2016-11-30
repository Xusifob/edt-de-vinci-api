<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

define('DEVINCI_LOGIN_URL','https://www.leonard-de-vinci.net/include/php/ident.php');
define('BCIT_LOGIN_URL','https://bss.bcit.ca/owa_prod/twbkwbis.P_ValLogin');
define('BCIT_CALENDAR_URL','https://bss.bcit.ca/owa_prod/bwskfshd.P_CrseSchd?start_date_in=');
define('DEVINCI_CALENDAR_LINK','https://www.leonard-de-vinci.net/ical_student/');


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
                $postData .= curl_escape($ch,$key) . '=' . curl_escape($ch,$value);
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
 * @param bool|true $echo
 */
function login($echo = true){

    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body,true);

    if(!isset($data['login']) || empty($data['login']))
        response(400,['ID_REQUIRED']);

    if(!isset($data['pass']) || empty($data['pass']))
        response(400,['PASSWORD_REQUIRED']);

    if(!isset($data['school']) || empty($data['school']))
        response(400,['SCHOOL_REQUIRED']);

    switch ($data['school']){
        case 'devinci':
            $result = curl_request('POST', DEVINCI_LOGIN_URL, [
                'front_type' => 'default',
                'login' => $data['login'],
                'pass' => $data['pass'],
            ]);

            if (strpos($result, 'Accès refusé') !== false) {
                response(401, ['error' => 'ERROR_ID_PASSWORD_INCORRECT']);
            }
            // Get the link
            get_calendar_link();
            break;
        case 'bcit':
            $result = curl_request('POST', BCIT_LOGIN_URL, [
                'sid' => $data['login'],
                'PIN' => $data['pass'],
            ]);
            if (strpos($result, 'Invalid') !== false) {
                response(401, ['error' => 'ERROR_ID_PASSWORD_INCORRECT']);
            }else{
                if($echo) {
                    response(200, ['id' => $data['login']]);
                }
            }
            break;
    }


    if($data['school'] == 'devinci') {

    }elseif($data['school'] == 'bcit'){

    }
}


function get_calendar_link(){
    $cal = curl_request('GET','https://www.leonard-de-vinci.net?my=edt');


    try {
        $dom = new Dom();

        $dom->load($cal);

        /** @var Dom\AbstractNode $a */
        $a = $dom->find('a[href^="/ical_student/]',0);
        response(200, ['id' => str_replace('/ical_student/', '', $a->getAttribute('href'))]);
    }catch (Exception $e){
        response(500,['error' => $e->getMessage()]);
    }

}


/**
 * @param $code
 * @param $data
 * @param $is_json
 */
function response($code,$data, $is_json = true){

    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept",false);
    // switch ($code) {
    //     case 401 :
    //      //   header("HTTP/1.1 401 Unauthorized",false,$code);
    //         break;
    //     case 200 :
    //      //   header('Content-Type: application/json; charset=utf-8',false);
    //         header("HTTP/1.1 200 OK",false,$code);
    //         break;
    //     case 400 :
    //         header('HTTP/1.0 401 Bad Request',false,$code);
    //         break;
    // }

    if($is_json) {
        $data = array_merge($data, ['status' => $code]);
        echo json_encode($data);
    }else{
        echo $data;
    }
    die();

}


/**
 * Get the data from the calendar
 */
function get_calendar_data(){

    if(!isset($_GET['id']) || empty($_GET['id'])){
        response(400,['error' => 'ID_REQUIRED']);
    }

    $result = curl_request('GET',DEVINCI_CALENDAR_LINK . $_GET['id']);

    if(strpos($result,'Undefined') !== false){
        response(404,['error' => 'CALENDAR_NOT_FOUND']);
    }
    else{
        response(200,$result,false);
    }
}


/**
 * @param $date
 * @return array
 */
function get_bcit_calendar_data($date){

    $url = BCIT_CALENDAR_URL . $date;

    $result = curl_request('GET',$url);


    preg_match('/<table  CLASS="datadisplaytable" SUMMARY="This layout table is used to present the weekly course schedule." WIDTH="80%">.+<\/table>/is',$result,$table);

    $dom = new Dom;

    if(isset($table[0])){

        $dom->load($table[0]);


        $rows = $dom->find('tr');

        $matches = [];
        // Add an array to handle all rowspans
        $rowspans = [];
        /** @var Dom $row */
        foreach($rows as $row){
            $cols = $row->find('td');

            // Handle the rowspan thing
            foreach($rowspans as $key => $rowspan){
                $rowspans[$key] = $rowspan-1;
                if($rowspan == 0){
                    unset($rowspans[$key]);
                }
            }

            // 0 = Monday, 1 Tuesday...
            $i = count($rowspans) ;

            /** @var Dom $col */
            foreach($cols as $col){

                if($col->getAttribute('rowspan') != ''){

                    array_push($rowspans,(int)$col->getAttribute('rowspan'));

                    $matches[] = ['html' => $col->innerHTML,'day' => $i];
                }
                $i++;
            }
        }

        $classes = [];

        foreach($matches as $class){
            $html = preg_replace('/<a href=".+">/i','',$class['html']);
            $html = preg_replace('/<\/a>/i','',$html);

            $class_broken = explode('<br />',$html);
            $class_broken[] = $class['day'];

            $dates = explode('-',$class_broken[3]);

            $class_beauty = [
                'number' => $class_broken[0],
                'title' => $class_broken[1],
                'type' => $class_broken[2],
                'start' => _getDate($dates[0],$class_broken[5],$date),
                'end' => _getDate($dates[1],$class_broken[5],$date),
                'location' => $class_broken[4],
            ];

            $classes[] = $class_beauty;

        }

        return $classes;

    }
    else {
        return [];
    }
}


/**
 * @param $time
 * @param $day
 * @param $date
 * @return int
 */
function _getDate($time,$day,$date){

    if(strlen($time) == 3){
        $time = 0 . $time;
    }

    $monday = strtotime("monday this week",strtotime($date));

    $day = strtotime("+ $day days",$monday);

    $hour = (int)($time[0] . $time[1]);
    $min = (int)($time[2] . $time[3]);
    $day = $day+($hour*3600)+($min*60);

    return date('c', $day);

}