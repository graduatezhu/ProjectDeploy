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
/**
 * 计算坐标点范围，可以做搜索用户
 * @param float $lat
 * @param float $lng
 * @param float $raidus 范围米
 * @return array
 */
function GetRange($lng,$lat,$raidus){
  $PI = 3.1415926535898;

  //计算纬度
  $degree = (24901 * 1609) / 360.0;
  $dpmLat = 1 / $degree; 
  $radiusLat = $dpmLat * $raidus;
  $minLat = $lat - $radiusLat; //得到最小纬度
  $maxLat = $lat + $radiusLat; //得到最大纬度   
  //计算经度
  $mpdLng = $degree * cos($lat * ($PI / 180));
  $dpmLng = 1 / $mpdLng;
  $radiusLng = $dpmLng * $raidus;
  $minLng = $lng - $radiusLng;  //得到最小经度
  $maxLng = $lng + $radiusLng;  //得到最大经度
  //范围
  $range = array(
    'minLat' => $minLat,
    'maxLat' => $maxLat,
    'minLng' => $minLng,
    'maxLng' => $maxLng
  );
  return $range;
}

/*
 * 生成随机数
 * @param int $digit 位数
 * @return string 随机数
 */
function genRand($digit){
  $a = range(0,9);
  for($i=0;$i<$digit;$i++){
    $b[] = array_rand($a);
  }
  return join("",$b);
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
 /*function jsonStr($str){
  return json_encode($str,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}*/


 /**
 * 当前电池续航
 *
 * @param rongliang
 *            电池容量(固定)
 * @param xuhang
 *            理论续航（固定）
 * @param Bili
 *            当前比例（由getbili获取）
 * @return
 */
function getXuhang($rongliang, $xuhang, $currentBili) {//(80, 400, 0.30);
  $currentRongliang=$currentBili*$rongliang;//当前电量
  //$xuhang_r = ($rongliang_d / ($xuhang_d * 0.8)) * $currentRongliang_d;//当前电池续航
  $xuhang_r = $currentRongliang / ($xuhang * 0.8) ;//当前电池续航
  return $xuhang_r;
}
 
 
  /* 导出excel函数*/
  function excel($data,$name='export')
  { 
    error_reporting(E_ALL);
    $head="ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    $objPHPExcel = new \PHPExcel();

    // set attribute
    $objPHPExcel->getProperties()->setCreator("EDog")
    ->setLastModifiedBy("EDog")
    ->setTitle("EDog")
    ->setSubject("EDog")
    ->setDescription("This is file export by EDog")
    ->setKeywords("excel")
    ->setCategory("result file");
    
    // set width
    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);

    //设置excel列名
    //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1','编号');
    //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B1','车牌');
    //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1','车辆图片');

    foreach(array_keys($data[0]) as $k=>$v){
      $objPHPExcel->setActiveSheetIndex(0)->setCellValue($head[$k].'1',$v);
    }

    for ($i = 0, $len = count($data); $i < $len; $i++) {
      // $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $data[$i]['id']);
      // $objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $data[$i]['plate']);
      // $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $data[$i]['picture']);
      foreach(array_keys($data[0]) as $k=>$v){
        $objPHPExcel->getActiveSheet(0)->setCellValue($head[$k].($i + 2), $data[$i][$v]);
      }

    }

    // Set active sheet index to the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
  
    // 输出
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$name.'.xls"');
    header('Cache-Control: max-age=0');
  
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
  }
  
  
  function get_micro_time($length=3,$type='string'){
  $temp = explode(" ", microtime());
  // echo $temp[0],'<br>';//毫秒小数位 0.960877001445485906
  // echo $temp[1],'<br>';//秒，和时间戳一样 1445485906

  if( $type == 'string' ){
    $re2 = round( $temp[0]*pow(10,$length) );//四舍五入
    // echo $temp[1],'<br>';
    // echo $re2,'<br>';
    // echo $re2,'<br>';
    settype($re2,$type);
    settype($temp[1],$type);
    return $temp[1].$re2;
  }else{
    $re = bcadd($temp[0], $temp[1], $length);//二者相加、保留 $length 位小数
    return $re*pow(10,$length);//6位竟然返回1.4454856885957E+15
  }
}

 
 /*
 * 短信发送函数
 * @param int $phone 手机号码
 * @param string $msg 短信内容
 * @return bool （true为发送成功）
*/
function send_duanxin($phone,$msg){
  $userName = 'yiyuandongli';
  $userPwd  = 'edogtecha9r9a9h9';
  
  /*
  cpName 用户名
  cpPwd 密码
  phones 手机号
  msg 内容
  spCode 流水号
  extNum 通道号（默认为0，预留扩展用）
  */
  $msg = urlencode( iconv('UTF-8','GBK',$msg) );//用gbk编码进行UrlEncode
  //$baseUrl = 'http://221.122.112.136:8080/sms/mt.jsp?cpName='.$userName.'&cpPwd='.$userPwd.'&phones='.$phone.'&spCode='.'1111111111'.'&msg='.$msg.'&extNum=0';
  $baseUrl = 'http://api.itrigo.net/mt.jsp?cpName='.$userName.'&cpPwd='.$userPwd.'&phones='.$phone.'&spCode='.'1111111111'.'&msg='.$msg.'&extNum=0';
  
  $re = file_get_contents($baseUrl);
  $re = iconv('GBK','UTF-8',$re);//转码
  
  if( $re === '0' ) {
    return true;
  } else {
    return false;
    $re_arr = explode('&',$re);
    echo $re_arr[1];
  }
}

