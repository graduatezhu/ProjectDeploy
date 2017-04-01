<?php
/**
 * @ClassName: CarQuotationController
 * @Description: 汽车报价列表/详情
 * @Company: EDog
 * @author ZXD
 * @date 2017年4月1日上午11:08:09
 */
class CarQuotationController extends CommonController {
    
    /*声明数据表Model对象*/
    private $tblQuotation;
    
    /*初始化*/
	public function _initialize() {
	    
		parent::_initialize(); //调用父类成员函数
		
		//A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN
		
		/*实例化模型对象*/
		$this->tblQuotation=D('CarsQuotation');
		
	}
	
	/**
	 * @Title: 汽车报价列表
	 * @Description: 返回测试数据
	 * @param 参数
	 * @return JSON
	 * @throws
	 */
    public function lists() {
        $return['success']=true; // 接口通信成功标志
        
        /*分页处理*/
        $page = I('post.page',1,'intval');//页码，从1开始
        $pageSize = I('post.pageSize',C('PAGE_LIMIT'),'intval');//分页数
        $limit = ($page-1)*$pageSize.','.$pageSize;
        
        /*查询数据*/
        $field='id,logo,brand';
        $re=$this->tblQuotation->selData(1,$limit,$field); //where为1默认为全部
        
        /*返回数据*/
        if(is_empty($re)){
            $return['status']=-1;
			$return['msg']='暂无数据!';
			$return['code']='10003';
        }else{
            $return['status']=0;
            $return['msg']='查询成功!';
            $return['info']=$re;
        }
        echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * @Title: 汽车报价详情
     * @Description: 返回该品牌下所有型号的报价详情
     * @param  参数
     * @return JSON
     * @throws
     */
    public function details() {
        $return['success']=true; // 接口通信成功标志
    
        /*分页处理*/
        $page = I('post.page',1,'intval');//页码，从1开始
        $pageSize = I('post.pageSize',C('PAGE_LIMIT'),'intval');//分页数
        $limit = ($page-1)*$pageSize.','.$pageSize;
    
        /*查询数据*/
        $map['brand']=I('post.brand');
        $field='id,brand,picture,model,battery_life,battery_capacity,engine,structure,quotation';
        $re=$this->tblQuotation->selData($map,$limit,$field);

        /*返回数据*/
        if(empty($re)){
            $return['status']=-1;
			$return['msg']='暂无数据!';
			$return['code']='10003';
        }else{
            $return['status']=0;
            $return['msg']='查询成功!';
            $return['info']=$re;
        }
        
        echo json_encode($return,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}