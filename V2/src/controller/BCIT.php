<?php

class BCIT
{
    const LOGIN_URL = 'https://bss.bcit.ca/owa_prod/twbkwbis.P_ValLogin';

    const CALENDAR_LINK = 'https://bss.bcit.ca/owa_prod/bwskfshd.P_CrseSchd?start_date_in=';



    /**
     * @param $id
     * @param $pass
     * @return mixed
     */
    public static function login($id,$pass){
        $response = Utils::curl_request('POST',self::LOGIN_URL,[
            'sid' => $id,
            'PIN' => $pass,
        ]);


        if (strpos($response, 'Invalid') !== false || strpos($response,'Sign in' !== false)) {
            Utils::response(401, ['error' => Utils::ERROR_ID_PASSWORD_INCORRECT]);
        }else{
            return $id;
        }

    }

    /**
     * @return array
     */
    public static function getCalendarEvents(){

        $date = date('m/d/Y');
        $number_of_week = 8;


        $events = [];
        $date = date('m/d/Y',strtotime('- 3 week',strtotime($date)));
        for($i=0;$i<$number_of_week;$i++){
            $date = date('m/d/Y',strtotime('+ 1 week',strtotime($date)));
            $events = array_merge($events,self::getEventForWeek($date));
        }

        return $events;

    }


    /**
     * Fetch the events for a week
     *
     * @param $date
     * @return array
     */
    private static function getEventForWeek($date){

        $url = self::CALENDAR_LINK . $date;

        $response = Utils::curl_request('GET',$url);

        preg_match('/<table  CLASS="datadisplaytable" SUMMARY="This layout table is used to present the weekly course schedule." WIDTH="80%">.+<\/table>/is',$response,$table);

        $dom = new \PHPHtmlParser\Dom();

        if(isset($table[0])){

            $dom->load($table[0]);


            $rows = $dom->find('tr');

            $matches = [];
            // Add an array to handle all rowspans
            $rowspans = [];
            /** @var \PHPHtmlParser\Dom $row */
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

                foreach($cols as $col){

                    /** @var \PHPHtmlParser\Dom\AbstractNode $col */
                    if($col->getAttribute('rowspan') != ''){

                        array_push($rowspans,(int)$col->getAttribute('rowspan'));

                        $matches[] = ['html' => $col->innerHtml(),'day' => $i];
                    }
                    $i++;
                }
            }

            $events = [];

            foreach($matches as $class){
                $html = preg_replace('/<a href=".+">/i','',$class['html']);
                $html = preg_replace('/<\/a>/i','',$html);

                $class_broken = explode('<br />',$html);
                $class_broken[] = $class['day'];

                $dates = explode('-',$class_broken[3]);

                $event = new Event();

                $event
                    ->setTitle($class_broken[1])
                    ->setDescription($class_broken[0])
                    ->setStart(self::createDate($dates[0],$class_broken[5],$date))
                    ->setEnd(self::createDate($dates[0],$class_broken[5],$date))
                    ->setLocation($class_broken[4])
                ;


                $events[] = $event;

            }

            return $events;

        }
        else {
            return [];
        }

    }

    /**
     * Create a date
     *
     * @param $time
     * @param $day
     * @param $date
     * @return int
     */
    public static function createDate($time,$day,$date){

        if(strlen($time) == 3){
            $time = 0 . $time;
        }

        $monday = strtotime("monday this week",strtotime($date));

        $day = strtotime("+ $day days",$monday);

        $hour = (int)($time[0] . $time[1]);
        $min = (int)($time[2] . $time[3]);
        $day = $day+($hour*3600)+($min*60);

        return $day;
    }

}