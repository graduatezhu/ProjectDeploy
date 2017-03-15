<?php
//namespace APIes\Model;
use Think\Model;
class ZcfavoriteModel extends Model{
    public function seldata($val,$lat,$lng){
        $field='e_zcfavorite.id,e_zc_station.name,e_zc_station.city,e_zc_station.county,e_zc_station.lat,e_zc_station.lng,e_zcfavorite.zc_stationid';
        $join='e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid';
        $where=array('e_zcfavorite.userid'=>$val);
        $ar1=$this->pubsel($field,$join,$where);
        //print_r($ar1);die;
        foreach ($ar1 as $key => $v) {
            //续航里程
            $xarr=$this->Table('e_zcfavorite')
            ->field('e_zc_cars.sn')
            ->join('e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid')
            ->join('e_zc_cars on e_zc_cars.station_id=e_zc_station.id')
            ->where(array('e_zc_cars.station_id'=>$v['zc_stationid']))
            ->select();
            foreach ($xarr as $key => $val) {
                $xarr[$key]=$val['sn'];
            }
            $sn=implode(',',$xarr);
	        //某站空闲车辆数
            $url = 'http://221.123.179.91:9819/yydl/GetCarsStatus.ashx?SN=' .$sn. '&customerFlag=000';// 智信通地址
            $file=json_decode( file_get_contents ( $url ),true );
            foreach ($file['cars'] as $key => $valu) {
                $mileage[$key]=$valu['mileage'];
            }
            $ar1[$key]['mileage']=max($mileage);
	        $map['e_zc_cars.station_id&e_zc_cars.occupation']=array(array('eq',$v['zc_stationid']),array('eq',0),'_multi'=>true);
	        //print_r($map);die;
	        $ar1[$key]['freecar']=$this->Table('e_zcfavorite')
	        ->join('e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid')
	        ->join('e_zc_cars on e_zc_cars.station_id=e_zc_station.id')
	        ->where($map)
	        ->count('e_zc_cars.id');
            //评分
            $aa1=$this->Table('e_zc_station')
            ->join('e_zccomment on e_zccomment.zc_stationid=e_zc_station.id')
            ->where(array('e_zc_station.id'=>$v['zc_stationid']))
            ->avg('e_zccomment.grade');
            $ar1[$key]['score']=round($aa1);
	        //距离
	        $ar1[$key]['facemiles']=sprintf('%.2f',getDistance($lat,$lng,$v['lat'],$v['lng'])/1000);
	        unset($ar1[$key]['zc_stationid']);
            unset($ar1[$key]['lat']);
            unset($ar1[$key]['lng']);
        }
        //print_r($ar1);die;
        return $ar1;
    }
    //删除收藏
    public function fdels($userid,$fid){
        $where['userid']=$userid;
        if($fid!='c'){
            $where['id']=$fid;
        }
        return $this->Table('e_zcfavorite')->where($where)->delete();
    }
    //公用查询
    public function pubsel($field,$join,$where='',$joins1=''){
    	return $this->Table('e_zcfavorite')
        ->field($field)
        ->join($join)
        ->join($joins1)
        ->where($where)
        ->order($order)
        ->select();
    }
    public function pubsell($field,$join,$where='',$joins1=''){
    	return $this->Table('e_zcfavorite')
        ->join($join)
        ->join($joins1)
        ->where($where)
        ->order($order)
        ->max('e_zc_cars.miles');
    }
}