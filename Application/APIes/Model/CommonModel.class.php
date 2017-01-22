<?php
use Think\Model;
class CommonModel extends Model {
	
	/**
     * 查询操作
     * @param  array $map 查询条件
     * @param  string $limit 查询条数，默认是全部都查
     * @param  string $field 查询字段，默认是全部字段
     * @param  string $order 查询字段，默认是让数据库本身自适应
     * @param  string $join 联查
     * @param  string $group 分组
     * @param  bool $isCache 是否启用查询缓存（默认不启用）
     * @return array
     */
	public function selData($map,$limit=0,$field='*',$order='',$join='',$group='',$isCache=false){
		if(!empty($map['having'])){//having的字符串条件
			$having = $map['having'];
			unset($map['having']);
		}
		
		// $isCache = ($limit != 1) ? true : false;//查询数量 >1 的、都加缓存
		return $this->field($field)->where($map)->having($having)->order($order)->join($join)->group($group)->limit($limit)->cache($isCache)->select();
	}
	
	/**
     * 更新操作
     * @param  array $map 查询条件
     * @param  array $data 数据
     * @return bool
     */
	public function upData($map,$data){
		$re = $this->where($map)->save($data);
		if( $re === false ){//empty($re) && ( $re !== 0 )
			return false;
		}else{//int(0) 也应返回 true
			return true;
		}
	}
	
	/**
     * 插入操作
     * @param  string $data 数据
     * @return int
     */
	public function addData($data){
		return $this->data($data)->add();
	}
	
	/**
     * 删除 操作
     * @param  string $map 条件
     * @return int
     */
	public function delData($map){
		$re = $this->where($map)->delete();
		if( $re === false ){//empty($re) && ( $re !== 0 )
			return false;
		}else{//int(0) 也应返回 true
			return true;
		}
	}
	
	/**
     * 返回ID串，并自动“去重”
     * @param array 查询的结果集
     * @param string 主键的 字段名，默认是id
     * @return string ID串,如"1,2,5"
     */
	public function returnIDs($array,$field='id'){
		$ids = array();
		if(!empty($array)){
			foreach($array as $v){
				if( !in_array($v[$field],$ids) ){
					// $ids .= $v[$field].',';
					$ids[] = $v[$field];
				}
			}
		}
		
		// return trim($ids,',');
		return implode(',',$ids);
	}
	
	/**
     * 更新 数量 操作
     * @param  array $map 查询条件
     * @param  string $colunm 字段名
     * @param  string $num 要修改的数量
     * @return bool
     */
	public function setColunm($map,$colunm,$num){
		return $this->where($map)->setInc($colunm,$num);
	}
	
	/**
     * 查询数量 操作
     * @param  array $map 查询条件
     * @return int
     */
	public function getNum($map){
		return $this->where($map)->count();
	}
	
	/**
     * 查询总和 操作
     * @param array $map 查询条件
     * @param string $colunm 字段名
     * @return int
     */
	public function getSum($map,$colunm){
		$re = $this->where($map)->sum($colunm);
		return empty($re) ? 0 : $re;
	}

}
?>