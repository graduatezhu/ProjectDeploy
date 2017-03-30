<?php
//namespace APIes\Model;
use Think\Model;
class EZcfavoriteModel extends Model{
    public function seldata($val,$lat,$lng){
        $field='e_zcfavorite.id,e_zc_station.name,e_zc_station.city,e_zc_station.county,e_zc_station.lat,e_zc_station.lng,e_zcfavorite.zc_stationid';
        $join='e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid';
        $where=array('e_zcfavorite.userid'=>$val);
        $ar1=$this->pubsel($field,$join,$where);
        //static $xarr=array();
        foreach ($ar1 as $key => $v) {
            //续航里程
            $xarr=$this->Table('e_zcfavorite')
            ->distinct(true)
            ->field('e_zc_cars.sn')
            ->join('e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid')
            ->join('e_zc_cars on e_zc_cars.station_id=e_zc_station.id')
            ->where(array('e_zc_cars.station_id'=>$v['zc_stationid']))
            ->select();
           // print_r($xarr);die;
            foreach ($xarr as $keyq => $val) {
                $ssn[$keyq]=$val['sn'];
            }
            foreach ($ssn as $kkk => $val) {
                if ($kkk>=count($xarr)) {
                    unset($ssn[$kkk]);
                }
            }
            //print_r($xarr);
            $sn=implode(',',$ssn);
            //print_r($sn);
	        //某站空闲车辆数
            $url = 'http://221.123.179.91:9819/yydl/GetCarsStatus.ashx?SN=' .$sn. '&customerFlag=000';// 智信通地址
            $file=json_decode( file_get_contents ( $url ),true );
            foreach ($file['cars'] as $keyw => $valu) {
                $mileage[$keyw]=$valu['mileage'];
            }
            foreach ($mileage as $ky => $ve) {
                if($ky>=count($xarr)){
                    unset($mileage[$ky]);
                }
            }
            //print_r($mileage);
            $ar1[$key]['mileage']=max($mileage);
	        $map['e_zc_cars.station_id&e_zc_cars.occupation']=array(array('eq',$v['zc_stationid']),array('eq',0),'_multi'=>true);
	        //print_r($map);die;
	        $chenum=$this->Table('e_zcfavorite')
	        ->join('e_zc_station on e_zc_station.id=e_zcfavorite.zc_stationid')
	        ->join('e_zc_cars on e_zc_cars.station_id=e_zc_station.id')
	        ->where($map)
	        ->select();
            //print_r($xarr);
            foreach ($chenum as $ky1 => $v) {
                if($ky1>=count($ssn)){
                    unset($chenum[$ky1]);
                }
            }
            $cnum=0;
            $cnum=count($chenum);
            $ar1[$key]['freecar']=$cnum;
            $aa1=$this->Table('e_zc_station')
            ->join('e_zccomment on e_zccomment.zc_stationid=e_zc_station.id')
            ->where(array('e_zc_station.id'=>$v['zc_stationid']))
            ->avg('e_zccomment.grade');
            $ar1[$key]['score']=round($aa1);
	        //距离
	        $ar1[$key]['facemiles']=getDistance($lat,$lng,$v['lat'],$v['lng']);
	        //unset($ar1[$key]['id']);
        }
        //print_r($ar1);die;
        return $ar1;
    }
    //删除收藏
    public function fdels($userid,$fid){
        $where['userid']=$userid;
        if($fid!='c'){
            $where['zc_stationid']=$fid;
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