<?php

// token 32字节
function getToken()
{
    for ($i = 1; $i <= 8; $i ++) {
        $str .= chr(rand(65, 90)) . chr(rand(48, 57)) . chr(rand(97, 122)) . chr(rand(48, 57));
    }
    return $str;
}

// openid 16字节
function getOpenID()
{
    $arrTime = explode(' ', microtime());
    $str = floor($arrTime[1] . $arrTime[0] * 1000);
    
    for ($i = 1; $i <= 3; $i ++) {
        $str .= chr(rand(48, 57));
    }
    return $str;
}
