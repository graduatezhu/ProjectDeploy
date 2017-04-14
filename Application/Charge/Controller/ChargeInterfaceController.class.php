<?php
use Think\Controller;
/**
 * @ClassName: ChargeInterface
 * @Description: app充电站数据接口
 * @Company: EDog
 * @author ZWS
 * @date 2017年3月1日上午11:08:09
 */
class ChargeInterfaceController extends CommonController{
    /*初始化*/
	public function _initialize() {

	parent::_initialize(); //调用父类成员函数

		//A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN

		/*实例化模型对象*/
        //$this->tblChargTmp=D('ChargeTmp');

	}
	
	/**
	 * @Title: 充电站列表
	 * @Description: 返回充电站列表数据
     * @param int  open 是否对外开放 1 开放 2 不开放 0 全部
     * @param int chargeMethod 站内充电桩类型 1快充2慢充 0 全部
     * @param int parkingMethod 电站停车是否收费 1免费2收费 0 全部
     * @param int app 0 支持app支付
     * @param int card 0 支持电卡支付
     * @param int cash 0 支持现金支付
	 * @return JSON
	 * @throws
	 */
	public function stationList() {

        //检查并处理传来的数据
        if(isset($_POST['open']) &&  isset($_POST['chargeMethod']) && isset($_POST['parkingMethod']) && isset($_POST['app']) && isset($_POST['card']) && isset($_POST['cash']) ){
            $open=intval($_POST['open']);
            $chargeMethod=intval($_POST['chargeMethod']);
            $parkingMethod=intval($_POST['parkingMethod']);
            $app=intval($_POST['app']);
            $card=intval($_POST['card']);
            $cash=intval($_POST['cash']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //添加筛选条件
        switch ($open){
            case 1:
                $where['open']=array('EQ',0);
                break;
            case 2:
                $where['open']=array('EQ',1);
                break;
        }

        switch ($chargeMethod){
            case 1:
                $where['DC_num']=array('NEQ',0);
                break;
            case 2:
                $where['AC_num']=array('NEQ',0);
                break;
        }

        switch ($parkingMethod){
            case 1:
                $where['parking_fee']=array('EQ',0);
                break;
            case 2:
                $where['parking_fee']=array('NEQ',0);
                break;
        }


        $app==0?$where['charge_app']=array('EQ',0):'';
        $card==0?$where['charge_card']=array('EQ',0):'';
        $cash==0?$where['charge_cash']=array('EQ',0):'';



        $station=M('charge_station');//实例化数据库模型
        $data=$station->field('id,name,lat,lng')->where($where)->select();//进行数据查询

        $return_data=[];//声明一个用来存放返回数据的数组

        //根据查询结果添加返回数据
        if(empty($data)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据';
            $return_data['code']=10003;
        }else{
            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            $return_data['info']=$data;
        }

        echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据

	}

    /**
    * @Title: 单个充电站详情
    * @Description: 返回充电站详情数据
    * @param int id 充电站id
    * @param float lat 用户经度
    * @param float lng 用户纬度
    * @return JSON
    * @throws
    */
    public function stationInfo(){
        $return_data=[];//声明一个用来存放返回数据的数组

        //判断传入参数的完整性并进行简单的安全性处理
        if(isset($_POST['id']) && isset($_POST['lat']) && isset($_POST['lng'])){
            $id=intval($_POST['id']);
            $lat1=floatval($_POST['lat']);
            $lng1=floatval($_POST['lng']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        $station=M('charge_station');//实例化数据库模型
        $data=$station->field('id,name,phone,carrieroperator,AC_num,DC_num,charge_app,charge_cash,charge_card,lat,lng,charging_fee,parking_fee')->where("id={$id}")->find();

        //查询无数据直接返回错误
        if(empty($data)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //处理数据
        $data['payment']='';//声明一个拼接支付方式的变量
        @$data['charge_app']==0 ? $data['payment'].='/APP支付':'';
        @$data['charge_cash']==0 ? $data['payment'].='/现金支付':'';
        @$data['charge_card']==0 ? $data['payment'].='/电卡支付':'';
        unset($data['charge_app'],$data['charge_cash'],$data['charge_card']);//销毁多与数据
        $data['payment']=substr($data['payment'],1);//去除最前面的/

        $data['distance']=getDistance($lat1,$lng1,$data['lat'],$data['lng']);//计算站点与用户距离

        $return_data['success']=true;
        $return_data['status']=0;
        $return_data['message']='命令执行成功';
        $return_data['info']=$data;
        echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据

    }


    /**
    * @Title: 充电站列表(筛选)
    * @Description: 返回筛选后的充电站列表
    * @param int  userid 用户id
    * @param int  open 是否对外开放 1 开放 2 不开放 0 全部
    * @param int  order 排序方式   智能1(默认)   距离最近2   评分3  价格最低4
    * @param string city 城市
    * @param int chargeMethod 站内充电桩类型 1快充2慢充 0 全部
    * @param int parkingMethod 电站停车是否收费 1免费2收费 0 全部
    * @param int app 0 支持app支付
    * @param int card 0 支持电卡支付
    * @param int cash 0 支持现金支付
    * @param float lat 用户经度
    * @param float lng 用户纬度
    * @return JSON
    * @throws
    */

    public function searchStationList(){

        $return_data=[];//声明一个用来存放返回数据的数组
        $where=[];//声明一个用来存放查询数据的数组

        //检查并处理传来的数据
        if(isset($_POST['app']) && isset($_POST['card']) && isset($_POST['cash']) && isset($_POST['userid']) && isset($_POST['open']) && isset($_POST['order']) && isset($_POST['city']) && isset($_POST['chargeMethod']) && isset($_POST['parkingMethod']) && isset($_POST['lat']) && isset($_POST['lng'])){
            $id=intval($_POST['userid']);
            $open=intval($_POST['open']);
            $order=intval($_POST['order']);
            $city=trim($_POST['city']);
            $chargeMethod=intval($_POST['chargeMethod']);
            $parkingMethod=intval($_POST['parkingMethod']);
            $app=intval($_POST['app']);
            $cash=intval($_POST['cash']);
            $card=intval($_POST['card']);
            $lat1=floatval($_POST['lat']);
            $lng1=floatval($_POST['lng']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //添加筛选条件
        switch ($open){
            case 1:
                $where['open']=array('EQ',0);
                break;
            case 2:
                $where['open']=array('EQ',1);
                break;
        }

        switch ($chargeMethod){
            case 1:
                $where['DC_num']=array('NEQ',0);
                break;
            case 2:
                $where['AC_num']=array('NEQ',0);
                break;
        }

        switch ($parkingMethod){
            case 1:
                $where['parking_fee']=array('EQ',0);
                break;
            case 2:
                $where['parking_fee']=array('NEQ',0);
                break;
        }

        $app==0?$where['charge_app']=array('EQ',0):'';
        $card==0?$where['charge_card']=array('EQ',0):'';
        $cash==0?$where['charge_cash']=array('EQ',0):'';

        if($city!=0){
            $where['city']=array('EQ',$city);
        }

        $station=M('charge_station');//实例化数据库模型
        $data=$station->field('id,name,phone,city,county,charge_app,phone,charge_cash,charge_card,lat,lng,charging_fee,parking_fee,score')->where($where)->select();

        if(empty($data)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //添加是否收藏的字段   并且加上距离的计算
        $collection=M('collection')->field('charge_id')->where("id={$id}")->find();

        if(empty($collection)){

            foreach($data as $k => $v){
                $data[$k]['collection']=1;
                $data[$k]['distance']=getDistance($lat1,$lng1,$v['lat'],$v['lng']);
                $data[$k]['address']=$v['city'].' '.$v['county'];
                unset($data[$k]['city'],$data[$k]['county']);
            }

        }else{

            $collection_station=explode(',',$collection['charge_id']);
            foreach($data as $k => $v){
                in_array($v['id'],$collection_station)? $data[$k]['collection']=0 : $data[$k]['collection']=1 ;
                $data[$k]['distance']=getDistance($lat1,$lng1,$v['lat'],$v['lng']);
                $data[$k]['address']=$v['city'].' '.$v['county'];
                unset($data[$k]['city'],$data[$k]['county']);
            }

        }

        $sort_arr=[];//排序数组

        switch ($order){
            case 2:
                foreach($data as $k => $v ){
                    $sort_arr[$k]=$v['distance'];
                }
                asort($sort_arr);
                break;
            case 3:
                foreach($data as $k => $v ){
                    $sort_arr[$k]=$v['score'];
                }
                arsort($sort_arr);
                break;
            case 4:
                foreach($data as $k => $v ){
                    $sort_arr[$k]=$v['charging_fee'];
                }
                asort($sort_arr);
                break;
            case 1:
                foreach($data as $k => $v ){
                    $sort_arr[$k]=$v['id'];
                }
                asort($sort_arr);
                break;
        }

        $new_data=[];
        foreach($sort_arr as $k => $v){
            $new_data[]=$data[$k];
        }

        $return_data['success']=true;
        $return_data['status']=0;
        $return_data['message']='命令执行成功';
        $return_data['info']=$new_data;
        echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据


    }

    /**
     * @Title: 分享充电站
     * @Description: 返回后台是否储存此次分享的信息
     * @param int  userid 用户id
     * @param string  address  地址
     * @param string phone 充电站电话
     * @param string name 充电站名称
     * @param float lat 充电站经度
     * @param float lng 充电站纬度
     * @param int open 是否对开开放 0 是 1 否
     * @param string photoinfo 照片base64
     * @return JSON
     * @throws
     */
	public function shareStation(){
        $return_data=[];//声明一个用来存放返回数据的数组
        $save_data=[];//声明一个用来存放插入数据的数组

        if(isset($_POST['userid']) && isset($_POST['address']) && isset($_POST['phone']) && isset($_POST['name']) && isset($_POST['lat']) && isset($_POST['lng']) && isset($_POST['open']) && isset($_POST['photoinfo']) ){
            $name=trim($_POST['name']);
            $userid=intval($_POST['userid']);
            $phone=intval($_POST['phone']);
            $address=trim($_POST['address']);
            $open=intval($_POST['open']);
            $lat1=floatval($_POST['lat']);
            $lng1=floatval($_POST['lng']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //保存图片
        if(!file_exists("./Public/share_station_pic")){
            mkdir("./Public/share_station_pic");
        }

        $time=time();
        $pic=base64_decode($_POST['photoinfo']);
        $pic_res=file_put_contents("Public/share_station_pic/{$time}_{$userid}.jpg",$pic);

        if($pic_res){
            $save_data['photo']="Public/share_station_pic/{$time}_{$userid}.jpg";
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,图片保存失败';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        //整理存储数据
        $save_data['uid']=$userid;
        $save_data['name']=$name;
        $save_data['phone']=$phone;
        $save_data['address']=$address;
        $save_data['open']=$open;
        $save_data['lat']=$lat1;
        $save_data['lng']=$lng1;

        //储存数据
        $share=M('share_charge_station');
        $shareres=$share->create($save_data);
        if($shareres){
            $shareinfo=$share->add();
            if($shareinfo){
                $return_data['success']=true;
                $return_data['status']=0;
                $return_data['message']='命令执行成功';
                echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            }else{
                $return_data['success']=true;
                $return_data['status']=-1;
                $return_data['message']='命令执行失败,存储错误2';
                $return_data['code']=10003;
                echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
                exit;
            }
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,存储错误1';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }




    }

    /**
     * @Title: 获取用户收藏的充电站
     * @Description: 返回用户收藏的充电站信息
     * @param int  userid 用户id
     * @return JSON
     * @throws
     */
	public function getChargeCollection(){
        $return_data=[];//声明一个用来存放返回数据的数组

        if(isset($_POST['userid']) && isset($_POST['lat']) && isset($_POST['lng'])){
            $id=intval($_POST['userid']);
            $lat1=floatval($_POST['lat']);
            $lng1=floatval($_POST['lng']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        $store=M('collection');
        $storedata=$store->field('charge_id')->where("id={$id}")->find();

        if(!$storedata){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        $station=M('charge_station');
        //$condition['id']=['IN',$storedata['charge_id']];
        $station_data=$station->field('id,name,city,county,score,parking_fee,lat,lng')->where("id IN({$storedata['charge_id']})")->select();

        if($station_data){

            foreach($station_data as $k=>$v){
                @$v['parking_fee']==0 ? $v['parking_fee']=0 :$v['parking_fee']=1;
                $station_data[$k]['distance']=getDistance($lat1,$lng1,$v['lat'],$v['lng']);
                $station_data[$k]['address']=$v['city'].' '.$v['county'];
                unset($station_data[$k]['city'],$station_data[$k]['county']);
            }

            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            $return_data['info']=$station_data;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据

        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据2';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

    }


    /**
     * @Title: 充电站查找
     * @Description: 返回查找的信息
     * @param int  userid 用户id
     * @param string  search 搜索值
     * @return JSON
     * @throws
     */
    public function searchCollection(){

        $return_data=[];//声明一个用来存放返回数据的数组
        if(isset($_POST['userid']) && isset($_POST['search']) && isset($_POST['lat']) && isset($_POST['lng'])){
            $userid=intval($_POST['userid']);
            $lat1=floatval($_POST['lat']);
            $lng1=floatval($_POST['lng']);
            $search=trim($_POST['search']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        $station=M('charge_station');
        $station_data=$station->field('id,name,city,county,score,parking_fee,lat,lng')->where("name like '%{$search}%'")->select();;

        if(empty($station_data)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }



        foreach($station_data as $k=>$v){
            @$v['parking_fee']==0 ? $v['parking_fee']=0 :$v['parking_fee']=1;
            $station_data[$k]['distance']=getDistance($lat1,$lng1,$v['lat'],$v['lng']);
            $station_data[$k]['address']=$v['city'].' '.$v['county'];
            unset($station_data[$k]['city'],$station_data[$k]['county']);
        }

        $return_data['success']=true;
        $return_data['status']=0;
        $return_data['message']='命令执行成功';
        $return_data['info']=$station_data;
        echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
        exit;


    }

    /**
     * @Title: 添加收藏
     * @Description: 返回查找的信息
     * @param userid  userid 用户id
     * @param id  id 充电站id
     * @return JSON
     * @throws
     */

    public function addChargeCollection(){
        if(isset($_POST['userid']) && isset($_POST['id'])){
            $userid=intval($_POST['userid']);
            $stationid=intval($_POST['id']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        $collection=M('collection')->where("uid={$userid}")->find();

        if(empty($collection)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='收藏失败';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        if(empty($collection['charge_id'])){
            $collection['charge_id']=$stationid;
            $collection['addtime']=time();
        }else{
            $collection['charge_id'].=','.$stationid;
            $collection['addtime']=time();
        }

        $save=M('collection')->save($collection);
        if($save){
            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='收藏失败';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

    }

    /**
     * @Title: 删除/清空收藏
     * @Description: 返回查找的信息
     * @param userid  userid 用户id
     * @param id  id 充电站id
     * @return JSON
     * @throws
     */

    public function deleteChargeCollection(){

        if(isset($_POST['userid']) && isset($_POST['id'])){

            $userid=intval($_POST['userid']);
            $stationid=trim($_POST['id']);
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='命令执行失败,传参不完整';
            $return_data['code']=10002;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

        if($stationid=='all'){
            $data['charge_id']='';
            $res=M('collection')->where("uid={$userid}")->save($data);
            if($res){
                $return_data['success']=true;
                $return_data['status']=0;
                $return_data['message']='命令执行成功';
                echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
                exit;
            }else{
                $return_data['success']=true;
                $return_data['status']=-1;
                $return_data['message']='取消收藏失败';
                $return_data['code']=10003;
                echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
                exit;
            }
        }

        $collection=M('collection')->where("uid={$userid}")->find();
        $arr=explode(',',$collection['charge_id']);

        foreach($arr as $k=>$v){
            if($v==$stationid){
                unset($arr[$k]);
            }
        }

        $data['charge_id']=implode(',',$arr);
        $res=M('collection')->where("uid={$userid}")->save($data);
        if($res){
            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }else{
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='取消收藏失败';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }

    }


    /**
     * @Title: 获取紧急电话
     * @Description: 返回需要获取的紧急电话
     * @return JSON
     * @throws
     */
    public function getPhone(){
        $phone=M('rescue_phone')->field('name,phone')->select();

        if(empty($phone)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }else{
            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            $return_data['info']=$phone;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }
    }

    /**
     * @Title: 获取热点城市
     * @Description: 返回查找的信息
     * @param userid  userid 用户id
     * @param id  id 充电站id
     * @return JSON
     * @throws
     */

    public function city(){
        $res=M('')->query("select distinct city from charge_station");
        if(empty($res)){
            $return_data['success']=true;
            $return_data['status']=-1;
            $return_data['message']='查询无数据';
            $return_data['code']=10003;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }else{
            array_unshift($res,['city'=>'全国']);
            $return_data['success']=true;
            $return_data['status']=0;
            $return_data['message']='命令执行成功';
            $return_data['info']=$res;
            echo json_encode($return_data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);//输出组装好的数据
            exit;
        }
    }



}