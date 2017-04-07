<?php
/*namespace APIes\Controller;

use Think\Controller;*/

class ZstationController extends \Think\Controller {
	/*
	@站点信息
	@time：2017-03-08
	@author：zwm
	@param:token 验证令牌
	@param:userid 用户id
	@param:stationid 租赁点ID
	@param:lng、lat 经纬度
	@、、、、、
	*/
	/**
	 * 初始化
	 */
	/*public function __construct() {
		parent::__construct();
		A('Tokens')->PublicToken();//验证 公共 token
	}*/
	//地图租车列表
	public function rentmap(){
		$return['success']=true;
		if (IS_POST) {
			//$token=trim(I('post.token')==''?'':I('post.token','','strip_tags'));
			$mo=D('EZcStation')->rentmaps();
			if ($mo) {
				$return['status']=0;
				$return['msg']='查询成功!';
				$return['info']=$mo;
			}else{
				$return['status']=-1;
				$return['msg']='暂无数据!';
				$return['code']='10003';
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail!';
			$return['code']='10004';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	//地图租车弹窗
	public function maptan(){
		$return['success']=true;
		if (IS_POST) {
			//$token=I('post.token','','strip_tags');
			$lat=trim(I('post.lat')==''?'':I('post.lat','','strip_tags'));
			$lng=trim(I('post.lng')==''?'':I('post.lng','','strip_tags'));
			$zc_stationid=trim(I('post.zc_stationid')==''?'':I('post.zc_stationid','','strip_tags'));
			if (empty($lat)||empty($lng)) {
				$return['msg']='参数不完整';
				$return['code']='10002';
				echo jsonStr($return);exit;
			}
			$zc_station=D('EZcStation')->where(array('id'=>$zc_stationid))->select();
			if(empty($zc_station)){
							$return['status']=-1;
							$return['msg']='此站点不存在!';
							echo jsonStr($return);exit;
						}
			$mo=D('EZcStation')->maptans($lat,$lng,$zc_stationid);
			if ($mo) {
				$return['status']=0;
				$return['msg']='查询成功!';
				$return['info']=$mo;
			}else{
				$return['status']=0;
				$return['msg']='暂无数据!';
				$return['code']='10003';
			}
		}else{
			$return['status']=-1;
			$return['msg']='查询失败!';
			$return['code']='10004';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	//地图筛选
	public function mapchoice(){
		$return['success']=true;
		if (IS_POST) {
		//$token=I('post.token','','strip_tags');
			$val['iscar']=trim(I('post.iscar')==''?'':I('post.iscar','','strip_tags'));
			$val['batterylife']=trim(I('post.batterylife')==''?'':I('post.batterylife','','strip_tags'));
			$val['capacity']=trim(I('post.capacity')==''?'':I('post.capacity','','strip_tags'));
			$val['equipment']=trim(I('post.equipment')==''?'':I('post.equipment','','strip_tags'));
			$mo=D('EZcStation')->mapchoices($val);
			if ($mo) {
				$return['status']=0;
				$return['msg']='查询成功!';
				$return['info']=$mo;
			}else{
				$return['status']=-1;
				$return['msg']='暂无数据!';
				$return['code']='10003';
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail !';
			$return['code']='10004';
		}
		echo jsonStr($return);exit;
	}
	//租车列表(城市、筛选、暂无排序、)
	public function renthelist(){
		$return['success']=true;
		if (IS_POST) {
		//$token=I('post.token','','strip_tags');
			$val['userid']=trim(I('post.userid')==''?'':I('post.userid','','strip_tags'));
			$val['city']=trim(I('post.city')==''?'':I('post.city','','strip_tags'));
			$val['lat']=trim(I('post.lat')==''?'':I('post.lat','','strip_tags'));
			$val['lng']=trim(I('post.lng')==''?'':I('post.lng','','strip_tags'));

			$val['psort']=trim(I('post.psort')==''?'':I('post.psort','','strip_tags'));
			$val['iscar']=trim(I('post.iscar')==''?'':I('post.iscar','','strip_tags'));
			$val['batterylife']=trim(I('post.batterylife')==''?'':I('post.batterylife','','strip_tags'));
			$val['capacity']=trim(I('post.capacity')==''?'':I('post.capacity','','strip_tags'));
			$val['equipment']=trim(I('post.equipment')==''?'':I('post.equipment','','strip_tags'));
			if (empty($val['lat'])||empty($val['lng'])) {
				$return['msg']='参数不完整';
				$return['code']='10002';
				echo jsonStr($return);exit;
			}
			$mo=D('EZcStation')->renthelists($val);
			//print_r($mo);die;
			if ($mo) {
				$return['status']=0;
				$return['msg']='查询成功!';
				$return['info']=$mo;
			}else{
				$return['status']=-1;
				$return['msg']='暂无数据!';
				$return['code']='10003';
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail !';
			$return['code']='10004';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	//搜索租赁点
	public function zsearch(){
		$return['success']=true;
		if (IS_POST) {
			//$token=I('post.token','','strip_tags');
			$search=trim(I('post.search')==''?'':I('post.search','','strip_tags'));
			$lat=trim(I('post.lat')==''?'':I('post.lat','','strip_tags'));
			$lng=trim(I('post.lng')==''?'':I('post.lng','','strip_tags'));
			if (empty($search)) {
				$return['status']=-1;
				$return['msg']='暂无数据';
				$return['code']='10003';
				echo jsonStr($return);exit;
			}
			if (empty($lat)||empty($lng)) {
				$return['status']=-1;
				$return['msg']='传参不完整!';
				$return['code']='10002';
			}else{
				$mo=D('EZcStation');
				$re=$mo->zsearchs($search,$lat,$lng);
				if($re){
					$return['status']=0;
					$return['msg']='查询成功';
					$return['info']=$re;
				}else{
					$return['status']=-1;
					$return['msg']='暂无数据';
					$return['code']='10003';
				}
				
			}
			
		}else{
			$return['code']='10004';
			$return['status']=-1;
			$return['msg']='request fail !';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
	//附近热点城市
	public function zhotcity(){
		$return['success']=true;
		if (IS_POST) {
			//$token=trim(I('post.token')==''?'':I('post.token','','strip_tags'));
			$mo=D('EZcHotcity')->field('city_name')->select();
			if ($mo) {
				$return['status']=0;
				$return['msg']='查询成功!';
				$return['info']=$mo;
			}else{
				$return['status']=-1;
				$return['msg']='暂无数据!';
				$return['code']='10003';
			}
		}else{
			$return['status']=-1;
			$return['msg']='request fail!';
			$return['code']='10004';
		}
		//print_r($return);die;
		echo jsonStr($return);exit;
	}
}