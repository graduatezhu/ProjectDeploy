<?php

/**
 * @Title: 时间戳转换为天-时-分-秒数组
 * @access public
 * @param int $startdate 开始时间
 * @param int $enddate 结束时间
 * @return Array
 * @author ZXD
 */
function timestap_to_array($startdate,$enddate){
    $time['day']=floor(($enddate-$startdate)/86400);
    $time['hour']=floor(($enddate-$startdate)%86400/3600);
    $time['minute']=floor(($enddate-$startdate)%86400%3600/60);
    $time['second']=floor(($enddate-$startdate)%86400%60);
    return $time;
}