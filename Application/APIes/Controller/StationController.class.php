<?php

class StationController extends CommonController {
	public function index() {
		$this->display ();
	}
	public function station() {
		@$station_id = isset ( $_POST ['station_id'] ) ? intval ( $_POST ['station_id'] ) : false;
		@$data = [ ];
		
		if (! $station_id) {
			// 失败返回值
			$data ["result"] = 1;
			$data ["status"] = 1;
			$data ["message"] = "传参不完整";
			$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
			echo $data;
			die ();
		}
		
		$station = M ( "" );
		// 查询折扣信息
		$discount_info = $station->table ( 'e_zc_sysparameter' )->field ( 'var_value' )->where ( 'id=14' )->find ();
		$discount = $discount_info ['var_value'];
		
		// 查询站点信息
		$res = $station->table ( "e_zc_station station" )->field ( "station.name,station.address,station.phone,station.comment,station.lng,station.lat" )->where ( "id={$station_id}" )->find ();
		//print_r($res);die;
		// 若有结果集 再查站点的车辆
		if ($res) {
			$res1 = $station->table ( "e_zc_cars car,e_zc_cars_model model,e_zc_cars_brand brand" )->field ( "model.img_url,car.plate,car.id car_id,car.capacity,car.occupation,model.name model,brand.name brand,car.sn,car.code" )->where ( "car.station_id={$station_id} and car.model_id=model.id and car.brand_id=brand.id" )->select ();

			if ($res1) {
				
				/* 调用智信通核查车辆是否在线 */
				foreach ( $res1 as $k => $v ) {
					$arrSN [$k] = $res1 [$k] ['sn'];//构造待核查SN一维数组
				}
				
				$strSN = implode ( ',', $arrSN );// 转换为逗号分隔的字符串
				$url = 'http://221.123.179.91:9819/yydl/GetOnlineList.ashx?SN=' . $strSN . '&customerFlag=000';// 智信通接口地址
				
				$arr = json_decode ( file_get_contents ( $url ),true ); // 返回的JSON转换为数组,
				$arrCarStatus=$arr['cars']; // 截取车辆状态二维数组，结构为array(array(sn,onlineStatus)..)
				
				//构造在线的SN一维数组
				$i=0;
				foreach ($arrCarStatus as $k => $v) {
					if($v['onlineStatus']=='1'){
						$arrSnOnline[$i++]=$v['sn'];
					}
				}

				//构造在线的站-车信息数组
				$calc=0;
				for($j=0;$j<=count($arrSnOnline);$j++){
					foreach ($res1 as $k=>$v){
						if ($v['sn']==$arrSnOnline[$j]) {
							$rsCarsOnline[$calc++]=$res1[$k];
						}
					}
				}
				
				$res1=$rsCarsOnline;//覆盖源结果集,休眠状态无法检测，待注释掉
				
				/* 调用智信通接口核查车辆是否在线结束 */
				
				$res ["result"] = 0;
				$res ["status"] = 0;
				$res ["message"] = "查询成功";
				$res ["discount"] = $discount;
				$res ['car_info'] = $res1;
				$res ['infocount'] = count ( $res1 );
				/* 返回json串 */
				$res = json_encode ( $res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				echo $res;
				die ();
			} else {
				// 失败返回值
				$res ["result"] = 0;
				$res ["status"] = 0;
				$res ["message"] = "查询成功";
				$res ["discount"] = $discount;
				$res ['car_info'] = null;
				$res ['infocount'] = 0;
				$res = json_encode ( $res, JSON_UNESCAPED_UNICODE );
				echo $res;
				die ();
			}
		} else {
			// 失败返回值
			$data ["result"] = 1;
			$data ["status"] = 1;
			$data ["message"] = "没有此站点的信息";
			$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
			echo $data;
			die ();
		}
	}
}
