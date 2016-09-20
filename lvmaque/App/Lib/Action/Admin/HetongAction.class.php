<?php
// 全局设置
class HetongAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
	public function index(){
		$data=M('hetong')->field(true)->limit(1)->order('id desc')->find();
		$this->assign("vo",$data);
		$this->display();
	}

	public function upload()
	{
		$model = M("hetong");
		$model->startTrans();
		if(!empty($_FILES['picpath']['name'])){
			$this->saveRule = 'uniqid';
			//$this->saveRule = date("YmdHis",time()).rand(0,1000);
			$this->savePathNew = C('ADMIN_UPLOAD_DIR').'Hetong/';
			$this->thumbMaxWidth = C('HETONG_UPLOAD_H');
			$this->thumbMaxHeight = C('HETONG_UPLOAD_W');
			$info = $this->CUpload();
			$data['hetong_img'] = $info[0]['savepath'].$info[0]['savename'];
		}
		if($data['hetong_img']) {
			$model->hetong_img=$data['hetong_img'];//合同图章
			//$model->thumb_hetong_img=$data['thumb_hetong_img'];//合同图章缩略图
		}
		/*$model->add_time=time();
		$model->deal_user=session('adminname');
		$model->name=$_POST['name'];
		$model->dizhi=$_POST['dizhi'];
		$model->tel= intval($_POST['tel']);*/
		
		$data['add_time']=time();
		$data['deal_user']=session('adminname');
		$data['name']=text($_POST['name']);
		$data['dizhi']=text($_POST['dizhi']);
		$data['tel']= text($_POST['tel']);
		
		$res=M('hetong')->field(true)->find();
		if($res==''){
			$result = $model->add($data);
		}else{
			$result = $model->where("id={$res['id']}")->save($data);
		}
		
        //保存当前数据对象
        if ($result) { //保存成功
			$model->commit();
			alogs("hetong",0,1,'合同章上传的操作成功！');//管理员操作日志
            //成功提示
            $this->assign('jumpUrl', __URL__);
            $this->success(L('上传成功'));
        } else {
			alogs("hetong",0,0,'合同章上传的操作失败！');//管理员操作日志
			$model->rollback();
            //失败提示
            $this->error(L('上传失败'));
        }
    
		
		
	}


}