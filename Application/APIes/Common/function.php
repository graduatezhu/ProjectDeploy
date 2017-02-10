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
        // return '身份校验帧：'.$receiveAuthStrHex;
        // 校验服务端返回的身份验证帧应答
        $boolResult=verifiy_code(strtoupper($receiveAuthStrHex));
        // return '身份校验结果：'.$boolResult;

        if (!$boolResult) {
            return;
        }else{
            $commandFrame='';
            for ($j = 0; $j < count($commandArray); $j++) {
                $commandFrame.=chr(hexdec($commandArray[$j]));
            }
            // return '发送的命令帧：'.bin2hex($commandFrame);
            socket_write($socket, $commandFrame);//发送命令帧

            $receiveCommandStr = "";
            $receiveCommandStr = socket_read($socket, 1024, PHP_BINARY_READ);  // 采用二进制方式接收数据
            $receiveCommandStrHex = bin2hex($receiveCommandStr);  // 将2进制数据转换成16进制

        }
        return $receiveCommandStrHex;
    }
    socket_close($socket);  // 关闭Socket
}

/**
 * @Title: 电桩启停
 * @access public
 * @param string $code 二维码
 * @param string $gun 枪号
 * @param string $type 开启/关闭
 * @param string $userID 用户ID
 * @return array 控制结果
 * @author ZXD
 */
function pile_control($code, $gun, $type = '1',$userID) {

    // 用于返回信息输出
    $switch=($type=='0')?'开启':'关闭';

    settype($code,'string');

    // 截取18位电站编号
    $stationNO=substr($code,0,18);//从下标0开始取18位

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($code,18,3).'A';

    // 补齐2位枪号
    $gun=str_repeat('0',(2-strlen($gun))).$gun;

    // 补齐24位用户身份编号，其中'!'ascii码为16
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
    // return $receiveFrame;

    // 校验服务端返回的身份验证帧应答
    $boolResult=verifiy_code($receiveFrame);

    if ($boolResult) {
        if(substr($receiveFrame, -6,2)=='00'){
            $return['status']='0';
            $return['message']='电桩'.$switch.'成功';
        }else {
            $return['status']='-1';
            $return['frameFromServer']=$receiveFrame;
            $return['message']='电桩'.$switch.'失败';
        }

    }else{
        $return['status']='-9';
        $return['message']='应答帧校验错误';
    }

    return $return;
}


/**
 * @Title: 修改电价
 * @access public
 * @param string $code 二维码
 * @param string $price 新电价
 * @return array 控制结果
 * @author ZXD
 */
function modify_price($code,$price) {
    settype($code,'string');

    // 截取18位电站编号
    $stationNO=substr($code,0,18);

    // 截取3位电桩编号并根据帧规则拼接字符A
    $pileNO = substr($code,18,3).'A';

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

    // 校验服务端返回的身份验证帧应答
    $boolResult=verifiy_code($receiveFrame);

    if ($boolResult) {
        if(substr($receiveFrame, -8,2)=='00'){
            $return['status']='0';
            $return['message']='电价修改成功';
        }else {
            if(substr($receiveFrame, -6,2)=='01'){
                $return['status']='-1';
                $return['frameFromServer']=$receiveFrame;
                $return['message']='已插枪，无法修改电价';
            }
            
        }

    }else{
        $return['status']='-9';
        $return['message']='应答帧校验错误';
    }

    return $return;
}