<?php

require_once("./lib/WxPay.Api.php");

$xml = $GLOBALS ["HTTP_RAW_POST_DATA"];


// 返回应答
if (isset($_GET)) {
	echo "SUCCESS";
}

 try {
	 $result=WxPayResults::Init($xml);//转换为数组
 } catch (Exception $e) {
	 echo 'Message: ' .$e->getMessage();
 }
 
 // 日志记录
function logger($log_content) {
	$max_size = 100000;
	$log_filename = "./logs/log.xml";
	if (file_exists ( $log_filename ) and (abs ( filesize ( $log_filename ) ) > $max_size)) {
		unlink ( $log_filename );
	}
	file_put_contents ( $log_filename, date ( 'Y-m-d H:i:s' ) . " " . $log_content . "\r\n", FILE_APPEND );
	// file_put_contents ( $log_filename, strftime("%Y%m%d%H%M%S",time()). " " . $log_content . "\r\n", FILE_APPEND );
}

logger($xml);

if(substr($result['out_trade_no'],0,3)=='EZC'){
	file_get_contents("http://121.42.53.24/zuche/APIes/Wxpay/savejs?out_trade_no={$result['out_trade_no']}&result_code={$result['result_code']}");
}else{
	file_get_contents("http://121.42.53.24/zuche/APIes/Wxpay/save?out_trade_no={$result['out_trade_no']}&result_code={$result['result_code']}");
}
	
