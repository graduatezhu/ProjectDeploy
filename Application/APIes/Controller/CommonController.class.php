<?php
// namespace Home\Controller;
use Think\Controller;
class CommonController extends Controller {
	/**
	 * 初始化
	 */
	public function _initialize() {
		
		header("Content-Type:text/html; charset=utf-8");
		header('Content-Type:application/json; charset=utf-8');
	}
}