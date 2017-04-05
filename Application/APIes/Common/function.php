<?php
/**
 * @Title: 执行shell命令
 * @access public
 * @param
 * @return string
 * @author ZXD
 */
function execShell($val1,$val2,$val3){
    shell_exec("/www/shell/cron.php  $val1 $val2 $val3"); // 把三个起始时间送给crontab定时调用改价命令
}

/**
 * @Title: 获取当前服务器地址
 * @access public
 * @param
 * @return string
 * @author ZXD
 */
function getHostAddress(){
    return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
}
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


/**
 * @Title: 判断变量是否为空
 * @param mixed $var 变量
 * @return true/false
 * @author lxk
 */
function is_empty($var) {
    if (!isset($var) || is_null($var) || (trim($var) == "" && !is_bool($var)) || (is_bool($var) && $var === false) || (is_array($var) && empty($var))) {
        return true;
    } else {
        return false;
    }
}

/**
 * @Title: 生成帧校验码
 * @access public
 * @param int $frame 帧数据
 * @return 16进制数
 * @author ZXD
 */

function generate_code($frame){

    //分隔帧为数组，并将16进制数组元素转换为10进制数组元素
    $j=0;
    for($i=0;$i<strlen($frame);$i+=2){
        $arr[$j]=hexdec(substr($frame, $i,2));
        $j++;
    }

    //校验位之前的所有字节参与异或运算
    $tmp=$arr[0];
    for($i=1;$i<count($arr)-2;$i++){
        $tmp=$tmp^$arr[$i];
    }

    //生成16进制校验码
    $XORcode=strtoupper(dechex($tmp));

    return $XORcode;
}


/**
 * @Title: 验证帧校验码
 * @access public
 * @param int $frame 帧数据
 * @return Boolean
 * @author ZXD
 */
function verifiy_code($frame){

    $bool=false;
    $frame=strtoupper($frame);
    $bool=(generate_code($frame)==substr($frame, -4,2))?true:false;

    return $bool;
}



/**
 * @Title: 发送帧命令
 * @access public
 * @param array $commandArray 2个1组的帧数据
 * @return string 应答帧
 * @author ZXD
 */
function send_frame($commandArray){
    // 身份验证帧
    $authStr = '85 00 06 0F 8C 7E';
    $authStrArray = str_split(str_replace(' ', '', $authStr), 2);  // 将16进制数据转换成两个一组的数组

    $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));  // 创建Socket

    $authFrame='';
    if (socket_connect($socket, "121.42.53.24", 8234)) {  //连接
        for ($i = 0; $i < count($authStrArray); $i++) {
            $authFrame.=chr(hexdec($authStrArray[$i])); // 组中为一帧数据一次性发送
        }
        socket_write($socket, $authFrame);//发送身份验证帧

        $receiveAuthStr = "";
        $receiveAuthStr = socket_read($socket, 1024, PHP_BINARY_READ);  // 采用二进制方式接收数据
        $receiveAuthStrHex = bin2hex($receiveAuthStr);  // 将2进制数据转换成16进制
 //return '身份校验应答帧：'.$receiveAuthStrHex; // for debug
        
        // 校验服务端返回的身份验证帧应答
        $boolResult=verifiy_code(strtoupper($receiveAuthStrHex));
// return '身份校验结果：'.$boolResult; // for debug

        if (!$boolResult) {
            $return['status'] ='-1';
            $return['msg']='开放平台身份校验错误';

        }else{
            // 身份校验成功后发送命令帧
            $commandFrame='';
            for ($j = 0; $j < count($commandArray); $j++) {
                $commandFrame.=chr(hexdec($commandArray[$j]));
            }
//return '待发送的命令帧：'.bin2hex($commandFrame); // for debug

            socket_write($socket, $commandFrame);//发送命令帧

            $receiveCommandStr = "";
            $receiveCommandStr = socket_read($socket, 1024, PHP_BINARY_READ);  // 采用二进制方式接收数据
            $receiveCommandStrHex = bin2hex($receiveCommandStr);  // 将2进制数据转换成16进制
            
            $return['status']='0';
            $return['msg']='控制命令应答返回成功';
            $return['info']=$receiveCommandStrHex; // 返回应答帧

        }
        return $return;// 所处位置？
    }else{
        $errorcode  =  socket_last_error();
        $errormsg  =  socket_strerror($errorcode);
        die( "Couldn't connect socket: [ $errorcode ]  $errormsg" );
    }
    socket_close($socket);  // 关闭Socket
    
//     return $return; // 所处位置？
}

/**
 * @Title: 电桩启停
 * @access public
 * @param string $QRcode 二维码
 * @param string $gun 枪号
 * @param string $type 开启/关闭
 * @param string $userID 用户ID
 * @return array 控制结果
 * @author ZXD
 */
function switch_pile($QRcode, $gun, $type,$userID) {

    // 用于返回信息输出
    $switch=($type=='0')?'开启':'关闭';

    settype($QRcode,'string');

    // 截取18位电站编号
    $stationNO=substr($QRcode,0,18);//从下标0开始取18位

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($QRcode,18,3).'A';

    // 补齐2位枪号
    $gun=str_repeat('0',(2-strlen($gun))).$gun;

    // 补齐24位用户身份编号，其中'!'ascii码为21
    $userIdHex='';
    for($i=0;$i<strlen($userID);$i++){
        $userIdHex.=dechex(ord(substr($userID, $i)));
    }
    $userIdHex.=str_repeat('21',24-(strlen($userIdHex)/2));

    // 补齐2位控制命令
    $type=str_repeat('0',(2-strlen($type))).$type;

    // 组装待校验帧,'xx'为待校验位
    $frame='85'.'002B'.'13'.$stationNO.$pileNO.$gun.$userIdHex.$type.'xx'.'7E';

    // 生成校验码并替换校验码位'xx'
    $code=generate_code($frame);
    $frame=substr_replace($frame, $code, -4,2);
//  return $frame;

    /*发送命令帧*/
    // 生成数组
    $j=0;
    for($i=0;$i<strlen($frame);$i+=2){
        $frameArray[$j]=substr($frame, $i,2);
        $j++;
    }
    // 发送
    $receiveFrame=send_frame($frameArray);
// return $receiveFrame;
    
    if($receiveFrame['status']=='0'){
        /*正常返回命令应答帧*/
        
        // 校验服务端返回的命令帧应答
        $boolResult=verifiy_code($receiveFrame['info']);
        
        if ($boolResult) {
            if(substr($receiveFrame['info'], -6,2)=='00'){
                $return['status']='0';
                $return['msg']='电桩'.$switch.'成功';
            }else {
                $return['status']='-1';
                $return['frameFromServer']=$receiveFrame; // for dubug
                $return['msg']='电桩'.$switch.'失败';
            }
        
        }else{
            $return['status']='-2';
            $return['msg']='命令应答帧校验错误';
        }
        
    }else{
        /*APP后台身份验证错误*/
        $return['status']='-3';
        $return['msg']='APP后台身份校验错误';
        
    }

    return $return;
}


/**
 * @Title: 修改电价
 * @access public
 * @param string $QRcode 二维码
 * @param string $price 新电价
 * @return array 控制结果
 * @author ZXD
 */
function modify_pile_price($QRcode,$price) {
    settype($QRcode,'string');

    // 截取18位电站编号
    $stationNO=substr($QRcode,0,18);

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($QRcode,18,3).'A';

    // 电价扩大100倍为正整数，高位补0，补齐8位
    $priceHex=strtoupper(dechex($price*100));
    $priceHex=str_repeat('0', 8-strlen($priceHex)).$priceHex;

    // 组装待校验帧,'xx'为待校验位
    $frame='85'.'0015'.'15'.$stationNO.$pileNO.$priceHex.'xx'.'7E';

    // 生成校验码并替换校验码位'xx'
    $code=generate_code($frame);
    $frame=substr_replace($frame, $code, -4,2);
    // return $frame;
    
    /*发送命令帧*/
    // 生成数组
    $j=0;
    for($i=0;$i<strlen($frame);$i+=2){
        $frameArray[$j]=substr($frame, $i,2);
        $j++;
    }
     
    // 发送
    $receiveFrame=send_frame($frameArray);
// return $receiveFrame; // for debug
    
    /*正常返回命令应答帧*/
    if($receiveFrame['status']=='0'){
        
        $boolResult=verifiy_code($receiveFrame['info']); // 校验服务端返回的应答帧
        
        if ($boolResult) {
            if(substr($receiveFrame['info'], -8,2)=='00'){
                $return['status']='0';
                $return['msg']='电价修改成功';
                $return['frameFromServer']=$receiveFrame; // for debug
            }else {
                if(substr($receiveFrame['info'], -6,2)=='01'){
                    $return['status']='-1';
                    $return['frameFromServer']=$receiveFrame; // for debug
                    $return['msg']='已插枪，无法修改电价';
                }else{
                    $return['status']='-1';
                    $return['frameFromServer']=$receiveFrame; // for debug
                    $return['msg']='其他错误';
                }
            }
        }else{
            $return['status']='-2';
            $return['msg']='应答帧校验错误';
        }
    }else{
        /*APP后台身份验证错误*/
        $return['status']='-3';
        $return['msg']='开放平台身份校验错误';
    }

    return $return;
}

/**
 * @Title: 重启电桩
 * @access public
 * @param string $QRcode 二维码
 * @return array 控制结果
 * @author ZXD
 */
function reset_pile($QRcode){

    settype($QRcode, 'string');

    // 截取18位电站编号
    $stationNO=substr($QRcode,0,18);

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($QRcode,18,3).'A';

    // 组装待校验帧,'xx'为待校验位
    $frame='85'.'0012'.'16'.$stationNO.$pileNO.'00'.'xx'.'7E';

    // 生成校验码并替换校验码位'xx'
    $code=generate_code($frame);
    $frame=substr_replace($frame, $code, -4,2);
//  return $frame;
    
    /*发送命令帧*/
    // 生成数组
    $j=0;
    for($i=0;$i<strlen($frame);$i+=2){
        $frameArray[$j]=substr($frame, $i,2);
        $j++;
    }
     
    // 发送
    $receiveFrame=send_frame($frameArray);
return $receiveFrame;

    /*正常返回命令应答帧*/
    if($receiveFrame['status']=='0'){

        $boolResult=verifiy_code($receiveFrame['info']); // 校验服务端返回的应答帧

        if ($boolResult) {
            if(substr($receiveFrame['info'], -5,1)=='0'){
                $return['status']='0';
                $return['msg']='电桩重启成功';
            }else {
                $return['status']='-1';
                //$return['frameFromServer']=$receiveFrame; // for debug
                $return['msg']='电桩重启失败';
            }
        }else{
            $return['status']='-2';
            $return['msg']='重启应答帧校验错误';
        }
    }else{
        /*开放平台身份验证错误*/
        $return['status']='-3';
        $return['msg']='开放平台身份校验错误!';
    }

    return $return;
}


/**
 * @Title: 锁定/解锁电桩
 * @access public
 * @param string $QRcode 二维码
 * @param string $gun 枪号
 * @param string $type 命令类型 1锁定 0解锁
 * @return array 控制结果
 * @author ZXD
 */
function lock_pile($QRcode, $gun, $type) {
    // 用于返回信息输出
    $switch=($type=='0')?'解锁':'锁定';

    settype($QRcode,'string');

    // 截取18位电站编号
    $stationNO=substr($QRcode,0,18);//从下标0开始取18位

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($QRcode,18,3).'A';

    // 补齐2位枪号
    $gun=str_repeat('0',(2-strlen($gun))).$gun;

    // 补齐2位控制命令
    $type=str_repeat('0',(2-strlen($type))).$type;

    // 组装待校验帧,'xx'为待校验位
    $frame='85'.'0013'.'14'.$stationNO.$pileNO.$gun.$type.'xx'.'7E';

    // 生成校验码并替换校验码位'xx'
    $code=generate_code($frame);
    $frame=substr_replace($frame, $code, -4,2);

    /*发送命令帧*/
    // 生成数组
    $j=0;
    for($i=0;$i<strlen($frame);$i+=2){
        $frameArray[$j]=substr($frame, $i,2);
        $j++;
    }
    // 发送
    $receiveFrame=send_frame($frameArray);
    // return $receiveFrame;

    if($receiveFrame['status']=='0'){
        /*正常返回命令应答帧*/

        // 校验服务端返回的命令帧应答
        $boolResult=verifiy_code($receiveFrame['info']);

        if ($boolResult) {
            if(substr($receiveFrame['info'], -6,2)=='00'){
                $return['status']='0';
                $return['msg']='电桩'.$switch.'成功';
            }else {
                $return['status']='-1';
                $return['frameFromServer']=$receiveFrame; // for dubug
                $return['msg']='电桩'.$switch.'失败';
            }

        }else{
            $return['status']='-2';
            $return['msg']='命令应答帧校验错误';
        }

    }else{
        /*APP后台身份验证错误*/
        $return['status']='-3';
        $return['msg']='开放平台身份校验错误';

    }

    return $return;

}
// for debug
// $cmdRTNArray=lock_pile('000860011001014001001','1','0');
//$cmdRTNArray=reset_pile('000860011001014001001');
// $cmdRTNArray=switch_pile('000860011001014001010','1','1','101');
// $cmdRTNArray=modify_pile_price('000860011001014001001','1.2');
//print_r($cmdRTNArray);


//图片url路径 变成 数据库路径
function url2Path($image){
	return preg_replace('/(^.*\/Uploads)/isU','./Uploads',$image);
}

//转换图片路径
function img2Path($image){//图片转换成完整的 url路径
    $content=str_replace(
			array(
				'../',
				'src="/',
				'href="/',
				'./Uploads',
				'/Public',
				'.http://',
			),
			array(
				WEBURL.'/',//有 “/”
				'src="'.WEBURL.'/',
				'href="'.WEBURL.'/',
				WEBURL.'/'.'Uploads',
				WEBURL.'/'.'Public',
				'http://',
			),
			$image
		);
	return $content;
}

function url_encode($str) {
	if(is_array($str)) {
		foreach($str as $key=>$value) {
			$str[urlencode($key)] = url_encode($value);
		}
	} else {
		$str = urlencode($str);
	}

	return $str;
}

/*
*时间戳转换为天-时-分-秒,此处返回字符串
*/
function second_to_date($startdate,$enddate){
	$time['day']=floor(($enddate-$startdate)/86400);
	$time['hour']=floor(($enddate-$startdate)%86400/3600);
	$time['minute']=floor(($enddate-$startdate)%86400%3600/60);
	$time['second']=floor(($enddate-$startdate)%86400%60);
	$result=$time['day'].'天'.$time['hour'].'小时'.$time['minute'].'分'.$time['second'].'秒';
	return $result;
}


//记录ali支付自定义日志
function logger($word='') {
	date_default_timezone_set("PRC");
	$fp = fopen("AliPay/alipay_seldefined_log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\r\n".$word."\r\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

function get_current_microtimestamp(){
	$time = explode ( " ", microtime () );
	$time = $time [1] . ($time [0] * 1000);
	$time2 = explode ( ".", $time );
	$TimeStamp = $time2 [0];
	return $TimeStamp;
}

function timeConvertStandard($day=0,$hour=0,$minute=0,$second=0){
	//秒-分进位
	$minute+=floor($second/60);
	$second=$second%60;
	
	//分-时进位
	$hour+=floor($minute/60);
	$minute=$minute%60;
	
	//时-天进位
	$day+=floor($hour/24);
	$hour=$hour%24;
	
	$time['day']=$day;
	$time['hour']=$hour;
	$time['minute']=$minute;
	$time['second']=$second;
	
	return $time;
}


/*租车活动优惠计时*/
function getFavorableTime($borrow_time,$return_time) {
	//提车、还车时间戳
	$borrowTime=$borrow_time;
	$returnTime=$return_time;
	
	//提车、还车年月日
	$borrowYMD=date('Ymd',$borrowTime);
	$returnYMD=date('Ymd',$returnTime);
	
	//时间戳
	$borrowStamp9=strtotime($borrowYMD.'090000');//提车9点时间戳
	$borrowStamp17=strtotime($borrowYMD.'170000');//提车17点时间戳
	$borrowStamp24=strtotime($borrowYMD.'235959')+1;//提车24点时间戳
	
	$returnStamp0=strtotime($returnYMD.'000000');//还车0点时间戳
	$returnStamp83=strtotime($returnYMD.'083000');//还车8点30分时间戳	
	$returnStamp9=strtotime($returnYMD.'090000');//还车9点时间戳
	$returnStamp17=strtotime($returnYMD.'170000');//还车17点时间戳
	
	//提车、还车时分秒
	$borrowHMS=date('His',$borrowTime);
	$returnHMS=date('His',$returnTime);
	
	//提车、还车星期
	$borrowWeek=date('w',$borrowTime);
	$returnWeek=date('w',$returnTime);
	
	//用车当日周几（0周日 1周一 依次类推）
	$inuseWeek=0;
	
	//工作日活动时间戳
	$activityBeginTime= strtotime('2016-12-15 00:00:00');
	$activityEndTime=strtotime('2016-12-31 17:00:00');

	//享有晚间套餐次数,用于主程序计算套餐资费
	$eveningPackageSum=0;
	
	//享有活动计时（时分秒）
	$activityHourSum=0;
	$activityMinuteSum=0;
	$activitySecondSum=0;

	//提、还车时间差（天）
	$subtract= date('Ymd',$returnTime)-date('Ymd',$borrowTime);
	
	switch ($subtract) {
		/*当日提、还车*/
		case 0:
/*12.15号-12.31号 9点-17点 周一至周五免费（0.01元）*/
			if($borrowTime>=$activityBeginTime && $borrowTime<$activityEndTime && $borrowWeek!=0 && $borrowWeek !=6){
				//9点前、17点后提、还车不享受优惠
				//条件一：9点前提车，9-17点间还车
				if($borrowTime<$borrowStamp9 && $returnTime>=$returnStamp9 && $returnTime<=$returnStamp17){
					//返回时间差数组
					$tmpArr=timestap_to_array($returnStamp9,$returnTime);
					if($tmpArr['hour']==8){
						$activityHourSum=8;//减免8小时,享受整个活动日
					}else{
						//9点-17点间还车减免的时间
						$activityHourSum=$tmpArr['hour'];
						$activityMinuteSum=$tmpArr['minute'];
						$activitySecondSum=$tmpArr['second'];
					}
				}
				//条件二：9点前提车，17点后还车
				if($borrowTime<$borrowStamp9 && $returnTime>$returnStamp17){
					$activityHourSum=8;
				}
				//条件三：9点-17点间提、还车
				if ($borrowTime>=$borrowStamp9 && $borrowTime<=$borrowStamp17 && $returnTime<=$returnStamp17) {
					$tmpArr=timestap_to_array($borrowTime,$returnTime);
					$activityHourSum=$tmpArr['hour'];
					$activityMinuteSum=$tmpArr['minute'];
					$activitySecondSum=$tmpArr['second'];
				}
				//条件四：9-17点间提车,17点后还车
				if ($borrowTime>=$borrowStamp9 && $borrowTime<$borrowStamp17 && $returnTime>$returnStamp17) {
					$tmpArr=timestap_to_array($borrowTime,$borrowStamp17);
					$activityHourSum=$tmpArr['hour'];
					$activityMinuteSum=$tmpArr['minute'];
					$activitySecondSum=$tmpArr['second'];
				}
				
			}

			break;
	
		case 1:
			/*首日提车,次日还车*/
			//首日
			if($borrowTime>=$activityBeginTime && $borrowTime<$activityEndTime && $borrowWeek!=0 && $borrowWeek !=6){
				
				if($borrowTime<$borrowStamp9){
					$activityHourSum=8;//减免8小时，享受整个活动日
				}elseif($borrowTime>=$borrowStamp9 && $borrowTime<$borrowStamp17){
					//返回时间差数组
					$tmpArr=timestap_to_array($borrowTime,$borrowStamp17);
					//9点-17点间提车减免的时间
					$activityHourSum=$tmpArr['hour'];
					$activityMinuteSum=$tmpArr['minute'];
					$activitySecondSum=$tmpArr['second'];
				}else{
					null;//17点后不享受工作日8小时优惠
				}
			}
			
			//次日
			if($returnTime>$activityBeginTime && $returnTime<$activityEndTime && $returnWeek!=0 && $returnWeek !=6){
				
				if($returnTime<=$returnStamp9){
					null;//不享有活动
				}elseif ($returnTime>$returnStamp9 && $returnTime<=$returnStamp17){
					//9点-17点间还车减免时间
					$tmpArr=timestap_to_array($returnStamp9,$returnTime);
					$activityHourSum += $tmpArr['hour'];
					$activityMinuteSum += $tmpArr['minute'];
					$activitySecondSum += $tmpArr['second'];
				}else{
					$activityHourSum+=8;
				}
			}
							
			break;
					
		default:
			//用车跨三个日期（含）以上
			//首日
			if($borrowTime>=$activityBeginTime && $borrowTime<$activityEndTime && $borrowWeek!=0 && $borrowWeek !=6){
				
				if($borrowTime<$borrowStamp9){
					$activityHourSum=8;//减免8小时，享受整个活动日
				}elseif($borrowTime>=$borrowStamp9 && $borrowTime<=$borrowStamp17){
					//返回时间差数组
					$tmpArr=timestap_to_array($borrowTime,$borrowStamp17);
					if($tmpArr['hour']==8){
						$activityHourSum=8;
					}else{
						//9点-17点间提车减免的时间
						$activityHourSum=$tmpArr['hour'];
						$activityMinuteSum=$tmpArr['minute'];
						$activitySecondSum=$tmpArr['second'];
					}
						
				}else{
					null;//17点后不享受8小时优惠
				}
				
			}
			
			//末日
			if($returnTime>$activityBeginTime && $returnTime<$activityEndTime && $returnWeek!=0 && $returnWeek !=6){
				
				if($returnTime<=$returnStamp9){
					null;//不享有活动
				}elseif ($returnTime>$returnStamp9 && $returnTime<=$returnStamp17){
					//9点-17点间还车减免时间
					$tmpArr=timestap_to_array($returnStamp9,$returnTime);
					$activityHourSum += $tmpArr['hour'];
					$activityMinuteSum += $tmpArr['minute'];
					$activitySecondSum += $tmpArr['second'];
				}else{
					$activityHourSum+=8;
				}
			}
			
			//除去首日提车、末日还车，每日享受活动8小时（如租车3天，首末各1天，中间1天）
			for ($i = 1; $i < $subtract; $i++) {
				$dayActivity=$returnTime-$i*86400;//“中间日”时间戳
				$inuseWeek=date('w',$returnTime-$i*86400);//周几
				
				if($dayActivity>=$activityBeginTime && $dayActivity<$activityEndTime && $inuseWeek!=0 && $inuseWeek!=6){
					$activityHourSum+=8;
				}
			}
			
			break;
	}
	$activitySecond=$activityHourSum*3600+$activityMinuteSum*60+$activitySecondSum;
	
	return $activitySecond;	
}

/*
 * 获取晚间套餐计数
 * 定义：
 * （1）17点~次日8点30分不能进行租车，只能还车；
 * （2）17点~次日8点30分还车的，按照分时价格计费；
 * （3）17点前租车次日8点30分后还车的，按照（总时长-夜间时长）*分时价格+夜间套餐价格
 * （4）在活动期间，8点30分-9点之间的按照分时价格计费
 * */
 function getEveningPackageTimes($borrow_time,$return_time){
 	
 	//提车、还车时间戳
 	$borrowTime=$borrow_time;
 	$returnTime=$return_time;
 	
 	//提车、还车年月日
 	$borrowYMD=date('Ymd',$borrowTime);
 	$returnYMD=date('Ymd',$returnTime);
 	
 	//时间戳
 	$borrowStamp17=strtotime($borrowYMD.'170000');//提车17点时间戳
 	$returnStamp83=strtotime($returnYMD.'083000');//还车8点30分时间戳
 	
 	//提、还车时间差（天）years-overlap leads to fault 
 	$subtract= date('Ymd',$returnTime)-date('Ymd',$borrowTime);
 	
 	$eveningPackageSum=0;//夜间套餐计数
 	
 	switch ($subtract) {
 		case 0:
 			//当日提、还车不存在夜间套餐
 			break;
 		case 1:
 			//当日提车、次日还车
 			if($returnTime > $returnStamp83){
 				//等同 $borrowTime < $borrowStamp17 && $returnTime > $returnStamp83
 				$eveningPackageSum++;//夜间套餐计数加1
 			}
 			break;
 		default:
 			//用车时间跨三个日期（含）以上
 			
 			//还车日
 			if($returnTime <= $returnStamp83){
 				
 				for($i=0;$i<$subtract-1;$i++){
 					$eveningPackageSum++;
 				}
 			}else{
 				for($i=0;$i<$subtract;$i++){
 					$eveningPackageSum++;
 				}
 			}
 			break;
 	}
 	
 	//计算晚间套餐时间(秒)
 	$eveningSecondSum=$eveningPackageSum*15.5*3600;
 	
 	return array('times'=>$eveningPackageSum,'second'=>$eveningSecondSum);
 }
