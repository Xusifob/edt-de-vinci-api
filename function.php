<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

define('DEVINCI_LOGIN_URL','https://www.leonard-de-vinci.net/include/php/ident.php');
define('BCIT_LOGIN_URL','https://bss.bcit.ca/owa_prod/twbkwbis.P_ValLogin');
define('BCIT_CALENDAR_URL','https://bss.bcit.ca/owa_prod/bwskfshd.P_CrseSchd?start_date_in=');
define('DEVINCI_CALENDAR_LINK','https://www.leonard-de-vinci.net/ical_student/');

if(!isset($_GET['dev']) && !isset($_POST['dev'])) {
    ini_set( 'error_reporting', 0 );
}


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
    curl_setopt ($ch, CURLOPT_TIMEOUT, 7);
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


    if(!isset($data['school']) || empty($data['school']))
        response(400,['SCHOOL_REQUIRED']);

    if(login_old_way($data)){
        if(is_calendar_valid($data['login'])){
            if($echo) {
                response(200, ['id' => $data['login']]);
            }
        }
    }

    if((!isset($data['pass']) || empty($data['pass'])))
        response(400,['PASSWORD_REQUIRED']);


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
 *
 **/
function get_calendar_data(){

    if(!isset($_GET['id']) || empty($_GET['id'])){
        response(400,['error' => 'ID_REQUIRED']);
    }

    $valid = is_calendar_valid($_GET['id']);

    if($valid == false){
        response(404,['error' => 'CALENDAR_NOT_FOUND']);
    }
    else{
        response(200,$valid,false);
    }
}


/**
 *
 * Return if the calendar is valid
 *
 * @param $id
 *
 * @return bool|string
 */
function is_calendar_valid($id)
{
    $result = curl_request('GET',DEVINCI_CALENDAR_LINK . $id);

    if(strpos($result,'Undefined') !== false){
        return false;
    }else{
        return $result;
    }

}


function login_old_way($data)
{
    return isset($data['login']) && $data['school'] == 'devinci' && $data['oldWay'] == true;
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

    if(isset($table[0])) {

        $classes = [];

        $dom->load( $table[0] );

        $rows = $dom->find( 'tr' );

        $columns = [ ];
        $html    = [ ];

        // Init the arrays
        for ( $i = 0; $i <= 7; $i ++ ) {
            $columns[] = 0;
            $html[]    = '';
        }

        // Foreach row of the table
        foreach ( $rows as $row ) {
            $cols = $row->find( 'td' );

            // Init the array
            $html = [ ];
            for ( $i = 0; $i <= 7; $i ++ ) {
                $html[] = '';
            }

            $empty = array_keys( $columns, 0 );

            // For all the columns
            foreach ( $cols as $key => $col ) {

                // Init
                $rowSpan = 1;
                $data    = '';

                // Fill the data
                if ( is_object( $col ) ) {
                    $rowSpan = max( $rowSpan, (int) $col->getAttribute( 'rowspan' ) );
                    $data    = $col->innerHTML;
                }

                $columns[ $empty[ $key ] ] = $rowSpan;
                $html[ $empty[ $key ] ]    = $data;


            }
            // Remove 1 to each column
            foreach ( $columns as $k => $value ) {
                $columns[ $k ] = max( $value - 1, 0 );
            }

            foreach ( $html as $k => $data ) {

                $pattern = '/<a href="[a-zA-Z\/._0-9;,=?&]+">/i';

                if ( ! preg_match( $pattern, $data ) ) {
                    continue;
                }

                $data = preg_replace( $pattern, '', $data );
                $data = preg_replace( '/<\/a>/i', '', $data );
                $data = preg_replace( '/<abbr>/i', '', $data );
                $data = preg_replace( '/<\/abbr>/i', '', $data );



                $data_array = explode( '<br />', $data );

                $data_array[] = $k;

                $data_ordered = [
                    'number'   => '',
                    'title'    => '',
                    'type'     => '',
                    'start'    => '',
                    'end'      => '',
                    'location' => '',
                ];

                if ( isset( $data_array[0] ) ) {
                    $data_ordered['number'] = $data_array[0];
                }

                if ( isset( $data_array[1] ) ) {
                    $data_ordered['title'] = $data_array[1];
                }

                if ( isset( $data_array[2] ) ) {
                    $data_ordered['type'] = $data_array[2];
                }

                if ( isset( $data_array[4] ) ) {
                    $data_ordered['location'] = $data_array[4];
                }


                if ( ! isset( $data_array[3] ) ) {

                    continue;
                } else {
                    $dates = explode( '-', $data_array[3] );

                    if ( isset( $data_array[5] ) ) {
                        $data_ordered['start'] = _getDate( $dates[0], $data_array[5], $date );
                        $data_ordered['end']   = _getDate( $dates[1], $data_array[5], $date );
                    }
                }


                $classes[] = $data_ordered;
            }
        }

        return $classes;

    }else{
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