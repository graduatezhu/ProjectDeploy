<?php
/*
 * 支付宝结算支付接口
 */
namespace APIes\Controller;
use Think\Controller;
class AliBalanceController extends Controller {
    public function index(){
        $this->display();
    }

    public function pay(){
		
		$out_trade_no = I('get.out_trade_no');//商户订单号
		$trade_no = I('get.trade_no');//支付宝交易号
		$trade_status = I('get.trade_status');//交易状态

		$return['caption']="商户订单号: $out_trade_no 结算交易状态：$trade_status";
		
		$curTime=time();//当前时间
		
		if(empty($out_trade_no) ){
			$return['status']=1;
			$return['message'] = '订单号为空';
		}else{
			switch($trade_status){
				case 'TRADE_SUCCESS':
					$payType = '3';
					break;
				case 'TRADE_FINISHED':
					$payType = '3';
					break;
				case 'WAIT_BUYER_PAY':
					return;					
				default:
					$payType = '6';//支付失败
			}//switch end

			$ob=M('e_zc_orders');
			$map['order_number']=$out_trade_no;//根据订单号查询
			$field='pay_type';
			$res=$ob->field($field)->where($map)->select();
			$flag=$res[0]['pay_type'];
			
			//已支付的无需再支付
			if($flag==2){
				
				//记录支付状态
				$data['pay_type']=$payType;
				$data['pay_time']=$curTime;
				$re_upd=$ob->where($map)->save($data);
				
				//$log=$ob->getlastsql();//记录日志
				//$logger('更新保证金支付状态：'.$log);
					
				if($re_upd===false){
					$return['status']=1;
					$return['message']='结算状态更新失败';
				}else {
					$return['status']=0;
					$return['message']='结算状态更新成功';
				}
			}else{
				$return['status']=1;
				$return['message']='该订单已支付或未缴纳保证金';
			}
		}
		$info=json_encode($return,JSON_UNESCAPED_UNICODE);
   		//logger('结算支付信息:'.$info);
		echo $info;
    }
}
