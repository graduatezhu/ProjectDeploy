<?php

class DemoController extends CommonController {
	public function _initialize() {
		parent::_initialize(); // check users authority
		
	}
    
    public function index() {
        $data['data']='this is the admin demo project';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
    }
}