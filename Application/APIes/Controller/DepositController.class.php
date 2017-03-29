<?php

class DepositController extends CommonController {
	public function index(){
		$this->display();
	}
	
	//生成保证金缴纳记录
    public function genDeposit(){
		$return['result'] = 0;
	
		$userID=I('post.userID','','trim');

		if(empty($userID)){
			$return['status'] = 1;
			$return['message'] = '用户ID不允许为空';
		}else{
			
			$data['user_id']=$userID;//用户ID
			$transactionNO='DPST'.get_micro_time(3).mt_rand(1000,9999);//生成交易编号
			$data['deposit_number']=$transactionNO;
			
			$ob= M('e_zc_sysparameter');
			$map['var_name']='deposit';
			$field='var_value';
			$re=$ob->field($field)->where($map)->select();
			$payment=$re[0]['var_value'];//保证金数值
			$data['payment']=$payment;
			
			//先查询此人是否有为支付的保证金订单

			$obj=M('e_zc_deposit');

			//如果有未支付的保证金订单  返回未支付的订单信息 不再生成新的订单
			$only_deposit=$obj->where("user_id={$userID} and pay_type=0")->find();
			if($only_deposit){
				$return['status']=0;
				$return['message']='有未支付的保证金订单，请去支付';
				$return['userID']=$userID;//返回用户ID
				$return['info']['transactionNO']=$only_deposit['deposit_number'];//交易编号
				$return['info']['payment']=$only_deposit['payment'];//保证金交易金额
				echo json_encode($return,JSON_UNESCAPED_UNICODE);
				die;
			}


			//如果是没有未支付的保证金订单 就生成并返回
			$depositID=$obj->data($data)->add();
//echo $obj->getlastsql();die;
	
			if(empty($depositID)){
				$return['status']=1;
				$return['message']='保证金记录生成失败';
			}else{
				$return['status']=0;
				$return['message']='已生成保证金记录，请去支付';
				$return['userID']=$userID;//返回用户ID
				$return['info']['transactionNO']=$transactionNO;//交易编号
				$return['info']['payment']=$payment;//保证金交易金额
			}
			
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }

    //查询是否可退保证金    
    public function depositStatus(){
    	$return['result']=0;
    	
    	$user_id=$_POST['userID'];
    	
    	if(empty($user_id)){
			$return['status'] = 1;
			$return['message'] = '用户ID不允许为空';
    	}else{
    		$ob=M('e_members');
    		$map['m.id']=$user_id;
    		$field='m.deposit,i.status,d.deposit_number,d.pay_time,d.payment';//待增删
    		$re=$ob->join('as m left join e_zc_info as i on i.user_id=m.id left join e_zc_deposit as d on d.user_id=m.id')
    				->field($field)
    				->where($map)
    				->order('d.id desc')
    				->limit(1)
    				->select();
//echo $ob->getlastsql();die;    		
    		if(empty($re)){
    			$return['status'] = 100;
    			$return['message'] = '无此用户数据信息';
    		}else{
    			$status=$re[0]['deposit'];
    			
    			switch ($status) {
    				case 0:
    					$return['status'] = 101;
    					$return['message'] = '未缴纳保证金';
					$return['payment']='0.00';
	    				break;
    				case 1:
    					$return['status'] = 102;
    					$return['message'] = '已缴纳保证金';
    					$return['payment']=number_format($re[0]['payment'],2);//保证金数额

    					//用车状态 2为使用中
    					if ($re[0]['status']==2) {
    						$info['flag']=1021;
    						$info['msg']='车辆使用中，无法退款';
    					}else{
    						$curdate=time();
    						$duration=($curdate-$re[0]['pay_time'])/86400;//缴纳保证金时间跨度,单位天
    						
    						$condition['var_name']='refund_time';
    						$res=M('e_zc_sysparameter')->field('var_value')->where($condition)->select();
    						
    						if($duration<$res[0]['var_value']){
	     						$info['flag']=1022;
	    						$info['msg']='未到退款日期，无法退款';
    						}else{
    							$info['flag']=1023;
    							$info['msg']='可以退款';
    							
    							$info['transactionNO']=$re[0]['deposit_number'];//交易编号
    							//$info['payment']=$re[0]['payment'];//保证金数额
    							$info['time']=$re[0]['pay_time'];//缴纳保证金时间
    						}

    					}
					$return['info']=$info;
    					break;
    				case 2:
    					$return['status'] = 103;
    					$return['message'] = '保证金退款中';
 					$return['payment']=number_format($re[0]['payment'],2);//保证金数额
    					break;
    			}

    		}
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
}
