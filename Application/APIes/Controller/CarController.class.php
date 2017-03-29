<?php
namespace APIes\Controller;
use Think\Controller;
class CarController extends Controller {
    public function index(){
        $this->display();
    }

    public function car(){

    	@$id=isset($_POST["carID"])?trim($_POST["carID"]):false;	
    	$data=[];

    	if($id){
    		$car=M("");
    		$res=$car->table("e_zc_cars,e_zc_cars_brand,e_zc_cars_model")->field("e_zc_cars.picture,e_zc_cars.capacity,e_zc_cars.plate,e_zc_cars.price,e_zc_cars_model.name model,e_zc_cars_brand.name brand")->where("e_zc_cars.brand_id=e_zc_cars_brand.id and e_zc_cars.model_id=e_zc_cars_model.id and e_zc_cars.id={$id}")->find();
    		if($res){

                $info=$car->table("e_zc_info")->field("start_time,end_time")->where("car_id={$id}")->select();

                if($info){
                    foreach($info as $k=>$v){
                        $info[$k]["start_time"]=date("Y-m-d H:i",$info[$k]["start_time"]);
                        $info[$k]["end_time"]=date("Y-m-d H:i",$info[$k]["end_time"]);
                    }

                }else{
                    $info=null;
                    $res['infocount']=0;
                }

    			$res["result"]=0;
                $res["status"]=0;
				$res['car_id']=$id;
                $res["dqdl"]="";
		       	$res["xhlc"]="";
                $res['infocount']=count($info);

		       	$res["message"]="查询成功";
                $res['info']=$info;
		       	$res=json_encode($res,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		       	echo $res;
		       	die;
    		}else{
    			//失败返回值
	    		$data["result"]=1;
		       	$data["status"]=1;
		       	$data["message"]="查询有误或没有此车辆ID";
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

}