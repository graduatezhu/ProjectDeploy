<?php
//namespace APIes\Model;
use Think\Model;
class ZcStationModel extends Model{
	public function seldata($val){
		//站列
		$field='e_zc_station.id,e_zc_station.name,e_zc_station.lat,e_zc_station.lng';
		//判断提交参数
		if ($val['iscar']==1) {
			$map['occupation']='0';
		}
		//续航km
		if ($val['batterylife']==1) {
			$batterylife=50;
		}elseif ($val['batterylife']==2) {
			$batterylife=100;
		}elseif ($val['batterylife']==3) {
			$batterylife=150;
		}else{
			$batterylife='';
		}
		//乘坐人
		if ($val['capacity']==1) {
			$map['capacity']='2';
		}elseif ($val['capacity']==2) {
			$map['capacity']='5';
		}elseif ($val['capacity']==3) {
			$map['capacity']='7';
		}
		//comfortable
		if ($val['equipment']==1) {
			$map['equipment']='1';
		}elseif ($equipment==2) {
			$map['equipment']='2';
		}elseif($equipment==3){
			$map['equipment']='3';
		}
		$a1=$this->pubsel($field,'','');
		foreach ($a1 as $k => $v) {
			static $iid=0;
			$field1='occupation';
			$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$map['e_zc_cars.station_id']=$v['id'];
			/*$where['e_zc_cars.station_id&occupation']=array(array('eq',$v['id']),array('eq',0),'_multi'=>ture);*/
			//print_r($map);
			$a2=$this->pubsell($field1,$join,$map,'');
			//print_r($a2);die;
			if($a2!=0){
				$a1[$k]['freecar']=1;
			}else{
				$a1[$k]['freecar']=2;
			}
			//unset();
		}
		return $a1;
	}
	public function zsearchs($val,$lat,$lng){
		$field='id,name,city,county,lat,lng';
		$map['name'] = array('like',"%$val%");
		$result=$this->pubsel($field,'',$map,'');
		foreach ($result as $ke => $va) {
			//评分
			$aa1=$this->Table('e_zc_station')
			->join('e_zccomment on e_zccomment.zc_stationid=e_zc_station.id')
			->where(array('e_zc_station.id'=>$va['id']))
			->avg('e_zccomment.grade');
			$result[$ke]['score']=round($aa1);
			//距离km
			$result[$ke]['distance']=sprintf('%.2f',getDistance($lat,$lng,$va['lat'],$va['lng'])/1000);
			//空闲车辆数
			$fieldd='e_zc_cars.occupation';
			$joinn='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$map['e_zc_cars.occupation']='0';
			$map['e_zc_cars.station_id']=$va['id'];
			$result[$ke]['freenum']=$this->pubsell($fieldd,$joinn,$map,'');
			//最大续航
			$fieldq='e_zc_cars.sn';
			$joinq='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$xar=$this->pubsel($fieldq,$joinq,$map,'');
			foreach ($xar as $key => $val) {
                $xar[$key]=$val['sn'];
            }
            $sn=implode(',',$xar);
	        //某站空闲车辆数
            $url = 'http://221.123.179.91:9819/yydl/GetCarsStatus.ashx?SN=' .$sn. '&customerFlag=000';// 智信通地址
            $file=json_decode( file_get_contents ( $url ),true );
            foreach ($file['cars'] as $key => $valu) {
            	$mileage[$key]=$valu['mileage'];
            }
            $result[$ke]['mileage']=max($mileage);
            unset($result[$ke]['lat']);
            unset($result[$ke]['lng']);
		}
		return $result;
	}
	//租车地图显示
	public function rentmaps(){
		$field='id,lat,lng';
		$zhan=$this->pubsel($field,'','','');
		foreach ($zhan as $k => $v) {
			$field='occupation';
			$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$where['e_zc_cars.station_id&occupation']=array(array('eq',$v['id']),array('eq',0),'_multi'=>true);
			$is=$this->pubsell($filed,$join,$where);
			if ($is!=0) {
				$zhan[$k]['isfree']=1;
			}else{
				$zhan[$k]['isfree']=2;
			}
		}
		return $zhan;
	}
	//地图弹窗
	public function maptans($lat,$lng,$stationid){
		$field='id,name,address,lat,lng';
		$map['id']=$stationid;
		//距离
		$maptan=$this->pubsel($field,'',$map,'');
		foreach ($maptan as $k => $v) {
			$maptan[$k]['distance']=sprintf('%.2f',getDistance($lat,$lng,$v['lat'],$v['lng'])/1000);
			//车辆数
			$field='occupation';
			$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$where['e_zc_cars.station_id&occupation']=array(array('eq',$v['id']),array('eq',0),'_multi'=>true);
			$maptan[$k]['freecarnum']=$this->pubsell($filed,$join,$where);
			//unset($maptan[$k]['id']);
			unset($maptan[$k]['lat']);
			unset($maptan[$k]['lng']);
		}
		
		$maptanq['id']=$maptan[0]['id'];
		$maptanq['name']=$maptan[0]['name'];
		$maptanq['address']=$maptan[0]['address'];
		$maptanq['distance']=$maptan[0]['distance'];
		$maptanq['freecarnum']=$maptan[0]['freecarnum'];
		return $maptanq;
	}
	//地图筛选
	public function mapchoices($val){
		//print_r($val);die;
		$field='e_zc_station.id,e_zc_station.name,e_zc_station.lat,e_zc_station.lng';
		//判断提交参数
		if ($val['iscar']==1) {
			$map['occupation']=array('eq','0');
		}
		//续航km
		if ($val['batterylife']==1) {
			$batterylife='50';
		}elseif ($val['batterylife']==2) {
			$batterylife='100';
		}elseif ($val['batterylife']==3) {
			$batterylife='150';
		}else{
			$batterylife='';
		}
		//乘坐人
		if ($val['capacity']==1) {
			$map['capacity']=array('eq','2');
		}elseif ($val['capacity']==2) {
			$map['capacity']=array('eq','5');
		}elseif ($val['capacity']==3) {
			$map['capacity']=array('eq','7');
		}elseif($val['capacity']==4){
			$map['capacity']=array('in','2,5');
		}elseif ($val['capacity']==5) {
			$map['capacity']=array('in','2,7');
		}elseif($val['capacity']==6){
			$map['capacity']=array('in','5,7');
		}
		//comfortable
		if ($val['equipment']==1) {
			$map['equipment']=array('eq','1');
		}elseif ($val['equipment']==2) {
			$map['equipment']=array('eq','2');
		}elseif($val['equipment']==3){
			$map['equipment']=array('eq','3');
		}elseif ($val['equipment']==4) {
			$map['equipment']=array('in','1,2');
		}elseif ($val['equipment']==5) {
			$map['equipment']=array('in','1,3');
		}elseif ($val['equipment']==6) {
			$map['equipment']=array('in','2,3');
		}
		//print_r($map);die;
		$a1=$this->pubsel($field,'','');
		foreach ($a1 as $k => $v) {
			static $iid=0;
			$field1='occupation';
			$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$iid=$v['id'];
			$map['e_zc_cars.station_id']=array('eq',$iid);
			$a2=$this->pubsell($field1,$join,$map,'');
	        //某站空闲车辆
			if($a2!=0){
				$a1[$k]['freecar']=1;
			}else{
				$a1[$k]['freecar']=2;
			}
			//获取mileage；
			$field2='e_zc_cars.id,e_zc_cars.occupation,e_zc_cars.sn';
			static $a3='';
			foreach ($a3 as $kk => $vv) {
				unset($a3[$kk]);
			}
			$a3=$this->pubseld($field2,$join,$map,'');
			//print_r($a3);
			static $sn=0;
			static $yuan1='';
			/*foreach ($a3 as $k2 => $v2) {
				$yuan1=$v2['sn'];
				$ssn[$k2]=$yuan1;
			}*/
			static $ssn=array();
			for ($i=0; $i <count($a3); $i++) {
				if(empty($a3[$i])){
					unset($a3[$i]);
				}			
			}
			for ($i=0; $i <count($a3); $i++) {
					$ssn[$i]=$a3[$i]['sn'];
			}
			foreach ($ssn as $ke1 => $ve1) {
				if ($ke1>=count($a3)) {
					unset($ssn[$ke1]);
				}
			}
			$sn=implode(',',$ssn);
			//print_r($sn);
			$url = 'http://221.123.179.91:9819/yydl/GetCarsStatus.ashx?SN=' .$sn. '&customerFlag=000';// 智信通地址
            $file=json_decode( file_get_contents ( $url ),true );
            
            //print_r(count($file['cars']));
            foreach ($file['cars'] as $k3 => $v3) {
            	static $yuan='';
            	$yuan=$v3['mileage'];
            	$mileage[$k3]=$yuan;
            }
            //print_r(count($mileage));
            foreach ($mileage as $kk1 => $vv1) {
            	if ($kk1>=count($file['cars'])) {
            		unset($mileage[$kk1]);
            	}
            }
            $a1[$k]['mileage']=max($mileage);
			//unset($a1[$k]['id']);
			unset($a1[$k]['name']);
		}
		//print_r($a1);
		foreach ($a1 as $key => $val) {
			if ($val['mileage']<$batterylife) {
				$a1[$k]['freecar']=2;
			}else{
				$a1[$k]['freecar']=1;
			}
			unset($a1[$key]['mileage']);
		}
		return $a1;
	}
	//租车站列表（城市筛选+右上角筛选设置偏好筛选+智能筛选）
	public function renthelists($val){
		
		if ($val['city']!=0) {
			//$we=array('city'=>$val['city']);
			$we['city']=array('eq',$val['city']);
		}
		if ($val['iscar']==1) {
			$we['occupation']=array('eq','0');
		}
		//续航km
		if ($val['batterylife']==1) {
			$batterylife=50;
		}elseif ($val['batterylife']==2) {
			$batterylife=100;
		}elseif ($val['batterylife']==3) {
			$batterylife=150;
		}else{
			$batterylife='';
		}
		//乘坐人
		if ($val['capacity']==1) {
			$we['capacity']=array('eq','2');
		}elseif ($val['capacity']==2) {
			$we['capacity']=array('eq','5');
		}elseif ($val['capacity']==3) {
			$we['capacity']=array('eq','7');
		}elseif($val['capacity']==4){
			$we['capacity']=array('in','2,5');
		}elseif ($val['capacity']==5) {
			$we['capacity']=array('in','2,7');
		}elseif($val['capacity']==6){
			$we['capacity']=array('in','5,7');
		}
		//comfortable
		if ($val['equipment']==1) {
			$we['equipment']=array('eq','1');
		}elseif ($val['equipment']==2) {
			$we['equipment']=array('eq','2');
		}elseif($val['equipment']==3){
			$we['equipment']=array('eq','3');
		}elseif ($val['equipment']==4) {
			$we['equipment']=array('in','1,2');
		}elseif ($val['equipment']==5) {
			$we['equipment']=array('in','1,3');
		}elseif ($val['equipment']==6) {
			$we['equipment']=array('in','2,3');
		}
		//print_r($we);die;
		$field='e_zc_station.id,e_zc_station.name,e_zc_station.city,e_zc_station.county,e_zc_station.phone,e_zc_station.lat,e_zc_station.lng';
		$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
		$aa=$this->pubseld($field,$join,$we,'');
		//print_r($aa);die;
		//评分
		foreach ($aa as $k => $v) {
			//print_r($v);die;
			$aa1=$this->Table('e_zc_station')
			->join('e_zccomment on e_zccomment.zc_stationid=e_zc_station.id')
			->where(array('e_zc_station.id'=>$v['id']))
			->avg('e_zccomment.grade');
			$aa[$k]['score']=round($aa1);
			//距离km
			$aa[$k]['distance']=sprintf('%.2f',getDistance($val['lat'],$val['lng'],$v['lat'],$v['lng'])/1000);
			if (!empty($val['userid'])) {
				$mapa['e_zcfavorite.userid&e_zcfavorite.zc_stationid']=array(array('eq',$val['userid']),array('eq',$v['id']),'_multi'=>true);
				$aa2=$this->Table('e_zc_station')
				->field('e_zcfavorite.zc_stationid')
				->join('e_zcfavorite on e_zcfavorite.zc_stationid=e_zc_station.id')
				->where($mapa)
				->select();
				if ($aa2) {
					$aa[$k]['isfavorite']=1;
				}else{
					$aa[$k]['isfavorite']=2;
				}
			}else{
				$aa[$k]['isfavorite']=2;
			}
			//空闲车辆数
			$field='occupation';
			$join='e_zc_cars on e_zc_cars.station_id=e_zc_station.id';
			$we['e_zc_cars.station_id&occupation']=array(array('eq',$v['id']),array('eq',0),'_multi'=>true);
			$aa[$k]['freecarnum']=$this->pubsell($filed,$join,$we);
			//获取mileage；
			$field2='e_zc_cars.id,e_zc_cars.occupation,e_zc_cars.sn';
			$a3=$this->pubsel($field2,$join,$we,'');
			//print_r($a3);
			//echo count($a3);
			foreach ($a3 as $k2 => $v2) {
				$ssn[$k2]=$v2['sn'];
			}
			foreach ($ssn as $ks => $vs) {
				if($ks>=count($a3)){
					unset($ssn[$ks]);
				}
			}
			$sn=implode(',',$ssn);
			//print_r($sn);
			$url = 'http://221.123.179.91:9819/yydl/GetCarsStatus.ashx?SN=' .$sn. '&customerFlag=000';// 智信通地址
            $file=json_decode( file_get_contents ( $url ),true );
            //print_r(count($file['cars']));
            foreach ($file['cars'] as $k3 => $v3) {
            	$mileage[$k3]=$v3['mileage'];
            }
            foreach ($mileage as $kk1 => $vv1) {
            	if($kk1>=count($a3)){
            		unset($mileage[$kk1]);
            	}
            }
            $aa[$k]['mileage']=max($mileage);
            //print_r($mileage);
            static $jianx=0;
            for ($i=0; $i < count($mileage); $i++) { 
            	if($mileage[$i]<$batterylife){
            		$jianx+=1;
            	}
            }
            $aa[$k]['freecarnum']=$aa[$k]['freecarnum']-$jianx;
            //print_r($mileage);die;
			//某租车站平均价格
			$aprice=$this->Table('e_zc_station')
			->join('e_zc_cars on e_zc_cars.station_id=e_zc_station.id')
			->where(array('e_zc_station.id'=>$v['id']))
			->avg('e_zc_cars.price');
			$aa[$k]['smprice']=round($aprice,3);
		}
		$paixu=$val['psort'];
		if($paixu==1){
			//智能排序
			foreach ($aa as $key => $row)
		    {
		        $distance[$key]  = $row['distance'];
		        $score[$key] = $row['score'];
		        $smprice[$key] = $row['smprice'];
		    }
		    array_multisort($distance,SORT_ASC,$score,SORT_DESC,$smprice,SORT_ASC,$aa);
		}elseif($paixu==2){
			//距离排序
			foreach ($aa as $key => $row)
		    {
		        $distance[$key] = $row['distance'];
		    }
		    array_multisort($distance, SORT_ASC, $aa);
		    //print_r($aa);die;
		}elseif($paixu==3){
			//按评分最高排序
			foreach ($aa as $key => $row)
		    {
		        $score[$key] = $row['score'];
		    }
		    array_multisort($score, SORT_DESC, $aa);
		    //print_r($aa);die;
		}elseif($paixu==4){
			//按价格最低
			foreach ($aa as $key => $row)
		    {
		        $smprice[$key] = $row['smprice'];
		    }
		    array_multisort($smprice, SORT_ASC, $aa);
		    //print_r($aa);die;
		}
		foreach ($aa as $key => $val) {
			unset($aa[$key]['smprice']);
		}
		return $aa;
	}
	//公用查询
    public function pubsel($field,$join,$where,$joins1=''){
    	//print_r($where.'2');die;
    	return $this->Table('e_zc_station')
        ->field($field)
        ->join($join)
        ->join($joins1)
        ->where($where)
        ->select();
    }
    public function pubsell($field,$join,$where,$joins1=''){
    	//print_r($where.'2');die;
    	return $this->Table('e_zc_station')
        ->field($field)
        ->join($join)
        ->join($joins1)
        ->where($where)
        ->count();
    }
    public function pubseld($field,$join,$where,$joins1=''){
    	//print_r($where.'2');die;
    	return $this->Table('e_zc_station')
    	->distinct(true)
        ->field($field)
        ->join($join)
        ->join($joins1)
        ->where($where)
        ->select();
    }
}