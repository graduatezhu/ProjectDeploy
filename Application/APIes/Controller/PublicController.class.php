<?php

/**
 * 公共模块
 */
class PublicController extends CommonController {
	/**
	 * 初始化
	 */
	public function _initialize() {
		parent::_initialize();
	}
	
	//验证 公共 token
	public function chkPublicToken() {
		if($_SERVER['SERVER_ADDR'] == '127.0.0.1') return true;//本机不验证

		if( I('get.token') != md5(md5('e_charge').C('TOKEN_ALL').md5('edog')) ){
			$return['status'] = -1;
			$return['message'] = 'Public Token Fault';
			echo json_encode($return,JSON_UNESCAPED_UNICODE);
			die();
		}
	}
	
}