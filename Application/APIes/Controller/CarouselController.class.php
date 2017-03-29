<?php
namespace APIes\Controller;
use Think\Controller;
class CarouselController extends Controller {
	public function index(){
		$this->display();
	}
	
	//列表
    public function lists(){
		$return['result'] = 0;
		
		$field = 'id,image,url';
		//$order = 'id desc';
		$res = M('e_zc_carousel')->field($field)->select();
		if( empty($res) ){
			$return['status'] = 1;
			$return['message'] = '没有数据';
		}else{
			$return['status'] = 0;
			$return['message'] = '查询成功';
			
			foreach ($res as $k => $v) {		
				//$res[$k]['image'] = img2Path($v['image']);
				$res[$k]['image'] = $v['image'];
			}
			
			$return['info'] = $res;
		}
		
		//echo json_encode($return,JSON_UNESCAPED_UNICODE);//无法处理反斜杠
		echo urldecode(json_encode(url_encode($return)));
	}
	
	//详情,该接口未使用
	public function details(){
		$return['result'] = 0;
	
		$id = I('post.id');
	
		if( empty($id) ){
			$return['status'] = 1;
			$return['message'] = '传参不完整';
		}else{
			$field = 'id,image,name,url,content';
			$res = M('e_Pictures')->field($field)->select();
			if( empty($res) ){
				$return['status'] = 1;
				$return['message'] = '没有数据';
			}else{
				$return['status'] = 0;
				$return['message'] = '查询成功';
	
				foreach ($res as $k => $v) {					
					$res[$k]['image'] = img2Path($v['image']);
				}
	
				$return['info'] = $res[0];
			}
		}
	
		echo urldecode(json_encode(url_encode($return)));
	}
}