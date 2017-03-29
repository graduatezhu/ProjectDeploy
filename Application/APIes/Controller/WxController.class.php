<?php
/**
*微信公众号充电接口
*/
namespace APIes\Controller;
use Think\Controller;
class WxController extends Controller {
    public function index(){
    	$this->display();
    }
    //获取城市的充电站列表
    public function getStationsInCity(){
    	$AreaNo=I('get.AreaNo','','trim');//区号
    	$PostNo=I('get.PostNo','','trim');//邮编
    	$Name=I('get.Name','','trim');//电站名或拼音
    	
    	$ob=M('e_electricities');
    	$field='No,name,address,lng,lat,operation_state';
    	
    	if(empty($AreaNo) && empty($PostNo)){
    		$return['code']=10103;
    		$return['message']='区号或邮政编码必须至少有一个不能为空';
    		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    	}else{
    		if (!empty($AreaNo)) {
    			$map['area_code']=$AreaNo;
    		}else{
    			$map['post_code']=$PostNo;
    		}

    		$list=$ob->field($field)->where($map)->select();
    		$count=$ob->where($map)->count();
    		
    		if(empty($list)){
    			if (!empty($AreaNo)) {
    				$return['code']=10101;
    				$return['message']='无效的区号';
    			}else{
    				$return['code']=10102;
    				$return['message']='无效的邮政编码';
    			}
    			echo json_encode($return,JSON_UNESCAPED_UNICODE);
    		}else{
    			if($count>500) {
    				$return['code']=10104;
    				$return['message']='结果数量超过500';
    				echo json_encode($return,JSON_UNESCAPED_UNICODE);
    			}else{
    				foreach ($list as $k=>$v ){
    					$re[$k]['No']=$v['No'];
    					$re[$k]['Name']=$v['name'];
    					$re[$k]['Address']=$v['address'];
    					$re[$k]['Lng']=$v['lng'];
    					$re[$k]['Lat']=$v['lat'];
    					$re[$k]['OperationState']=$v['operation_state'];
    				}
    				$return['Stations']=$re;
    				echo json_encode($return,JSON_UNESCAPED_UNICODE);
    			}
    		}
    	}
    }
    //获取周边的充电站列表
	public function getNearByStations(){
    	$Lng=I('get.Lng','','trim');
    	$Lat=I('get.Lat','','trim');
    	
    	//$map['id']=array('lt',20);
    	$ob=M('e_electricities');
    	$field='No,name,address,lng,lat,operation_state';

    	$list=$ob->field($field)->select();
    	if(empty($list)){
    		$return['code']=10201;
    		$return['message']='无效的GPS位置';
    		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    	}else{
    		foreach($list as $k=>$v){
    			$distance=getDistance($v['lat'],$v['lng'],$Lat,$Lng);
    			$re[$k]['distance']=$distance/1000; //单位千米
    			$re[$k]['No']=$v['No'];
    			$re[$k]['Name']=$v['name'];
    			$re[$k]['Address']=$v['address'];
    			$re[$k]['Lng']=$v['lng'];
    			$re[$k]['Lat']=$v['lat'];
    			$re[$k]['OperationState']=$v['operation_state'];
    		}
			
			sort($re);//对所有充电站的距离排序
			
 			$return['Distance']=$re[19]['distance'];//获取20个充电站中的最大距离(第19个元素最大)

 			//截取前20条记录
 			for ($i = 0; $i < 20; $i++) {
 				$records[$i]=$re[$i];
 			}
 
 			$return['Stations']=$records;
    		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    	}
    }
    //获取充电站详细信息
    public function getStationDetails() {

    	$No=$_GET['No'];//电站编号
    	
		//电站详情
    	$ob=M('e_electricities');
    	$map['No']=$No;
    	$field=('id,No,name,address,lng,lat,image,area_code,post_code,operation_state,service_category,pchong,kchong,idle,price,fee,tels,station_star');
    	$station_info=$ob->where($map)->field($field)->select();
    	

    	if(empty($station_info)){
    		$return['code']=10301;
    		$return['message']='无效的电站编号';
    		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    	}else{
    		$where['e_id']=$station_info[0]['id'];
    		
    		//电桩详情
	    	$ob2=M('e_electric_pile');
	    	$field='charging_no,charging_grade,charging_mode,charging_state,price';
	    	$pile_info=$ob2->where($where)->field($field)->select();
			
	    	//unset($station_info[0]['id']);
	    	$list['No']=$station_info[0]['No'];
	    	$list['Name']=$station_info[0]['name'];
	    	$list['Address']=$station_info[0]['address'];
	    	$list['Lng']=$station_info[0]['lng'];
	    	$list['Lat']=$station_info[0]['lat'];
	    	$list['AreaCode']=$station_info[0]['area_code'];
	    	$list['PostCode']=$station_info[0]['post_code'];
	    	$list['OperationSate']=$station_info[0]['operation_state'];
	    	$list['ServiceCategory']=$station_info[0]['service_category'];
	    	$list['TotalNumOfCharging']=$station_info[0]['pchong']+$station_info[0]['kchong'];
	    	$list['NumOfFreeCharging']=$station_info[0]['pchong']+$station_info[0]['kchong']; //空闲数量由站控推送，暂时未取到 $station_info[0]['idle'];
	    	$list['ElectricityPrice']=$station_info[0]['price'];
	    	$list['ParkingFee']=$station_info[0]['fee'];
	    	$list['ServicePhone']=$station_info[0]['tels'];
	    	
	    	$list['ChargingDetails']=$pile_info;
	    	
	    	//电站星级
	    	$list['StationStar']=$station_info[0]['station_star'];
	    	
	    	//电站评价
	    	$ob3=M('e_comment');
	    	$field='No,userid,add_time,content,ServiceStar,FacilitiesStar,TrafficStar,ImageUrl';
	    	$comment_info=$ob3->where($where)->field($field)->select();

	    	unset($map);
	    	$ob=M('e_members');
	    	$field='name,user_id'; 

	    	foreach($comment_info as $k=>$v){
 	    		$map['id']=$v['userid'];
 	    		$re=$ob->where($map)->field($field)->select();
 	    		//电站评价
	    		$StationEvaluations[$k]['EvaluationNo']=$v['No'];
	    		$StationEvaluations[$k]['UserName']=$re[0]['name'];
	    		$StationEvaluations[$k]['UserId']=$re[0]['user_id'];
	    		$StationEvaluations[$k]['Time']=$v['add_time'];
	    		$StationEvaluations[$k]['Content']=$v['content'];
	    		//星级评价
	    		$EvaluationStars[$k]['ServiceStar']=$v['ServiceStar'];
	    		$EvaluationStars[$k]['FacilitiesStar']=$v['FacilitiesStar'];
	    		$EvaluationStars[$k]['TrafficStar']=$v['TrafficStar'];
	    		$StationEvaluations[$k]['EvaluationStars']=$EvaluationStars[$k];
	    		
	    		//评价图片
	    		$EvaluationImages[$k]['ImageUrl']=explode(',',$v['ImageUrl']);
	    		$StationEvaluations[$k]['EvaluationImages']=$EvaluationImages[$k];

	    	}

	    	$list['StationEvaluations']=$StationEvaluations;
	    	
	    	$Images['ImageUrl']=explode(',',$station_info[0]['image']);
	    	$list['Images']=$Images;

	    	echo urldecode(json_encode(url_encode($list)));
    	}
    	
    }
   //获取充电站评论信息
   public function getStationEvaluations() {
    	$No=I('get.No','','trim');//电站编号
    	$EvaluationNo=I('get.EvaluationNo','','trim');//评论编号
    	$NumberOfEvaluation=I('get.NumberOfEvaluation','','trim');//评论条数
    	
    	$ob=M('e_electricities');
    	$map['No']=$No;
    	$re=$ob->field('id')->where($map)->select();
    	if(empty($re)){
    		$return['code']=10401;
    		$return['message']='无效的电站编号';
    		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    	}else{
    		unset($map);
			$map['e.No']=$No;
    		$map['c.No']=array('lt',"$EvaluationNo");//指定评论编号之前的记录
    		$list=$ob->join('as e left join e_comment as c on e.id=c.e_id left join e_members as m on m.id=c.userid')
    			 ->field('e.id,c.No,c.userid,c.add_time,c.content,c.ServiceStar,c.FacilitiesStar,c.TrafficStar,c.ImageUrl,m.user_id,m.name')
    			 ->where($map)
    			 ->select();
 		
    		if(empty($list)){
    		 	$return['code']=10402;
    		 	$return['message']='无效的评论编号';
    		 	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    		 }else{
    		 	foreach ($list as $k=>$v){
    		 		$base_comment[$k]['EvaluationNo']=$v['No'];//评价序号
    		 		$base_comment[$k]['UserName']=$v['name'];//评价人姓名
    		 		$base_comment[$k]['UserNo']=$v['user_id'];//评价人编号
    		 		$base_comment[$k]['Time']=$v['add_time'];//评价时间
    		 		$base_comment[$k]['Content']=$v['content'];//评价内容  		 		
    		 		
    		 		$EvaluationStars[$k]['ServiceStar']=$v['ServiceStar'];
    		 		$EvaluationStars[$k]['FacilitiesStar']=$v['FacilitiesStar'];
    		 		$EvaluationStars[$k]['TrafficStar']=$v['TrafficStar'];
    		 		
    		 		$EvaluationImages[$k]['ImageUrl']=explode(',',$v['ImageUrl']);
    		 		
    		 		$return[$k]=$base_comment[$k];
    		 		$return[$k]['EvaluationStars']=$EvaluationStars[$k];
    		 		$return[$k]['EvaluationImages']=$EvaluationImages[$k];
    		 	}
    		 	
    		 	echo urldecode(json_encode(url_encode($return)));
    		 }
    	}
    }
	//发送短信验证码
	public function sendSmsVerficationCode(){
        session_start();
        @$phone=$_POST['PhoneNo'];

        if(!$phone){
            $return['SendResult']='FAILURE';
            $return['code']='400';
            $return['message']='系统错误';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $code=mt_rand(100000,999999);
        $_SESSION["$phone"]=[];
        $_SESSION["$phone"]['code']=$code;
        $_SESSION["$phone"]['time']=time();
        $msg="您好,您的验证码[$code],请在3分钟内使用，过期无效【电狗科技】";
        //dump($_SESSION);
        if(send_duanxin($phone,$msg)){
            $return['SendResult']='SUCCESS';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }else{
            $return['SendResult']='FAILURE';
            $return['code']='400';
            $return['message']='系统错误';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

    }
	//验证短信验证码
	public function verficateSmsCode(){
        session_start();
        @$phone=$_POST['PhoneNo'];
        @$code=$_POST['VerficationCode'];
        @$openid=$_POST['OPENID'];

        if(!$phone || !$code){
            $return['SendResult']='FAILURE';
            $return['code']='400';
            $return['message']='系统错误';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $checktime=time()-$_SESSION["$phone"]['time'];
        //dump($_SESSION);
        if($code==$_SESSION["$phone"]['code'] && $checktime <= 180 ){
            $member=M('e_members');
            $member_info=$member->field('wechat')->where("phone={$phone}")->find();
            if(!$member_info){
                $data['phone']=$phone;
                $data['wechat']=1;
                $data['tels_id']=1;
                $data['cars_model_id']=1;
                $data['cars_categories_year']=1;
                $data['cars_categories_mile']=1;
                $data['openid']=$openid;
                if($member->add($data)){
                    $return['SendResult']='SUCCESS';
                    echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;                   
                }else{
                    $return['SendResult']='FAILURE';
                    $return['code']='400';
            	$return['message']='系统错误';
                    echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }

            }else{
                //如果用户表里有此用户 那么只修改字段值
                $data['wechat']=1;
                $data['openid']=$openid;
                $member->save($data);
            }

            if($member_info['wechat']=='1'){
                $return['SendResult']='FAILURE';
                $return['code']='10601';
            	$return['message']='该手机号、验证码已经被验证过一次';
                echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }else{
                $data['wechat']=1;
                $data['openid']=$openid;
                $save_info=$member->where("phone={$phone}")->save($data);
                $return['SendResult']='SUCCESS';
                echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            
        }else{
            $return['SendResult']='FAILURE';
            $return['code']='400';
            $return['message']='系统错误';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

    }

	//获取用户信息
    public function getUserInfo(){
        
        @$user_id=isset($_POST['UserId'])?$_POST['UserId']:false;
        @$phone=isset($_POST['PhoneNo'])?$_POST['PhoneNo']:false;
        @$openid=isset($_POST['OpenId'])?$_POST['OpenId']:false;
 
        if($user_id || $phone || $openid){
            $where=[];
            if($user_id){
                $where['id']=$user_id;
            }

            if($phone){
                $where['phone']=$phone;
            }

            if($openid){
                $where['openid']=$openid;
            }

        }else{
            $return['SendResult']='FAILURE';
            $return['code']='10701';
            $return['message']='OPENID、手机号、用户编号不能全部为空';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }


        $user=M('e_members');
        $userinfo=$user->field('phone,image,openid,name,user_sex,birthday,dirver_license_time')->where($where)->find();

        if($userinfo){
            $return['PhoneNo']=$userinfo['phone'];
            $return['OpenId']=$userinfo['openid'];
            $return['Head']=$userinfo['image'];
            $return['UserName']=$userinfo['name'];
            $return['Sex']=$userinfo['user_sex'];
            $return['Birthday']=$userinfo['birthday'];
            $return['DriverLicenseTime']=$userinfo['dirver_license_time'];
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }else{
            $return['SendResult']='FAILURE';
            $return['code']='400';
            $return['message']='系统错误';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }




    }


    //更新用户信息
    public function updateUserInfo(){
        @$user_id=isset($_POST['UserId'])?$_POST['UserId']:false;
        @$phone=isset($_POST['PhoneNo'])?$_POST['PhoneNo']:false;
        @$openid=isset($_POST['OpenId'])?$_POST['OpenId']:false;
        @$name=isset($_POST['UserName'])?$_POST['UserName']:false;
        @$head=isset($_POST['Head'])?$_POST['Head']:false;
        @$sex=isset($_POST['Sex'])?$_POST['Sex']:false;
        @$birthday=isset($_POST['Birthday'])?$_POST['Birthday']:false;
        @$DriverLicenseTime=isset($_POST['DriverLicenseTime'])?$_POST['DriverLicenseTime']:false;

        if(empty($user_id)){
            $return['UpdateResult']='FAILURE';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;   
        }

        $data=[];
        $data['phone']=$phone;
        $data['openid']=$openid;
        $data['name']=$name;
        $data['head']=$head;
        $data['sex']=$sex;
        $data['birthday']=$birthday;
        $data['driver_license_time']=$DriverLicenseTime;

        foreach ($data as $k => $v) {
            if(empty($v)){
                unset($data[$k]);
            }
        }

        $user=M('e_members');
        $create_data=$user->create($data);
        if(!$create_data){
            $return['UpdateResult']='FAILURE';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }


        $res=$user->where("id={$user_id}")->save();
        if($res){
            $return['UpdateResult']='SUCCESS';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }else{
            $return['UpdateResult']='FAILURE';
            $return['code']='10801';
            $return['message']='无效的用户编号';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }



    }
	//获取订单列表
    public function getOrders(){
	
		$orderNO=I('get.OrderNo','','trim');//订单编号
		$userID=I('get.UserId','','trim');//用户编号
		
		$ob=M('e_orders');
		$map['order_number']=$orderNO;
		$list=$ob->join('as o left join e_electricities as e on o.e_id=e.id left join e_electric_pile as p on o.e_pile_id=p.id left join e_electric_gun as g on o.e_gun_id=g.id')
				 ->field('o.order_number as OrderNo,e.station_id as `No`,e.name as `Name`,p.e_number as ChargingNo,p.charging_grade as ChargingGrade,g.g_number as GunNo,p.price as ElectricityPrice,
				 		p.charging_mode as ChargingMode,o.e_volume as ChargedBattery,o.e_start_time as StartChargingTime,o.order_state as OrderState,p.discount as Discount,o.proportion,o.car_mode_id,
				 		o.e_voltage,o.e_current')
				 ->where($map)
				 ->find();

		if(empty($list)){
			$return['code'] = '11101';
			$return['message'] = '无效的订单号';
			echo urldecode(json_encode(url_encode($return)));
			return;
			
		}else{
			if(empty($list['OrderState'])){
				$return['code'] = '11103';
				$return['message'] = '订单状态获取失败';
				echo urldecode(json_encode(url_encode($return)));
				return;
			}
		}
		
		$ob=M('e_cars_models');
		$where['id']=$list['car_mode_id'];
		$re=$ob->field('rongliang,xuhang')->where($where)->find();
		
		//当前电量=用户设置电量+已充电量
		$CurrentBattery=$list['proportion']*$re['rongliang']+$list['ChargedBattery'];
		//当前预计需付金额=已充电量*电价
		$CurrentExpectedPayment=$list['ChargedBattery']*$list['ElectricityPrice'];
		//充满预付金额
		$ExpectedPaymentForFull=(100-$list['proportion'])*$list['ElectricityPrice'];
		
		//当前预计可行驶里程
		$currentBili=getbili($list['proportion'],$list['ChargedBattery'],$re['rongliang'],0);//当前比例
		$CurrentExpectedMileage=getXuhang($re['rongliang'], $re['xuhang'], $currentBili);//行驶里程
		
		//预计充满时间
		$ExpectedFinishTime=getShengyuTime($list['proportion'], $re['rongliang'], $list['e_voltage'], $list['e_current']);
		//减免金额
		$ReliefPayment=$list['ElectricityPrice']*$list['Discount'];
		
		//取消无需返回的元素
		unset($list['proportion'],$list['car_mode_id'],$list['e_voltage'],$list['e_current']);

		//追加元素
		$return=$list;
		$return['CurrentBattery']=$CurrentBattery;
		$return['CurrentExpectedPayment']=$CurrentExpectedPayment;
		$return['ExpectedPaymentForFull']=$ExpectedPaymentForFull;
		$return['CurrentExpectedMileage']=$CurrentExpectedMileage;
		$return['ExpectedFinishTime']=$ExpectedFinishTime;
		$return['ReliefPayment']=$ReliefPayment;
		
		echo urldecode(json_encode(url_encode($return)));
	
	}

	//更新订单
    public function updateOrder(){
        @$orderno=isset($_POST['OrderNo'])?$_POST['OrderNo']:false;

        $where['order_number']=$orderno;
        $data['pay_type']='1';

        $_order=M('e_orders');
        $order_res=$_order->where($where)->save($data);
        if($order_res){
            $return['UpdateResult']='SUCCESS';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }else{
            $return['UpdateResult']='FAILURE';
            $return['code']='11401';
            $return['message']='无效的订单号';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }

    }
	
	//服务评价
	public function evaluateService(){
        @$UserId=$_POST['UserId']?$_POST['UserId']:false;
        @$Content=$_POST['Content']?$_POST['Content']:false;
        @$OrderNo=$_POST['OrderNo']?$_POST['OrderNo']:false;
        @$ServiceStar=$_POST['ServiceStar']?$_POST['ServiceStar']:false;
        @$FacilitiesStar=$_POST['FacilitiesStar']?$_POST['FacilitiesStar']:false;
        @$TrafficStar=$_POST['TrafficStar']?$_POST['TrafficStar']:false;
        @$EvaluationImages=$_POST['EvaluationImages']?$_POST['EvaluationImages']:false;

        // if(!$UserId || !$Content || !$OrderNo || !$ServiceStar || !$FacilitiesStar || $TrafficStart){

        //     $return['UpdateResult']='FAILURE';
        //     $return['code']='400';
        //     $return['message']='参数不完整';
        //     echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        //     exit; 

        // }

        $comment=M('e_comment');
        $comment_info=$comment->where("No={$OrderNo}")->find();
        if($comment_info){
            $return['UpdateResult']='FAILURE';
            $return['code']='11503';
            $return['message']='已评价的订单不能再次评价';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }

      
        //保存图片
        if($EvaluationImages){
            //先检测并生成相应的目录
            if(!file_exists("./Public/comment_pic")){
                mkdir("./Public/comment_pic");
            }

            if(!file_exists("./Public/comment_pic/{$UserId}")){
                mkdir("./Public/comment_pic/{$UserId}");
            }

            //解码图片
            $EvaluationImages=base64_decode($EvaluationImages);
            //保存图片
            $EvaluationImages_res=file_put_contents("Public/comment_pic/{$UserId}/{$OrderNo}id.jpg",$EvaluationImages);

            //保存成功加入数组
            if($EvaluationImages_res){
                $data['ImageUrl']="http://www.e-chongdian.com/Public/comment_pic/{$UserId}/{$OrderNo}.jpg";
            }
        }

        $data['content']=$Content;
        $data['add_time']=time();
        $data['userid']=$UserId;
        $data['No']=$OrderNo;
        $data['ServiceStar']=$ServiceStar;
        $data['FacilitiesStar']=$FacilitiesStar;
        $data['TrafficStar']=$TrafficStart;

        if($comment->add($data)){
            $return['UpdateResult']='SUCCESS';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }else{
            $return['UpdateResult']='FAILURE';
            echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit; 
        }

    }
	
    /*查询可用充电枪编号*/
    public function queryGun() {
    	
    	$userId = I('get.UserId');
    	$QRcode = I('get.ChargingNo','','format_code');//充电桩二维码
    	$OpenId = I('get.OpenId');//微信号（OPENID）
    
    	if( empty($userId) && empty($QRcode) ){
    		$return['code'] = '11703';
    		$return['message'] = '用户编号和OpenId不能都为空';
    	}else{
    		$map['e_number'] = $QRcode;
    			
    		//查询状态是“插入”的充电枪
    		$re = M('e_electric_tmp')->field('e_gun')->where($map)->select();
    		if( empty($re) ){
    			$return['code'] = '11701';
    			$return['message'] = '无此充电桩';
    		}else{
    			$gun = $re[0]['e_gun'];
    			settype($gun,'string');//转换字符串类型
    
    			$len = strlen($gun);//奇数位，要补0
    			if( $len%2 == 1 ){
    				$gun = '0'.$gun;//补齐4位
    			}
    
    			$guns = substr($gun,2);//充电枪列表
    			// var_dump($guns);
    			//拆分为数组
    			$guns = '0102000102';
    			$len2 = strlen($guns)/2;
    			$arr = array();
    			for($i=0;$i<$len2;$i++){
    				$start = $i*2;
    				$arr[] = substr($guns,$start,2);
    			}
    			//var_dump($arr);
    
    			//查询第一个“插枪”的枪号
    			$num = array_search('01',$arr);
    			//var_dump($num);
    			if( $num === false ){
    				//没有状态为 “插枪” 的充电枪
    				$return['status'] = '11702';
    				$return['message'] = '无可用的充电桩';
    			}else{
    				$return['info'] = $num+1;//枪号
    			}
    		}
    	}
    	echo urldecode(json_encode(url_encode($return)));
    }

    //订单详情
    public function getOrderDetails(){
    
    	$orderNO=I('get.OrderNo','','trim');//订单编号
    	$userID=I('get.UserId','','trim');//用户编号
    	$OpenId=I('get.OpenId','','trim');//公众号
    	
    	if(empty($userID) && empty($OpenId)){
    		$return['code'] = '11104';
    		$return['message'] = '用户编号和OpenID不能全部为空';
    	}else{
	    	$ob=M('e_orders');
	    	$map['order_number']=$orderNO;
	    	$list=$ob->join('as o left join e_electricities as e on o.e_id=e.id left join e_electric_pile as p on o.e_pile_id=p.id left join e_electric_gun as g on o.e_gun_id=g.id')
	    	->field('o.order_number as OrderNo,e.station_id as `No`,e.name as `Name`,p.e_number as ChargingNo,p.charging_grade as ChargingGrade,g.g_number as GunNo,p.price as ElectricityPrice,
					 		p.charging_mode as ChargingMode,o.e_volume as ChargedBattery,o.e_start_time as StartChargingTime,o.order_state as OrderState,p.discount as Discount,o.proportion,o.car_mode_id,
					 		o.e_voltage,o.e_current')
	    	->where($map)
	    	->find();
    	
	    	if(empty($list)){
	    		$return['code'] = '11101';
	    		$return['message'] = '无效的订单号';
	    		echo urldecode(json_encode(url_encode($return)));
	    		return;
	    			
	    	}else{
	    		if(empty($list['OrderState'])){
	    			$return['code'] = '11103';
	    			$return['message'] = '订单状态获取失败';
	    			echo urldecode(json_encode(url_encode($return)));
	    			return;
	    		}
	    	}
    				 		
	    	$ob=M('e_cars_models');
	    	$where['id']=$list['car_mode_id'];
	    	$re=$ob->field('rongliang,xuhang')->where($where)->find();
	    		
	    	//当前电量=用户设置电量+已充电量
	    	$CurrentBattery=$list['proportion']*$re['rongliang']+$list['ChargedBattery'];
	    	//当前预计需付金额=已充电量*电价
	    	$CurrentExpectedPayment=$list['ChargedBattery']*$list['ElectricityPrice'];
	    	//充满预付金额
	    	$ExpectedPaymentForFull=(100-$list['proportion'])*$list['ElectricityPrice'];
	    		
	    	//当前预计可行驶里程
	    	$currentBili=getbili($list['proportion'],$list['ChargedBattery'],$re['rongliang'],0);//当前比例
	    	$CurrentExpectedMileage=getXuhang($re['rongliang'], $re['xuhang'], $currentBili);//行驶里程
	    		
	    	//预计充满时间
	    	$ExpectedFinishTime=getShengyuTime($list['proportion'], $re['rongliang'], $list['e_voltage'], $list['e_current']);
	    	//减免金额
	    	$ReliefPayment=$list['ElectricityPrice']*$list['Discount'];
	    		
	    	//取消无需返回的元素
	    	unset($list['proportion'],$list['car_mode_id'],$list['e_voltage'],$list['e_current']);
	    		
	    	//追加元素
	    	$return=$list;
	    	$return['CurrentBattery']=$CurrentBattery;
	    	$return['CurrentExpectedPayment']=$CurrentExpectedPayment;
	    	$return['ExpectedPaymentForFull']=$ExpectedPaymentForFull;
	    	$return['CurrentExpectedMileage']=$CurrentExpectedMileage;
	    	$return['ExpectedFinishTime']=$ExpectedFinishTime;
	    	$return['ReliefPayment']=$ReliefPayment;
    	}
    	echo urldecode(json_encode(url_encode($return)));
    }
    

    
    //开始充电
    public function startCharging($OrderNo='',$UserId='',$OpenId='',$switch='1') {
    	set_time_limit(60*2);
    
    	$Electricpile_code = I('post.ChargingNo','','format_code');//电桩编号
    	$gun = I('post.GunNo');//充电枪编号
    	 
    	$userId = I('post.UserId');//用户ID
    	$OpenId = I('post.OpenId');//公众号
    	 
    	$CurrentBattery = I('post.CurrentBattery');//当前电量（百分比）
    	$VehicleType = I('post.VehicleType');//车型ID
    	 
    	$type = $switch;//命令，1开启，2关闭
    
    	//检查用户是否有未支付订单
    	$ob_order = M('e_orders');
    	$map1['user_id'] = $userId;
    	$map1['pay_type'] = '0';
    	$re1 = $ob_order->field('id')->where($map1)->select();
    	if( $re1[0]['id'] ){
    		$return['code'] = '10906';
    		$return['message'] = '用户存在未支付的订单';
    		echo urldecode(json_encode(url_encode($return)));
    		return;
    	}
    	 
    	//查询充电枪状态
    	$re_gun = is_useful($Electricpile_code,$gun,$type);
    	if( $re_gun['status'] != '1' ){
    		$return['code'] = $re_gun['status'];
    		$return['message'] = $re_gun['message'];
    	}else{
    		$command = code_to_command($Electricpile_code,$gun,$type);
    		// var_dump($command);
    		 
    		//查询 socket IP
    		$map_socket_ip['e_number'] = $Electricpile_code;
    		$socket_ip = M('e_electric_tmp')->field('socket_ip')->where($map_socket_ip)->select();
    		 
    		$reC = sendSocketMsg($command,1,$socket_ip[0]['socket_ip']);//服务器返回值
    		$reC = str_replace('Server reply-connected to server.','',$reC);//去掉干扰符号
    		// var_dump($reC);
    		$re_server = str_replace('85 05 08 ','85 06 08 ',$command);//预测的 服务器返回值“85 06 08 00 01 01 01 7E”
    		// var_dump($re_server);
    		if( $re_server == $reC ){
    			if( $type == '2' ){
    				//关闭命令、需要修改订单状态
    				$return['StopResult'] = 'SUCCESS';
    			
    				$where['id']=$order_id;
    				$info['is_charging']=0;//状态标示：0未充电 1正在充电
    				$reUpd = D('Orders')->upData($where,$info);
    			}else{
	    			//开启充电枪
	    			$map_e_pile['e_number'] = $Electricpile_code;//电桩编号
	    			$e_pile = M('e_electric_tmp')->field('id,e_id')->where($map_e_pile)->select();
	    
	    			$map_e_gun['p_id'] = $e_pile[0]['id'];//充电枪编号
	    			$map_e_gun['g_number'] = $gun;//充电枪编号
	    			$e_gun = M('e_electric_gun')->field('id')->where($map_e_gun)->select();
	    
	    			$time = time();
	    			$payType = '0';//支付状态：0未支付、1已支付、2已退款
	    			// $order_number = 'ECD'.$time.mt_rand(100000,999999);//订单号
	    			$order_number = $Electricpile_code.date('YmdHis',$time);//订单号
	    
	    			//产生订单号
	    			$data['user_id'] = $userId;
	    			$data['order_number'] = $order_number;
	    			$data['e_id'] = $e_pile[0]['e_id'];
	    			$data['e_pile_id'] = $e_pile[0]['id'];
	    			$data['e_gun_id'] = $e_gun[0]['id'];
	    			$data['e_start_time'] = $time;//充电-起始时间
	    			$data['pay_type'] = $payType;
	    			$data['e_type'] = '2';//充电枪状态：0空闲1插入2工作
	    			$data['add_time'] = $time;
	    
	    			$data['is_charging'] = 1;//状态标示：0未充电 1正在充电
	    			$re_order = $ob_order->add($data);
	    
	    			if(empty($re_order) ){
	    				$return['code'] = '10904';
	    				$return['message'] = '订单生成失败';
	    			}else{
	    				//订单生成成功，设置充电比例
	    				$ob_cars_model=M('e_cars_models');
	    				$where['id']=$VehicleType;
	    				$field='rongliang';
	    				$re_rongliang=$ob_cars_model->field($field)->where($where)->find();
	    					
	    				//获取充电比例并存储
	    				$proportion=getbili($CurrentBattery,0,$re_rongliang['rongliang'],0);
	    				$map['id'] = $re_order;
	    				$info['proportion'] = $proportion;
	    				$ob_order->where($map)->save($info);
	    
	    				$return['OrderNo'] = $order_number;//返回订单编号
	    			}
    			}
    		}else{
    			if ($type == '2') {
    				$return['StopResult'] = 'FAILURE';
    			}else{
	    			$return['code'] = '10903';
	    			$return['message'] = '充电枪开启失败';
    			}
    		}
    	}
    	echo urldecode(json_encode(url_encode($return)));
    }
	
    //结束充电
    public function finishCharging() {
    	
    	$OrderNo=I('post.OrderNo');//订单编号
    	$UserId=I('post.UserId');//用户ID
    	$OpenId=I('post.OpenId');//公众号
    	
    	$type=2;//关闭电枪
    	
    	$return=$this->startCharging($OrderNo,$UserId,$OpenId,$type);
    	echo urldecode(json_encode(url_encode($return)));
    }
}