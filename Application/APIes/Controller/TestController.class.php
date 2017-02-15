<?php
/**
 * @ClassName: PilesSwitch
 * @Description: 电桩充电接口
 * @Company: EDog
 * @author ZXD
 * @date 2017年2月13日上午11:08:09
 */
class TestController extends CommonController {
    
    /*声明数据表Model对象*/
//     public $tblChargTmp;
    
    /*初始化*/
	public function _initialize() {
	    
		parent::_initialize(); //调用父类成员函数
		
		A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN

		
	}
	
	public function index() {
	    set_time_limit(3);
	     $return['success'] = true;
	     
	     echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}
	
	
}