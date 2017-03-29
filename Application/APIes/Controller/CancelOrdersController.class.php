<?php
/*
 * 取消订单接口
 */
namespace APIes\Controller;
use Think\Controller;
class CancelOrdersController extends Controller {
    public function index(){
        $this->display();
    }
	
	//10分钟取消未付款的预支付订单,系统自动取消
    public function autoCancel(){

        $ob=M('e_zc_orders');
    	$map['pay_type']='1';//支付状态 0未支付、1已付保证金
    	$map['status']='0';//订单状态 0正常、-1超时取销订单
    	$field='id,user_id,add_time,car_id';

    	$list=$ob->field($field)->where($map)->select();
    	
    	//超过10分钟取消订单
    	foreach($list as $k=>$v){
    		$endtime=$v['add_time'];//取出每条记录的下单时间
    		$curtime=time();//记录当前时间
    		if($curtime-$endtime>=600){
    			$data['status']='-1';
    			$where['id']=$v['id'];
    			$ob->where($where)->save($data); 
    			
    			//取消订单后用户表中当日取消订单次数+1
	    		$member=M('e_members');
	    		$member->where("id={$v['user_id']}")->setInc('order_time');	

	    		//取消订单后车辆置为空闲
	    		$car=M('e_zc_cars');
	    		$car_info['occupation']=0;
	    		$car->where("id={$v['car_id']}")->save($car_info);		 
    		}
    	}
    }
    
    //APP取消订单
	public function appCancel(){
		$orderID=I('post.orderID','','trim');

		$ob=M('e_zc_orders');
		$map['id']=$orderID;
		$data['status']='-1';
		$re=$ob->where($map)->save($data);

		if($re===false){
			$return['status'] = 1;
			$return['message'] = '订单取消失败';
		}else{
			//取消订单后用户表中当日取消订单次数+1
			$u_id=$ob->field('user_id,car_id')->where("id={$orderID}")->find();
	    	$member=M('e_members');
	    	$member->where("id={$u_id['user_id']}")->setInc('order_time');

	    	//取消订单后车辆置为空闲
    		$car=M('e_zc_cars');
    		$car_info['occupation']=0;
    		$car->where("id={$u_id['car_id']}")->save($car_info);
	    	
			$return['status'] = 0;
			$return['message'] = '订单取消成功';
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}
	
	//订单当前时间
	public function getOrderStatus(){
		$return['result']=0;
		
		$userID=I('post.userID','','trim');//用户ID
		if (empty($userID)) {
			$return['status']=1;
			$return['message']='传参不完整';
		}else{		 
			$ob=M('e_zc_orders');
			$map['o.user_id']=$userID;
			$map['o.pay_type']= 1;//已付保证金，过滤待结算等情况
			$re=$ob->join('as o left join e_members as m on o.user_id=m.id left join e_zc_cars as c on o.car_id=c.id left join e_zc_station as s on c.station_id=s.id')
				   ->field('o.id,o.user_id,o.brand,o.model,o.image,o.plate,o.car_id,o.status,c.station_id,c.capacity,c.occupation,c.sn,c.code,s.name,s.address,s.phone,s.lat,s.lng,o.add_time')
				   ->where($map)
				   ->order('o.id desc')
				   ->limit(1)
				   ->select();
// echo $ob->getlastsql();die;
			//加入用户当前取消订单次数
			$member=M('e_members');
			$member_data=$member->field('order_time')->where("id={$userID}")->find();
			
		   	if(empty($re)){
		   		$return['status']=101;
		   		$return['message']='数据库查询失败';
		   	}else {
				if($re[0]['status']==-1){
					$return['status']=102;
					$return['message']='此用户无倒计时订单';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
					return;
				}else{
			   		$return['status']=100;
			   		$return['message']='订单倒计时信息查询成功';
			   		$re[0]['orderID']=$re[0]['id'];
			   		$re[0]['end_time']=time();
			   		unset($re[0]['id']);
					unset($re[0]['status']);
			   		
			   		$return['info']=$re[0];
			   		$return['info']['remain_time']=(2-$member_data['order_time']);
				}
		    }
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}

}
