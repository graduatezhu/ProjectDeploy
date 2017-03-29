<?php

/**
 * TOKEN模块 有误供调试
 */
class TokenController extends CommonController {
	
    public $token;
    
	public function _initialize() {
		parent::_initialize();
		
	}
	
	//验证token
	public function chkToken() {
		if($_SERVER['SERVER_ADDR'] == '127.0.0.1') return true;//本机不验证

		if( I('get.token') != $this->token ){
			$return['status'] = -1;
			$return['message'] = 'Public Token Fault';
			$return['token_get']=I('get.token');
			$return['token_source']=$this->token;
			echo json_encode($return,JSON_UNESCAPED_UNICODE);
			die();
		}
	}
	
	//获取token
	public function getToken() {
	    
	    $return['success']=true; // 接口通信成功标志
	    
	    $time=time();
	    $this->token=md5(md5('e_charge').C('TOKEN_ALL').md5($time));
	    
	    $return['status'] = 0;
		$return['message'] = $this->token;
	    
	    echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}
	
}