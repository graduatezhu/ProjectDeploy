<?php

class DemoController extends CommonController {
	public function _initialize() {
		parent::_initialize(); // invoke parent class' member methods
		A('Public')->chkPublicToken(); // check the public token for APP accessing
	}
    
    public function index() {
        $data['data']='this is the demo project';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
    }
}