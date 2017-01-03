<?php

include 'function.php';

if($_GET['school'] == 'bcit') {
// Login
    login(false);

    $date = isset($_POST['date']) ? $_POST['date'] : date('m/d/Y');
    $number_of_week = isset($_POST['number_of_week']) ? $_POST['number_of_week'] : 2;


    $data = [];
    $date = date('m/d/Y',strtotime('- 1 week',strtotime($date)));
    for($i=0;$i<$number_of_week;$i++){
        $date = date('m/d/Y',strtotime('+ 1 week',strtotime($date)));
        $data = array_merge($data,get_bcit_calendar_data($date));
    }

    response(200,['data' => $data]);
}

