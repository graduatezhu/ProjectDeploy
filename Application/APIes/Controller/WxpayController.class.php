<?php
namespace APIes\Controller;
use Think\Controller;
require_once("./WXpay/lib/WxPay.Api.php");

class WxpayController extends Controller {
    public function index(){

    	//微信支付类在跟命名空间定义 所以要加上\
		$aa=new \WxPayUnifiedOrder();

		//商户订单号
		$aa->SetOut_trade_no($_POST['out_trade_no']);
		//$aa->SetOut_trade_no(aaa);

		//商品描述
		if($_POST['payment_type']=='deposit'){
			$aa->SetBody('电狗科技-租车定金');
		}
		//$aa->SetBody('电狗科技-租车定金');
		if($_POST['payment_type']=='balance'){
			$aa->SetBody('电狗科技-租车结算');
		}

		//总价格
		$aa->SetTotal_fee($_POST['total_fee']);
		//$aa->SetTotal_fee(1);

		//交易类型
		$aa->SetTrade_type('APP');

		//接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
		$aa->SetNotify_url('http://121.42.53.24/zuche/WXpay/callback.php');
		
		
		//微信支付类在跟命名空间定义 所以要加上\
		$res=\WxPayApi::unifiedOrder($aa);
		
		//二次签名
		$data["appid"] = 'wx0eaf6d4bb487d742';
		$data["noncestr"] =$res['nonce_str'];//二次签名时不要重新生成
		$data["package"]="Sign=WXPay";
		$data["partnerid"] = '1373241302';
		$data["prepayid"] = $res['prepay_id'];
		$data["timestamp"] =time();
	
		ksort($data);
		$str='';
		foreach($data as $k=>$v){
			$str.=$k.'='.$v.'&';
		}
		$str1=substr($str,0,-1);
		
		$str1.='&key=edogA9R9A9H9eenergya9r9a9h9YYDL7';
		
		//echo $str;
		$str1=md5($str1);
		$res['sign']=strtoupper($str1);
	
		//加时间戳
		
		$res['package']="Sign=WXPay";
		$res['partnerId']='1373241302';
		$res['timeStamp']=$data["timestamp"];
		
		
		if($res['result_code']=='SUCCESS'){
			echo json_encode($res,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}else{
			echo json_encode($res,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

	}
	
	public function save(){
		if(empty($_GET['out_trade_no']) || empty($_GET['result_code']) ){
			exit;
		}
		
		$out_trade_no=$_GET['out_trade_no'];
		$result_code=$_GET['result_code'];
		$res=M('e_zc_deposit');
		$data=$res->where("deposit_number='{$out_trade_no}'")->find();
		$time=time();
		//echo $res->_sql();
		//dump($data);
		if($result_code=='SUCCESS'){
			//先判断是押金付款还是结算付款
			if($data['pay_type']==0){

				//保证金表里修改支付信息
				$deposit_data['pay_type']=1;
				$deposit_data['pay_time']=$time;
				$deposit=M('e_zc_deposit');
				$deposit->where("deposit_number='{$out_trade_no}'")->save($deposit_data);

				//用户表里修改信息
				$deposit_user=$deposit->where("deposit_number='{$out_trade_no}'")->find();

				$member_data['deposit']=1;
				M('e_members')->where("id={$deposit_user['user_id']}")->save($member_data);

			}
			
					
			
		}else{
			$modify['pay_type']=6;
			$modify['prepay_time']=time();//支付保证金时间
			$re_upd=$res->where("order_number='{$out_trade_no}'")->save($modify);
		}	

		
	}
	
	
	public function savejs(){
		if(empty($_GET['out_trade_no']) || empty($_GET['result_code']) ){
			exit;
		}
		
		$out_trade_no=$_GET['out_trade_no'];
		$result_code=$_GET['result_code'];
		$res=M('e_zc_orders');
		$data=$res->where("order_number='{$out_trade_no}'")->find();
		$time=time();
		//echo $res->_sql();
		//dump($data);
		if($result_code=='SUCCESS'){
			//先判断是押金付款还是结算付款
			if($data['pay_type']==2){
				$modify['pay_type']=3;//支付状态
				//修改订单表字段
				$re_upd=$res->where("order_number='{$out_trade_no}'")->save($modify);
			
			}

			//失败后再付款
			if($data['pay_type']==6){
				$modify['pay_type']=3;//支付状态
				//修改订单表字段
				$re_upd=$res->where("order_number='{$out_trade_no}'")->save($modify);
			
			}


		}else{
			$modify['pay_type']=6;
			$modify['prepay_time']=time();//支付定金时间
			$re_upd=$res->where("order_number='{$out_trade_no}'")->save($modify);
		}		
		
	}




}
