<?php
// 全局设置
class KissyAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$this->savePathNew = C('ADMIN_UPLOAD_DIR').'Article/' ;
		$this->saveRule = date("YmdHis",time());
		$info = $this->CUpload();
		$image_url = $info[0]['savepath'].$info[0]['savename'];
		//上传成功
		if($image_url){
			echo '{"status": "0", "imgUrl": "' .__APP__."/".$image_url. '"}';
		}else{
			echo '{"status": "1", "error": "'.$info['info'].'"}';
		}
    }
	
}
?>