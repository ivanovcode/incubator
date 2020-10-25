<?php
function strExtract( $output , $str_a, $str_b )
{
    $start_flag = false;
    $end_flag = false;
    $str_result = "";

    for ($i=0; $i<strlen($output); $i++) {
        if(substr($output, $i, strlen($str_b)) == $str_b && $start_flag) {
            $end_flag = true;
        }
        if($end_flag) {
            break;
        }
        if($start_flag) {
            $str_result .=  substr($output, $i, 1);
        }
        if(substr($output, $i, strlen($str_a)) == $str_a) {
            $start_flag = true;
            $i = $i + (strlen($str_a) - 1);
        }
    }
    return $str_result;
}

function getUnlockUrl( $output, $str_a, $str_b )
{
    if (strripos($output, 'unlock') !== false) {
        return strExtract($output, $str_a, $str_b) . strExtract($output, $str_a, $str_b);
    }

    return false;
}