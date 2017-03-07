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


/**
 *  @desc 根据两点间的经纬度计算距离
 *  @param float $lat 纬度值
 *  @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters

    /*
      Convert these degrees to radians
      to work with the formula
    */

    $lat1 = ($lat1 * pi() ) / 180;
    $lng1 = ($lng1 * pi() ) / 180;

    $lat2 = ($lat2 * pi() ) / 180;
    $lng2 = ($lng2 * pi() ) / 180;

    /*
      Using the
      Haversine formula

      http://en.wikipedia.org/wiki/Haversine_formula

      calculate the distance
    */

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;

    return round($calculatedDistance);
}