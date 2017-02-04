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
		
		/*实例化模型对象*/
		$this->tblChargTmp=D('ChargeTmp');
		
	}
	
	/**
     * @Title: 返回DEMO数据
     * @access public
	 * @param 参数
	 * @return JSON
	 * @throws
	 */
    public function index() {
        
        /*查询数据*/
        $map['id']=1;
        $field='id,pile_no,site_no';
        $list=$this->tblChargTmp->selData($map,0,$field);
        
        $tip='Admin demo page';

        $this->assign('list', $list);
        $this->assign('tip', $tip);
        $this->display();
    }
}