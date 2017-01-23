<?php

/**
 * @ClassName: CommonController
 * @Description: APIes通用控制器
 * @Company: EDog
 * @author ZXD
 * @date 2017年1月23日上午11:08:09
 */
 
use Think\Controller;
class CommonController extends Controller {
	//初始化
	public function _initialize() {
		header("Content-Type:text/html; charset=utf-8");
		header('Content-Type:application/json; charset=utf-8');
	}
}