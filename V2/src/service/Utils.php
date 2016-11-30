<?php

class Utils
{
    const ERROR_ID_PASSWORD_INCORRECT = 'ERROR_ID_PASSWORD_INCORRECT';
    const ERROR_SERVER = 'ERROR_SERVER';
    const SCHOOL_REQUIRED = 'SCHOOL_REQUIRED';
    const ID_REQUIRED = 'ID_REQUIRED';
    const PASSWORD_REQUIRED = 'PASSWORD_REQUIRED';

    const SCHOOL_BCIT = 'bcit';
    const SCHOOL_DEVINCI = 'devinci';


    /**
     * @param $request
     * @param $url
     * @param array $data
     * @return string
     */
    public static function curl_request($request,$url,$data = []){

        $cookie_jar  = __DIR__ . '/../../tmp/cookie.txt';

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
     * @param $code
     * @param $data
     * @param bool|true $is_json
     */
    public static function response($code,$data, $is_json = true){

        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept",false);
        header('Content-Type: application/json; charset=utf-8',false);
        switch ($code) {
             case 401 :
                 header("HTTP/1.1 401 Unauthorized",false,$code);
                 break;
             case 200 :
                 header("HTTP/1.1 200 OK",false,$code);
                 break;
             case 400 :
                 header('HTTP/1.0 400 Bad Request',false,$code);
                 break;
         }

        if($is_json) {
            $data = array_merge($data, ['status' => $code]);
            echo json_encode($data);
        }else{
            echo $data;
        }
        die();

    }


    /**
     * @param $time
     * @param $day
     * @param $date
     * @return bool|string
     */
    public static function createDate($time,$day,$date){


        return date('c', $day);

    }

}