<?php

class Devinci
{

    const LOGIN_URL = 'https://www.leonard-de-vinci.net/include/php/ident.php';

    const CALENDAR_LINK = 'https://www.leonard-de-vinci.net/ical_student/';

    const CALENDAR_FETCH_URL = 'https://www.leonard-de-vinci.net?my=edt';


    /**
     * @param $id
     * @param $pass
     * @return mixed
     */
    public static function login($id,$pass){
        $response = Utils::curl_request('POST',self::LOGIN_URL,[
            'front_type' => 'default',
            'login' => $id,
            'pass' => $pass,
        ]);

        if (strpos($response, 'Accès refusé') !== false) {
            Utils::response(401, ['error' => Utils::ERROR_ID_PASSWORD_INCORRECT]);
        }else{
            return self::getCalendarLink();
        }

    }


    /**
     * Get the calendar link
     */
    private static function getCalendarLink(){
        $response = Utils::curl_request('GET',self::CALENDAR_FETCH_URL);

        try {
            $dom = new \PHPHtmlParser\Dom();

            $dom->load($response);
            /** @var $content \PHPHtmlParser\Dom */
            $content = $dom->find('.social-box', 1);


            /** @var $a \PHPHtmlParser\Dom\AbstractNode */
            $a = $dom->find('a[href^="/ical_student/]',0);
            return str_replace('/ical_student/', '', $a->getAttribute('href'));
            //Utils::response(200, ['id' => str_replace('/ical_student/', '', $a->getAttribute('href'))]);
        }catch (Exception $e){
            Utils::response(500,['error' => $e->getMessage()]);
        }
    }


    /**
     * @param $id
     * @return array
     */
    public static function getCalendarEvents($id){
        $result = Utils::curl_request('GET',self::CALENDAR_LINK  . $id);

        $ical = new \ICal\ICal();

        $ical->initString($result);

        $evts = [];

        foreach($ical->events() as $event){

            $evt = new Event();
            $evt
                ->setEnd($event->dtstart)
                ->setStart($event->dtstart)
                ->setLocation($event->location);

            if(isset($event->prof)) {
                $evt->setDescription($event->prof);
            }
            if(isset($event->title)) {
                $evt->setTitle($event->title);
            }

            $evts[] = $evt;

        }
        return $evts;
    }


}