<?php
/**
 * @ClassName: DemoController
 * @Description: todo
 * @Company: EDog
 * @author ZXD
 * @date 2017年1月23日上午11:08:09
 */
class DemoController extends CommonController {
    
    /*声明数据表Model对象*/
    public $tblStudent;
    public $tblGrade;
    
    /*初始化*/
	public function _initialize() {
	    
		parent::_initialize(); //调用父类成员函数
		
		A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN
		
		/*实例化模型对象*/
		$this->tblStudent=D('Student');
		$this->tblGrade=D('grade');
		
	}
	
	/**
	 * @Title: DEMO
	 * @Description: 返回测试数据
	 * @param 参数
	 * @return JSON
	 * @throws
	 */
    public function index() {
        $return['success']=true; // 接口通信成功标志
        
        /*分页处理*/
        $page = I('post.page',1,'intval');//页码，从1开始
        $pageSize = I('post.pageSize',C('PAGE_LIMIT'),'intval');//分页数
        $limit = ($page-1)*$pageSize.','.$pageSize;
        
        /*查询数据*/
        $map['id']=2;
        $field='id,name';
        $reStu=$this->tblStudent->selData($map,$limit,$field);  
        $reGrade=$this->tblGrade->selData($map,'',''); //参数列表中若有空白项，书写空串''填补空位
        
        /*返回数据*/
        $return['info']=$reStu;
        $return['data']='this is the demo project';
        echo json_encode($return,JSON_UNESCAPED_UNICODE);
    }
}