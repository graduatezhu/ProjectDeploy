<?php
namespace APIes\Controller;
use Think\Controller;
class CityController extends Controller {
    public function index(){
        $this->display();
    }

    public function province(){
        @$data1=[];
        $province=M('e_zc_station');
        $data=$province->field('province')->select();
        @$res=[];
        foreach($data as $k=>$v){
            if(!in_array($v,$res)){
                $res[]=$v;
            }
        }

        if($res){
            //成功返回值
            $data1["result"]=0;
            $data1["status"]=0;
            $data1["message"]="查询成功";
            $data1["info"]=$res;
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }else{
            //失败返回值
            $data1["result"]=1;
            $data1["status"]=1;
            $data1["message"]="查询失败,没有相关数据";
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }
    }





    public function city(){
        @$province=isset($_POST['province'])?trim($_POST['province']):false;
        @$data1=[];

        //$province='北京市';
        if(!$province){
            $data1["result"]=1;
            $data1["status"]=1;
            $data1["message"]="查询失败,传参有误";
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }
    	
        $city=M("e_zc_station");
        $data=$city->field("province,city")->where("province='{$province}'")->select();
        @$res=[];
        foreach($data as $k=>$v){
            if(!in_array($v,$res)){
                $res[]=$v;
            }
        }

        if($res){
            //成功返回值
            $data1["result"]=0;
            $data1["status"]=0;
            $data1["message"]="查询成功";
            $data1["info"]=$res;
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }else{
            //失败返回值
            $data1["result"]=1;
            $data1["status"]=1;
            $data1["message"]="查询失败,没有相关数据";
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }
    	

    }

    public function county(){
        @$province=isset($_POST['province'])?trim($_POST['province']):false;
        @$city=isset($_POST['city'])?trim($_POST['city']):false;
        @$data1=[];

        //$province='北京市';
        //$city='市辖区';
        if(!$province || !$city){
            $data1["result"]=1;
            $data1["status"]=1;
            $data1["message"]="查询失败,传参有误";
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }
        
        $county=M("e_zc_station");
        $data=$county->field("province,city,county")->where("province='{$province}' and city='{$city}'")->select();
        @$res=[];
        foreach($data as $k=>$v){
            if(!in_array($v,$res)){
                $res[]=$v;
            }
        }

        if($res){
            //成功返回值
            $data1["result"]=0;
            $data1["status"]=0;
            $data1["message"]="查询成功";
            $data1["info"]=$res;
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }else{
            //失败返回值
            $data1["result"]=1;
            $data1["status"]=1;
            $data1["message"]="查询失败,没有相关数据";
            $data1=json_encode($data1,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $data1;
            die;
        }
    }

}