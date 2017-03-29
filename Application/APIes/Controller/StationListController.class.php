<?php
namespace APIes\Controller;
use Think\Controller;
class StationListController extends Controller {
    public function index(){
        $this->display();
    }


    public function stationlist(){
    	if(empty($_POST['lng'])){
    		unset($_POST['lng']);
    	}

    	if(empty($_POST['lat'])){
    		unset($_POST['lat']);
    	}

    	if(empty($_POST['country'])){
    		unset($_POST['country']);
    	}

        if(empty($_POST['city'])){
            unset($_POST['city']);
        }

        if(empty($_POST['province'])){
            unset($_POST['province']);
        }

       
    	
        @$province=isset($_POST['province'])?trim($_POST['province']):false;
    	@$city=isset($_POST['city'])?trim($_POST['city']):false;
    	@$county=isset($_POST['county'])?trim($_POST['county']):false;
    	@$lng=isset($_POST['lng'])?trim($_POST['lng']):false;
    	@$lat=isset($_POST['lat'])?trim($_POST['lat']):false;
    	@$data=[];
        @$where=[];
        if($province){
            $where['province']=array('eq',$province);
        }

        if($city){
            $where['city']=array('eq',$city);
        }

        if($county){
            $where['county']=array('eq',$county);
        }
       
        if($county){
            $where['county']=array('eq',$county);
        }
      
    	$list=M("e_zc_station");
        $res=$list->field("id station_id,lng,lat,name,address,province,city,county,phone")->where($where)->select();
        if(!$res){
            //失败返回值
            $data["result"]=1;
            $data["status"]=1;
            $data["message"]="无查询结果";
            $data=json_encode($data,JSON_UNESCAPED_UNICODE);
            echo $data;
            die; 
        }
        
        if($lat && $lng){
            //有经纬度传入的返回值
            foreach($res as $k=>$v){
                //此处调用经纬度距离计算函数
                $res[$k]["distance"]=getDistance($lat, $lng, $res[$k]['lat'], $res[$k]['lng']);
            }
            $data["result"]=0;
            $data["status"]=0;
            $data["message"]="查询成功";
            $data["info"]=$res;
            $data=json_encode($data,JSON_UNESCAPED_UNICODE);
            echo $data;
            die; 
        }else{
            //没有经纬度传入的返回值
            foreach($res as $k=>$v){
                $res[$k]["distance"]=null;
            }
            $data["result"]=0;
            $data["status"]=0;
            $data["message"]="查询成功";
            $data["info"]=$res;
            $data=json_encode($data,JSON_UNESCAPED_UNICODE);
            echo $data;
            die;    
        }
    	

    }
}