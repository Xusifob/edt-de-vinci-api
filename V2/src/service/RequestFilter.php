<?php

class RequestFilter
{

    /**
     * Check if the school is defined in the call
     */
    private static function hasSchool(){
        if(!isset($_GET['school'])){
            Utils::response(400,['error' => Utils::SCHOOL_REQUIRED]);
        }
    }

    /**
     *
     * Filter if the request has the correct params for login call
     * @param $data
     */
    public static function login($data){

        if(!isset($data['login']) || empty($data['login']))
            Utils::response(400,['error' => Utils::ID_REQUIRED]);

        if(!isset($data['pass']) || empty($data['pass']))
            Utils::response(400,['error' => Utils::PASSWORD_REQUIRED]);

        self::hasSchool();
    }

    /**
     * Filter if the request has the correct params for calendar call
     *
     * @param $data
     */
    public static function calendar($data){

        self::hasSchool();
        if(!isset($data['id']))
            Utils::response(400,['error' => Utils::ID_REQUIRED]);
    }
    
}