<?php
/*namespace APIes\Controller;
use Think\Controller;*/
class TokensController extends \Think\Controller {
	
	//验证 公共 token
	public function PublicToken() {
		if( I('post.token') != md5(md5('e_charge').'20160601'.md5('edog')) ){
			$return['status'] = 10001;
			$return['message'] = '公共token错误';
			//var_dump($return);die;
			echo json_encode($return);exit;
		}
	}
}