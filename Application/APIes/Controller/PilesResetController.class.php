<?php
/**
 * @ClassName: PilesReset
 * @Description: 电桩充电接口
 * @Company: EDog
 * @author ZXD
 * @date 2017年2月13日上午11:08:09
 */
class PilesResetController extends CommonController {
    
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
	 * @Title: 重启电桩
	 * @Description: 返回重启控制结果
	 * @param string QRCode 桩二维码
	 * @return JSON
	 * @throws
	 */
	public function reset() {
	    set_time_limit(120);
	    
	    $return['success'] = true;
	    
	    $QRCode = I('post.QRCode','','trim'); // 电桩二维码编号
	    
	    if (is_empty($QRCode)){
	        $return['status'] = '-1';
	        $return['code']='10002';
	        $return['msg'] = '传参不完整';
	        
	    }else{
	        $cmdRTNArray=reset_pile($QRCode); // 返回电桩重启控制结果的状态数组
	        switch ($cmdRTNArray['status']) {
	            case '0':
	                $return['status'] = '0';
	                $return['msg']=$cmdRTNArray['msg']; // 电桩重启成功
	                break;
	            case '-1':
	                $return['status'] = '-1';
	                $return['code']='10101';
	                $return['msg']=$cmdRTNArray['msg']; // 电桩重启失败;
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