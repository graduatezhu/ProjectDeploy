<?php
/*
 * 超时/长租缴费短信提醒接口
 */
namespace APIes\Controller;
use Think\Controller;
class MessageAlarmController extends Controller {
    public function index(){
        $this->display();
    }
	
	//超时短信提醒
    public function overTimeAlarm(){
		
		$phone = I('get.phone','','trim');//用户手机号
		$plate = I('get.plate','','trim');//车牌号
		$end_time = date('Y-m-d H:i:s',I('get.end_time','','trim'));//到期时间

		$ob=M('e_zc_sysparameter');
		$map['var_name']='excess';
		$field='var_value';
		$re=$ob->field($field)->where($map)->select();
		$price=$re[0]['var_value'].'元/小时';
		
		//echo $phone,'<br>',$plate,'<br>',$end_time;die;
		$msg='【电狗科技】 尊敬的用户，您租用的车牌号为['.$plate.']电动汽车于['.$end_time.']到期，为避免不必要的损失，请于['.$end_time.']前还车或续租，超时部分将按['.$price.']收取费用。祝您用车愉快';
		
		$this->send_duanxin($phone,$msg);
		$this->success("短信发送成功");
    }
    
//长租短信提醒 分期付款后启用，每日调用一次
    public function longRentAlarm(){
    
    	$time=time();
    	
    	$ob=M('e_zc_info');
    	$map['i.long_rent']=array('eq','1');
    	$map['i.start_time']=array('lt',"$time");
    	$map['i.end_time']=array('gt',"$time");
    	
    	$re = $ob->join('as i left join e_zc_orders as o on o.id = i.order_id left join e_members as m on m.id = i.user_id left join e_zc_cars as c on c.id=i.car_id')
    	->field('i.start_time,i.end_time,o.payment,o.credit,c.plate,m.phone')
    	->where($map)
    	->select();
    	//echo $ob->getlastsql();die;
    	
    	$phone=$re[0]['phone'];
    	$plate=$re[0]['plate'];
    	$total=$re[0]['payment'];
    	$credit=$re[0]['credit'];
    	$haspay=$total-$credit;
    	$msg='【电狗科技】 尊敬的用户，您租用的车牌号为['.$plate.']电动汽车租金总计['.$payment.']元，已付['.$haspay.']元，仍需缴纳['.$credit.']元 祝您用车愉快';
    	
    	$date=substr(date('Y-m-d',$time),-2,2);//取日期
    	if ($date=='01') {
    		$this->send_duanxin($phone,$msg);//每月1号发一次短信
    	}
    }
    function send_duanxin($phone,$msg){
    	$userName = 'yiyuandongli';
    	$userPwd  = '123456';
    
    	/*
    	 cpName 用户名
    	 cpPwd 密码
    	 phones 手机号
    	 msg 内容
    	 spCode 流水号
    	 extNum 通道号（默认为0，预留扩展用）
    	 */
    	$msg = urlencode( iconv('UTF-8','GBK',$msg) );//用gbk编码进行UrlEncode
    	//$baseUrl = 'http://221.122.112.136:8080/sms/mt.jsp?cpName='.$userName.'&cpPwd='.$userPwd.'&phones='.$phone.'&spCode='.'1111111111'.'&msg='.$msg.'&extNum=0';
    	$baseUrl = 'http://api.itrigo.net/mt.jsp?cpName='.$userName.'&cpPwd='.$userPwd.'&phones='.$phone.'&spCode='.'1111111111'.'&msg='.$msg.'&extNum=0';
    
    	$re = file_get_contents($baseUrl);
    	$re = iconv('GBK','UTF-8',$re);//转码
    
    	if( $re === '0' ) {
    		return true;
    	} else {
    		return false;
    		$re_arr = explode('&',$re);
    		echo $re_arr[1];
    	}
    }
}