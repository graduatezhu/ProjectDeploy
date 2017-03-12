<?php

namespace APIes\Controller;

use Think\Controller;

class FavoriteController extends Controller {
	/*
	@用户收藏
	@time：2017-03-06
	@author：zwm
	@param:token 验证令牌
	@param:userid 用户id
	@param:stationid 租赁点ID
	@param:lng、lat 经纬度
	*/
	/**
	 * 初始化
	 */
	/*public function __construct() {
		parent::__construct();
		A('Tokens')->PublicToken();//验证 公共 token
	}*/
	//收藏租赁点
	public function zcollect(){
		//连接服务器标识
		$return['success']=true;
		if (IS_POST) {
			//$token=I('post.token','','strip_tags');
			$userid=trim(I('post.userid')==''?'':I('post.userid','','strip_tags'));
			$lat=trim(I('post.lat')==''?'':I('post.lat','','strip_tags'));
			$lng=trim(I('post.lng')==''?'':I('post.lng','','strip_tags'));
			if(empty($userid)||empty($lat)||empty($lng)){
				$return['msg']='参数不完整';
				$return['code']='-10000';
			}else{
				$mo=D('Zcfavorite')->seldata($userid,$lat,$lng);
				//print_r($mo);die;
				if ($mo) {
					$return['status']=0;
					$return['msg']='查询成功!';
					$return['code']='10001';
					$return['info']=$mo;
				}else{
					$return['status']=-1;
					$return['code']='10002';
					$return['msg']='查询无数据!';
				}
			}
			
			
		}else{
			$return['status']=-1;
			$return['msg']='查询失败!';
			$return['code']='10003';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	
	//删除 清空收藏
	public function fdel(){
		$return['success']=true;
		if (IS_POST) {
			$userid=trim(I('post.userid')==''?'':I('post.userid','','strip_tags'));
			$fid=trim(I('post.fid')==''?'':I('post.fid','','strip_tags'));
			if (empty($userid)) {
				$return['msg']='参数不完整!';
				$return['code']='-10000';
				echo jsonStr($return);exit;
			}
			$mo=D('Zcfavorite');
			$isa=$mo->fdels($userid,$fid);
			//$searchre=$mo->seldata($userid,$lat,$lng);
			if ($isa) {
				$return['status']=0;
				$return['msg']='删除成功!';
				$return['code']='10001';
				$return['del']='1';
			}else{
				$return['status']=-1;
				$return['msg']='删除失败!';
				$return['code']='10003';
				$return['del']='0';
			}
		}
		echo jsonStr($return);exit;
	}
	
}