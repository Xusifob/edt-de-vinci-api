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
            /** @var \PHPHtmlParser\Dom\AbstractNode $row */
            foreach ( $rows as $row ) {
                $cols = $row->find( 'td' );

                // Init the array
                $html = [ ];
                for ( $i = 0; $i <= 7; $i ++ ) {
                    $html[] = '';
                }

                $empty = array_keys( $columns, 0 );

                // For all the columns
                /**
                 * @var int $key
                 * @var \PHPHtmlParser\Dom\AbstractNode $col
                 */
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