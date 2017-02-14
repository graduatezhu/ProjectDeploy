<?php
/**
 * @ClassName: PilesPriceModify
 * @Description: 修改电价接口
 * @Company: EDog
 * @author ZXD
 * @date 2017年2月13日上午11:40:09
 */
class PilesPriceModifyController extends CommonController {
    
    /*声明数据表Model对象*/
//     public $tblChargTmp;
    
    /*初始化*/
	public function _initialize() {
	    
		parent::_initialize(); //调用父类成员函数
		
		A('Public')->chkPublicToken(); // 校验APP访问接口时传入的TOKEN
		
		/*实例化模型对象*/
// 		$this->tblChargTmp=D('ChargeTmp');
		
	}
	
	/**
	 * @Title: 修改指定电桩充电价格
	 * @Description: 返回修改电价结果
	 * @param string QRCode 桩二维码
	 * @param string price 新电价
	 * @return JSON
	 * @throws
	 */
	public function modify() {
	    $return['success'] = true;
	    
	    $QRCode=I('post.QRCode','','trim');
	    $price=I('post.price','','trim');
	    
	    $cmdRTNArray=modify_pile_price($QRCode,$price); // 返回命令结果的状态数组
	    
	    switch($cmdRTNArray['status']){
	        case '0':
	            $return['status']='0';
	            $return[msg]='电价修改成功';
	            break;
	        case '-1':
	            $return['status']='-1';
	            $return['code']='10201';
	            $return[msg]='电价修改成功';
	            break;
            case '-2':
                $return['status']='-1';
                $return['code']='10202';
                $return[msg]='电价修改成功';
                break;
            case '-3':
                $return['status']='-1';
                $return['code']='10203';
                $return[msg]='电价修改成功';
                break;
	    }
	    
	    echo json_encode($return,JSON_UNESCAPED_UNICODE);
	}
	
}