<?php

class UploadimgController extends CommonController {
    public function index(){
    	$this->display();
    }


    //用户身份验证图片上传接口
    public function upload(){
    	//首先接收传来的信息
       	@$id=isset($_POST['userID'])?trim($_POST['userID']):false;
        @$idcard_a=isset($_POST['IDcard_a'])?$_POST['IDcard_a']:false;
       	@$idcard_b=isset($_POST['IDcard_b'])?$_POST['IDcard_b']:false;
        @$dlcard_a=isset($_POST['DLcard_a'])?$_POST['DLcard_a']:false;
       	//@$dlcard_b=isset($_POST['DLcard_b'])?$_POST['DLcard_b']:false;

		@$data=[];//声明返回信息的数组

		//先判断传参是否完整
       	if($id && $idcard_a && $idcard_b &&  $dlcard_a){

       		//若此用户的图片目录不存在 则创建
       		if(!file_exists("./Public/upload_pic/{$id}")){
			  	mkdir("./Public/upload_pic/{$id}");
			}
			//base64解码(需要没有头信息的base64图片转码)
          $idcard_a=base64_decode($idcard_a);
       		$idcard_b=base64_decode($idcard_b);
          $dlcard_a=base64_decode($dlcard_a);
       		//$dlcard_b=base64_decode($dlcard_b);
       		$time=time();//时间

			//身份证图片 保存
          $idcard_res_a=file_put_contents("Public/upload_pic/{$id}/{$time}id_a.jpg",$idcard_a);
       		$idcard_res_b=file_put_contents("Public/upload_pic/{$id}/{$time}id_b.jpg",$idcard_b);
       		
       		//驾照图片保存
          $dlcard_res_a=file_put_contents("Public/upload_pic/{$id}/{$time}dl_a.jpg",$dlcard_a);
       		//$dlcard_res_b=file_put_contents("Public/upload_pic/{$id}/{$time}dl_b.jpg",$dlcard_b);

       		//图片保存成功 继续吧路径写入数据库
       		if($dlcard_res_a && $idcard_res_b && $idcard_res_a){

       			//把要存的字段付给post 方便进行tp的create方法
       			$_POST['upload_time']=$time;
       			$_POST['verified']=1;
            $_POST['id_card_image']="/Public/upload_pic/{$id}/{$time}id_a.jpg";
       			$_POST['id_card_image_b']="/Public/upload_pic/{$id}/{$time}id_b.jpg";
            $_POST['driving_image']="/Public/upload_pic/{$id}/{$time}dl_a.jpg";
       			//$_POST['driving_image_b']="/Public/upload_pic/{$id}/{$time}dl_b.jpg";
       			
       			//实例化model类
       			$img=M("e_members");
       			//创建数据对象
       			$res=$img->create($_POST);

       			//进行数据更新并且判断
       			if($img->where("id={$id}")->save()){
       				$data["result"]=0;
			       	$data["status"]=0;
			       	$data["message"]="上传成功";

			       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


			       	echo $data;
			       	die;
       			}else{
       				$data["result"]=1;
			       	$data["status"]=1;
			       	$data["message"]="数据库写入失败";

			       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


			       	echo $data;
			       	die;
       			}


       			
       		}else{
       			//图片保存失败 返回错误信息
       			$data["result"]=1;
		       	$data["status"]=1;
		       	$data["message"]="图片保存失败";

		       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


		       	echo $data;
		       	die;
       		}


       	}else{

       		//传入的参数不完整 返回不完整的数据
	       	$data["result"]=1;
	       	$data["status"]=1;
	       	$data["message"]="传参不完整";

	       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


	       	echo $data;
	       	die;

       	}
    }


    //用车前上传
    public function upload_photo(){
    	//首先接收传来的信息
       	@$id=isset($_POST['userID'])?trim($_POST['userID']):false;
       	@$photo[]=isset($_POST['photo1'])?$_POST['photo1']:false;
       	@$photo[]=isset($_POST['photo2'])?$_POST['photo2']:false;
       	@$photo[]=isset($_POST['photo3'])?$_POST['photo3']:false;
       	@$photo[]=isset($_POST['photo4'])?$_POST['photo4']:false;

		@$data=[];//声明返回信息的数组

		//先判断传参是否完整
       	if($id && $photo[0]){

       		//若此用户的图片目录不存在 则创建
       		if(!file_exists("./Public/upload_pic/{$id}_photo")){
			  	mkdir("./Public/upload_pic/{$id}_photo");
			}

       		$time=time();//时间
       		foreach($photo as $k=>$v){
       			if($v){
	       			$savephoto=base64_decode($v);
					//图片 保存
		       		$k=$k+1;
              $savephoto=file_put_contents("Public/upload_pic/{$id}_photo/{$time}_id_{$k}.jpg",$savephoto);
              //进行create的数组
              $photo_info["pic{$k}"]="/Public/upload_pic/{$id}_photo/{$time}_id_{$k}.jpg";          
       			}
       		}



       		

   			//把要存的字段付给photo_info 方便进行tp的create方法
   			$photo_info['add_time']=$time;
   			$photo_info['user_id']=$id;

   			//实例化model类
   			$img=M("e_zc_car_photo");
   			//创建数据对象
   			$res=$img->create($photo_info);

   			//进行数据更新并且判断
   			if($img->add($photo_info)){
   				$data["result"]=0;
		       	$data["status"]=0;
		       	$data["message"]="上传成功";

		       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


		       	echo $data;
		       	die;
   			}else{
   				$data["result"]=1;
		       	$data["status"]=1;
		       	$data["message"]="数据库写入失败";

		       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


		       	echo $data;
		       	die;
   			}


       	}else{

       		//传入的参数不完整 返回不完整的数据
	       	$data["result"]=1;
	       	$data["status"]=1;
	       	$data["message"]="传参不完整";

	       	$data=json_encode($data,JSON_UNESCAPED_UNICODE);


	       	echo $data;
	       	die;

       	}
    }



}