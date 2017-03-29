<?php
/*
 * 支付宝保证金支付接口
 */

class AliPayController extends CommonController {
    public function index(){
        $this->display();
    }

    public function pay(){
		
		$out_trade_no = I('get.out_trade_no');//商户订单号
		$trade_no = I('get.trade_no');//支付宝交易号
		$trade_status = I('get.trade_status');//交易状态

		$return['caption']="商户订单号: $out_trade_no 保证金交易状态：$trade_status";
		
		$curTime=time();//当前时间
		
		if(empty($out_trade_no) ){
			$return['status']=1;
			$return['message'] = '订单号为空';
		}else{
			switch($trade_status){
				case 'TRADE_SUCCESS':
					$payType = '1';
					break;
				case 'TRADE_FINISHED':
					$payType = '1';
					break;
				case 'WAIT_BUYER_PAY':
					return;
				default:
					$payType = '2';//支付失败
			}//switch end

			$ob=M('e_zc_deposit');
			$map['deposit_number']=$out_trade_no;//根据订单号查询
			$field='pay_type,user_id';
			$res=$ob->field($field)->where($map)->select();
			$flag=$res[0]['pay_type'];
			$user_id=$res[0]['user_id'];

			//已支付的无需再支付
			if($flag==0){
				
				//记录保证金支付状态
				$data['pay_type']=$payType;
				$data['pay_time']=$curTime;
				$re_deposit=$ob->where($map)->save($data);

				$obj=M('e_members');
				$where['id']=$user_id;
				$info['deposit']=1;
				$re_members=$obj->where($where)->save($info);
				
					
				if($re_deposit===false||$re_members===false){
					$return['status']=1;
					$return['message']='支付状态更新失败';
				}else {
					$return['status']=0;
					$return['message']='支付状态更新成功';
				}
			}else{
				$return['status']=1;
				$return['message']='该订单已支付或已退款';
			}
		}
		$info=json_encode($return,JSON_UNESCAPED_UNICODE);
   		logger('保证金支付信息:'.$info);

    }
}
