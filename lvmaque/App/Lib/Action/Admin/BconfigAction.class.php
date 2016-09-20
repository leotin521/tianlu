<?php
header("Content-type:text/html;charset=utf-8");
	// 全局设置
class BconfigAction extends ACommonAction
{
    /**
    +----------------------------------------------------------
    * 默认操作
    +----------------------------------------------------------
    */
    public function index()
    {
		$integration = FS("Webconfig/integration");
		$this->assign('integration', $integration);
		
		$version = FS("Webconfig/version");
		$this->assign('version', $version);
		
		$borrowconfig = FS("Webconfig/borrowconfig");
		
		$bc=array_values($borrowconfig);
		$buse=$borrowconfig['BORROW_USE'];
		$bmin=$borrowconfig['BORROW_MIN'];
		$bmax=$borrowconfig['BORROW_MAX'];
		$btime=$borrowconfig['BORROW_TIME'];
		//$brepa=$borrowconfig['REPAYMENT_TYPE'];
		//$btype=$borrowconfig['BORROW_TYPE'];
		//$breward=$borrowconfig['IS_REWARD'];
		//$bstatus=$borrowconfig['BORROW_STATUS'];
		$bsearch=$borrowconfig['MONEY_SEARCH'];
		$bdatatype=$borrowconfig['DATA_TYPE'];
		$bbankname=$borrowconfig['BANK_NAME'];
		
		
		
		$this->assign('buse',$buse);
		$this->assign('bmin',$bmin);
		$this->assign('bmax',$bmax);
		$this->assign('btime',$btime);
		//$this->assign('brepa',$brepa);
		//$this->assign('btype',$btype);
		//$this->assign('breward',$breward);
		//$this->assign('bstatus',$bstatus);
		$this->assign('bsearch',$bsearch);
		$this->assign('bdatatype',$bdatatype);
		$this->assign('bbankname',$bbankname);
        $this->display();
    }
    public function save()
    {
		
		function array_combines($arr){
		$avv=array();
		$auu=array();
		
		foreach($arr as $key=>$v){
			if($v===''){
				exit('<script> alert(\'填入数据不能为空\'); window.location.href="/admin/Bconfig/index";</script>');
				
			}
			if($key%2==0){
			$avv[]=$v;
				if(count(array_unique(array_values(array_count_values($avv))))>1){

					//dump($avv);
					exit('<script> alert("该值已存在，参数不允许重复！"); window.location.href="/admin/Bconfig/index";</script>');
					
				}
			}else{
				if(count(array_unique(array_values(array_count_values($avv))))>1){
					
					exit('<script> alert("该值已存在，参数不允许重复！"); window.location.href="/admin/Bconfig/index";</script>');
				}
			$auu[]=$v;
			}
		}
		$amm=array_combine($avv,$auu);
		return $amm;
		}
		
        //判断是否上传图片
		if (isset($_POST['isupload']) && $_POST['isupload']==1){
		    if (session("UPLOADFILE")!="YES"){
		        exit('<script> alert("新添加的银行卡需要上传图标！"); window.location.href="/admin/Bconfig/index";</script>');
		    }
		}

		$arr1=filter_only_array($_POST['borrow']['BORROW_USE']);
		$_POST['borrow']['BORROW_USE']=array_combines($arr1);

		$arr2=filter_only_array($_POST['borrow']['BORROW_MIN']);
		$_POST['borrow']['BORROW_MIN']=array_combines($arr2);
		
		$arr3=filter_only_array($_POST['borrow']['BORROW_MAX']);
		$_POST['borrow']['BORROW_MAX']=array_combines($arr3);
		
		$arr4=filter_only_array($_POST['borrow']['BORROW_TIME']);
		$_POST['borrow']['BORROW_TIME']=array_combines($arr4);
		
		$arr9=filter_only_array($_POST['borrow']['MONEY_SEARCH']);
		$_POST['borrow']['MONEY_SEARCH']=array_combines($arr9);
		
		$arr10=filter_only_array($_POST['borrow']['DATA_TYPE']);
		$_POST['borrow']['DATA_TYPE']=array_combines($arr10);
		
		$arr11=filter_only_array($_POST['borrow']['BANK_NAME']);
		$_POST['borrow']['BANK_NAME']=array_combines($arr11);
		
		$data = $_POST['integration'];
		$result = array();
		foreach($data as $k=>$v){
		    $result[$k] = filter_only_array($data[$k]);
		}
		
		FS("borrowconfig",$_POST['borrow'],"Webconfig/");
        
        $integration = $this->integration_array($result);
		FS("integration",$integration,"Webconfig/"); 
	
		$this->success("操作成功",__URL__."/index/");
    }
	
	 /**
    * 将多维数组合并
    * 
    * @param mixed $arr
    */
    private function  integration_array($arr)
    {
        if(!is_array($arr['parameter'])){
            return false;
        } 
        foreach($arr['parameter'] as $key=>$val){
            if(empty($val)){
                continue;
            }
            $array[$val]['fraction'] = $arr['fraction'][$key]; 
             $array[$val]['utype'] = $arr['utype'][$key]; 
            $array[$val]['description'] = $arr['description'][$key]; 
        }
        
        return $array;
    }
    
    /**
     * 
     */
    public function ajaximg(){
        $max_file_size = 2000000 ;     //上传文件大小限制, 单位BYTE
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $uptypes = array(
                'image/jpg',
                'image/jpeg',
                'image/png',
                'image/gif',
            );
            if (!is_uploaded_file($_FILES['file']['tmp_name'])) //是否存在文件
            {
                ajaxmsg('图片不存在~',0);
            }
            $file = $_FILES["file"];
            if($max_file_size < $file["size"]) //检查文件大小
            {
                ajaxmsg('文件太大~',0);
            }
            if(!in_array($file["type"], $uptypes))  //检查文件类型
            {
                ajaxmsg('文件类型不符~'.$file["type"],0);
            }
            $ckimage = getimagesize($_FILES['file']['tmp_name']);
            if($ckimage[0] > 143) //检查图片宽度
            {
                ajaxmsg('文件尺寸限制为143*38~',0);
            }
            if($ckimage[1] > 38) //检查图片高度
            {
                ajaxmsg('文件尺寸限制为143*38~',0);
            }
            $destination_folder = "Style/M/images/bank_/";
            if(!file_exists($destination_folder)) mkdir('./'.$destination_folder,0777,true);
            $file_pre = intval($_POST['title']);
            $destination = $destination_folder.$file_pre.".png";
            if(!move_uploaded_file ($_FILES['file']['tmp_name'], $destination))
            {
                ajaxmsg('移动文件出错~',0);
            }else{
                session("UPLOADFILE","YES");
            }
        }
    }
}
?>