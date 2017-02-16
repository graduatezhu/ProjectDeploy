<?php
/**
 * @ClassName: PilesSwitch
 * @Description: 电桩充电接口
 * @Company: EDog
 * @author ZXD
 * @date 2017年2月13日上午11:08:09
 */
class PilesSwitchController extends CommonController {
    
    /*声明数据表Model对象*/
//     public $tblChargTmp;
    
    /*初始化*/
	public function _initialize() {
	    
		parent::_initialize(); //调用父类成员函数
		
		A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN
		
		/*实例化模型对象*/
// 		$this->tblChargTmp=D('ChargeTmp');
		
	}
	
	/**
	 * @Title: 电桩启停控制
	 * @Description: 返回启停控制结果
	 * @param string QRCode 桩二维码
	 * @param string gunCode 枪编号
	 * @param string cmdType 启停命令
	 * @param string userID 用户编号
	 * @return JSON
	 * @throws
	 */
	public function switchs() {
	    set_time_limit(120);
	    
	    $return['success'] = true;

	    $QRCode = I('post.QRCode','','trim'); // 电桩二维码编号
	    $gunCode = I('post.gunCode','1'); // 充电枪编号,APP用户从界面选择,默认单枪1号枪
	    $cmdType = I('post.cmdType','1'); // 命令，0开启，1关闭
	    $userID = I('post.userID'); // 用户ID

// 	    echo $QRCode;die;
	    
	    if (is_empty($QRCode)||is_empty($gunCode)||is_empty($cmdType)||is_empty($userID)){
	        $return['status'] = '-1';
	        $return['code']='10002';
	        $return['msg'] = '传参不完整';
	        
	    }else{
	        $cmdRTNArray=switch_pile($QRCode,$gunCode,$cmdType,$userID); // 返回电桩启停控制结果的状态数组
	        switch ($cmdRTNArray['status']) {
	            case '0':
	                $return['status'] = '0';
	                $return['msg']=$cmdRTNArray['msg']; // 电桩启停成功
	                break;
	            case '-1':
	                $return['status'] = '-1';
	                $return['code']='10101';
	                $return['msg']=$cmdRTNArray['msg']; // 电桩启停失败;
	                break;
	            case '-2':
	                $return['status'] = '-1';
	                $return['code']='10102';
	                $return['msg']=$cmdRTNArray['msg']; // 命令应答帧校验错误
	                break;
	            case '-3':
	                $return['status'] = '-1';
	                $return['code']='10103';
	                $return['msg']=$cmdRTNArray['msg']; // APP后台身份校验错误
	                break;
	        }
	        
	    }

	    echo json_encode($return,JSON_UNESCAPED_UNICODE);
	    
	}
	
	
	
	
	
}