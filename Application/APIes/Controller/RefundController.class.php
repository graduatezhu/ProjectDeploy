<?php
namespace APIes\Controller;
use Think\Controller;
class RefundController extends Controller {
	public function index(){
		$this->display();
	}
	
	//提交保证金退款申请
    public function submit(){
		$return['result'] = 0;
	
		$userID=I('post.userID');
		$transactionNO=I('post.transactionNO');

		if(empty($userID)||empty($transactionNO)){
			$return['status'] = 1;
			$return['message'] = '传参不完整';
		}else{
			$ob_m=M('e_members');
			$map['id']=$userID;
			$field='deposit';
			$re=$ob_m->field($field)->where($map)->find();
			$flag=$re['deposit'];

			//如已缴纳保证金
			if($flag==1){
				$return['status'] = 101;
				$return['message'] = '保证金退款已受理，待审核';
				
				//更新保证金交易表的标志
				$ob_d= M('e_zc_deposit');
				$where['deposit_number']=$transactionNO;
				$data['refund']=1;//用户申请退款
				$data['refund_check']=0;//系统待审核
				$res=$ob_d->where($where)->save($data);

				//更新用户表状态
				$info['deposit']=2;//退款中
				$re_m_upd=$ob_m->where($map)->save($info);

			}else{
				$return['status'] = 102;
				$return['message'] = '用户未缴纳保证金';
			}
		}
		echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
    
    //保证金退款进度查询
    public function query(){
    	$return['result']=0;
    	
    	//$userID=$_POST['userID'];
    	$transactionNO=I('post.transactionNO');
    	
    	if(empty($transactionNO)){
			$return['status'] = 1;
			$return['message'] = '传参不完整';
    	}else{
    		$ob=M('e_zc_deposit');
    		$map['deposit_number']=$transactionNO;
    		$field='refund_check,refund_memo';
    		$re=$ob->field($field)->where($map)->find();
    		$flag=$re['refund_check'];

    		switch($flag){
    			case 0:
    				$return['status'] = 100;
    				$return['message'] = '保证金退款申请已受理，待审核';
    				break;
    			case 1:
    				$return['status'] = 101;
    				$return['message'] = '保证金退款申请未通过';
    				break;
    			case 2:
    				$return['status'] = 102;
    				$return['message'] = '保证金退款申请已通过，退款中';
    				break;
    			case 3:
    				$return['status'] = 103;
    				$return['message'] = '保证金退款完成';
    				break;    				
    			default:
    				$return['status'] = 104;
    				$return['message'] = '保证金状态错误';
    				break;
    		}
    		
    		$return['refund_memo']=empty($re['refund_memo'])?'无':$re['refund_memo'];
    		
    	}
    	echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
    
}
    
    		
