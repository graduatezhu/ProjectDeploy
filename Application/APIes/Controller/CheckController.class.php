<?php
namespace APIes\Controller;
use Think\Controller;
class CheckController extends Controller {
    public function index(){
        $this->display();
    }


    //查看用户实名审核的状态
    public function check(){

    	@$id=isset($_POST["userID"])?trim($_POST["userID"]):false;
    	@$data=[];

    	if($id){
    		$check=M("e_members");
    		$res=$check->field('verified')->where("id={$id}")->find();

    		//成功返回值
    		if($res){
    			$data["result"]=0;
		       	$data["status"]=0;
		       	$data["message"]="查询成功";
		       	$data["flag"]=$res["verified"];
		       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);
		       	echo $data;
		       	die;
    		}else{
    			//失败返回值
    			$data["result"]=1;
		       	$data["status"]=1;
		       	$data["message"]="查询失败,或许是不存在的id";
		       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);
		       	echo $data;
		       	die;
    		}

    	}else{
    		//失败返回值
    		$data["result"]=1;
	       	$data["status"]=1;
	       	$data["message"]="传参不完整";
	       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);
	       	echo $data;
	       	die;
    	}
    }

//用户重新上传资料审核
    public function reCheck(){

        $return['result']=0;//接口通讯成功

        $ob=M('e_members');

        $id=I('post.userID','','trim');
        $reVerify=I('post.reVerify','','trim');

        if(!empty($id)||!empty($reVerify)){
            if($reVerify==1){
                $map['id']=$id;
                $data['verified']=0;//用户需要重新上传资料
                $re=$ob->data($data)->where($map)->save();
                //echo $ob->getlastsql();
                if ($re===false) {
                    $return['status']=1;
                    $return['message']='审核状态更新失败';
                }else{
                    $return['status']=0;
                    $return['message']='审核状态更新成功';
                }

            }else{
                $return['status']=1;
                $return['message']='重新审核标志错误';
            }
        }else{
            $return['status']=1;
            $return['message']='传参不完整';
        }

        echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }

}