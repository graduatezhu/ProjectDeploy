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
function jsonStr($arr) {//不过滤敏感词
  $str = compress_html(urldecode( json_encode( urlencode_deep(htmlspecialchars_deep($arr)) ) ));
  return $str;
}
function urlencode_deep($value){
  if(is_array($value)){
    $value = array_map('urlencode_deep', $value);
  }else if(is_object($value)){
    $value = ($value);
  }else{
    $value = urlencode($value);
  }
  return $value;
}
function htmlspecialchars_deep($value){
  if(is_array($value)){
    $value = array_map('htmlspecialchars_deep', $value);
  }else if(is_object($value)){
    $value = ($value);
  }else{
    $value = htmlspecialchars($value);
  }
  return $value;
}
function compress_html($string) { 
  $string = str_replace("\r\n", '\\n', $string); 
  $string = str_replace("\n", '\\n', $string);
  // $string = str_replace("\t", '', $string);
  $pattern = array (
    "/> *([^ ]*) *</", //去掉注释标记 
    // "/[\s]+/", 
    "/<!--[^!]*-->/", 
    "/\" /", 
    "/ \"/", 
    "'/\*[^*]*\*/'" 
  ); 
  $replace = array (
    ">\\1<", 
    // "\n", 
    "", 
    "\"", 
    "\"", 
    "" 
  ); 
  return preg_replace($pattern, $replace, $string); 
} 