<?php
namespace APIes\Controller;
use Think\Controller;
class CarmodelController extends Controller {
    public function index(){
        $this->display();
    }


    public function carmodel(){

        @$data=[];

        $model=M("");
        $res=$model->table("e_zc_cars_model model,e_zc_cars_brand brand")->field("brand.name brand,model.name model,model.id model_id,model.img_url,model.price")->where("brand.id=model.brand_id")->select();

        if($res){
            $data["result"]=0;
            $data["status"]=0;
            $data["message"]="查询成功";
            $data['info']=$res;
            $data=json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data;
            die;
        }else{
            $data["result"]=1;
            $data["status"]=1;
            $data["message"]="内部错误";
            $data=json_encode($data,JSON_UNESCAPED_UNICODE);
            echo $data;
            die;
        }


    }



}