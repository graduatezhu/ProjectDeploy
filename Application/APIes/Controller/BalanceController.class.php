<?php
/*
 * 支付金额为0时，本地结算的接口，无需调用微信或支付宝。调用此接口更新相关业务状态
 */
namespace APIes\Controller;
use Think\Controller;
class BalanceController extends Controller {
    public function index(){
        $this->display();
    }

    public function pay(){
    	$return['result']='0';
		
		$ob=M('e_zc_orders');
		$map['order_number']=$_POST['out_trade_no'];//根据订单号查询
		$field='pay_type';
		$res=$ob->field($field)->where($map)->select();
		//echo $ob->getlastsql();die;
		if(!empty($res)){
			
				if ($res[0]['pay_type']==2) {
				//结算支付 更新订单信息
					$info['pay_type']=3;//已结算,状态为3
					$info['pay_time']=$curTime;//结算时间
					$re_order=$ob->where($map)->save($info);
					
					if($re_order===false){
						$return['status']='302';
						$return['message']='查询数据库出错';
					}else {
						$return['status']='1001';
						$return['message']='消费交易成功';
					}
				}else {
					$return['status']='104';
					$return['message']='请求交易类型错误';
				}
		}else {
			$return['status']='301';
			$return['message']='查询的交易不存在';
		}
   		 echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
}