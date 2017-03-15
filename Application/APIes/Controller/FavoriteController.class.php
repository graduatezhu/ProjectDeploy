<?php
/*
namespace APIes\Controller;

use Think\Controller;*/

class FavoriteController extends \Think\Controller {
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
	public function zfavorite(){
		//连接服务器标识
		$return['success']=true;
		if (IS_POST) {
			//$token=I('post.token','','strip_tags');
			$userid=trim(I('post.userid')==''?'':I('post.userid','','strip_tags'));
			$user=D('Members')->where(array('id'=>$userid))->select();
			$zc_stationid=trim(I('post.zc_stationid')==''?'':I('post.zc_stationid','','strip_tags'));
			$zcsta=D('ZcStation')->where(array('id'=>$zc_stationid))->select();
			if(empty($user)){
				$return['status']=-1;
				$return['msg']='此用户不存在!';
				echo jsonStr($return);exit;
			}
			if(empty($zcsta)){
				$return['status']=-1;
				$return['msg']='此站点不存在!';
				echo jsonStr($return);exit;
			}
			if(empty($userid)||empty($zc_stationid)){
				$return['msg']='参数不完整';
				$return['code']='10002';
			}else{
				$val['userid']=$userid;
				$val['zc_stationid']=$zc_stationid;
				$mo=D('Zcfavorite');
				$ismo=$mo->where($val)->select();
				if($ismo){
					$return['status']=-1;
					$return['msg']='已经添加!';
					$return['iscollect']='3';
					echo jsonStr($return);exit;
				}
				$moo=$mo->add($val);
				if ($moo) {
					$return['status']=0;
					$return['msg']='添加成功!';
					//$return['info']=$mo;
					$return['isfavorite']='1';
				}else{
					$return['status']=-1;
					$return['code']='10003';
					$return['msg']='添加失败!';
					$return['iscollect']='2';
				}
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail !';
			$return['code']='10004';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	//收藏租赁点列表
	public function zcollect(){
		//连接服务器标识
		$return['success']=true;
		if (IS_POST) {
			//$token=I('post.token','','strip_tags');
			$userid=trim(I('post.userid')==''?'':I('post.userid','','strip_tags'));
			$lat=trim(I('post.lat')==''?'':I('post.lat','','strip_tags'));
			$lng=trim(I('post.lng')==''?'':I('post.lng','','strip_tags'));
			$user=D('Members')->where(array('id'=>$userid))->select();
			if(empty($user)){
							$return['status']=-1;
							$return['msg']='此用户不存在!';
							echo jsonStr($return);exit;
						}
			if(empty($userid)||empty($lat)||empty($lng)){
				$return['msg']='参数不完整';
				$return['code']='10002';
			}else{
				$mo=D('Zcfavorite')->seldata($userid,$lat,$lng);
				//print_r($mo);die;
				if ($mo) {
					$return['status']=0;
					$return['msg']='查询成功!';
					$return['info']=$mo;
				}else{
					$return['status']=-1;
					$return['code']='10003';
					$return['msg']='查询无数据!';
				}
			}
			
			
		}else{
			$return['status']=-1;
			$return['msg']='request fail !';
			$return['code']='10004';
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
				$return['code']='10002';
				echo jsonStr($return);exit;
			}
			$user=D('Members')->where(array('id'=>$userid))->select();
			if(empty($user)){
							$return['status']=-1;
							$return['msg']='此用户不存在!';
							echo jsonStr($return);exit;
						}
			$mo=D('Zcfavorite');
			$isa=$mo->fdels($userid,$fid);
			//$searchre=$mo->seldata($userid,$lat,$lng);
			if ($isa) {
				$return['status']=0;
				$return['msg']='删除成功!';
				$return['del']='1';
			}else{
				$return['status']=-1;
				$return['msg']='删除失败!';
				$return['code']='10003';
				$return['del']='2';
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail !';
			$return['code']='10004';
		}
		echo jsonStr($return);exit;
	}
	
}