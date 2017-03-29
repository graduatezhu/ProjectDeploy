<?php
namespace APIes\Controller;
use Think\Controller;

/*
 * 提车接口，需要更新租车信息表 status 提车状态
 */
class PickUpController extends Controller {
    public function index(){
        $this->display();
    }

    public function pickupCar(){
    	
    	$return['result']=0;
    	
    	if(empty($_POST['orderID'])||empty($_POST['TotalMileage'])||empty($_POST['SN'])||empty($_POST['Code'])){
    		$return['status'] = 1;
    		$return['message'] = '传参不完整';
    	}else {
    		$orderID=$_POST['orderID'];
    		$TotalMileage=$_POST['TotalMileage'];
    		$startTime=time();//开门时间，作为结算时候的用车起始时间
    		
    		//用于调用智信通接口开启车门
    		$SN=$_POST['SN'];
    		$Code=$_POST['Code'];
    		$Value='500';
    		$TimeStamp=get_current_microtimestamp();
    		$CustomerFlag='000';
    		$PrivateKey='1170tsYYDL';
    		    		
    		//判断是否首次提车
    		$map['order_id']=$orderID;
    		$field='borrow_time';
    		$ob=M('e_zc_info');
    		$re=$ob->field($field)->where($map)->select();
    		   			
    		//首次提车
    		if (empty($re)) {
				
				//调用智信通开启车门接口
				$baseUrl='http://221.123.179.91:9819/yydl/RemoteControlCarsNew.ashx?';
				$parameter='SN='.$SN.'&Code='.$Code.'&Value='.$Value.'&TimeStamp='.$TimeStamp.'&CustomerFlag='.$CustomerFlag;
				
				$encrypt= $parameter.$PrivateKey;
				$md5=md5($encrypt);

				$url=$baseUrl.$parameter.'&checkInfo='.$md5;
				$json=json_decode(file_get_contents($url));
				
				$result= $json->result;//解析执行结果
				if($result!=1){
					//开启车门失败
					
					//删除插入的租车info记录
					//$condition['order_id']=$orderID;
					//$obj->where($condition)->delete();
					
					//删除订单
					//$order->where($map1)->delete();
					
					$return['status'] = 102;
					$return['message'] = '智信通开启车门失败';
					$return['info']=$json;//智信通状态
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
					return;
				}else{
					
					//查询订单信息并生成租车记录
	    			$order=M('e_zc_orders');
	    			$order_info=$order->where("id={$orderID}")->find();
	
	    			$info_data['user_id']=$order_info['user_id'];
	    			$info_data['car_id']=$order_info['car_id'];
	    			$info_data['station']=$order_info['station'];
	    			$info_data['station_id']=$order_info['station_id'];
	    			$info_data['order_id']=$order_info['id'];
	    			$info_data['add_time']=$startTime;
	    			$info_data['start_time']=$startTime;
	    			$info_data['borrow_time']=$startTime;
	    			$info_data['status']=2;//使用中
	    			$info_data['long_rent']=0;
	    			
	    			$obj=M('e_zc_info');
	    			$re_info=$obj->add($info_data);
	    			
	    			//更新车辆总里程信息
	    			unset($map);
	    			$info['miles']=$TotalMileage;
	    			$map[id]=$order_info['car_id'];
	    			$cars_udp=M('e_zc_cars')->where($map)->save($info);  			
	    			
	    			//更新订单信息
	    			$map1['id']=$orderID;
	    			$data['borrow_time']=$startTime;//记录实际提车时间
	    			$data['pay_type']=2;//待结算
	    			$orders_udp=$order->where($map1)->save($data);
					
					if (empty($re_info)||$orders_udp===false||$cars_udp===false) {
	    				$return['status'] = 101;
	    				$return['message'] = '业务系统用车时间记录失败';
	    			}else{
						$return['status'] = 100;
						$return['message'] = '开始用车时间记录成功';
						$return['PickUpTime']=$startTime;//提车时间
						$return['info']=$json;//智信通状态返回的状态
					}
				}
    			
    		}else{
    			//非首次提车，由APP我的租车调用智信通开启车门
    			$return['status'] = 0;
    			$return['message'] = '开始用车';
    		}
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
}
