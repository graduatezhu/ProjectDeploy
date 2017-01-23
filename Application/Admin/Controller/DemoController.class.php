<?php
/**
 * @ClassName: DemoController
 * @Description: todo
 * @Company: EDog
 * @author ZXD
 * @date 2017年1月23日上午11:08:09
 */
class DemoController extends CommonController {
	public function _initialize() {
		parent::_initialize(); // check users authority
		
	}
	
	/**
     * @Title: 返回DEMO数据
     * @access public
	 * @param 参数
	 * @return JSON
	 * @throws
	 */
    public function index() {
        $data['data']='this is the admin demo project';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
    }
}