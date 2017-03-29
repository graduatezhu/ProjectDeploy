<?php
namespace APIes\Controller;
use Think\Controller;
class CarRentalController extends Controller {
	public function index(){
		$this->display();
	}
	//我的租车信息
	public function customerRentalInfo() {
		$return['result']=0;
		
		$userId=I('post.userID');
		if (empty($userId)) {
			$return['status']=1;
			$return['message']='传参不完整';
		}else{
			$time=time();
			
			//长租列表
			$map['i.user_id']=$userId;
			$map['i.long_rent']=1;
			//$map['i.start_time']=array('elt',"$time");//时间范围在提车接口pickup判断
			//$map['i.end_time']=array('egt',"$time");
			
			$ob1 = M('e_zc_info');

			$list1 = $ob1->join('as i left join e_zc_cars as c on c.id=i.car_id left join e_zc_orders as o on o.id=i.order_id left join e_zc_station as s on s.id=i.station_id')
						->field('i.id,i.order_id,o.order_number,i.station,s.address,s.lat,s.lng,i.car_id,c.plate,i.borrow_time,s.phone,i.long_rent,c.sn,c.code')
						->where($map)
						->select();
			//echo $ob1->getlastsql();die;
			//dump($list1);
			
			//非长租列表
			$where['i.user_id']=$userId;
			$where['o.pay_type']=2;//待结算，由提车接口更新,长租无此标志故表示为短租
			$where['i.return_time'] = array('EXP','IS NULL');

			$ob2 = M('e_zc_info');
			$list = $ob2->join('as i left join e_zc_cars as c on c.id=i.car_id left join e_zc_orders as o on o.id=i.order_id left join e_zc_station as s on s.id=i.station_id')
						->field('i.id,i.order_id,o.order_number,i.station,s.address,s.lat,s.lng,i.car_id,c.plate,i.borrow_time,i.return_time,s.phone,i.long_rent,c.sn,c.code')			
					   ->where($where)
					   ->select();
			//echo $ob2->getlastsql();die;
			//dump($list);
			
			//如果没有非长租记录
			if(empty($list)){
				$list=[];
			}
			//追加记录
			foreach ($list1 as $k=>$v) {
				array_push($list, $v);
			}
			
			unset($map);
			$ob_c=M('e_zc_cars');
			foreach ($list as $k=>$v){
				$map['c.id']=$list[$k]['car_id'];
				$res=$ob_c->join('as c left join e_zc_cars_model as m on m.id=c.model_id left join e_zc_cars_brand as b on b.id=c.brand_id')
						  ->field('b.name as brand,m.name as model,m.img_url')
						  ->where($map)
						  ->select();
				
				$list[$k]['carID']=$v['car_id'];
				$list[$k]['orderID']=$v['order_id'];
				//$list[$k]['start_time']=date('Y-m-d H:i',$v['start_time']);//采用时间戳
				//$list[$k]['start_time']=$v['start_time'];
				//$list[$k]['end_time']=date('Y-m-d H:i',$v['end_time']);
				unset($list[$k]['car_id']);
				unset($list[$k]['order_id']);
				
				$list[$k]['image']=$res[0]['img_url'];
				$list[$k]['brand']=$res[0]['brand'];
				$list[$k]['model']=$res[0]['model'];
				
// 				if(empty($list[$k]['borrow_time'])){
// 					$list[$k]['use']=0;//首次提车
// 				}else{
// 					$list[$k]['use']=1;//继续用车
// 				}
			}

			//foreach($list as $k=>$v){
				//if($v['return_time']){
					//unset($list[$k]);
				//}
			//}
			
			if(empty($list)){
				$return['status']=1;
				$return['message']='查询无数据';
			}else {
				$return['status']=0;
				$return['message']='查询成功';
				
				$return['info']=$list;
			}
		}
		echo urldecode(json_encode(url_encode($return)));
	}
	
	
	//确认用车
	public function genPrepayOrder(){
		$return['result'] = 0;
		
		$userID=I('post.userID');
		$stationID=I('post.stationID');
		$carID=I('post.carID');
	

		if (empty($userID)||empty($stationID)||empty($carID)) {
			$return['status']=1;
			$return['message']='传参不完整';
		}else{
			//检查实名认证
			$member=M('e_members');
			$member_data=$member->field('verified,deposit,order_time')->where("id={$userID}")->find();
			switch ($member_data['verified']){
				case 0:
				  	$return['status']=101;
					$return['message']='您未进行实名认证';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
				  	die;
				case 1:
				  	$return['status']=102;
					$return['message']='您还在实名认证中';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
				  	die;
				case 3:
				  	$return['status']=103;
					$return['message']='您的实名认证失败';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
				  	die;
			}


			//检查是否缴纳保证金
			if($member_data['deposit']==0){

				//查看保证金人数上限是否达到
				$member_deposit_count=$member->where('deposit=1')->count();
				if($member_deposit_count>=100){
					$return['status']=104;
					$return['message']='保证金名额超过试运营活动人数限制';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
					die;
				}else{
					$return['status']=105;
					$return['message']='您还没有缴纳保证金';
					echo json_encode($return,JSON_UNESCAPED_UNICODE);
					die;				
				}
			}

			//检查是否已提交保证金退款申请
			if($member_data['deposit']==2){
				$return['status']=120;
				$return['message']='您已提交保证金退款，退款完成前不能下订单';
				echo json_encode($return,JSON_UNESCAPED_UNICODE);
				die;
			}



			//查看当日是否两次以上刚取消订单
			if($member_data['order_time']>=100){
				$return['status']=106;
				$return['message']='当日已经取消订单两次';
				echo json_encode($return,JSON_UNESCAPED_UNICODE);
				die;
			}

			//8:30-17:00允许租车
			// $hour=date('H',time());
			// $minute=date('i',time());
			// if($hour>8 && $hour<17){
			
			// }else{
			// 	if($hour==8 && $minute>30){

			// 	}else{
			// 		$return['status']=110;
			// 		$return['message']='不在允许的时间内';
			// 		echo json_encode($return,JSON_UNESCAPED_UNICODE);
			// 		die;
			// 	}
			// }
			
			//查看车辆是否被占用
			$car_status=M('e_zc_cars');
			$car_status_data=$car_status->field('occupation')->where("id={$carID}")->find();
			if($car_status_data['occupation']==1){
				$return['status']=109;
				$return['message']='车辆已经被占用';
				echo json_encode($return,JSON_UNESCAPED_UNICODE);
				die;
			}


			//您有未支付订单，请先去支付
			$ob=M('e_zc_orders');
			$map['user_id']=$userID;
			$map['status']=0;
			$map['pay_type']=array('NEQ','3');
			$count=$ob->where($map)->count();
			if($count>0){
				$return['status']=108;
				$return['message']='您有未支付订单，请先去支付';
				echo json_encode($return,JSON_UNESCAPED_UNICODE);
				die;
			}

			$map_c['id']=$carID;
			//先把车辆状态改为占用
			$car_info['occupation']=1;
			M('e_zc_cars')->where("id={$carID}")->save($car_info);


			$field='id,plate,brand_id,model_id,picture,price';
			$re_c=M('e_zc_cars')->field($field)->where($map_c)->select();
			//echo  M("zc_cars")->getLastSql();die;
			
			//租车站名称
			$map_s['id']=$stationID;
			$re_s=M('e_zc_station')->field('name')->where($map_s)->select();
			
			//品牌名称
			$map_b['id']=$re_c[0]['brand_id'];
			$re_b=M('e_zc_cars_brand')->field('name')->where($map_b)->select();
			//车型名称
			$map_m['id']=$re_c[0]['model_id'];
			$re_m=M('e_zc_cars_model')->field('name')->where($map_m)->select();

			$time=time();
			
			//生成预支付订单号
			$order_number = 'EZC'.get_micro_time(3).mt_rand(1000,9999);
			$fixed_use_time=$end_time-$start_time;//预约用车时长(秒)
			
			//额定总价=单价（元/时）*用车时间（小时,这里是整时，无分秒所以除以3600即可）
			//$fixed_total_fee=$re_c[0]['price']*($fixed_use_time/3600);

			$data['user_id']=$userID;
			$data['car_id']=$carID;
			$data['station']=$re_s[0]['name'];//站点名称
			$data['plate']=$re_c[0]['plate'];//车牌号
			$data['image']=$re_c[0]['picture'];//图片
			$data['price']=$re_c[0]['price'];//车辆租用单价 0.5元/分钟
			$data['brand']=$re_b[0]['name'];//品牌
			$data['model']=$re_m[0]['name'];//车型
			$data['station_id']=$stationID;//站点ID

			$data['add_time']=$time;//下单时间
			$data['fixed_use_time']=$fixed_use_time;//预约用车时长（秒）
			//$data['fixed_total_fee']=$fixed_total_fee;//额定总价
			$data['upd_time']=$time;//记录更新时间
			$data['order_number']=$order_number;//预支付订单号
			
			//定金
			$where['var_name']='deposit';
			$re_dj=M('e_zc_sysparameter')->field('var_value')->where($where)->select();
			$data['deposit']=$re_dj[0]['var_value'];
			
			$order_id=M('e_zc_orders')->data($data)->add();//生成预支付订单

// 			$info['car_id']=$carID;
// 			$info['user_id']=$userID;
// 			$info['order_id']=$order_id;
// 			$info['start_time']=$start_time;
// 			$info['end_time']=$end_time;
// 			$info['add_time']=time();
			
// 			$re=M('e_zc_info')->data($info)->add();//生成租车信息记录,改由支付接口pay生成

			if(empty($order_id)){
				$return['status']=1;
				$return['message']='预支付订单生产失败';
			}else {
				$return['status']=100;
				$return['message']='订单生产成功';
				$return['remain_time']=(2-$member_data['order_time']);
				$return['orderID']=$order_id;
				//$return['info']['order_number']=$order_number;//此处不返回订单编号
			}
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}

	//订单列表，进行中和已完成的订单
	public function orderList(){
		$return['result'] = 0;
	
		@$userID=I('post.userID');
		
		if( empty($userID) ){
			$return['status'] = 1;
			$return['message'] = '传参不完整';
		}else{
			$map['user_id']=$userID;
			//$map['pay_type']=0;//未支付定金，这里不应限定
			$fields='id,station,brand,model,plate,add_time,pay_type,order_number,status,borrow_time,return_time';
			$lists = M('e_zc_orders')->field($fields)->where($map)->order('id desc')->select();//订单信息
			//dump($lists);die;
			$ob=M('e_zc_info');
			foreach($lists as $k=>$v){
 				$map_id['order_id']=$v['id'];
 				$res=$ob->field('status')->where($map_id)->select();
 				$lists[$k]['return_type']=$res[0]['status'];//车辆使用状态

 				if($v['pay_type']==6){
 					$lists[$k]['pay_type']=2;
 				}

 				if($v['add_time']==0){
 					$lists[$k]['add_time']='0';
 				}else{
					$lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);//下单时间
 				}

 				if($v['borrow_time']==0){
 					$lists[$k]['start_time']='0';
 				}else{
					$lists[$k]['start_time']=date('Y-m-d H:i:s',$v['borrow_time']);//取车时间
 				}

 				if($v['return_time']==0){
 					$lists[$k]['end_time']='0';
 				}else{
					$lists[$k]['end_time']=date('Y-m-d H:i:s',$v['return_time']);//还车时
 				}

			}
			
					
			if( empty($lists) ){
				$return['status'] = 1;
				$return['message'] = '查询无数据';
			}else{
				$return['status'] = 0;
				$return['message'] = '查询成功';
				
				$return['info'] = $lists;
			}
		}
	
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}
	
	//预支付订单详情
    public function prepayOrderdetails(){
		$return['result'] = 0;
		
		//$userID=I('post.userID');
		$orderID=I('post.orderID');
		//dump($_POST);die;
		if(empty($orderID)){
			$return['status'] = 1;
			$return['message'] = '传参不完整';
		}else{
			//$map['user_id']=$userID;
			$map['id']=$orderID;
			$map['pay_type']=0;//未支付定金，用于显示需要缴纳定金明细
			
			$field = 'id,station,brand,plate,model,start_time,end_time,order_number,add_time,deposit';
			$res = M('e_zc_orders')->field($field)->where($map)->select();
			
			foreach ($res  as $k=> $v) {
				$res[$k]['start_time']=date('Y-m-d H:i',$v['start_time']);
				$res[$k]['end_time']=date('Y-m-d H:i',$v['end_time']);
				$res[$k]['add_time']=date('Y-m-d H:i',$v['add_time']);
				//unset($res[$k][id]);
			}
				
			if( empty($res) ){
				$return['status'] = 1;
				$return['message'] = '没有数据';
			}else{
				$return['status'] = 0;
				$return['message'] = '查询成功';
				
				$return['info'] = $res;
			}
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
    
    //生成结算订单
    public function genBalance() {
    	$return['result']=0;
    	
    	$userID=I('post.userID');
    	$carID=I('post.carID');
    	$TotalMileage=I('post.TotalMileage');//车辆行驶总里程

    	if (empty($userID)||empty($carID)||empty($TotalMileage)) {
    		$return['status']=1;
    		$return['message']='传参不完整';
    	}else {
    		$ob=M('e_zc_info');
    		
    		$where['user_id']=$userID;
    		$where['car_id']=$carID;
    		$where['status']=2;//正在用车的用户，才可以还车结算，该状态由提车接口更新
    		$reInfo=$ob->field('order_id')->where($where)->order('id desc')->limit(1)->select();
    		
    		//当前时间为 实际还车时间
			$time=time();
    		
    		$map['id']=$reInfo[0]['order_id'];
    		$field='id,user_id,price,start_time,end_time,deposit,borrow_time,order_number,add_time';
    		$re=M('e_zc_orders')->field($field)->where($map)->select();
			
    		if (empty($re)) {
    			$return['status'] = 1;
				$return['message'] = '没有该用户租车数据';
    		}else{
    			
    			$return_time=$time;//实际还车时间
// $return_time=1482453001;
				$borrow_time=$re[0]['borrow_time']==0?$re[0]['add_time']:$re[0]['borrow_time'];//提车时间为开车门时间，为避免开车门时未记录，所以如果为0则取下单时间
				$actual_use_time=$return_time-$borrow_time;//实际用车时长 秒
// echo date('Y-m-d H:i:s',$borrow_time),'<br>',date('Y-m-d H:i:s',$return_time),'<br>';

// 				$array=timestap_to_array(0,$actual_use_time);//时间戳返回天-时-分-秒的数组
// 				$day=$array['day'];
// 				$hour=$array['hour'];
// 				$minute=$array['minute'];
// 				$second=$array['second'];
// dump($array);echo '<br>';
				
				/*提取租车价格列表*/
				unset($where);
				$obj=M('e_zc_sysparameter');
				$field='var_name,var_value';
				$where['flag']=array(array('eq','price'),array('eq','discount'),'or');
				$list=$obj->field($field)->where($where)->select();

				foreach ($list as $key=>$value) {
					$arr[$value['var_name']]=$value;
				}	
				/*END*/
				
				/*工作日优惠活动*/
				//$activitySecond=getFavorableTime($borrow_time, $return_time);//返回优惠活动计时数量（秒）
				//$actual_use_time -= $activitySecond;//扣除活动时间
				/*END*/
				
// echo '扣除工作日活动计时后','<br />';dump(timestap_to_array(0,$actual_use_time));echo '<br />';

				/*晚间套餐*/
				$arrTmp=getEveningPackageTimes($borrow_time, $return_time);//返回数组，晚间套餐次数及计时数量（秒）
				$eveningTimes=$arrTmp['times'];
				$eveningSecond=$arrTmp['second'];
				
				$actual_use_time -= $eveningSecond;//扣除晚间套餐时间
				$eveningFee=$arr['evening'][var_value]*$eveningTimes; //晚间套餐计费
				/*END*/


				/*租车价格计算BEGIN*/
				//租车用时
				$arrRentalTime=timestap_to_array(0,$actual_use_time);//返回时间戳天-时-分-秒的数组
				$day=$arrRentalTime['day'];
				$hour=$arrRentalTime['hour'];
				$minute=$arrRentalTime['minute'];
				$second=$arrRentalTime['second'];
				
// echo '最终租车计时','<br />';dump($arrRentalTime);echo '<br>';
				
				//租车时长不足1小时,并且租车用时不全在工作日活动区间内的，按1小时计费
				$tmpTime=$return_time-$borrow_time;
				if($tmpTime<3600 && ($tmpTime-$activitySecond>0)){
					$day=0;$hour=1;$minute=0;$second=0;
				}

				//计算日费用
				$day_fee=$day*$arr['day1'][var_value];

				//计算小时费用
				$hour_fee=$hour*$arr['hour1'][var_value];
				
				// if($hour<=1){
					// $hour_fee=$hour*$arr['hour1'][var_value];//每小时28元，小时皆为整数
				// }elseif($hour>=2 && $hour<4){
					// $hour_fee=($hour-2)*60*$arr['min1'][var_value]+$arr['hour2'][var_value];//2小时48元，大于2小时（例3小时）每分钟0.5元
				// }elseif($hour>=4 && $hour<24){
					// $hour_fee=($hour-4)*60*$arr['min1'][var_value]+$arr['hour4'][var_value];//4小时88元，大于4小时每分钟0.5元
				// }

				//计算分钟费用
				$minute_fee=$minute*$arr['min1'][var_value];
				
				// if($minute<10){
					// $minute_fee=$minute*$arr['min1'][var_value];//10分钟5元
				// }elseif ($minute>=10 && $minute <30){
					// $minute_fee=($minute-10)*$arr['min1'][var_value]+$arr['min10'][var_value];//大于10分钟每分钟0.5元
				// }elseif($minute>=30 && $minute<60){
					// $minute_fee=($minute-30)*$arr['min1'][var_value]+$arr['min30'][var_value];//30分钟15元，大于30分钟每分钟0.5元
				// }
				
				//计算秒费用
				if($second>0){
					$second_fee=$arr['min1'][var_value];//不足1分钟，按1分钟计算
				}else{
					$second_fee=0;
				}
				
				//合计
				$fee=$day_fee+$hour_fee+$minute_fee+$second_fee+$eveningFee;
// echo 'dayfee= '.$day_fee,'hourfee= '.$hour_fee,'minutefee= '.$minute_fee,'secondfee= '.$second_fee,'fee= '.$fee;die;
				
				$actual_total_fee=$fee;//应付金额
				//$fee==0?$actual_total_fee=0.01:$actual_total_fee=$fee;
				
				$discount=$arr['discount'][var_value];
				
				if($discount>0){
					$fee=$fee*$arr['discount'][var_value];//折扣
				}
				
				//实付金额
				$fee==0?$payment=0.01:$payment=$fee;
				//$payment=$fee;

				/*END*/
				
				//更新订单信息
    			$data['return_time']=$return_time;
    			$data['payment']=$payment;
    			$data['upd_time']=$time;
    			$data['actual_use_time']=$actual_use_time;
    			
    			$data['actual_total_fee']=$actual_total_fee;
    			$data['payment']=$payment;
    			$data['discount']=$discount;//折扣
    			//$data['pay_type']=2;//待结算,由提车接口更新
    			$reu=M('e_zc_orders')->where($map)->save($data);

//echo M('e_zc_orders')->getlastsql();die;

				//提取车辆总行驶里程
				$ob_cars=M('e_zc_cars');
				$field='miles';
				$condition['id']=$carID;
				$re_cars=$ob_cars->field($field)->where($condition)->select();

    			//更新租车信息
    			$map_info['order_id']=$reInfo[0]['order_id'];
    			$info['status']=1;//已还车，解锁APP订单列表为待结算状态，避免未还车结算
    			$info['return_time']=$time;//实际还车时间
    			$info['miles']=$TotalMileage-$re_cars[0]['miles'];//用户用车里程
				$info['alarm']=0;//取消超时短信提醒
    			$res=$ob->where($map_info)->save($info);
				
				//更新车辆属性信息
				$map_use['id']=$carID;
				$data2['occupation']=0;//车辆空闲
				$data2['miles']=$TotalMileage;//车辆总里程
				$re_use=M('e_zc_cars')->where($map_use)->save($data2);
				
    			if($reu===false||$res===false||$re_use===false){
    				$return['status'] = 1;
    				$return['message'] = '结算订单生成失败';
    			}else {
    				$return['status'] = 0;
    				$return['message'] = '结算订单生成成功';
    				$return['info']['orderID']=$re[0]['id'];//订单ID，供APP调用订单详情使用
    			}
    		}
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
    
    //结算订单详情
    public function balanceDetails() {
    	$return['result']=0;
    
    	$orderID=I('post.orderID');
    	 
    	if (empty($orderID)) {
    		$return['status']=1;
    		$return['message']='传参不完整';
    	}else {
    
    		$map['id']=$orderID;
    		$field='id,car_id,order_number,brand,model,plate,add_time,borrow_time,return_time,actual_use_time,actual_total_fee,payment,discount';//待增删
    		$re=M('e_zc_orders')->field($field)->where($map)->select();
    		
			//$where['order_id']=$orderID;//结算表的结算编号
    		//$field='balance_number';
    		//$res=M('e_zc_balance')->field($field)->where($where)->select();
    		
    		//获取用户行驶里程
    		$where['order_id']=$orderID;
    		$field='miles';
    		$res=M('e_zc_info')->field($field)->where($where)->select();
    		
    		if (empty($re)) {
    			$return['status']=1;
    			$return['message']='无订单结算数据';
    		}else{
    			$return['status']=0;
    			$return['message']='订单结算详情查询成功';
    			 
    			$data['orderID']=$re[0]['id'];//订单ID
    			$data['order_number']=$re[0]['order_number'];//订单编号,此处仍使用EZC作为结算编号，供结算调起支付使用
				//$data['order_number']=$res[0]['balance_number'];//结算编号
				
    			$data['brand']=$re[0]['brand'].$re[0]['model'];//车辆型号
    			$data['plate']=$re[0]['plate'];//车牌号码
    			$data['total_mile_age']=$res[0]['miles'];//行驶里程
    			$data['actual_use_time']=second_to_date(0,$re[0]['actual_use_time']);//实际用车时长
    			
    			$data['order_time']=date('Y-m-d H:i:s',$re[0]['add_time']);//下单时间
    			$data['borrow_time']=date('Y-m-d H:i',$re[0]['borrow_time']);//实际取车时间
    			$data['return_time']=date('Y-m-d H:i',$re[0]['return_time']);//实际还车时间
    			
    			$data['actual_total_fee']=$re[0]['actual_total_fee'];//费用总计
    			
			if($re[0]['discount']<1){
				$discount=10*$re[0]['discount'].'折';
			}else{
				$discount='无';
			}
				
    			$data['discount']=$discount;//优惠折扣

			//$data['discount']=$re[0]['discount'];//优惠折扣
    			$data['payment']=number_format($re[0]['payment'],2,'.','');//应付金额
    			
    			
				//实际用车时长
				//$actual_use_time=second_to_date(0,$re[0]['actual_use_time']);
				//$data['actual_use_time']=second_to_date(0,$re[0]['actual_use_time']);
				
    			//$data['actual_total_fee']=number_format($re[0]['actual_total_fee'],2,'.','');//应付金额
    			//$data['deposit']=$re[0]['deposit'];//已付定金
    			//$data['payment']=number_format($re[0]['payment'],2,'.','');//实付金额
    			 
    			$return['info']=$data;
    		}
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
    
    //清算订单明细
    public function balanceMX() {
    	$return['result']=0;
    	 
    	$orderID=I('post.orderID');
    	
    	if (empty($orderID)) {
    		$return['status']=1;
    		$return['message']='传参不完整';
    	}else {

    		$map['id']=$orderID;  
    		$res=M('e_zc_orders')->field('*')->where($map)->select();
			
			$where['order_id']=$orderID;
    		$field='balance_number';//结算表的结算编号
    		$re=M('e_zc_balance')->field($field)->where($where)->select();
    		if (empty($res)||empty($re)) {
    			$return['status']=1;
    			$return['message']='结算订单查询无数据';
    		}else{
    			$return['status']=0;
    			$return['message']='结算订单查询成功';
    			
    			//$data['order_number']=$res[0]['order_number'];//订单编号
				$data['order_number']=$re[0]['balance_number'];//结算编号
    			$data['station']=$res[0]['station'];//租车地点
    			$data['brand']=$res[0]['brand'].$res[0]['model'];//车辆型号
    			$data['plate']=$res[0]['plate'];//车牌号码
    			$data['order_time']=date('Y-m-d H:i:s',$res[0]['add_time']);//下单时间
    			$data['fixed_pickup_time']=date('Y-m-d H:i',$res[0]['start_time']);//额定取车时间
    			$data['fixed_return_time']=date('Y-m-d H:i',$res[0]['end_time']);//额定还车时间
				$data['borrow_time']=date('Y-m-d H:i',$res[0]['borrow_time']);//实际取车时间
    			$data['actual_return_time']=date('Y-m-d H:i',$res[0]['return_time']);//实际还车时间
    			$data['fixed_use_time']=second_to_date(0,$res[0]['fixed_use_time']);//预约用车时长(秒)
    			$data['actual_use_time']=second_to_date(0,$res[0]['actual_use_time']);//实际用车时长
    			$data['excess_use_time']=second_to_date(0,$res[0]['excess_use_time']);//超额用车时长
    			$data['price']=$res[0]['price'];//租车单价
    			//$data['fixed_total_fee']=$res[0]['fixed_total_fee'];//额定总价
    			$data['excess_fee']=empty($res[0]['excess_fee'])?'0':$res[0]['excess_fee'];//超时费用
    			$data['deposit']=$res[0]['deposit'];//预付定金
    			$data['actual_total_fee']=$res[0]['actual_total_fee'];//应付金额
				$data['payment']=number_format($res[0]['payment'],2,'.','');//实付金额
    			$return['info']=$data;
    		}
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }

    public function parkingInRange() {
    	$return['result']=0;
    
    	$stationID=$_POST['stationID'];
    	$lat=$_POST['lat'];
    	$lng=$_POST['lng'];

 		if (empty($stationID)||empty($lat)||empty($lng)) {
 			$return['status']=1;
    			$return['message']='传参不完整';
 		}else{
	    		$ob=M('e_zc_station');
	    		$field=('lat_max,lat_min,lng_max,lng_min');
	    		$map['id']=$stationID;
	    		$re=$ob->field($field)->where($map)->find();
	    
	    
	    		if($lat<$re['lat_max'] && $lat>$re['lat_min'] && $lng<$re['lng_max'] && $lng>$re['lng_min']) {
	    			$return['status']=0;
	    			$return['message']='在还车范围内';
	    		}else{
	    			$return['status']=1;
	    			$return['message']='不在还车范围内';
	    		}
 		}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }

}
