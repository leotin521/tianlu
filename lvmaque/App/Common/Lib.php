<?php

 /**
    * 授权记录
    * 
    * @author  fanyelei
    * @time 2015-01-17 15:07
    * @copyright lvmaque
    * @link www.lvmaque.com
    */
function lvmaqueinfo(){
    $str="<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<center>
	<fieldset>
    <legend style='width: 300px;background-color: #FEEEED; text-align: center;margin-left: 40%; padding: 5px;border: 1px solid #FF7900;font-size:14px;color:green'>绿麻雀授权信息查询</legend>
		<div style='width: 700px;background-color: #FEEEED; line-height: 20px; text-align: left; padding: 5px 0px 5px 5px;border: 1px solid #FF7900;margin: 10px 0 10px 35px;font-size:12px;height: 100%; overflow: hidden; '>
		授权域名：	localhost、***.**.**.***、127.0.0.1、www.***.com<br>
		最后修改时间：2015-01-17<br>
		最后修改人： ###<br>
		授权时间：    2015-01-17  <br>
		授权到期时间：2015-02-10 15:00 <br>
		授权人：		###<br/>
		</div>
	</fieldset>
	</center>
	";
	exit($str); 
}
//对提交的参数进行过滤
function EnHtml($v){
	return htmlspecialchars($v, ENT_QUOTES);
}
function mydate($format,$time,$default=''){
	if(intval($time)>10000) return date($format,$time);	
	else return $default;
}
function textPost($data){
	if(is_array($data)){
		foreach($data as $key => $v){
			$x[$key]=text($v);
		}
	}
	return $x;
}


/*$url：要生成的地址,$vars:参数数组,$domain：是否带域名*/
function MU($url,$type,$vars=array(),$domain=false){
	//获得基础地址START
	$path = explode("/",trim($url,"/"));
	$model = strtolower($path[1]);
	$action = isset($path[2])?strtolower($path[2]):"";
	//获得基础地址START
	//获取前缀根目录及分组
	$http = UD($path,$domain);
	//获取前缀根目录及分组
	switch($type){
		case "article":
		default:
			if(!isset($vars['id'])){//特殊栏目,用nid来区分,不用ID
				unset($path[0]);//去掉分组名
				$url = implode("/",$path)."/";
				$newurl=$url;
			}else{//普通栏目,带ID
				if(1==1||strtolower(GROUP_NAME) == strtolower(C('DEFAULT_GROUP'))) {//如果是默认分组则去掉分组名
					unset($path[0]);//去掉分组名
					$url = implode("/",$path)."/";
				}
				$newurl=$url.$vars['id'].$vars['suffix'];
			}
		break;
		case "typelist":
				if(1==1||strtolower(GROUP_NAME) == strtolower(C('DEFAULT_GROUP'))) {//如果是默认分组则去掉分组名
					unset($path[0]);//去掉分组名
					$url = implode("/",$path);
				}
				$newurl=$url.$vars['suffix'];
		break;
	}
	
	return $http.$newurl;
	
}
// URL组装 支持不同模式
// 格式：UD('url参数array('分组','model','action')','显示域名')在传入的url数组中，只用到分组
function UD($url=array(),$domain = false) {
    // 解析URL
	$isDomainGroup = true;//当值为true时,不对任何链接加分组前缀,当为false时,自动判断分组及域名等,加前缀
	$isDomainD = false;
	$asdd = C('APP_SUB_DOMAIN_DEPLOY');
	//###########修复START#############，增加对当前分组分配了二级域名的判断,变量给下面用
	if($asdd){
		foreach (C('APP_SUB_DOMAIN_RULES') as $keyr => $ruler) {
			if(strtolower($url[0]."/") == strtolower($ruler[0])){
				$isDomainGroup = true;//分组分配了二级域名
				$isDomainD = true;
				break;
			}
		}
	}

	//#########及默认分组不需要加分组名 都转换成小写来比较，避免在linux上出问题
	if(strtolower(GROUP_NAME) == strtolower(C('DEFAULT_GROUP'))) $isDomainGroup = true;
	//###########修复END#############，增加对当前分组分配了二级域名的判断
    // 解析子域名
    if($domain===true){
        $domain = $_SERVER['HTTP_HOST'];
        if($asdd) { // 开启子域名部署
			//###########修复START#############，增加对没带前缀域名的判断
			$xdomain = explode(".",$_SERVER['HTTP_HOST']);
			if(!isset($xdomain[2])) $ydomain="www.".$_SERVER['HTTP_HOST'];
			else  $ydomain=$_SERVER['HTTP_HOST'];
			//###########修复END#############，增加对没带前缀域名的判断
            $domain = $domain=='localhost'?'localhost':'www'.strstr($ydomain,'.');
            // '子域名'=>array('项目[/分组]');
            foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                if(false === strpos($key,'*') && $isDomainD) {
                    $domain = $key.strstr($domain,'.'); // 生成对应子域名
                    $url   =  substr_replace($url,'',0,strlen($rule[0]));
                    break;
                }
            }
        }
    }
	
	if(!$isDomainGroup) $gpurl = __APP__."/".$url[0]."/";
	else $gpurl = __APP__."/";

    if($domain) {
        $url   =  'http://'.$domain.$gpurl;
    }else{
        $url   =  $gpurl;
	}

	return $url;
}

function Mheader($type){
	header("Content-Type:text/html;charset={$type}"); 
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from='gbk', $to='utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if ( ($to=='utf-8'&&is_utf8($fContents)) || strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else {
        return $fContents;
    }
}
//判断是否utf8
function is_utf8($string) {
	return preg_match('%^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
}

//获取日期
/*			case "yesterday";
				$date = date("Y-m-d",$now_time);//d,w,m分别表示天，周，月,后面的第三个参数选填，正数1表示后一天(d)的00:00:00到23:59:59负数表示前一天(d),-2表示前面第二天的00:00:00到23:59:59
				$day = get_date($date,'d',-1);//第三个参数表示时间段包含的天数
			break;
*/
function get_date($date,$t='d',$n=0){
	if($t=='d'){
		$firstday = date('Y-m-d 00:00:00',strtotime("$n day"));
		$lastday = date("Y-m-d 23:59:59",strtotime("$n day"));
	}elseif($t=='w'){
		if($n!=0){$date = date('Y-m-d',strtotime("$n week"));}
		$lastday = date("Y-m-d 23:59:59",strtotime("$date Sunday"));
		$firstday = date("Y-m-d 00:00:00",strtotime("$lastday -6 days"));
	}elseif($t=='m'){
		if($n!=0){
			if(date("m",time())==1) $date = date('Y-m-d',strtotime("$n months -1 day"));//2特殊的2月份
			else $date = date('Y-m-d',strtotime("$n months"));
		}
		
		$firstday = date("Y-m-01 00:00:00",strtotime($date));
		$lastday = date("Y-m-d 23:59:59",strtotime("$firstday +1 month -1 day"));
	}
	return array($firstday,$lastday);

}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
 
 
function rand_string($ukey="",$len=6,$type='1',$utype='1',$addChars='') {
    $str ='';
    switch($type) {
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 1:
            $chars= str_repeat('0123456789',3);
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
    }
    if($len>10 ) {//位数过长重复字符串一定次数
        $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
    }
    $chars   =   str_shuffle($chars);
    $str     =   substr($chars,0,$len);
	if(!empty($ukey)){
		$vd['code'] = $str;
		$vd['send_time'] = time();
		$vd['ukey'] = $ukey;
		$vd['type'] = $utype;
		M('verify')->add($vd);
	}
    return $str;
}

//验证是否通过
function is_verify($uid,$code,$utype,$timespan){
	if(!empty($uid)) $vd['ukey'] = $uid;
	$vd['type'] = $utype;
	$vd['send_time'] = array("gt",time()-$timespan);
	$vd['code'] = $code;
	$vo = M("verify")->field('ukey')->where($vd)->find();
	if(is_array($vo)) return $vo['ukey'];
	else return false;
}
/**
 * 网站基本设置
 * @param null $code
 * @return array|mixed|string
 */
function get_global_setting($code = null){
    $ret = false;
	$list=array();
	if(!S('global_setting')){
		$list_t = M('global')->field('code,text')->select();
		foreach($list_t as $key => $v){
			$list[trim($v['code'])] = de_xie($v['text']);//`mxl:teamreward`
		}
		S('global_setting',$list);
		S('global_setting',$list,3600*C('TTXF_TMP_HOUR'));
	}else{
		$list = S('global_setting');
	}
    if( isset($code) ) {
        if( isset($list[$code]) ) {
            if( strpos($list[$code], '|')) {
                $ret = explode('|', $list[$code]);
            }else {
                $ret = $list[$code];
            }
        }
    } else {
        $ret = $list;
    }
	
	return $ret;
}
//acl权限管理
/*
print_r(acl_get_key(array('global','data','eqaction_edit'),$acl_inc));
*/


//获取用户权限数组
function get_user_acl($uid=""){
	$model=strtolower(MODULE_NAME);

	if(empty($uid)) return false;
	$gid = M('ausers')->field('u_group_id')->find($uid);
	
	$al = get_group_data($gid['u_group_id']);
	
	$acl = $al['controller'];
	$acl_key = acl_get_key();
	
	if( array_keys($acl[$model],$acl_key) ) return true;
	else return false;
}

//获取权限列表
function get_group_data($gid=0){
	$gid=intval($gid);
	$list=array();
	
	if($gid==0){
		if( !S("ACL_all") ){
			$_acl_data = M('acl')->select();
			$acl_data=array();
			
			foreach($_acl_data as $key => $v){
				$acl_data[$v['group_id']] = $v;
				$acl_data[$v['group_id']]['controller'] = unserialize($v['controller']);
			}
			
			S("ACL_all",$acl_data,C('ADMIN_CACHE_TIME')); 
			$list = $acl_data;
		}else{
			$list = S("ACL_all");
		}
	}else{
		if( !S("ACL_".$gid) ){
			$_acl_data = M('acl')->find($gid);
			$_acl_data['controller'] = unserialize($_acl_data['controller']);
			$acl_data = $_acl_data;
			S("ACL_".$gid,$acl_data,C('ADMIN_CACHE_TIME')); 
			$list = $acl_data;
		}else{
			$list = S("ACL_".$gid);
		}
	}
	return $list;
}
//删除文件夹并重建文件夹
function rmdirr($dirname) {

	if (!file_exists($dirname)) {
		return false;
	}

	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}

	$dir = dir($dirname);

	while (false !== $entry = $dir->read()) {

		if ($entry == '.' || $entry == '..') {
			continue;
		}

		rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
	}

	$dir->close();

	return rmdir($dirname);
}
//删除文件夹及文件夹下所有内容
function Rmall($dirname) {
	if (!file_exists($dirname)) {
		return false;
	}
	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}

	$dir = dir($dirname);//如果对像是目录

	while (false !== $file = $dir->read()) {

		if ($file == '.' || $file == '..') {
			continue;
		}
		if(!is_dir($dirname."/".$file)){
			unlink($dirname."/".$file);
		}else{
			Rmall($dirname."/".$file);
		}
		
		rmdir($dirname."/".$file);
	}

	$dir->close();
	
	rmdir($dirname);

	return true;
}

//取得文件内容
function ReadFiletext($filepath){
	$htmlfp=@fopen($filepath,"r");
	while($data=@fread($htmlfp,1000))
	{
		$string.=$data;
	}
	@fclose($htmlfp);
	return $string;
}

//生成文件
function MakeFile($con,$filename){//$filename是全物理路径加文件名
	MakeDir(dirname($filename));
	$fp=fopen($filename,"w");
	fwrite($fp,$con);
	fclose($fp);
}

//生成全路径文件夹
function MakeDir($dir){
	return is_dir($dir) or (MakeDir(dirname($dir)) and mkdir($dir,0777));
}

//友情链接
function get_home_friend($type,$datas = array()){
	$condition['is_show']=array('eq',1);
	
	$condition['link_type']=array('eq',$type);
	$type = "friend_home".$type;


	if(!S($type)){
		$_list = M('friend')->field('link_txt,link_href,link_img,link_type')->where($condition)->order("link_order DESC")->select();
		$list=array();
		foreach($_list as $key => $v){
			$list[$key] = $v;
		}
		S($type,$list,3600*C('HOME_CACHE_TIME')); 
	}else{
		$list = S($type);
	}
	
	return $list;
}

/*
栏目相关函数
Start
*/

//获取某栏目下的所有子栏目以nid-nid顺次链接
function get_type_leve($id="0"){
	$model = D('Acategory');
	if(!S("type_son_type")){
		$allid=array();
		$data = $model->field('id,type_nid')->where("parent_id = {$id}")->select();
		if(count($data)>0){
			foreach($data as $v){
				//二级
				$allid[$v['type_nid']]=$v['id'];
				$data_1=array();//清空,避免下面判断错误
				$data_1 = $model->field('id,type_nid')->where("parent_id = {$v['id']}")->select();
				if(count($data_1)>0){
					foreach($data_1 as $v1){
						//三级
						$allid[$v['type_nid']."-".$v1['type_nid']]=$v1['id'];
						$data_2=array();//清空,避免下面判断错误
						$data_2 = $model->field('id,type_nid')->where("parent_id = {$v1['id']}")->select();
						if(count($data_2)>0){
							foreach($data_2 as $v2){
								//四级
								$allid[$v['type_nid']."-".$v1['type_nid']."-".$v2['type_nid']]=$v2['id'];
								$data_3=array();//清空,避免下面判断错误
								$data_3 = $model->field('id,type_nid')->where("parent_id = {$v2['id']}")->select();
	
								if(count($data_3)>0){
									foreach($data_3 as $v3){
										$allid[$v['type_nid']."-".$v1['type_nid']."-".$v2['type_nid']."-".$v3['type_nid']]=$v3['id'];
									}
								}
								//四级
							}
						}
						//三级
					}
				}
				//二级
			}
	
		}
		S("type_son_type",$allid,3600*C('HOME_CACHE_TIME')); 
	}else{
		$allid = S("type_son_type");
	}
	
	return $allid;
}


//获取某栏目下的所有子栏目以nid-nid顺次链接
function get_area_type_leve($id="0",$area_id=0){

	$model = D('Aacategory');
	if(!S("type_son_type_area".$area_id)){
		$allid=array();
		$data = $model->field('id,type_nid')->where("parent_id = {$id} AND area_id={$area_id}")->select();
		if(count($data)>0){
			foreach($data as $v){
				//二级
				$allid[$area_id.$v['type_nid']]=$v['id'];
				$data_1=array();//清空,避免下面判断错误
				$data_1 = $model->field('id,type_nid')->where("parent_id = {$v['id']}")->select();
				if(count($data_1)>0){
					foreach($data_1 as $v1){
						//三级
						$allid[$area_id.$v['type_nid']."-".$v1['type_nid']]=$v1['id'];
						$data_2=array();//清空,避免下面判断错误
						$data_2 = $model->field('id,type_nid')->where("parent_id = {$v1['id']}")->select();
						if(count($data_2)>0){
							foreach($data_2 as $v2){
								//四级
								$allid[$area_id.$v['type_nid']."-".$v1['type_nid']."-".$v2['type_nid']]=$v2['id'];
								$data_3=array();//清空,避免下面判断错误
								$data_3 = $model->field('id,type_nid')->where("parent_id = {$v2['id']}")->select();
	
								if(count($data_3)>0){
									foreach($data_3 as $v3){
										$allid[$area_id.$v['type_nid']."-".$v1['type_nid']."-".$v2['type_nid']."-".$v3['type_nid']]=$v3['id'];
									}
								}
								//四级
							}
						}
						//三级
					}
				}
				//二级
			}
	
		}
		S("type_son_type_area".$area_id,$allid,3600*C('HOME_CACHE_TIME')); 
	}else{
		$allid = S("type_son_type_area".$area_id);
	}
	return $allid;
}

//获取某栏目的所有父栏目的type_nid,按由远到近的顺序出现在数组中1/2
function get_type_leve_nid($id="0"){
	if(empty($id)) return;
	global $allid;
	static $r=array();//先声明要返回静态变量,不然在下面被赋值时是引用赋值
	get_type_leve_nid_run($id);
	
	$r = $allid;
	$GLOBALS['allid'] = NULL;
	
	return array_reverse($r);
}
//获取某栏目的所有父栏目的type_nid,按由远到近的顺序出现在数组中2/2
function get_type_leve_nid_run($id="0"){
	global $allid;
	$data_parent = $data = "";
	$data = D('Acategory')->field('parent_id,type_nid')->find($id);
	$data_parent = D('Acategory')->field('id,type_nid')->where("id = {$data['parent_id']}")->find();
	if(isset($data_parent['type_nid'])>0){
		if(!isset($allid[0])) $allid[]=$data['type_nid'];
		$allid[]=$data_parent['type_nid'];
		get_type_leve_nid_run($data_parent['id']);
	}else{
		if(!isset($allid[0])) $allid[]=$data['type_nid'];
	}
}


//获取某栏目的所有父栏目的type_nid,按由远到近的顺序出现在数组中1/2
function get_type_leve_area_nid($id="0",$area_id=0){
	if(empty($id)||empty($area_id)) return;
	global $allid_area;
	static $r=array();//先声明要返回静态变量,不然在下面被赋值时是引用赋值

	get_type_leve_area_nid_run($id);
	
	$r = $allid_area;
	$GLOBALS['allid_area'] = NULL;
	
	return array_reverse($r);
}
//获取某栏目的所有父栏目的type_nid,按由远到近的顺序出现在数组中2/2
function get_type_leve_area_nid_run($id="0"){
	global $allid_area;
	$data_parent = $data = "";
	$data = D('Aacategory')->field('parent_id,type_nid,area_id')->find($id);
	$data_parent = D('Aacategory')->field('id,type_nid,area_id')->where("id = {$data['parent_id']}")->find();
	if(isset($data_parent['type_nid'])>0){
		if(!isset($allid_area[0])) $allid_area[]=$data['type_nid'];
		$allid_area[]=$data_parent['type_nid'];
		get_type_leve_area_nid_run($data_parent['id']);
	}else{
		if(!isset($allid_area[0])) $allid_area[]=$data['type_nid'];
	}
}

//获取某栏目下的所有子栏目,查询次数较少，查询效率更高,入口函数1/2
function get_son_type($id){
	$tempname = "type_sfs_son_all".$id;
	if(!S($tempname)){
		$row = get_son_type_run($id);
		S($tempname,$row,3600*C('HOME_CACHE_TIME')); 
	}else{
		$row = S($tempname);
	}
	return $row;
}

//获取某栏目下的所有子栏目,查询次数较少，查询效率更高2/2
function get_son_type_run($id){
	static $rerow;
	global $allid;
	$data = M('type')->field('id')->where("parent_id in ({$id})")->select();
	if(count($data)>0){
		foreach($data as $key=>$v){
			$allid[]=$v['id'];
			$nowid[]=$v['id'];
		}
		$id = implode(",",$nowid);
		get_son_type_run($id);
	}
//递归函数不要加else来返回内容，不然得不到返回值
//	else{
//		return $allid;
//	}
	$rerow = $allid;
	$allid=array();
	return $rerow;
}

//获取某栏目下所有的子栏目,以数组的形式返回,入口函数1/2
function get_type_son($id=0){
	$tempname = "type_son_all".$id;
	if(!S($tempname)){
		$row = get_type_son_all($id);
		S($tempname,$row,3600*C('HOME_CACHE_TIME')); 
	}else{
		$row = S($tempname);
	}
	return $row;
}
//获取某栏目下所有的子栏目2/2
function get_type_son_all($id="0"){
	static $rerow;
	global $get_type_son_all_row;
	
	if(empty($id)) exit;
	
	$data = M('type')->where("parent_id = {$id}")->field('id')->select();
	foreach($data as $key=>$v){
		$get_type_son_all_row[]=$v['id'];
		$data_son = M('type')->where("parent_id = {$v['id']}")->field('id')->select();
		if(count($data_son)>0){
			get_type_son_all($v['id']);
		}
	}
	
	$rerow = $get_type_son_all_row;
	$get_type_son_all_row = array();
	return $rerow;
}
//获取所有栏目每个栏目的父栏目的nid,以栏目ID为键名
function get_type_parent_nid(){
	$row=array();
	$p_nid_new=array();
	if(!S("type_parent_nid_temp")){
		$data = M('type')->field('id')->select();
		if(count($data)>0){
			foreach($data as $key => $v){
				$p_nid = get_type_leve_nid($v['id']);
				$i=$n=count($p_nid);
				//倒序处理
				if($i>1){
					for($j=0;$j<$n;$j++,$i--){
						$p_nid_new[($i-1)]=$p_nid[$j];
					}
				}else{
					$p_nid_new = $p_nid;
				}
				//倒序处理
				$row[$v['id']] = $p_nid_new;
			}
		}
		S("type_parent_nid_temp",$row,3600*C('HOME_CACHE_TIME')); 
	}else{
		$row = S("type_parent_nid_temp");
	}
	
	return $row;
}

//获取以栏目ID为键的所有栏目数组,二维,如果field只有两个，并且其中一个是id，那么就会自动成为一维数组
function get_type_list($model,$field=true){
	$acaheName=md5("type_list_temp".$model.$field);
	if(!S($acaheName)){
		$list = D(ucfirst($model))->getField($field);
		S($acaheName,$list,3600*C('HOME_CACHE_TIME')); 
	}else{
		$list = S($acaheName);
	}
	return $list;
}

//通过网址获取栏目相关信息
function get_type_infos(){
	$row=array();
	$type_list = get_type_list('acategory','id,type_nid,type_set');
	if(!isset($_GET['typeid'])){
		$type_nid = get_type_leve();//获得所有栏目自己的nid的组合 
		$rurl = explode("?",$_SERVER['REQUEST_URI']); 
		$xurl_tmp = explode("/",str_replace(array("index.html",".html"),array('',''),$rurl[0]));//获得组合的type_nid
		$zu = implode("-",array_filter($xurl_tmp));//组合
		//print_r($type_nid);
		$typeid = $type_nid[$zu];
		$typeset = $type_list[$typeid]['type_set'];
	}else{
		$typeid = intval($_GET['typeid']);
		$typeset = $type_list[$typeid]['type_set'];
	}

	if($typeset==1){//列表
		$templet = "list_index";
	}else{//单页
		$templet = "index_index";
	}
	
	$row['typeset'] = $typeset;
	$row['templet'] = $templet;
	$row['typeid'] = $typeid;
	
	return $row;
}

//通过网址获取栏目相关信息
function get_area_type_infos($area_id=0){
	$row=array();
	$type_list = get_type_list('aacategory','id,type_nid,type_set,area_id');
	if(!isset($_GET['typeid'])){

		$type_nid = get_area_type_leve(0,$area_id);//获得所有栏目自己的nid的组合 
		$rurl = explode("?",$_SERVER['REQUEST_URI']); 
		$xurl_tmp = explode("/",str_replace(array("index.html",".html"),array('',''),$rurl[0]));//获得组合的type_nid
		$zu = implode("-",array_filter($xurl_tmp));//组合
		//print_r($type_nid);
		$typeid = $type_nid[$area_id.$zu];
		$typeset = $type_list[$typeid]['type_set'];
	}else{
		$typeid = intval($_GET['typeid']);
		$typeset = $type_list[$typeid]['type_set'];
	}

	if($typeset==1){//列表
		$templet = "list_index";
	}else{//单页
		$templet = "index_index";
	}
	
	$row['typeset'] = $typeset;
	$row['templet'] = $templet;
	$row['typeid'] = $typeid;
	
	return $row;
}

//获取栏目列表,按栏目分级,有缩进,入口函数1/2
function get_type_leve_list($id=0,$modelname=false, $type){
	static $rerow;
	global $get_type_leve_list_run_row;


	if(!$modelname) $model = D("type");
	else $model=D(ucfirst($modelname));
	$stype = $modelname."home_type_leve_list".$id;
	if(!S($stype)){
		get_type_leve_list_run($id,$model, $type);
		$rerow = $get_type_leve_list_run_row;//把全局变量赋值给静态变量，避免引用清空
		$GLOBALS['get_type_leve_list_run_row']=NULL;//清空全局变量避免影响其他数据,不能用unset,unset只能清空单个变量或者数组中的某一元素,并且unset只能清空局部变量，清空全局变量要用unset($GLOBALS
		$data = $rerow;
		//S($stype,$data,3600*C('HOME_CACHE_TIME')); 
	}else{
		$data = S($stype);
	}
	return $data;
}

//获取栏目列表,按栏目分级,有缩进2/2
function get_type_leve_list_run($id=0,$model, $type){
	global $get_type_leve_list_run_row;
	//全局变量的定义都要放在最前面
	$spa = "----";
	if(count($get_type_leve_list_run_row)<1) $get_type_leve_list_run_row=array();

	$typelist = $model->where("parent_id={$id} and model='{$type}'")->field('type_name,id,parent_id')->order('sort_order DESC')->select();//上级栏目

	foreach($typelist as $k=>$v){
		$leve = intval(get_typeLeve($v['id'],$model));
		$v['type_name'] = str_repeat($spa,$leve).$v['type_name'];
		$get_type_leve_list_run_row[]=$v;
		
		$typelist_s1 = $model->where("parent_id={$v['id']} and model='{$type}'")->field('type_name,id')->select();//上级栏目
		if(count($typelist_s1)>0){
			get_type_leve_list_run($v['id'],$model, $type);
		}
	}
}//id


//获取栏目列表地区性的,按栏目分级,有缩进,入口函数1/2
function get_type_leve_list_area($id=0,$modelname=false,$area_id=0){
	static $rerow;
	global $get_type_leve_list_area_run_row;


	if(!$modelname) $model = D("type");
	else $model=D(ucfirst($modelname));
	$stype = $modelname."home_type_leve_list_area".$id.$area_id;
	if(!S($stype)){
		get_type_leve_list_area_run($id,$model,$area_id);
		$rerow = $get_type_leve_list_area_run_row;//把全局变量赋值给静态变量，避免引用清空
		$GLOBALS['get_type_leve_list_area_run_row']=NULL;//清空全局变量避免影响其他数据,不能用unset,unset只能清空单个变量或者数组中的某一元素,并且unset只能清空局部变量，清空全局变量要用unset($GLOBALS
		$data = $rerow;
		S($stype,$data,3600*C('HOME_CACHE_TIME')); 
	}else{
		$data = S($stype);
	}
	return $data;
}

//获取栏目列表,按栏目分级,有缩进2/2
function get_type_leve_list_area_run($id=0,$model,$area_id){
	global $get_type_leve_list_area_run_row;
	//全局变量的定义都要放在最前面
	$spa = "----";
	if(count($get_type_leve_list_area_run_row)<1) $get_type_leve_list_area_run_row=array();

	$typelist = $model->where("parent_id={$id} AND area_id={$area_id}")->field('type_name,id,parent_id')->order('sort_order DESC')->select();//上级栏目

	foreach($typelist as $k=>$v){
		$leve = intval(get_typeLeve($v['id'],$model));
		$v['type_name'] = str_repeat($spa,$leve).$v['type_name'];
		$get_type_leve_list_area_run_row[]=$v;
		
		$typelist_s1 = $model->where("parent_id={$v['id']}")->field('type_name,id')->select();//上级栏目
		if(count($typelist_s1)>0){
			get_type_leve_list_area_run($v['id'],$model,$area_id);
		}
	}
}//id


//获取栏目的级别1/2
function get_typeLeve($typeid,$model){
	$typeleve = 0;
	global $typeleve;
	static $rt=0;//先声明要返回静态变量,不然在下面被赋值时是引用赋值
	get_typeLeve_run($typeid,$model);
	$rt = $typeleve;
	unset($GLOBALS['typeleve']);
	return $rt;
}
//获取栏目的级别2/2
function get_typeLeve_run($typeid,$model){
	global $typeleve;
	$condition['id'] = $typeid;
	$v = $model->field('parent_id')->where($condition)->find();
	if($v['parent_id']>0){
		$typeleve++;
		get_typeLeve_run($v['parent_id'],$model);
	}
}

/*
栏目相关函数
End
*/
//在前台显示时去掉反斜线,传入数组，最多二维
function de_xie($arr){
	$data=array();
	if(is_array($arr)){
		foreach($arr as $key=>$v){
			if(is_array($v)){
				foreach($v as $skey=>$sv){
					if(is_array($sv)){
							
					}else{
						$v[$skey] = stripslashes($sv);
					}
				}
				$data[$key] = $v;
			}else{
				$data[$key] = stripslashes($v);
			}
		}
	}else{
		$data = stripslashes($arr);
	}
	return $data;
}


//输出纯文本
function text($text,$parseBr=false,$nr=false){
    $text = htmlspecialchars_decode($text);
    $text	=	safe($text,'text');
    if(!$parseBr&&$nr){
        $text	=	str_ireplace(array("\r","\n","\t","&nbsp;"),'',$text);
        $text	=	htmlspecialchars($text,ENT_QUOTES);
    }elseif(!$nr){
        $text	=	htmlspecialchars($text,ENT_QUOTES);
	}else{
        $text	=	htmlspecialchars($text,ENT_QUOTES);
        $text	=	nl2br($text);
    }
    $text	=	trim($text);
    return $text;
}

function filter_only_array($data)
{
	$ret = false;
	if( is_array($data) ) {
		foreach($data as $key=>$val ) {
			$ret[$key] = htmlspecialchars($val, ENT_QUOTES);
		}
	}
	return $ret;
}

function safe($text,$type='html',$tagsMethod=true,$attrMethod=true,$xssAuto = 1,$tags=array(),$attr=array(),$tagsBlack=array(),$attrBlack=array()){

    //无标签格式
    $text_tags	=	'';

    //只存在字体样式
    $font_tags	=	'<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';

    //标题摘要基本格式
    $base_tags	=	$font_tags.'<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';

    //兼容Form格式
    $form_tags	=	$base_tags.'<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';

    //内容等允许HTML的格式
    $html_tags	=	$base_tags.'<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed>';

    //专题等全HTML格式
    $all_tags	=	$form_tags.$html_tags.'<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';

    //过滤标签
    $text	=	strip_tags($text, ${$type.'_tags'} );

        //过滤攻击代码
        if($type!='all'){
            //过滤危险的属性，如：过滤on事件lang js
            while(preg_match('/(<[^><]+) (onclick|onload|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i',$text,$mat)){
                $text	=	str_ireplace($mat[0],$mat[1].$mat[3],$text);
            }
            while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
                $text	=	str_ireplace($mat[0],$mat[1].$mat[3],$text);
            }
        }
        return $text;
}


//输出安全的html
function h($text, $tags = null){
	$text	=	trim($text);
	$text	=	preg_replace('/<!--?.*-->/','',$text);
	//完全过滤注释
	$text	=	preg_replace('/<!--?.*-->/','',$text);
	//完全过滤动态代码
	$text	=	preg_replace('/<\?|\?'.'>/','',$text);
	//完全过滤js
	$text	=	preg_replace('/<script?.*\/script>/','',$text);

	$text	=	str_replace('[','&#091;',$text);
	$text	=	str_replace(']','&#093;',$text);
	$text	=	str_replace('|','&#124;',$text);
	//过滤换行符
	$text	=	preg_replace('/\r?\n/','',$text);
	//br
	$text	=	preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
	$text	=	preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
	//过滤危险的属性，如：过滤on事件lang js
	while(preg_match('/(<[^><]+) (lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
		$text=str_replace($mat[0],$mat[1],$text);
	}
	while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
		$text=str_replace($mat[0],$mat[1].$mat[3],$text);
	}
	if(empty($tags)) {
		$tags = 'table|tbody|td|th|tr|i|b|u|strong|img|p|br|div|span|em|ul|ol|li|dl|dd|dt|a|alt|h[1-9]?';
		$tags.= '|object|param|embed';	// 音乐和视频
	}
	//允许的HTML标签
	$text	=	preg_replace('/<(\/?(?:'.$tags.'))( [^><\[\]]*)?>/i','[\1\2]',$text);
	//过滤多余html
	$text	=	preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|style|xml)[^><]*>/i','',$text);
	//过滤合法的html标签
	while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
		$text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
	}
	//转换引号
	while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
		$text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4],$text);
	}
	//过滤错误的单个引号
	// 修改:2011.05.26 kissy编辑器中表情等会包含空引号, 简单的过滤会导致错误
//	while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
//		$text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
//	}
	//转换其它所有不合法的 < >
	$text	=	str_replace('<','&lt;',$text);
	$text	=	str_replace('>','&gt;',$text);
    $text   =   str_replace('"','&quot;',$text);
    //$text   =   str_replace('\'','&#039;',$text);
	 //反转换
	$text	=	str_replace('[','<',$text);
	$text	=	str_replace(']','>',$text);
	$text	=	str_replace('|','"',$text);
	//过滤多余空格
	$text	=	str_replace('  ',' ',$text);
	return $text;
}
//根据原图片地址得到缩略图地址
function get_thumb_pic($str){
	$path = explode("/",$str);
	$sc = count($path);
	$path[($sc-1)] = "thumb_".$path[($sc-1)];
	return implode("/",$path);
}

/*
* 中文截取，支持gb2312,gbk,utf-8,big5 
*
* @param string $str 要截取的字串
* @param int $start 截取起始位置
* @param int $length 截取长度
* @param string $charset utf-8|gb2312|gbk|big5 编码
* @param $suffix 是否加尾缀
*/
function cnsubstr($str, $length, $start=0, $charset="utf-8", $suffix=true)
{
	   $str = strip_tags($str);
	   if(function_exists("mb_substr"))
	   {
			   if(mb_strlen($str, $charset) <= $length) return $str;
			   $slice = mb_substr($str, $start, $length, $charset);
	   }
	   else
	   {
			   $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			   $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			   $re['gbk']          = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			   $re['big5']          = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			   preg_match_all($re[$charset], $str, $match);
			   if(count($match[0]) <= $length) return $str;
			   $slice = join("",array_slice($match[0], $start, $length));
	   }
	   if($suffix) return $slice."…";
	   return $slice;
}

/*
	格式化显示时间
*/
function getLastTimeFormt($time,$type=0){
	if($type==0) $f="m-d H:i"; 
	else if($type==1) $f="Y-m-d H:i";
	$agoTime = time() - $time;
    if ( $agoTime <= 60&&$agoTime >=0 ) {
        return $agoTime.'秒前';
    }elseif( $agoTime <= 3600 && $agoTime > 60 ){
        return intval($agoTime/60) .'分钟前';
    }elseif ( date('d',$time) == date('d',time()) && $agoTime > 3600){
		return '今天 '.date('H:i',$time);
    }elseif( date('d',$time+86400) == date('d',time()) && $agoTime < 172800){
		return '昨天 '.date('H:i',$time);
    }else{
        return date($f,$time);
    }

}

/**
 * 获取指定uid的头像文件规范路径
 * 来源：Ucenter base类的get_avatar方法
 *
 * @param int $uid
 * @param string $size 头像尺寸，可选为'big', 'middle', 'small'
 * @param string $type 类型，可选为real或者virtual
 * @return unknown
 */
function get_avatar($uid, $size = 'middle', $type = '') {
	$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'big';
	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	$path = __ROOT__.'/Style/header/customavatars/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
	if(!file_exists(C("WEB_ROOT").$path)) $path = __ROOT__.'/Style/header/images/'."noavatar_$size.gif";
	return  $path;
}
/**
 * 获取地区列表，id为键，地区名为值的二维数组
 */
function get_Area_list($id="") {
	$cacheName = "temp_area_list_s";
	if(!S($cacheName)){
		$list = M('area')->getField('id,name');
		S($cacheName,$list,3600*1000000); 
	}else{
		$list = S($cacheName);
	}
	if(!empty($id)) return $list[$id];
	else return $list;
}

/**
 * IP转换成地区
 */
function ip2area($ip="") {
	if(strlen($ip)<6) return;
	import("ORG.Net.IpLocation");
	$Ip = new IpLocation("CoralWry.dat"); 
	$area = $Ip->getlocation($ip);
	$area = auto_charset($area);
	if($area['country']) $res = $area['country'];
	if($area['area']) $res = $res."(".$area['area'].")";
	if(empty($res)) $res = "未知";
	return $res;
}
//把秒换成小时或者天数
function second2string($second,$type=0){
	$day = floor($second/(3600*24));
	$second = $second%(3600*24);//除去整天之后剩余的时间
	$hour = floor($second/3600);
	$second = $second%3600;//除去整小时之后剩余的时间 
	$minute = floor($second/60);
	$second = $second%60;//除去整分钟之后剩余的时间 
	
	switch($type){
		case 0:
			if($day>=1) $res = $day."天";
			elseif($hour>=1) $res = $hour."小时";
			else  $res = $minute."分钟";
		break;
		case 1:
			if($day>=5) $res = date("Y-m-d H:i",time()+$second);
			elseif($day>=1&&$day<5) $res = $day."天前";
			elseif($hour>=1) $res = $hour."小时前";
			else  $res = $minute."分钟前";
		break;
	}
	//返回字符串
	return $res;
}


//快速缓存调用和储存
function FS($filename,$data="",$path=""){
	$path = C("WEB_ROOT").$path;
	if($data==""){
		$f = explode("/",$filename);
		$num = count($f);
		if($num>2){
			$fx = $f;
			array_pop($f);
			$pathe = implode("/",$f);
			$re = F($fx[$num-1],'',$pathe."/");
		}else{
			isset($f[1])?$re = F($f[1],'',C("WEB_ROOT").$f[0]."/"):$re = F($f[0]);
		}
		return $re;
	}else{
		if(!empty($path)) $re = F($filename,$data,$path);
		else $re = F($filename,$data);
	}
}
//格式化URL，只判断域名，前台后台共用，前台生成供判断的URL，后台生成供储存以便对比的URL
function formtUrl($url){
	if(!stristr($url,"http://")) $url = str_replace("http://","",$url);
	
	$fourl = explode("/",$url);
	$domain = get_domain("http://".$fourl[0]);
	$perfix = str_replace($domain,'',$fourl[0]);
	return $perfix.$domain;
}
function get_domain($url)
{
	$pattern = "/[/w-]+/.(com|net|org|gov|biz|com.tw|com.hk|com.ru|net.tw|net.hk|net.ru|info|cn|com.cn|net.cn|org.cn|gov.cn|mobi|name|sh|ac|la|travel|tm|us|cc|tv|jobs|asia|hn|lc|hk|bz|com.hk|ws|tel|io|tw|ac.cn|bj.cn|sh.cn|tj.cn|cq.cn|he.cn|sx.cn|nm.cn|ln.cn|jl.cn|hl.cn|js.cn|zj.cn|ah.cn|fj.cn|jx.cn|sd.cn|ha.cn|hb.cn|hn.cn|gd.cn|gx.cn|hi.cn|sc.cn|gz.cn|yn.cn|xz.cn|sn.cn|gs.cn|qh.cn|nx.cn|xj.cn|tw.cn|hk.cn|mo.cn|org.hk|is|edu|mil|au|jp|int|kr|de|vc|ag|in|me|edu.cn|co.kr|gd|vg|co.uk|be|sg|it|ro|com.mo)(/.(cn|hk))*/";
	preg_match($pattern, $url, $matches);
	if(count($matches) > 0)
	{
		return $matches[0];
	}else{
		$rs = parse_url($url);
		$main_url = $rs["host"];
		if(!strcmp(long2ip(sprintf("%u",ip2long($main_url))),$main_url))
		{
			return $main_url;
		}else{
			$arr = explode(".",$main_url);
			$count=count($arr);
			$endArr = array("com","net","org");//com.cn net.cn 等情况
			if (in_array($arr[$count-2],$endArr))
			{
				$domain = $arr[$count-3].".".$arr[$count-2].".".$arr[$count-1];
			}else{
				$domain = $arr[$count-2].".".$arr[$count-1];
			}
			return $domain;
		}
	}
} 

function getFloatValue($f,$len)
{
  return  number_format($f,$len,'.','');   
} 

//获取远程图片
function get_remote_img($content){
	$rt = C("WEB_ROOT");
	$img_dir = C("REMOTE_IMGDIR")?C("REMOTE_IMGDIR"):"/UF/Remote";//img_dir远程图片的保存目录，带前"/"不带后"/"
	$base_dir = substr($rt,0,strlen($rt)-1);//$base_dir网站根目录物理路径，不带后"/"
	
	$content = stripslashes($content); 
	$img_array = array(); 
	preg_match_all("/(src|SRC)=[\"|'| ]{0,}(http:\/\/(.*)\.(gif|jpg|jpeg|bmp|png|ico))/isU",$content,$img_array); //获取内容中的远程图片
	$img_array = array_unique($img_array[2]); //把重复的图片去掉
	set_time_limit(0); 
	$imgUrl = $img_dir."/".strftime("%Y%m%d",time()); //img_dir远程图片的保存目录，带前"/"不带后"/"
	$imgPath = $base_dir.$imgUrl; //$base_dir网站根目录物理路径，不带后"/"
	$milliSecond = strftime("%H%M%S",time()); 
	if(!is_dir($imgPath)) MakeDir($imgPath,0777);//如果路径不存在则创建
	foreach($img_array as $key =>$value) 
	{ 
		$value = trim($value); 
		$get_file = @file_get_contents($value); 
		$rndFileName = $imgPath."/".$milliSecond.$key.".".substr($value,-3,3); 
		$fileurl = $imgUrl."/".$milliSecond.$key.".".substr($value,-3,3); 

		if($get_file) 
		{ 
			$fp = @fopen($rndFileName,"w"); 
			@fwrite($fp,$get_file); 
			@fclose($fp); 
		} 
		$content = ereg_replace($value,$fileurl,$content); 
	} 
	//$content = addslashes($content); 
	return $content;
}

function getSubSite(){
	$map['is_open'] = 1;
	$list = M("area")->field(true)->where($map)->select();
	$cdomain = explode(".",$_SERVER['HTTP_HOST']);
	$cpx = array_pop($cdomain);
	$doamin = array_pop($cdomain);
	$host = ".".$doamin.".".$cpx;
	foreach($list as $key=>$v){
		$list[$key]['host'] = "http://".$v['domain'].$host;
	}
	return $list;
}
function getCreditsLog($map,$size){
	if(empty($map['uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('member_creditslog')->where($map)->count('id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}

	$list = M('member_creditslog')->where($map)->order('id DESC')->limit($Lsql)->select();
	$type_arr = C("MONEY_LOG");
	foreach($list as $key=>$v){
		//$list[$key]['type'] = $type_arr[$v['type']];
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

function getCredit($uid){
	$pre = C('DB_PREFIX');
	$user = M('members m')->join("{$pre}member_money mm ON m.id=mm.uid")->where("m.id={$uid}")->find();
	if( !is_array($user) ) 	return "用户出错，请重新操作";

	$credit = array();
	$credit['xy']['limit'] = 	getFloatValue($user['credit_limit'],2);
	$credit['xy']['use'] = 		getFloatValue(M('borrow_info')->where("borrow_uid = {$uid} AND borrow_status in(0,2,4,6) AND borrow_type=1")->sum("borrow_money-repayment_money"),2);
	$credit['xy']['cuse'] = 	getFloatValue($credit['xy']['limit'] - $credit['xy']['use'],2);

	$credit['db']['limit'] = 	getFloatValue($user['vouch_limit'],2);
	$credit['db']['use'] = 		getFloatValue(M('borrow_info')->where("borrow_uid = {$uid} AND borrow_status in(0,2,4,6) AND borrow_type=2")->sum("borrow_money-repayment_money"),2);
	$credit['db']['cuse'] = 	getFloatValue($credit['db']['limit'] - $credit['db']['use'],2);
	
	$credit['dy']['limit'] = 	getFloatValue($user['diya_limit'],2);
	$credit['dy']['use'] = 		getFloatValue(M('borrow_info')->where("borrow_uid = {$uid} AND borrow_status in(0,2,4,6) AND borrow_type=5")->sum("borrow_money-repayment_money"),2);
	$credit['dy']['cuse'] = 	getFloatValue($credit['dy']['limit'] - $credit['dy']['use'],2);

	$credit['jz']['limit'] = 	getFloatValue(0.9 * M('investor_detail')->where(" investor_uid={$uid} AND status =7 ")->sum("capital+interest-interest_fee"),2);
	$credit['jz']['use'] = 		getFloatValue(M('borrow_info')->where("borrow_uid = {$uid} AND borrow_status in(0,2,4,6) AND borrow_type=4")->sum("borrow_money+borrow_interest-repayment_money-repayment_interest"),2);
	$credit['jz']['cuse'] = 	getFloatValue($credit['jz']['limit'] - $credit['jz']['use'],2);

	$credit['all']['limit'] = 	getFloatValue($credit['xy']['limit'] + $credit['db']['limit'] + $credit['dy']['limit'],2);
	$credit['all']['use'] = 	getFloatValue($credit['xy']['use'] + $credit['db']['use'] + $credit['dy']['use'],2);
	$credit['all']['cuse'] = 	getFloatValue($credit['all']['limit'] - $credit['all']['use'],2);

	return $credit;
}

//积分日志
function getIntegralLog($map,$size){
	if(empty($map['uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('member_integrallog')->where($map)->count('id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}

	$list = M('member_integrallog')->where($map)->order('id DESC')->limit($Lsql)->select();
	$type_arr = C("INTEGRAL_LOG");
	foreach($list as $key=>$v){
		$list[$key]['type'] = $type_arr[$v['type']];
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

//所有圈子列表,以id为键
function Notice($type,$uid,$data=array()){
		$datag = get_global_setting();
		$datag=de_xie($datag);
		$msgconfig = FS("Webconfig/msgconfig");
		
		$emailTxt = FS("Webconfig/emailtxt");
		$smsTxt = FS("Webconfig/smstxt");
		$msgTxt = FS("Webconfig/msgtxt");
		$emailTxt=de_xie($emailTxt);
		$smsTxt=de_xie($smsTxt);
		$msgTxt=de_xie($msgTxt);
		
		//邮件
		header("content-type:text/html;charset=utf-8");
		import("ORG.Net.Phpmailer");
		
		$stmpport = $msgconfig['stmp']['port'];//25;
		$stmphost = $msgconfig['stmp']['server'];
		$stmpuser = $msgconfig['stmp']['user'];
		$stmppass = $msgconfig['stmp']['pass'];
		
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		$mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
		$mail->SMTPAuth   = true;                  //开启认证
		$mail->Port       = $stmpport;//25;
		$mail->Host       = $stmphost; 
		$mail->Username   = $stmpuser;
		$mail->Password   = $stmppass;
		$mail->AddReplyTo($stmpuser,$stmpuser);//回复地址
		$mail->From       = $stmpuser;
		$mail->FromName   = $stmpuser;
		/*发送对象 start*/
		$minfo = M('members')->field('user_email,user_name,user_phone')->find($uid);
		$uname = $minfo['user_name'];
		/*发送对象 end*/
		$to = $minfo['user_email'];
		if(!empty($data)){
		    $to = $data['new_email'];
		}
		$mail->AddAddress($to);
		
	switch($type){
		
		case 1://注册成功发送邮件
			$vcode = rand_string($uid,32,0,1);
			$link='<a href="'.C('WEB_URL').'/member/common/emailverify?vcode='.$vcode.'">点击链接验证邮件</a>';
			//站内信
			$innermsg = str_replace(array("#UserName#"),array($uname),$msgTxt['regsuccess']); 
			addInnerMsg($uid,"恭喜您注册成功",$innermsg);
			//邮件
			$subject = "您刚刚在".$datag['web_name']."注册成功"; 
			$body = str_replace(array("#UserName#","#LINK#"),array($uname,$link),$emailTxt['regsuccess']); 
		break;
		
		case 2://安全中心通过验证码改密码安全问题
		    //手机
		    $pcode = rand_string($uid,6,1,3);
		    $content = str_replace(array("#CODE#"),array($pcode),$smsTxt['safecode']);
		    $sendp = sendsms($minfo['user_phone'],$content);
		    //邮件
			$vcode = rand_string($uid,10,3,3);
			$subject = "您刚刚在".$datag['web_name']."注册成功"; 
			$body = str_replace(array("#CODE#"),array($vcode),$emailTxt['safecode']); 
		break;
		
		case 3://安全中心通过验证码改手机
			$vcode = rand_string($uid,6,1,4);
			$content = str_replace(array("#CODE#"),array($vcode),$smsTxt['safecode']); 
			$send = sendsms($minfo['user_phone'],$content);
			return $send;
		
		case 4://安全中心新手机验证码
			$vcode = rand_string($uid,6,1,5);
			$content = str_replace(array("#CODE#"),array($vcode),$smsTxt['safecode']); 
			$send = sendsms($data['phone'],$content);
			return $send;
		break;
		
		case 5://安全中心新手机验证码安全码
		    //邮件
			$vcode = rand_string($uid,10,1,6);
			$subject = "您刚刚在".$datag['web_name']."申请更换手机的安全码"; 
			$body = str_replace(array("#CODE#"),array($vcode),$emailTxt['changephone']); 
		break;
		
		case 6://借款发布成功审核通过
		    //站内信
		    $innermsg = str_replace(array("#UserName#"),array($uname),$msgTxt['verifysuccess']);
		    addInnerMsg($uid,"恭喜借款审核通过",$innermsg);
			//邮件
			$subject = "恭喜，你在".$datag['web_name']."发布的借款审核通过"; 
			$body = str_replace(array("#UserName#"),array($uname),$emailTxt['verifysuccess']); 
		break;

		case 7://密码找回
		    //邮件
			$vcode = rand_string($uid,32,0,7);
			$link='<a href="'.C('WEB_URL').'/member/common/getpasswordverify?vcode='.$vcode.'">点击链接验证邮件</a>';
			$subject = "您刚刚在".$datag['web_name']."申请了密码找回"; 
			$body = str_replace(array("#UserName#","#LINK#"),array($uname,$link),$emailTxt['getpass']); 
		break;
		
		case 8://验证中心邮件验证
		    //邮件
		    if(!empty($data)){
		        $sign = SignModel::generate_sign($data);
		        $link='<a href="'.C('WEB_URL').'/member/common/emailverify?email='.$data['new_email'].'&uid='.$data['uid'].'&time='.$data['time'].'&sign='.$sign.'">&nbsp点击链接确认修改</a>';
		        $subject = "您刚刚在".$datag['web_name']."申请修改邮箱";
		        $body = str_replace(array("#UserName#","#LINK#","恭喜您注册成功,请点击下面的链接即可完成激活"),array($uname,$link,$subject),$emailTxt['regsuccess']);
		    }else {
		        $vcode = rand_string($uid,32,0,1);
		        $link='<a href="'.C('WEB_URL').'/member/common/emailverify?vcode='.$vcode.'">点击链接验证邮件</a>';
		        $subject = "您刚刚在".$datag['web_name']."申请邮箱验证";
		        $body = str_replace(array("#UserName#","#LINK#"),array($uname,$link),$emailTxt['regsuccess']);
		    }
		break;
		
		case 9://还款到期提醒
			//邮件
			$subject = "您在".$datag['web_name']."的还款最终期限即将到期。"; 
			$body = str_replace(array("#UserName#","#borrowName#","#borrowMoney#"),array($uname,$data['borrowName'],$data['borrowMoney']),$emailTxt['repaymentTip']); 
		break;
		
		case 10://支付密码找回
		    //邮件
			$vcode = rand_string($uid,32,0,7);
			$link='<a href="'.C('WEB_URL').'/member/index/getpaypasswordverify?vcode='.$vcode.'">点击链接验证邮件</a>';
			$subject = "您刚刚在".$datag['web_name']."申请了支付密码找回"; 
			$body = str_replace(array("#UserName#","#LINK#"),array($uname,$link),$emailTxt['getpaypass']); 
		break;
	}
	$mail->Subject  = $subject;
	$mail->Body = $body;
	$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
	$mail->WordWrap   = 80; // 设置每行字符串的长度
	//$mail->AddAttachment("f:/test.png");  //可以添加附件
	$mail->IsHTML(true);
	$send = $mail->Send();
	return $send;
}

function SMStip($type,$mob,$from=array(),$to=array()){
	if(empty($mob)) return;
		$datag = get_global_setting();
		$datag=de_xie($datag);
		$smsTxt = FS("Webconfig/smstxt");
		$smsTxt=de_xie($smsTxt);
		if($smsTxt[$type]['enable']==1){
			//记录发送时间
			$DX_time=time()+3;
			//用$to替换内容中的$from，作为内容
			$body = str_replace($from,$to,$smsTxt[$type]['content']); 
			$send=sendsms($mob,$body,$DX_time); // 
		}else{
			return;	
		}
}


/**
 * 所有圈子列表,以id为键
 * @param string $type 消息类型
 * @param int|array $uid  接收的用户uid,当uid为数组的时候可批量操作
 * @param string $info  标号的borrow_id
 * @param string $autoid
 * @param string $db
 * @param number|bool $way  1（邮件）|2（站内信）|3（短信） 如果$way为空，则为用户自动设置的消息通知，需要走是否接收通知的设定，用户允许接收才发送，否则直接发送
 * @param bool $filter  false不进行通知筛选，1|true 需要通过用户通知设置允许才可以发
 * @return boolean
 */
function MTip($type,$uid=0,$info="",$autoid="", $db = null, $way=1, $filter = false){
    //获取平台信息
    $web_name = get_global_setting('web_name');
    $web_name= de_xie($web_name);
	//邮件
    if( $way == 1 ) {
        header("content-type:text/html;charset=utf-8");
        import("ORG.Net.Phpmailer");
        $msgconfig = FS("Webconfig/msgconfig");
        $stmpport = $msgconfig['stmp']['port'];
        $stmphost = $msgconfig['stmp']['server'];
        $stmpuser = $msgconfig['stmp']['user'];
        $stmppass = $msgconfig['stmp']['pass'];
        $mail = new PHPMailer(true);
        $mail->IsSMTP();
        $mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
        $mail->SMTPAuth   = true;                  //开启认证
        $mail->Port       = $stmpport;//25;
        $mail->Host       = $stmphost;
        $mail->Username   = $stmpuser;
        $mail->Password   = $stmppass;
        $mail->AddReplyTo($stmpuser,$stmpuser);//回复地址
        $mail->From       = $stmpuser;
        $mail->FromName   = $stmpuser;   
    }
    if( !is_array($uid) && !empty($uid) ) $uid = array($uid);
        else $uid = array_unique($uid);
    if( $filter == 1 ) {
        $notice = M('sys_tip')->where(array('uid' => implode(',', $uid)))->select();
        if ( !empty($notice)){
            foreach( $notice as $val ) {
                if (strpos($val['tipset'], $type.'_'.$way) === false ){
                    $key = array_search($val['uid'], $uid);
                    unset($uid[$key]);
                }
            }
            if( empty($uid)) return true;

        } else {
            return true;
        }
    }

    //获取当前用户邮箱,手机号
    if( $way != 2 ) {
        if( is_array($uid) ) {
            $user_ids = implode(",", $uid);
        } else {
            $user_ids[] = $uid;
        }
        $memail = M('members')->field('id,user_email,user_phone')->where(array('id'=>array('in', $user_ids)))->select();
        if( !empty($memail) ) {
            if( $way == 1 ) {
                $to = only_array($memail, 'user_email');
                $to = implode(",", $to);
            } else { // $way == 3
                $to = only_array($memail, 'user_phone');
                $to = implode(",", $to);
            }
        }
    }
	switch($type){

		case "chk1"://修改密码
		    $body = "您刚刚在".$web_name."修改了登录密码,如不是自己操作,请尽快联系客服【".$web_name."友情提醒】";
		    if ($way == '1') {    #邮件
		        $subject = "您刚刚在".$web_name."修改了登录密码";
		    }elseif ($way == '2'){    #站内信
		        $innerbody = "您刚刚修改了登录密码,如不是自己操作,请尽快联系客服";
                $inner_title = "您刚刚修改了登录密码";
		    }
		break;
		
		case "chk2"://修改银行帐号
		    $body = "您刚刚在".$web_name."修改了提现的银行帐户,如不是自己操作,请尽快联系客服【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您刚刚在".$web_name."修改了提现的银行帐户";
		    }elseif ($way == '2'){
		        $innerbody = "您刚刚修改了提现的银行帐户,如不是自己操作,请尽快联系客服";
                $inner_title = "您刚刚修改了提现的银行帐户";
		    }
		break;
		
		case "chk6"://资金提现
		    $body = "您刚刚在".$web_name."申请了提现操作,如不是自己操作,请尽快联系客服【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您刚刚在".$web_name."申请了提现操作";
		    }elseif ($way == '2'){
		        $innerbody = "您刚刚申请了提现操作,如不是自己操作,请尽快联系客服";
                $inner_title = "您刚刚申请了提现操作";
		    }
		break;
		
		case "chk7"://借款标初审未通过
		    $body = "您在".$web_name."发布的第{$info}号借款标刚刚初审未通过【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."发布的借款标刚刚初审未通过";
		    }elseif ($way == '2'){
		        $innerbody = "您发布的第{$info}号借款标刚刚初审未通过";
                $inner_title = "刚刚您的借款标初审未通过";
		    }
		break;
		
		case "chk8"://借款标初审通过
		    $body = "您在".$web_name."发布的第{$info}号借款标刚刚初审通过【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."发布的借款标刚刚初审通过";
		    }elseif ($way == '2'){
		        $innerbody = "您发布的第{$info}号借款标刚刚初审通过";
                $inner_title = "刚刚您的借款标初审通过";
		    }
		break;
		
		case "chk9"://借款标复审通过
		    $body = "您在".$web_name."发布的第{$info}号借款标刚刚复审通过【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."发布的借款标刚刚复审通过";
		    }elseif ($way == '2'){
		        $innerbody = "您发布的第{$info}号借款标刚刚复审通过";
                $inner_title = "刚刚您的借款标复审通过";
		    }
		break;
		
		case "chk12"://借款标复审未通过
		    $body = "您在".$web_name."的发布的第{$info}号借款标复审未通过【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."的发布的借款标刚刚复审未通过";
		    }elseif ($way == '2'){
		        $innerbody = "您发布的第{$info}号借款标复审未通过";
                $inner_title = "刚刚您的借款标复审未通过";
		    }
		break;
		
		case "chk10"://借款标满标
		    $body = "刚刚您在".$web_name."的第{$info}号借款标已满标，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."的借款标已满标";
		    }elseif ($way == '2'){  
		        $innerbody = "刚刚您的借款标已满标";
                $inner_title = "刚刚您的第{$info}号借款标已满标";
		    }
		break;
		
		case "chk11"://借款标流标
		    $body = "您在".$web_name."发布的第{$info}号借款标已流标，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."的借款标已流标";
		    }elseif ($way == '2'){
		        $innerbody = "您的第{$info}号借款标已流标";
                $inner_title = "刚刚您的借款标已流标";
		    }
		break;
		
		case "chk25"://借入人还款成功
		    $body = "您对在".$web_name."借入的第{$info}号借款进行了还款，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."的借入的还款进行了还款操作";
		    }elseif ($way == '2'){
		        $innerbody = "您对借入的第{$info}号借款进行了还款";
                $inner_title = "您对借入标还款进行了还款操作";
		    }
		break;
		
		case "chk27"://自动投标借出完成
		    $body = "您在".$web_name."设置的第{$autoid}号自动投标按设置对第{$info}号借款进行了投标，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."设置的第{$autoid}号自动投标按设置投了新标";
		    }elseif ($way == '2'){
		        $innerbody = "您设置的第{$autoid}号自动投标对第{$info}号借款进行了投标";
                $inner_title = "您设置的第{$autoid}号自动投标按设置投了新标";
		    }
		break;
		
		case "chk14"://借出成功
		    $body = "您在".$web_name."投标的第{$info}号借款借出成功了【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."投标的借款成功了";
		    }elseif ($way == '2'){
		        $innerbody = "您投标的借款成功了";
                $inner_title = "您投标的第{$info}号借款借款成功";
		    }
		break;
		
		case "chk15"://借出流标
		    $body = "您在".$web_name."投标的第{$info}号借款流标了，相关资金已经返回帐户，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."投标的借款流标了";
		    }elseif ($way == '2'){
		        $innerbody = "您投标的借款流标了";
                $inner_title = "您投标的第{$info}号借款流标了，相关资金已经返回帐户";
		    }
		break;
		
		case "chk16"://收到还款
		    $body = "您在".$web_name."借出的第{$info}号借款收到了新的还款，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."借出的借款收到了新的还款";
		    }elseif ($way == '2'){
		        $innerbody = "您借出的借款收到了新的还款";
                $inner_title = "您借出的第{$info}号借款收到了新的还款";
		    }
		break;
		
		case "chk18"://网站代为偿还
		    $body = "您在".$web_name."借出的第{$info}号借款逾期网站代还了本金，请登陆查看【".$web_name."友情提醒】";
		    if ($way == '1') {
		        $subject = "您在".$web_name."借出的借款逾期网站代还了本金";
		    }elseif ($way == '2'){
		        $innerbody = "您借出的第{$info}号借款逾期网站代还了本金";
                $inner_title = "您借出的第{$info}号借款逾期网站代还了本金";
		    }
       break;
	}
    if( $way == 1 ) {//发送邮件
        if( !empty($mail) && !empty($subject) && !empty($body) && !empty($to) ) {
            $handler_arr = explode(',',$to);
            $count = count($handler_arr);
            if(  C('MCQ_USE') == true ) {
                $single_per = 50; // 每次扔n个
                $Mcq = new McqModel(C('SEND_MAIL'));
                // 扔进queue
                $batch_time = ceil($count/$single_per);

                for($i = 0; $i<$batch_time; $i++ ) {
                    $output = array_slice($handler_arr, $i*$single_per, $single_per); //每次处理n个
                    $data['subject'] = $subject;
                    $data['body'] = $body;
                    $data['user_ids'] = $output;
                    $Mcq->add(json_encode($data));
                }
                return true;
            } else {
                    $to = explode(",",$to);
                    $count = count($to);
                    for($i = 0; $i<$count; $i++ ) {
                        $mail->AddAddress($to[$i]);
                    }
                    $mail->Subject  = $subject;
                    $mail->Body = $body;
                    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
                    //$mail->AddAttachment("f:/test.png");  //可以添加附件
                    $mail->IsHTML(true);
                    $send = $mail->Send();
                return $send;
            }
        }
    } elseif( $way == 2 ) {//发送站内信
        if( !empty($inner_title) && !empty($innerbody) ) {
            addInnerMsg($uid, $inner_title, $innerbody, $db);
        }
    } elseif( $way == 3 ) {//发送短信
        if( !empty($body) && !empty($to) ) {
            sendsms($to, $body);
        }
    }
}

/**
 * 账户管理--通知设置 #DATE:20150312 liu.
 * @property integer $uid
 * @property integer $info   一般指借款编号borrow_id
 * @property string $type. [For example: $type='chk1'].
 */
function NoticeSet($type, $uid, $info="", $autoid="", $db=null){
    $arr = M('sys_tip')->where("uid=$uid")->find();
    if (empty($arr)){
        return true;
    }
    $pieces = explode(",", $arr['tipset']);
    array_pop($pieces);
    if(in_array($type.'_1',$pieces)) {  #邮件
        MTip($type,$uid, $info, $autoid, $db, 1);
    }
    if (in_array($type.'_2',$pieces)){  #站内信
        MTip($type,$uid, $info, $autoid, $db, 2);
    }
    if (in_array($type.'_3',$pieces)){  #3短信
        MTip($type,$uid, $info, $autoid, $db, 3);
    }
    return true;
}

/**
 * @param $uid
 * @param $borrow_id
 * @param $money 投资金额
 * @param int $_is_auto
 * @param string $coupon_ids  coupon_id1,couponid2,[,,,]
 * @return bool
 * @throws Exception
 */
function investMoney($uid, $borrow_id, $money, $_is_auto=0, $coupon_ids = false){
	$pre = C('DB_PREFIX');
	$done = $lastInvest = $lastInterest = false;
	$datag = get_global_setting();
    $db = new Model();
    $db->startTrans();

    $discount_money = $taste_money = 0;
    // 如果使用优惠券
    if( !empty($coupon_ids) ) {
        $coupon_items = M('expand_money')
            ->field('money, invest_money, expired_time, status, is_taste')
            ->where(array('id'=>array('in',$coupon_ids),'uid'=>$uid))
            ->select();
        // 只要有一个优惠券不能使用，则认为是非法请求
        foreach( $coupon_items as $val ) {
            if( $val['invest_money'] > $money || $val['expired_time'] < time() || $val['status'] == 4 ) {
                return "非法请求";
            }else {
                $discount_money += $val['money'];
                if( $val['is_taste'] == ExpandMoneyModel::LZH_EXPAND_MONEY_IS_TASTE_YES ) {
                    $taste_money += $val['money'];
                }
            }
        }
        //优惠券过期
        $coupon_data = array(
            'loanno' => $borrow_id,
            'status' => 4,
            'use_time' => time(),
                  
        );
        if( !M('expand_money')->where(array('id'=>array('in',$coupon_ids)))->save($coupon_data) ) {
            $db->rollback();
            return '系统繁忙';
        }
		if( is_array($coupon_ids) ) {
			$investinfo['coupon_id']  = ",".implode(',',$coupon_ids).",";
		}else{
			$investinfo['coupon_id']  = ",{$coupon_ids},";
		}

    }

    $fields = "duration_unit,borrow_duration,borrow_uid,borrow_money,borrow_interest,borrow_interest_rate,borrow_type,borrow_duration,duration_unit,repayment_type,has_borrow,reward_money,money_collect";
	$binfo = M("borrow_info")->field($fields)->lock(true)->find($borrow_id);//新加入了奖金reward_money到资金总额里
    $durationMonth = $binfo['duration_unit'];
	$vminfo = getMinfo($uid,'m.user_leve,m.time_limit,mm.account_money,mm.back_money,mm.money_collect');
	
	if(($vminfo['account_money']+$vminfo['back_money']+$binfo['reward_money']) + $discount_money <$money) {
		return "您当前的可用金额为：".($vminfo['account_money']+$vminfo['back_money']+$binfo['reward_money'])." 对不起，可用余额不足，不能投标";
	}
	
	////////////新增投标时检测会员的待收金额是否大于标的设置的代收金额限制，大于就可投标，小于就不让投标 2013-08-26 fan//////////////
	
	if($binfo['money_collect']>0){//判断是否设置了投标待收金额限制
		if($vminfo['money_collect']<$binfo['money_collect']){
			return "对不起，此标设置有投标待收金额限制，您当前的待收金额为".$vminfo['money_collect']."元，小于该标设置的待收金额限制".$binfo['money_collect']."元。";
		}
	}
	
	////////////新增投标时检测会员的待收金额是否大于标的设置的代收金额限制，大于就可投标，小于就不让投标 2013-08-26 fan//////////////
	
	//不同会员级别的费率
	//($vminfo['user_leve']==1 && $vminfo['time_limit']>time())?$fee_rate=($fee_invest_manage[1]/100):$fee_rate=($fee_invest_manage[0]/100);
	$fee_rate=$datag['fee_invest_manage']/100;
	//投入的钱
	$havemoney = $binfo['has_borrow'];
	if(($binfo['borrow_money'] - $havemoney -$money)<0) 
	{
		return "对不起，此标还差".($binfo['borrow_money'] - $havemoney)."元满标，您最多投标".($binfo['borrow_money'] - $havemoney)."元";
	}

    // 如果刚好满标，说明是最后一个，利息计算方式为总利息减去已经投标计算的利息总和
    if( $money == $binfo['borrow_money'] - $havemoney ) {
        $lastInvest = true;
        $sumInterest = BorrowInvestorModel::get_sum_investor_interest($borrow_id); // 兼容还款方式4,
        $lastInterest = $binfo['borrow_interest'] - $sumInterest;
        $investinfo['investor_interest'] = $lastInterest; // 最后一位的利息
    }
	
	$borrow_invest = M("borrow_investor")->where("borrow_id = {$borrow_id}")->lock(true)->sum('investor_capital');//新加投资金额检测
	
		//还款概要公共信息START
		$investinfo['status'] = 1;//等待复审
		$investinfo['borrow_id'] = $borrow_id;
		$investinfo['investor_uid'] = $uid;
		$investinfo['borrow_uid'] = $binfo['borrow_uid'];
		
		/////////////////////////////////////新加投资金额检测/////////////////////////////////////////////
		if($borrow_invest['investor_capital']>$binfo['borrow_money']){
			$investinfo['investor_capital'] = $binfo['borrow_money'] - $binfo['has_borrow'];
		}else{
			$investinfo['investor_capital'] = $money;
		}
		/////////////////////////////////////新加投资金额检测/////////////////////////////////////////////

        $investinfo['transfer_duration'] = TborrowModel::get_transfer_duration_days($borrow_id);
        $investinfo['taste_money'] = $taste_money;
		$investinfo['is_auto'] = $_is_auto;
		$investinfo['add_time'] = time();
		$investinfo['borrow_type'] = $binfo['borrow_type']; // 新增borrow_type字段
		//还款详细公共信息START
		$savedetail=array();
		switch($binfo['repayment_type']){
			case 1://按天到期还款
				//还款概要START
                if( $lastInvest === false ) {
                    $investinfo['investor_interest'] = getFloatValue($binfo['borrow_interest_rate']/365*$investinfo['investor_capital']*$binfo['borrow_duration']/100,4);
                }

				$investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest'],4);//修改投资人的天标利息管理费2013-03-19 fan
				$invest_info_id = M('borrow_investor')->add($investinfo);
                if( !empty($invest_info_id) ) {
                    //还款概要END
                    $investdetail['borrow_id'] = $borrow_id;
                    $investdetail['invest_id'] = $invest_info_id;
                    $investdetail['investor_uid'] = $uid;
                    $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                    $investdetail['capital'] = $investinfo['investor_capital'];
                    $investdetail['interest'] = $investinfo['investor_interest'];
                    $investdetail['interest_fee'] = $investinfo['invest_fee'];
                    $investdetail['status'] = 0;
                    $investdetail['sort_order'] = 1;
                    $investdetail['total'] = 1;
                    if( $taste_money > 0 ) {
                        $investdetail['taste_money'] = $taste_money;
                    }
                    $savedetail[] = $investdetail;
                } else {
                    $db->rollback();
                    return false;
                }

			break;
			case 2://每月还款
				//还款概要START
				$monthData['type'] = "all";
				$monthData['money'] = $investinfo['investor_capital'];
				$monthData['year_apr'] = $binfo['borrow_interest_rate'];
				$monthData['duration'] = $binfo['borrow_duration'];
				$repay_detail = EqualMonth($monthData);
                if( $lastInvest === false ) {
                    $investinfo['investor_interest'] = ($repay_detail['repayment_money'] - $investinfo['investor_capital']);
                }

				$investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest'],4);
				$invest_info_id = M('borrow_investor')->add($investinfo);
				//还款概要END
                if( !empty($invest_info_id) ) {
                    $monthDataDetail['money'] = $investinfo['investor_capital'];
                    $monthDataDetail['year_apr'] = $binfo['borrow_interest_rate'];
                    $monthDataDetail['duration'] = $binfo['borrow_duration'];
                    if( empty($lastInterest) ) { //每一期的投资表利息对应每一期的还款表利息
                        $lastInterest = $investinfo['investor_interest'];
                    }
                    $repay_list = EqualMonth($monthDataDetail, $lastInterest);
                    // 体验金每期还款本金
                    if( $taste_money > 0 ) {
                        $monthDataDetail['money'] = $taste_money;
                        $taste_list = EqualMonth($monthDataDetail, $lastInterest);
                    }

                    $i=1;
                    foreach($repay_list as $k=>$v){
                        $investdetail['borrow_id'] = $borrow_id;
                        $investdetail['invest_id'] = $invest_info_id;
                        $investdetail['investor_uid'] = $uid;
                        $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                        $investdetail['capital'] = $v['capital'];
                        $investdetail['interest'] = $v['interest'];
                        $investdetail['interest_fee'] = getFloatValue($fee_rate*$v['interest'],4);
                        $investdetail['status'] = 0;
                        $investdetail['sort_order'] = $i;
                        $investdetail['total'] = $binfo['borrow_duration'];
                        if( !empty($taste_list) ) {
                            foreach( $taste_list as $key=>$val ) {
                                if( $key == $k ) {
                                    $investdetail['taste_money'] = $val['capital'];
                                    break;
                                }
                            }
                        }
                        $i++;
                        $savedetail[] = $investdetail;
                    }
                } else {
                    $db->rollback();
                    return false;
                }

			break;
			case 3://按季分期还款
				//还款概要START
				$monthData['month_times'] = $binfo['borrow_duration'];
				$monthData['account'] = $investinfo['investor_capital'];
				$monthData['year_apr'] = $binfo['borrow_interest_rate'];
				$monthData['type'] = "all";
				$repay_detail = EqualSeason($monthData);
                if( $lastInvest === false ) {
                    $investinfo['investor_interest'] = ($repay_detail['repayment_money'] - $investinfo['investor_capital']);
                }

				$investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest'],4);
				$invest_info_id = M('borrow_investor')->add($investinfo);
				//还款概要END
                if( !empty($invest_info_id) ) {
                    $monthDataDetail['month_times'] = $binfo['borrow_duration'];
                    $monthDataDetail['account'] = $investinfo['investor_capital'];
                    $monthDataDetail['year_apr'] = $binfo['borrow_interest_rate'];
                    $repay_list = EqualSeason($monthDataDetail, $lastInterest);

                    // 体验金每期还款本金
                    if( $taste_money > 0 ) {
                        $monthDataDetail['money'] = $taste_money;
                        $taste_list = EqualSeason($monthDataDetail, $lastInterest);
                    }

                    $i=1;
                    foreach($repay_list as $k=>$v){
                        $investdetail['borrow_id'] = $borrow_id;
                        $investdetail['invest_id'] = $invest_info_id;
                        $investdetail['investor_uid'] = $uid;
                        $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                        $investdetail['capital'] = $v['capital'];
                        $investdetail['interest'] = $v['interest'];
                        $investdetail['interest_fee'] = getFloatValue($fee_rate*$v['interest'],4);
                        $investdetail['status'] = 0;
                        $investdetail['sort_order'] = $i;
                        $investdetail['total'] = $binfo['borrow_duration'];
                        if( !empty($taste_list) ) {
                            foreach( $taste_list as $key=>$val ) {
                                if( $key == $k ) {
                                    $investdetail['taste_money'] = $val['capital'];
                                    break;
                                }
                            }
                        }
                        $i++;
                        $savedetail[] = $investdetail;
                    }
                } else {
                    $db->rollback();
                    return false;
                }

			break;
			case 4://按天计息，每月还息，到期还本, 复审时再创建investor_detail
				$monthData['duration'] = $binfo['borrow_duration'];
				$monthData['account'] = $investinfo['investor_capital'];
				$monthData['year_apr'] = $binfo['borrow_interest_rate'];
				$monthData['type'] = "all";
				$repay_detail = EqualEndMonth($monthData, $durationMonth);

                if( $lastInvest === false ) {
                    $investinfo['investor_interest'] = ($repay_detail['repayment_account'] - $investinfo['investor_capital']);
                }
				$investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest'],4);
				$invest_info_id = M('borrow_investor')->add($investinfo);
                if( !empty($invest_info_id) ) {
                    //还款概要END
                    $monthDataDetail['month_times'] = $binfo['borrow_duration'];
                    $monthDataDetail['account'] = $investinfo['investor_capital'];
                    $monthDataDetail['year_apr'] = $binfo['borrow_interest_rate'];
                    $invest_defail_id = true; // 还款方式为4时，不创建详情表，在复审通过的时候创建详情表
                } else {
                    $db->rollback();
                    return false;
                }
			break;
			case 5://一次性还款
				$monthData['month_times'] = $binfo['borrow_duration'];
				$monthData['account'] = $investinfo['investor_capital'];
				$monthData['year_apr'] = $binfo['borrow_interest_rate'];
				$monthData['type'] = "all";
				$repay_detail = EqualEndMonthOnly($monthData, $durationMonth, $lastInterest);
                if( $lastInvest === false ) {
                    $investinfo['investor_interest'] = ($repay_detail['repayment_account'] - $investinfo['investor_capital']);
                }
				$investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest'],4);
				$invest_info_id = M('borrow_investor')->add($investinfo);
                if( !empty($invest_info_id) ) {
                    //还款概要END
                    $investdetail['borrow_id'] = $borrow_id;
                    $investdetail['invest_id'] = $invest_info_id;
                    $investdetail['investor_uid'] = $uid;
                    $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                    $investdetail['capital'] = $repay_detail['capital'];
                    $investdetail['interest'] = $repay_detail['interest'];
                    $investdetail['interest_fee'] = getFloatValue($fee_rate*$repay_detail['interest'],4);
                    $investdetail['status'] = 0;
                    $investdetail['sort_order'] = 1;
                    $investdetail['total'] = 1;
                    if( $taste_money > 0 ) {
                        $investdetail['taste_money'] = $taste_money;
                    }
                    $savedetail[] = $investdetail;
                } else {
                    $db->rollback();
                    return false;
                }
			break;
		}
		if( !empty($savedetail) ) {
            $invest_defail_id = M('investor_detail')->addAll($savedetail);//保存还款详情 batch insert
            if( empty($invest_defail_id) ) {
                $db->rollback();
                return false;
            }
        }

		$last_have_money = M("borrow_info")->lock(true)->getFieldById($borrow_id,"has_borrow");
		$upborrowsql = "update `{$pre}borrow_info` set ";
		$upborrowsql .= "`has_borrow`=".($last_have_money+$money).",`borrow_times`=`borrow_times`+1";
		$upborrowsql .= " WHERE `id`={$borrow_id}";
		$upborrow_res = $db->execute($upborrowsql);
		
		//更新投标进度
	if( !empty($upborrow_res) ){//还款概要和详情投标进度都保存成功
		$res = memberMoneyLog($uid,6,-$money+$discount_money,"对{$borrow_id}号标进行投标",$binfo['borrow_uid'],'',0,$db, $discount_money);
        if( empty($res) ) {
            $db->rollback();
            return false;
        }
		if( ($havemoney+$money) == $binfo['borrow_money']){
            if(!borrowFull($borrow_id,$binfo['borrow_type'])) { //满标，标记为复审中，更新相关数据
                $db->rollback();
                return false;
            } else {
                $full_borrow = true;
            }
		}
		$done = true;
	}else{
        $db->rollback();
        return false;
	}
    $db->commit();
    invite_reward($uid); //成功之后，发送邀请奖励优惠券, TODO:发送优惠券暂不需要事务处理
    if( isset($full_borrow) && $full_borrow == true ){
        //满标发送通知信息
        NoticeSet("chk10", $binfo['borrow_uid'], $borrow_id);
    }
	return $done;
}
//满标处理
function borrowFull($borrow_id,$btype = 0){
	$pre = C('DB_PREFIX');
    $saveborrow['borrow_status']=4;
    $saveborrow['full_time']=time();
    $upborrow_res = M("borrow_info")->where("id={$borrow_id}")->save($saveborrow);
	if($btype==3){//秒还标
		borrowApproved($borrow_id);
		sleep(3);
		borrowRepayment($borrow_id,1);
	}
    return $upborrow_res;
}

/**
 * 复审不通过
 * @param $borrow_id
 * @param $type $type=3 代表流标返还; $type=3代表复审未通过，返还
 * @param null $db
 * @return bool
 */
function borrowRefuse($borrow_id, $type, $db = null){
	$pre = C('DB_PREFIX');
	$done = false;
	$borrowInvestor = D('borrow_investor');
	$binfo = M("borrow_info")->field("id,borrow_type,borrow_money,add_time,borrow_uid,borrow_duration,repayment_type")->find($borrow_id);
	//$investorList = $borrowInvestor->field('id,investor_uid,investor_capital')->where("borrow_id={$borrow_id}")->select();
	$investorList = M("borrow_investor")->field('coupon_id,id,investor_uid,investor_capital')->where("borrow_id={$borrow_id}")->select();
	M('investor_detail')->where("borrow_id={$borrow_id}")->delete();//流标将删除其对应的还款记录表

    if( !isset($db)) {
        $db = M();
    }
    //查询此标使用优惠券的情况，返还实际支付的金额，优惠券也一并返还
    $coupon_items = M('expand_money')
        ->field("id,uid,money")
        ->where(array('loanno' => $borrow_id,'status'=>4))
        ->select();
    $discount_money = 0; // 折扣金额

    if( !empty($coupon_items) ) {
        //返还优惠券,过期时间再加上标的募集期时间
        $diff_time = time() - $binfo['add_time'];
        $sql = "update {$pre}expand_money set status=1,expired_time = expired_time+{$diff_time} where loanno={$borrow_id}";
        if( !$db->execute($sql) ) {
            $db->rollback();
        }
    }

	if($binfo['borrow_type']==1){//如果是普通标
		$limit_credit = memberLimitLog($binfo['borrow_uid'],12,($binfo['borrow_money']),$info="{$borrow_id}号标流标,返还借款信用额度");//返回借款额度
	}


	$bstatus = ($type==2)?3:5;//3:标未满，结束，流标   5:复审未通过，结束
	$upborrow_info = M('borrow_info')->where("id={$borrow_id}")->setField("borrow_status",$bstatus);
	//处理借款概要
	$buname = M('members')->getFieldById($binfo['borrow_uid'],'user_name');
	//处理借款概要
	
	if(is_array($investorList)){
		$upsummary_res = M('borrow_investor')->where("borrow_id={$borrow_id}")->setField("status",$type);
		$moneynewid_x_temp = true;
		$bxid_temp = true;
		foreach($investorList as $v){
              $discount_money=0;
		    NoticeSet('chk15',$v['investor_uid'],$borrow_id);//sss
			$accountMoney_investor = M("member_money")->field(true)->find($v['investor_uid']);
            if( !empty($coupon_items) ) {
                foreach( $coupon_items as $val ) {     //$arr=  explode(',', ltrim($str, ','));
                    if($val['uid'] == $v['investor_uid'] && strpos($v['coupon_id'],$val['id'])) {
                        $discount_money = $val['money'];
                        break;
                    }
                }
            }

			$datamoney_x['uid'] = $v['investor_uid'];
			$datamoney_x['type'] = ($type==3)?16:8;
			$datamoney_x['affect_money'] = $v['investor_capital'] - $discount_money;
			$datamoney_x['account_money'] = ($accountMoney_investor['account_money'] + $datamoney_x['affect_money']);//投标不成功返回充值资金池
			$datamoney_x['collect_money'] = $accountMoney_investor['money_collect'];
			$datamoney_x['freeze_money'] = $accountMoney_investor['money_freeze'] - $datamoney_x['affect_money'] - $discount_money;//冻结金额扣除(本金+折扣金额)
			$datamoney_x['back_money'] = $accountMoney_investor['back_money'];
			
			//会员帐户
			$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
			$mmoney_x['money_collect']=$datamoney_x['collect_money'];
			$mmoney_x['account_money']=$datamoney_x['account_money'];
			$mmoney_x['back_money']=$datamoney_x['back_money'];
			
			//会员帐户
			$_xstr = ($type==3)?"复审未通过":"募集期内标未满,流标";
			$datamoney_x['info'] = "第{$borrow_id}号标".$_xstr."，返回冻结资金";
			$datamoney_x['add_time'] = time();
			$datamoney_x['add_ip'] = get_client_ip();
			$datamoney_x['target_uid'] = $binfo['borrow_uid'];
			$datamoney_x['target_uname'] = $buname;
			$moneynewid_x = M('member_moneylog')->add($datamoney_x);
			if($moneynewid_x) $bxid = M('member_money')->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
			$moneynewid_x_temp = $moneynewid_x_temp && $moneynewid_x;
		    $bxid_temp = $bxid_temp && $bxid;

		}
	}else{
		$moneynewid_x_temp = true;
		$bxid_temp = true;
		$upsummary_res=true;
	}

	if($moneynewid_x_temp && $upsummary_res && $bxid_temp && ($upborrow_info!== false)){
		/////////////////////////回款续投奖励已变成奖励投资积分，在复审通过时奖励，所以不存在返还续投奖励积分，已删150424 minister.xiang@gmail.com///////////////////////////////
		$done=true;
        if( !isset($db) ) $borrowInvestor->commit();
	}else{
        if( !isset($db) ) $borrowInvestor->rollback();
	}
	
	return $done;
}


//借款成功，进入复审处理
function borrowApproved($borrow_id){
    set_time_limit(0);
	$pre = C('DB_PREFIX');
	$done = false;
	$_P_fee = get_global_setting();
	$invest_integral = $_P_fee['invest_integral'];//投资积分
	$borrowInvestor = D('borrow_investor');
    // borrow_info 借款信息管理表
	$binfo = M("borrow_info")->field("money_deposit,borrow_type,total,reward_type,reward_num,borrow_fee,borrow_money,borrow_interest,borrow_uid,borrow_duration,duration_unit,repayment_type,borrow_interest_rate")->find($borrow_id);
	$investorList = $borrowInvestor->field('id,borrow_id,investor_uid,investor_capital,investor_interest,reward_money, add_time,taste_money')
        ->where("borrow_id={$borrow_id}")
        ->order("id asc") // 升序排列
        ->select();
    
	//$endTime = strtotime(date("Y-m-d",time())." 23:59:59");
	//借款天数、还款时间
	$endTime = strtotime(date("Y-m-d",time())." ".$_P_fee['back_time']);
    //复审通过时，借款时间都变成当前时间，截止日期也重新计算
    $deadline_last = BorrowModel::get_deadline_time($binfo['duration_unit'],$binfo['borrow_duration'],$endTime );

	$getIntegralDays = intval(($deadline_last-$endTime)/3600/24);//借款天数
	
	//////////////////////////////////
     
    try{  //捕获错误异常
	    //更新投资概要
        $reward_money_update = array();

	    foreach($investorList as $key=>$v){
		    $_reward_money=0;
		    if($binfo['reward_type']>0){
                $reward_money_update[$v['id']] = getFloatValue($v['investor_capital']*$binfo['reward_num']/100,4);
		    }
	    }
        $investor_uids = only_array($investorList, 'investor_uid');
        NoticeSet('chk14', $investor_uids, $borrow_id); // I/O消耗太大，可扔进Queue
        //更新投资概要
        $upsummary_res = M()->execute("update `{$pre}borrow_investor` set `deadline`={$deadline_last},`status`=4 WHERE `borrow_id`={$borrow_id} ");
        if( $upsummary_res !== false && !empty($reward_money_update) ) {
            $ids = implode(',', array_keys($reward_money_update));
            $sql = "UPDATE `{$pre}borrow_investor` SET reward_money = CASE id ";
            foreach ($reward_money_update as $id => $ordinal) {
                $sql .= sprintf("WHEN %d THEN %f ", $id, $ordinal);
            }
            $sql .= "END WHERE id IN ($ids)";
            $ret_reward_money = M()->execute($sql);
            if( $ret_reward_money === false ) {
                return false;
            }
        }
        $borrowInvestor->startTrans();
	    //更新借款信息
        $secondtime = time();
	    $upborrow_res = M()->execute("update `{$pre}borrow_info` set `deadline`={$deadline_last},`second_verify_time`={$secondtime},`borrow_status`=6  WHERE `id`={$borrow_id}");
	    //更新借款信息
	    //更新投资详细

	    switch($binfo['repayment_type']){
		    case 2://每月还款
		    case 3://每季还本
                for($i=1;$i<=$binfo['borrow_duration'];$i++){
                    $deadline=0;
                    $deadline=strtotime("+{$i} month",$endTime);
                    $updetail_res = M()->execute("update `{$pre}investor_detail` set `deadline`={$deadline},`status`=7 WHERE `borrow_id`={$borrow_id} AND `sort_order`=$i");
                }
                break;
		    case 4://按天计息，按月付息，到期还本
            /**
             * 这个不是按30天每个月还款，每个月还款日相同，详情参考文档,如果利息以复审之后开始计算，还款时间需要在这个时间
             * 天数变了，每个回款日所得的利息也变了。
             * interest,interest_fee,status,deadline
             */
             $datag = get_global_setting();
             $fee_rate=$datag['fee_invest_manage']/100;
             foreach($investorList as $key => $val ) { // 给每个borrow_investor创建还款详情表
                 $savedetail = array();
                 $interest_prov_all = 0;
                 $borrowScan = EqualEndMonth(array(
                     'duration' => $binfo['borrow_duration'],
                     'account' => $val['investor_capital'],
                     'year_apr' => $binfo['borrow_interest_rate']
                 ), true);
                 $tasteScan = null;
                 if( !empty($val['taste_money']) ) {
                     $tasteScan = EqualEndMonth(array(
                         'duration' => $binfo['borrow_duration'],
                         'account' => $val['taste_money'],
                         'year_apr' => $binfo['borrow_interest_rate']
                     ), true);
                 }
                 $i=1;
                 foreach($borrowScan as $k=>$v){
                     $investdetail['borrow_id'] = $borrow_id;
                     $investdetail['invest_id'] = $val['id'];
                     $investdetail['investor_uid'] = $val['investor_uid'];
                     $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                     $investdetail['capital'] = $v['capital'];
                     if( $k == count($borrowScan) -1 && $interest_prov_all > 0 ) {
                         $investdetail['interest'] = $val['investor_interest'] - $interest_prov_all;
                     } else {
                         $investdetail['interest'] = $v['interest'];
                     }
                     $investdetail['interest_fee'] = getFloatValue($fee_rate*$v['interest'],4);
                     $investdetail['status'] = 7;
                     $investdetail['sort_order'] = $i;
                     $investdetail['total'] = count($borrowScan); // 总基数
                     $investdetail['deadline'] = $v['repayment_time'];
                     if( !empty($tasteScan) ) {
                         foreach( $tasteScan as $kk=>$vv) {
                             if( $kk == $k ) {
                                 $investdetail['taste_money'] = $vv['capital'];
                                 break;
                             }
                         }
                     }else{
                         $investdetail['taste_money'] = '0.00';
                     }
                     $i++;
                     $interest_prov_all += $investdetail['interest'];
                     $savedetail[] = $investdetail;
                 }
                 if( !empty($savedetail) ) {
                     $updetail_res = M('investor_detail')->addAll($savedetail);//保存还款详情
                 }
             }
		    break;
		    case 1://按天一次性还款
			case 5://一次性还款
				    $deadline=0;
				    $deadline=$deadline_last;
				    $updetail_res = M()->execute("update `{$pre}investor_detail` set `deadline`={$deadline},`status`=7 WHERE `borrow_id`={$borrow_id}");
		    break;
	    }		
        
        if($updetail_res && $upsummary_res !== false && $upborrow_res){
            $done=true;
        }else{
			$done=false;
			$borrowInvestor->rollback();
		}
    }catch(Exception $e){
        $done=false;
        $borrowInvestor->rollback();
    }
    
    
	//更新投资详细

	// 当以上操作没有异常正确执行后执行下面的工作
	if($done){
        special_award($borrow_id);

		//借款者帐户
			$_P_fee=get_global_setting();
			
			$_borraccount = memberMoneyLog($binfo['borrow_uid'],17,$binfo['borrow_money'],"第{$borrow_id}号标复审通过，借款金额入帐", "", "", 0, $borrowInvestor);//借款入帐
        if( !empty($_borraccount) ) {
            $_borrfee = memberMoneyLog($binfo['borrow_uid'],18,-$binfo['borrow_fee'],"第{$borrow_id}号标借款成功，扣除借款管理费", "", "", 0, $borrowInvestor);//借款
            if( !empty($_borrfee) ) {
                      if($binfo['money_deposit']>0){
                              $_freezefee = memberMoneyLog($binfo['borrow_uid'],19,-$binfo['borrow_money']*$binfo['money_deposit']/100,"第{$borrow_id}号标借款成功，冻结{$binfo['money_deposit']}%的保证金", "", "", 0, $borrowInvestor);//冻结保证金
                              if(empty($_freezefee) ) {
                                  $borrowInvestor->rollback();
                                  return false;
                              }      
                      }
            } else {
                $borrowInvestor->rollback();
                return false;
            }
        } else {
            $borrowInvestor->rollback();
            return false;
        }
		//借款者帐户
        //查询所有用户的回款金额
        $back_money_where = array(
            'uid'=> array("in", implode(',', $investor_uids)),
            'back_money'=>array("gt",0)
        );
        $invest_back_money = M('member_money')->field('uid, back_money')->where($back_money_where)->select();


		foreach($investorList as $v){  //TODO： 181投资人，70多秒
			//////////////////////////增加投资者的投资积分 2013-08-28 fans////////////////////////////////////
			$integ = intval($v['investor_capital']*$getIntegralDays*$invest_integral/1000);
			$reintegral = memberIntegralLog(
                $v['investor_uid'],
                2,
                $integ,
                "第{$borrow_id}号标复审通过，应获积分：".$integ."分,投资金额：".$v['investor_capital']."元,投资天数：".$getIntegralDays."天",
                $borrowInvestor
            );
            if( empty($reintegral) ) {
                $borrowInvestor->rollback();
                return false;
            }
			if(isBirth($v['investor_uid'])){
				$reintegral = memberIntegralLog($v['investor_uid'],2,$integ,"亲，祝您生日快乐，本站特赠送您{$integ}积分作为礼物，以表祝福。", $borrowInvestor);
			}
			//////////////////////////增加投资者的投资积分 2013-08-28 fans////////////////////////////////////
			
			//////////////////////////处理待收金额为负的问题/////////////////////

			$wmap['investor_uid'] = $v['investor_uid'];
			$wmap['borrow_id'] = $v['borrow_id'];
			$daishou = M('investor_detail')->field('interest')->where("invest_id ={$v['id']}")->sum('interest');//待收金额
			//////////////////////////处理待收金额为负的问题/////////////////////
			//投标奖励
			if($binfo['reward_num']>0){
                $reward_money = null;
                $reward_money = getFloatValue($v['investor_capital']*$binfo['reward_num']/100,4);
				$_reward_m = memberMoneyLog($v['investor_uid'],20,$reward_money,"第{$borrow_id}号标复审通过，获取投标奖励",$binfo['borrow_uid'],'',0,$borrowInvestor);
                if( !empty($_reward_m) ) {
                    $_reward_m_give = memberMoneyLog($binfo['borrow_uid'],21,-$reward_money,"第{$borrow_id}号标复审通过，支付投标奖励",$v['investor_uid'],'',0,$borrowInvestor);
                    if( empty($_reward_m_give) ) {
                        $borrowInvestor->rollback();
                        return false;
                    }
                } else {
                    $borrowInvestor->rollback();
                    return false;
                }
			}
			//投标奖励
			
			$remcollect = memberMoneyLog($v['investor_uid'],15,$v['investor_capital'],"第{$borrow_id}号标复审通过，冻结本金成为待收金额",$binfo['borrow_uid'],'',0,$borrowInvestor);
            if( !empty($remcollect) ) {
                $reinterestcollect = memberMoneyLog($v['investor_uid'],28,$daishou,"第{$borrow_id}号标复审通过，应收利息成为待收利息",$binfo['borrow_uid'],'',0,$borrowInvestor);
                if( empty($reinterestcollect) ) {
                    $borrowInvestor->rollback();
                    return false;
                }
            } else {
                $borrowInvestor->rollback();
                return false;
            }

			$back_money = null;
            foreach( $invest_back_money as $val ) {
                if( $v['investor_uid'] == $val['uid'] ) {
                    $back_money = $val['back_money'];
					// 回款续投奖励,不使用事务处理，提高效率
					return_reward($borrow_id, $v['investor_uid'], $back_money,  $v['investor_capital'], $binfo['duration_unit'], $binfo['borrow_duration'], $borrowInvestor);
                    break;
                }
            }

		}

	}
    $borrowInvestor->commit();
	return $done;
}

function lastRepayment($binfo, $db=null){
	$x=true;//因为下面有!x的判断，所以为了避免影响其他标，这里默认为true
	if($binfo['borrow_type']==2){
		$x=false;
		//返回借款人的借款担保额度
		$x = memberLimitLog($binfo['borrow_uid'],8,($binfo['borrow_money']),$info="{$binfo['id']}号标还款完成");
		if(!$x) return false;
		//返回投资人的投资担保额度
		$vocuhlist = M('borrow_vouch')->field("uid,vouch_money")->where("borrow_id={$binfo['id']}")->select();
		foreach($vocuhlist as $vv){
			$x = memberLimitLog($vv['uid'],10,($vv['vouch_money']),$info="您担保的{$binfo['id']}号标还款完成");
		}
	}elseif($binfo['borrow_type']==1){
		$x=false;
		$x = memberLimitLog($binfo['borrow_uid'],7,($binfo['borrow_money']),$info="{$binfo['id']}号标还款完成");
	}
	//如果是担保
	
	if(!$x) return false;
	


	//解冻保证金
    if( $binfo['money_deposit'] > 0 ) {
        $accountMoney_borrower = M('member_money')->field('account_money,money_collect,money_freeze,back_money')->find($binfo['borrow_uid']);
        $datamoney_x['uid'] = $binfo['borrow_uid'];
        $datamoney_x['type'] = 24;
        $datamoney_x['affect_money'] = ($binfo['borrow_money']*$binfo['money_deposit']/100);
        $datamoney_x['account_money'] = ($accountMoney_borrower['account_money'] + $datamoney_x['affect_money']);
        $datamoney_x['collect_money'] = $accountMoney_borrower['money_collect'];
        $datamoney_x['freeze_money'] = ($accountMoney_borrower['money_freeze']-$datamoney_x['affect_money']);
        $datamoney_x['back_money'] = $accountMoney_borrower['back_money'];

        //会员帐户
        $mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
        $mmoney_x['money_collect']=$datamoney_x['collect_money'];
        $mmoney_x['account_money']=$datamoney_x['account_money'];
        $mmoney_x['back_money']=$datamoney_x['back_money'];

        //会员帐户
        $datamoney_x['info'] = "网站对{$binfo['id']}号标还款完成的解冻保证金";
        $datamoney_x['add_time'] = time();
        $datamoney_x['add_ip'] = get_client_ip();
        $datamoney_x['target_uid'] = 0;
        $datamoney_x['target_uname'] = '@网站管理员@';
        $moneynewid_x = M('member_moneylog')->add($datamoney_x);
        if($moneynewid_x) $bxid = M('member_money')->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
        //解冻保证金
    }

	if($x) return true;
	else return false;
}

//借款人记账 //`mxl 20150310`
function setBorrowlog($capital, $interest, $uid, $borrow_id, $transfer_repayment, $sort_order, $accountMoney_borrower, &$datamoney_x, &$mmoney_x, $db=null){
    if( !isset($db) ) return false;
	$affect_money = array();
	$mmoney_x = $accountMoney_borrower;
	if ($capital > 0){ $affect_money['capital'] = array("money" => $capital, "type" => "11", "text" => "本金"); }
	if ($interest > 0){ $affect_money['interest'] = array("money" => $interest, "type" => "50", "text" => "利息"); }
    $ret = false;
	foreach ($affect_money as $affk => $affv){
		$datamoney_x = array();
		$datamoney_x['uid'] = $uid;
		$datamoney_x['type'] = $affv['type'];
		$datamoney_x['affect_money'] = -$affv['money'];
		if(($datamoney_x['affect_money']+$mmoney_x['back_money'])<0){//如果需要还款的金额大于回款资金池资金总额
			$datamoney_x['account_money'] = $mmoney_x['account_money']+$mmoney_x['back_money'] + $datamoney_x['affect_money'];
			$datamoney_x['back_money'] = 0;
		}else{
			$datamoney_x['account_money'] = $mmoney_x['account_money'];
			$datamoney_x['back_money'] = $mmoney_x['back_money'] + $datamoney_x['affect_money'];//回款资金注入回款资金池
		}	
		$datamoney_x['collect_money'] = $mmoney_x['money_collect'];
		$datamoney_x['freeze_money'] = $mmoney_x['money_freeze'];
		$datamoney_x['info'] = "对{$borrow_id}号".$transfer_repayment."第{$sort_order}期还款（{$affv['text']}）";
		$datamoney_x['add_time'] = time();
		$datamoney_x['add_ip'] = get_client_ip();
		$datamoney_x['target_uid'] = 0;
		$datamoney_x['target_uname'] = '@网站管理员@';
        $ret =  $db->table(C('DB_PREFIX').'member_moneylog')->add($datamoney_x);
        if( empty($ret) ) {
            $db->rollback();
            $this->error('服务器忙');
        }
		$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
		$mmoney_x['money_collect']=$datamoney_x['collect_money'];
		$mmoney_x['account_money']=$datamoney_x['account_money'];
		$mmoney_x['back_money']=$datamoney_x['back_money'];
	}
	return $ret;
}

//投资人记账 //`mxl 20150310`
function setInvestlog($capital, $interest, $uid, $buid, $buname, $borrow_id, $borrow_type, $repay_type, $transfer_repayment, $sort_order, &$datamoney, &$mmoney, $db=null, $type =1, $taste_money = 0){
	$mmoney = M('member_money')->field('money_freeze,money_collect,account_money,back_money')->find($uid);
	$affect_money = array();
	if ($capital > 0){ $affect_money['capital'] = array("money" => $capital, "taste_money"=>$taste_money, "type" => ((intval($repay_type) === 2) ? "10" : "9"), "text" => "本金"); }
	if ($interest > 0){ $affect_money['interest'] = array("money" => $interest,  "taste_money"=>0, "type" => ((intval($repay_type) === 2) ? "52" : "51"), "text" => "利息"); }
    $ret = false;
	foreach ($affect_money as $affk => $affv){
		$datamoney = array();
		$datamoney['uid'] = $uid;
		$datamoney['type'] = $affv['type'];
		$datamoney['affect_money'] = $affv['money'];
		$datamoney['collect_money'] = $mmoney['money_collect'] - $datamoney['affect_money'];//之前加多少，现在减多少，之前没扣除体验金
		$datamoney['freeze_money'] = $mmoney['money_freeze'];
		///////////////秒标回款不进入汇款资金池，也就可实现秒标回款不给回款续投奖励的功能了 2013-08-23 fan//////////////////

		if($borrow_type <> 3 ){//如果不是秒标，那么回的款会进入回款资金池，如果是秒标，回款则会进入充值资金池
			$datamoney['account_money'] = $mmoney['account_money'];
			$datamoney['back_money'] = ($mmoney['back_money'] + $datamoney['affect_money']  - $affv['taste_money']);
		}else{
			$datamoney['account_money'] = $mmoney['account_money'] + $datamoney['affect_money']  - $affv['taste_money'];
			$datamoney['back_money'] = $mmoney['back_money'];
		}

		///////////////秒标回款不进入汇款资金池，也就可实现秒标回款不给回款续投奖励的功能了 2013-08-23 fan//////////////////
        $suffix = '';
        if( $affv['taste_money'] > 0) {
            $datamoney['affect_money'] = $datamoney['affect_money'] - $affv['taste_money'];
            $suffix = ",扣除体验金{$taste_money}元";
        }
		$datamoney['info'] = ($type==2)?"网站对{$borrow_id}号".$transfer_repayment."第{$sort_order}期代还".$suffix:"收到会员对{$borrow_id}号".$transfer_repayment."第{$sort_order}期的还款（{$affv['text']}）".$suffix;
		$datamoney['add_time'] = time();
		$datamoney['add_ip'] = get_client_ip();
		if($type==2){
			$datamoney['target_uid'] = 0;
			$datamoney['target_uname'] = '@网站管理员@';
		}else{
			$datamoney['target_uid'] = $buid;
			$datamoney['target_uname'] = $buname;
		}
        $ret = $db->table(C('DB_PREFIX').'member_moneylog')->add($datamoney);
		$mmoney['money_freeze']=$datamoney['freeze_money'];
		$mmoney['money_collect']=$datamoney['collect_money'];
		$mmoney['account_money']=$datamoney['account_money'];
		$mmoney['back_money']=$datamoney['back_money'];
	}
	return $ret;
}

/**
 * @param $borrow_id
 * @param $sort_order
 * @param int $type
 * @return bool
 */
function borrowRepayment($borrow_id,$sort_order,$type=1){//type 1:会员自己还,2网站代还
    $designer = FS("Webconfig/designer");
	$pre = C('DB_PREFIX');
	$done = false;
    $msg = '服务器忙';
	$borrowDetail = D('investor_detail');
    // 处理债权转让
    $debtBehavior = new DebtBehavior();
    $debtBehavior->repaymentDealDebt($borrow_id);

	$binfo = M("borrow_info")->field("money_deposit,id,borrow_uid,borrow_type,borrow_money,borrow_duration,repayment_type,has_pay,total,deadline")->find($borrow_id);
    // 区分企业直投还款还是个人还款
    $transfer_repayment = ( $binfo['borrow_type'] == BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID ) ? $designer[6] : '标';

	$b_member=M('members')->field("user_name")->find($binfo['borrow_uid']);
	if( $binfo['deadline']>time()){
		if( $binfo['has_pay']>=$sort_order) return "本期已还过，不用再还";
		if( $binfo['has_pay'] == $binfo['total'])  return "此标已经还完，不用再还";
	}

	//if( $binfo['deadline']>time() && $type==2)  return "此标还没逾期，不用代还";
	//企业直投与普通标,判断还款期数不一样
	$voxe = $borrowDetail->field('sort_order,sum(capital) as capital, status, sum(interest) as interest,sum(interest_fee) as interest_fee,deadline,substitute_time')
                        ->where("borrow_id={$borrow_id} and status<>14") //TODO: 14为转让状态
                        ->group('sort_order')
                        ->select();
    $all_status = array();
	foreach($voxe as $ee=>$ss){
		if($ss['sort_order']==$sort_order) {
            $vo = $ss;
            break;
        }else {
            $all_status[] = $ss['status'];
        }
	}

    if( $type == 1 ) {
        if( $binfo['borrow_type'] == BorrowModel::BID_CONFIG_TYPE_FINANCIAL ) {
            $need_web_pay = InvestorDetailModel::get_need_self_repay_status();
            $is_need_repay = array_intersect($need_web_pay, $all_status);
            if( !empty($is_need_repay) ) {
                return "对不起，此借款的上一期还未完，请先还上一期";
            }
        }else {
            if( ($binfo['has_pay']+1)<$sort_order) return "对不起，此借款第".($binfo['has_pay']+1)."期还未还，请先还第".($binfo['has_pay']+1)."期";
        }
    }else {
        //判断是否代还过，是否需要还，上一期未还的不允许代还下一期
        $need_web_pay = InvestorDetailModel::get_need_web_repay_status();
		//判断状态是否是未还，如果已还或者已代还过，则不应该再代还
		if( !in_array($vo['status'], $need_web_pay) ) {
			return '系统繁忙';
		}
        $is_need_repay = array_intersect($need_web_pay, $all_status);
        if( !empty($is_need_repay) ) {
            return "对不起，此借款的上一期还未完，请先还上一期";
        }
    }

	if($vo['deadline']<time()){//此标已逾期
		$is_expired = true;
		if($vo['substitute_time']>0) $is_substitute=true;//已代还
		else $is_substitute=false;
		//逾期的相关计算
		$expired_days = getExpiredDays($vo['deadline']);
		$expired_money = getExpiredMoney($expired_days,$vo['capital'],$vo['interest']);
		$call_fee = getExpiredCallFee($expired_days,$vo['capital'],$vo['interest']);
		//逾期的相关计算
	}else{
		$is_expired = false;
		$expired_days = 0;
		$expired_money = 0;
		$call_fee = 0;
	}
    $db = new Model();
    $db->startTrans(); //开启事务

	NoticeSet('chk25',$binfo['borrow_uid'],$borrow_id);//sss
	$accountMoney_borrower = M('member_money')->field('money_freeze,money_collect,account_money,back_money')->find($binfo['borrow_uid']);
	if($type==1 && $binfo['borrow_type']<>3 && ($accountMoney_borrower['account_money']+$accountMoney_borrower['back_money'])<($vo['capital']+$vo['interest']+$expired_money+$call_fee)) return "帐户可用余额不足，本期还款共需".($vo['capital']+$vo['interest']+$expired_money+$call_fee)."元，请先充值";
	if($is_substitute && $is_expired){//已代还后的会员还款，则只需要对会员的帐户进行操作后然后更新还款时间即可返回
			setBorrowlog($vo['capital'], $vo['interest'], $binfo['borrow_uid'], $borrow_id, $transfer_repayment, $sort_order, $accountMoney_borrower, $datamoney_x, $mmoney_x, $db);

			//逾期罚息
			$accountMoney = $mmoney_x;
			$datamoney_x = array();
			$mmoney_x=array();
			
			$datamoney_x['uid'] = $binfo['borrow_uid'];
			$datamoney_x['type'] = 30;
			$datamoney_x['affect_money'] = -($expired_money);
			if(($datamoney_x['affect_money']+$accountMoney['back_money'])<0){//如果需要还款的逾期罚息金额大于回款资金池资金总额
				$datamoney_x['account_money'] = $accountMoney['account_money']+$accountMoney['back_money'] + $datamoney_x['affect_money'];
				$datamoney_x['back_money'] = 0;
			}else{
				$datamoney_x['account_money'] = $accountMoney['account_money'];
				$datamoney_x['back_money'] = $accountMoney['back_money'] + $datamoney_x['affect_money'];//回款资金注入回款资金池
			}	
			$datamoney_x['collect_money'] = $accountMoney['money_collect'];
			$datamoney_x['freeze_money'] = $accountMoney['money_freeze'];
			
			//会员帐户
			$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
			$mmoney_x['money_collect']=$datamoney_x['collect_money'];
			$mmoney_x['account_money']=$datamoney_x['account_money'];
			$mmoney_x['back_money']=$datamoney_x['back_money'];
			//会员帐户
			$datamoney_x['info'] = "{$borrow_id}号".$transfer_repayment."第{$sort_order}期的逾期罚息";
			$datamoney_x['add_time'] = time();
			$datamoney_x['add_ip'] = get_client_ip();
			$datamoney_x['target_uid'] = 0;
			$datamoney_x['target_uname'] = '@网站管理员@';
			$moneynewid_x = $db->table("{$pre}member_moneylog")->add($datamoney_x);
            if( empty($moneynewid_x) ) {
                $db->rollback();
                return $msg;
            }

			//催收费
			$accountMoney_2 = $mmoney_x;
			$datamoney_x = array();
			$mmoney_x=array();
			
			$datamoney_x['uid'] = $binfo['borrow_uid'];
			$datamoney_x['type'] = 31;
			$datamoney_x['affect_money'] = -($call_fee);
			if(($datamoney_x['affect_money']+$accountMoney_2['back_money'])<0){//如果需要还款的催收费金额大于回款资金池资金总额
				$datamoney_x['account_money'] = $accountMoney_2['account_money']+$accountMoney_2['back_money'] + $datamoney_x['affect_money'];
				$datamoney_x['back_money'] = 0;
			}else{
				$datamoney_x['account_money'] = $accountMoney_2['account_money'];
				$datamoney_x['back_money'] = $accountMoney_2['back_money'] + $datamoney_x['affect_money'];//回款资金注入回款资金池
			}	
			$datamoney_x['collect_money'] = $accountMoney_2['money_collect'];
			$datamoney_x['freeze_money'] = $accountMoney_2['money_freeze'];
				
			//会员帐户
			$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
			$mmoney_x['money_collect']=$datamoney_x['collect_money'];
			$mmoney_x['account_money']=$datamoney_x['account_money'];
			$mmoney_x['back_money']=$datamoney_x['back_money'];
			//会员帐户
			$datamoney_x['info'] = "网站对借款人收取的第{$borrow_id}号".$transfer_repayment."第{$sort_order}期的逾期催收费";
			$datamoney_x['add_time'] = time();
			$datamoney_x['add_ip'] = get_client_ip();
			$datamoney_x['target_uid'] = 0;
			$datamoney_x['target_uname'] = '@网站管理员@';
			$moneynewid_x = $db->table("{$pre}member_moneylog")->add($datamoney_x);
			if($moneynewid_x) {
                $bxid_3 =  $db->table("{$pre}member_money")->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
                if( $bxid_3 === false ) {
                    $db->rollback();
                    return $msg;
                }
            }else {
                $db->rollback();
                return $msg;
            }
		
		//逾期了
			$updetail_res = M()->execute("update `{$pre}investor_detail` set `repayment_time`=".time().",`status`=5 WHERE `borrow_id`={$borrow_id} AND status<>14 AND `sort_order`={$sort_order}");
        if( empty($updetail_res) ) {
            $db->rollback();
            return $msg;
        }
			//更新借款信息
			$upborrowsql = "update `{$pre}borrow_info` set ";
			$upborrowsql .= "`substitute_money`=0";
			if ( $sort_order == $binfo['total'] )
			{
				$upborrowsql .= ",`borrow_status`=10";
			}
			$upborrowsql .= ",`has_pay`={$sort_order}";
			if ( $is_expired )
			{
				$upborrowsql .= ",`expired_money`=`expired_money`+{$expired_money}";
			}
			$upborrowsql .= " WHERE `id`={$borrow_id}";
			$upborrow_res = $db->execute($upborrowsql);
			//更新借款信息

		if( empty($upborrow_res) ){
            $db->rollback();
            return false;
		}else {
            $db->commit();
            return true;
        }
	}

	//企业直投与普通标,判断还款期数不一样
	  $detailList = $borrowDetail->field('invest_id,investor_uid,capital,interest,interest_fee,borrow_id,total,taste_money')->where("borrow_id={$borrow_id} AND status<>14 AND sort_order={$sort_order}")->select();

	/*************************************逾期还款积分与还款状态处理开始 20130509 fans***********************************/
	$datag = get_global_setting();
	$credit_borrow = explode("|",$datag['credit_borrow']);
	if($type==1){//客户自己还款才需要记录这些操作
		$day_span = ceil(($vo['deadline']-time())/(3600*24));
        //TODO: 逾期扣除信用积分是以用户的本金来计算，在按天计息（按天计息按月付息到期还本）的时候，除最后一期外前面还款的是利息，所以将其本金平均分摊到每一期扣除信用积分。
        if( $binfo['repayment_type'] == BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_FINAL_CAPITAL ) {
            $credits_money = intval(($vo['capital']/$binfo['total'])/$credit_borrow[2]);
        } else {
            $credits_money = intval($vo['capital']/$credit_borrow[2]);
        }
		if($day_span>=0 && $day_span<=3){//正常还款

			$credits_result = memberIntegralLog($binfo['borrow_uid'],20,intval($vo['capital']/1000),"对第{$borrow_id}号".$transfer_repayment."进行了正常的还款操作", $db);//还款积分处理
			$idetail_status=1;
		}elseif($day_span>=-3 && $day_span<0){//迟还
			$credits_result = borrowCreditsLog($binfo['borrow_uid'],20,$credits_money*$credit_borrow[0],"对第{$borrow_id}号".$transfer_repayment."的还款操作(迟到还款),扣除信用积分", $db);
			$idetail_status=3;
		}elseif($day_span<-3){//逾期还款
			$credits_result = borrowCreditsLog($binfo['borrow_uid'],20,$credits_money*$credit_borrow[1],"对第{$borrow_id}号".$transfer_repayment."的还款操作(逾期还款),扣除信用积分", $db);
			$idetail_status=5;
		}elseif($day_span>3){//提前还款
			$credits_result = memberIntegralLog($binfo['borrow_uid'],20,intval($vo['capital'] * $day_span/1000),"对第{$borrow_id}号".$transfer_repayment."进行了提前还款操作,获取投资积分", $db);//还款积分处理
			$idetail_status=2;
		}
		if(!$credits_result) {
            $db->rollback();
            return "因积分记录失败，未完成还款操作";
        }
	}
	/*************************************逾期还款积分与还款状态处理结束 20150602 fans***********************************/
	//对借款者帐户进行减少
	$bxid = true;
	if($type==1){
		$bxid = false;
		$moneynewid_x = setBorrowlog($vo['capital'], $vo['interest'], $binfo['borrow_uid'], $borrow_id, $transfer_repayment, $sort_order, $accountMoney_borrower, $datamoney_x, $mmoney_x, $db);
			if($moneynewid_x) {
                $bxid = $db->table("{$pre}member_money")->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
                if( empty($bxid) ) {
                    $db->rollback();
                    return $msg;
                }
            }else {
                $db->rollback();
                return $msg;
            }
			
		//逾期了
		if($is_expired){
			//逾期罚息
			if($expired_money>0){
				$accountMoney = M('member_money')->field('money_freeze,money_collect,account_money,back_money')->find($binfo['borrow_uid']);
				$datamoney_x = array();
				$mmoney_x=array();
				
				$datamoney_x['uid'] = $binfo['borrow_uid'];
				$datamoney_x['type'] = 30;
				$datamoney_x['affect_money'] = -($expired_money);
				if(($datamoney_x['affect_money']+$accountMoney['back_money'])<0){//如果需要还款的逾期罚息金额大于回款资金池资金总额
					$datamoney_x['account_money'] = $accountMoney['account_money']+$accountMoney['back_money'] + $datamoney_x['affect_money'];
					$datamoney_x['back_money'] = 0;
				}else{
					$datamoney_x['account_money'] = $accountMoney['account_money'];
					$datamoney_x['back_money'] = $accountMoney['back_money'] + $datamoney_x['affect_money'];//回款资金注入回款资金池
				}	
				$datamoney_x['collect_money'] = $accountMoney['money_collect'];
				$datamoney_x['freeze_money'] = $accountMoney['money_freeze'];
				
				//会员帐户
				$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
				$mmoney_x['money_collect']=$datamoney_x['collect_money'];
				$mmoney_x['account_money']=$datamoney_x['account_money'];
				$mmoney_x['back_money']=$datamoney_x['back_money'];
				
				//会员帐户
				$datamoney_x['info'] = "{$borrow_id}号".$transfer_repayment."第{$sort_order}期的逾期罚息";
				$datamoney_x['add_time'] = time();
				$datamoney_x['add_ip'] = get_client_ip();
				$datamoney_x['target_uid'] = 0;
				$datamoney_x['target_uname'] = '@网站管理员@';
				$moneynewid_x = M('member_moneylog')->add($datamoney_x);
				if($moneynewid_x) {
                    $bxid = M('member_money')->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
                    if( empty($bxid) ) {
                        $db->rollback();
                        return $msg;
                    }
                } else {
                    $db->rollback();
                    return $msg;
                }
			}
			
			//催收费
			if($call_fee>0){
				$accountMoney_borrower = M('member_money')->field('money_freeze,money_collect,account_money,back_money')->find($binfo['borrow_uid']);
				$datamoney_x = array();
				$mmoney_x=array();
				
				$datamoney_x['uid'] = $binfo['borrow_uid'];
				$datamoney_x['type'] = 31;
				$datamoney_x['affect_money'] = -($call_fee);
				if(($datamoney_x['affect_money']+$accountMoney_borrower['back_money'])<0){//如果需要还款的催收费金额大于回款资金池资金总额
					$datamoney_x['account_money'] = $accountMoney_borrower['account_money']+$accountMoney_borrower['back_money'] + $datamoney_x['affect_money'];
					$datamoney_x['back_money'] = 0;
				}else{
					$datamoney_x['account_money'] = $accountMoney_borrower['account_money'];
					$datamoney_x['back_money'] = $accountMoney_borrower['back_money'] + $datamoney_x['affect_money'];//回款资金注入回款资金池
				}	
				$datamoney_x['collect_money'] = $accountMoney_borrower['money_collect'];
				$datamoney_x['freeze_money'] = $accountMoney_borrower['money_freeze'];
				
				//会员帐户
				$mmoney_x['money_freeze']=$datamoney_x['freeze_money'];
				$mmoney_x['money_collect']=$datamoney_x['collect_money'];
				$mmoney_x['account_money']=$datamoney_x['account_money'];
				$mmoney_x['back_money']=$datamoney_x['back_money'];
				
				//会员帐户
				$datamoney_x['info'] = "网站对借款人收取的第{$borrow_id}号".$transfer_repayment."第{$sort_order}期的逾期催收费";
				$datamoney_x['add_time'] = time();
				$datamoney_x['add_ip'] = get_client_ip();
				$datamoney_x['target_uid'] = 0;
				$datamoney_x['target_uname'] = '@网站管理员@';
				$moneynewid_x = M('member_moneylog')->add($datamoney_x);
				if($moneynewid_x) {
                    $bxid = M('member_money')->where("uid={$datamoney_x['uid']}")->save($mmoney_x);
                    if( empty($bxid) ) {
                        $db->rollback();
                        return $msg;
                    }
                } else {
                    $db->rollback();
                    return $msg;
                }
			}
		}
		//逾期了
	}
	//对借款者帐户进行减少
	//更新借款信息
	$upborrowsql = "update `{$pre}borrow_info` set ";
	$upborrowsql .= "`repayment_money`=`repayment_money`+{$vo['capital']}";
	$upborrowsql .= ",`repayment_interest`=`repayment_interest`+ {$vo['interest']}";

	//如果是网站代还的，则记录代还金额
	if($type==2){
		$total_subs = ($vo['capital']+$vo['interest']);
		$upborrowsql .= ",`substitute_money`=`substitute_money`+ {$total_subs}";
		if( $detailList[0]['total'] == $sort_order){ //has_pay为自已还的，网站代还不作更新操作
			$upborrowsql .= ",`borrow_status`=9";//网站代还款完成
		}
		
	}
	//如果是网站代还的，则记录代还金额
	if($type==1){
	  	$upborrowsql .= ",`has_pay`={$sort_order}";//代还则不记录还到第几期，避免会员还款时，提示已还过
		if($sort_order == $binfo['total']){
			$upborrowsql .= ",`borrow_status`=7";//还款完成
		}
	}
	
	if($is_expired)  $upborrowsql .= ",`expired_money`=`expired_money`+{$expired_money}";//代还则不记录还到第几期，避免会员还款时，提示已还过
	$upborrowsql .= " WHERE `id`={$borrow_id}";
	$upborrow_res = $db->execute($upborrowsql);
    if( empty($upborrow_res) ) {
        $db->rollback();
        return $msg;
    }
	//更新借款信息
	
	//更新还款详情表
	if($type==2){//网站代还 逾期时网站代还不再进行subsitute_money = *+*操作，上面已经有过操作了。
		$updetail_res = M()->execute("update `{$pre}investor_detail` set `receive_capital`=`capital`,`receive_interest`=(`interest`-`interest_fee`),`substitute_time`=".time()." ,`substitute_money`=(`capital`+`interest`),`status`=4 WHERE `borrow_id`={$borrow_id} AND status<>14 AND `sort_order`={$sort_order}");
	}else if($is_expired){
		$updetail_res = M()->execute( "update `{$pre}investor_detail` set `receive_capital`=`capital` ,`receive_interest`=(`interest`-`interest_fee`),`repayment_time`=".time().",`call_fee`={$call_fee},`expired_money`={$expired_money},`expired_days`={$expired_days},`status`={$idetail_status} WHERE `borrow_id`={$borrow_id} AND status<>14 AND `sort_order`={$sort_order}" );
	}else{
		$updetail_res = M()->execute("update `{$pre}investor_detail` set `receive_capital`=`capital` ,`receive_interest`=(`interest`-`interest_fee`),`repayment_time`=".time().", `status`={$idetail_status} WHERE `borrow_id`={$borrow_id} AND status<>14 AND `sort_order`={$sort_order}");
	}
    if( empty($updetail_res) ) {
        $db->rollback();
        return $msg;
    }
	//更新还款概要表
	$smsUid = "";

	foreach($detailList as $v){
		$getInterest = $v['interest'] - $v['interest_fee'];
		$upsql = "update `{$pre}borrow_investor` set ";
		$upsql .= "`receive_capital`=`receive_capital`+{$v['capital']},";
		$upsql .= "`receive_interest`=`receive_interest`+ {$getInterest},";
		if($type==2){
			$total_s_invest = $v['capital'] + $getInterest;
			$upsql .= "`substitute_money` = `substitute_money` + {$total_s_invest},";
		}
		if($sort_order == $binfo['total']) $upsql .= "`status`=5,";//还款完成
		$upsql .= "`paid_fee`=`paid_fee`+{$v['interest_fee']}";
		$upsql .= " WHERE `id`={$v['invest_id']} and status<>14";
		$upinfo_res = $db->execute($upsql);
		
		//对投资帐户进行增加
		if($upinfo_res){
			$moneynewid = setInvestlog($v['capital'], $v['interest'], $v['investor_uid'], $binfo['borrow_uid'], $b_member['user_name'], $borrow_id, $binfo['borrow_type'], $type, $transfer_repayment, $sort_order, $datamoney, $mmoney, $db, $type, $v['taste_money']);//`mxl 20150310`
			if($moneynewid){
				$xid = $db->table("{$pre}member_money")->where("uid={$datamoney['uid']}")->save($mmoney);
                if( empty($xid) ) {
                    $db->rollback();
                    return $msg;
                }
			}
			
			if($type==2){//如果是网站代还
				NoticeSet('chk18',$v['investor_uid'],$borrow_id);//sss
			}else{
				NoticeSet('chk16',$v['investor_uid'],$borrow_id);//sss
			}
			$smsUid .= (empty($smsUid))?$v['investor_uid']:",{$v['investor_uid']}";
			
			//利息管理费
			$xid_z = true;
			if($v['interest_fee']>0 && $type==1){
				$xid_z = false;
				$accountMoney_z = M('member_money')->field('money_freeze,money_collect,account_money,back_money')->find($v['investor_uid']);
				$datamoney_z['uid'] = $v['investor_uid'];
				$datamoney_z['type'] = 23;
				$datamoney_z['affect_money'] = -($v['interest_fee']);//扣管理费
				
				$datamoney_z['collect_money'] = $accountMoney_z['money_collect'];
				$datamoney_z['freeze_money'] = $accountMoney_z['money_freeze'];
				if(($accountMoney_z['back_money'] + $datamoney_z['affect_money'])<0){
					$datamoney_z['back_money'] =0;
					$datamoney_z['account_money'] = $accountMoney_z['account_money'] +$accountMoney_z['back_money']+ $datamoney_z['affect_money'];
				}else{
					$datamoney_z['account_money'] = $accountMoney_z['account_money'];
					$datamoney_z['back_money'] = ($accountMoney_z['back_money'] + $datamoney_z['affect_money']);
				}
				
				//会员帐户
				$mmoney_z['money_freeze']=$datamoney_z['freeze_money'];
				$mmoney_z['money_collect']=$datamoney_z['collect_money'];
				$mmoney_z['account_money']=$datamoney_z['account_money'];
				$mmoney_z['back_money']=$datamoney_z['back_money'];
				
				//会员帐户
				$datamoney_z['info'] = "网站已将第{$v['borrow_id']}号".$transfer_repayment."第{$sort_order}期还款的利息管理费扣除";
				$datamoney_z['add_time'] = time();
				$datamoney_z['add_ip'] = get_client_ip();
				$datamoney_z['target_uid'] = 0;
				$datamoney_z['target_uname'] = '@网站管理员@';
				$moneynewid_z = M('member_moneylog')->add($datamoney_z);
				if($moneynewid_z) {
                    $xid_z = M('member_money')->where("uid={$datamoney_z['uid']}")->save($mmoney_z);
                    if( empty($xid_z) ) {
                        $db->rollback();
                        return $msg;
                    }
                }else {
                    $db->rollback();
                    return $msg;
                }
			}

		   //利息管理费
		} else {
            $db->rollback();
            return $msg;
        }
		//对投资帐户进行增加
		
	}
	//更新还款概要表
    if($binfo['total'] == ($binfo['has_pay']+1) && $type==1){
        $_last=false;
        $_is_last = lastRepayment($binfo, $db);//最后一笔还款
    }
    $done=true;
    $db->commit();
    $vphone = M("member_info")->field("cell_phone")->where("uid in({$smsUid}) and cell_phone !=''")->select();
    $sphone = "";
    foreach($vphone as $v){
        $sphone.=(empty($sphone))?$v['cell_phone']:",{$v['cell_phone']}";
    }
    SMStip("payback",$sphone,array("#ID#","#ORDER#"),array($borrow_id,$sort_order));
	return $done;
}

function getBorrowInterestRate($rate,$duration){
	return ($rate/(12*100)*$duration);
}


function getMoneyLog($map,$size){
	if(empty($map['uid'])) return;
	
	if($size){
		//分页处理
		import("ORG.Util.Page");
		$count = M('member_moneylog')->where($map)->count('id');
		$p = new Page($count, $size);
		$page = $p->show();
		$Lsql = "{$p->firstRow},{$p->listRows}";
		//分页处理
	}

	$list = M('member_moneylog')->where($map)->order('id DESC')->limit($Lsql)->select();
	$type_arr = C("MONEY_LOG");
	foreach($list as $key=>$v){
	    $list[$key]['typenum'] = $list[$key]['type'];
		$list[$key]['type'] = $type_arr[$v['type']];
		/*if($v['affect_money']>0){
			$list[$key]['in'] = $v['affect_money'];
			$list[$key]['out'] = '';
		}else{
			$list[$key]['in'] = '';
			$list[$key]['out'] = $v['affect_money'];
		}*/
	}
	
	$row=array();
	$row['list'] = $list;
	$row['page'] = $page;
	return $row;
}

/**
 * @param $uid
 * @param $type
 * @param $amoney
 * @param string $info
 * @param string $target_uid
 * @param string $target_uname
 * @param int $fee
 * @param null $db 数据库db
 * @param int $discount_money 折扣金额
 * @param int $wid 提现记录id
 * @return bool
 */
function memberMoneyLog($uid,$type,$amoney,$info="",$target_uid="",$target_uname="",$fee=0, $db = null, $discount_money = 0, $wid=0){
	$xva = floatval($amoney);
	if(empty($xva)) return true;
	if($wid==0){
	    $withdraw['back_money'] = 0;
	    $withdraw['account_money'] = 0;
	}else{
	    $withdraw = M("member_withdraw")->field("back_money,account_money")->where(array('uid'=>$uid,'id'=>$wid))->find();
	}
	$done = false;
	$MM = M("member_money")->field("money_freeze,money_collect,account_money,back_money")->find($uid);
	if(!is_array($MM)||empty($MM)){
	 	M("member_money")->add(array('uid'=>$uid));
		$MM = M("member_money")->field("money_freeze,money_collect,account_money,back_money")->find($uid);
	}
	$Moneylog = D('member_moneylog');
	if(in_array($type,array("71","72","73"))) $type_save=7;
	else $type_save = $type;
	
	if($target_uname=="" && $target_uid>0){
		$tname = M('members')->getFieldById($target_uid,'user_name');
	}else{
		$tname = $target_uname;
	}
	if($target_uid=="" && $target_uname==""){
		$target_uid=0;
		$tname = '@网站管理员@';	
	}
    if( !isset($db) ) {
        $Moneylog->startTrans();
    }

    //type 更改之后实现映射关系，原状态合并key=>value
    $map = array(
        '37' => 6,
        '38' => 28,
        '39' => 15,
        '41' => 20,
        '42' => 21
    );
    $map_key = array_keys($map);
    if( in_array($type, $map_key) ) {
        $type_save = $map[$type];
    }

		$data['uid'] = $uid;
		$data['type'] = $type_save;
		$data['info'] = $info;
		$data['target_uid'] = $target_uid;
		$data['target_uname'] = $tname;
		$data['add_time'] = time();
		$data['add_ip'] = get_client_ip();
		switch($type){
		/////////////////////////////////////////
			case 5://撤消提现
				$data['affect_money'] = $amoney;

				if(($MM['back_money']+$amoney+$fee)<0){//提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
					$data['back_money'] = 0;
					$data['account_money'] = $MM['account_money']+$MM['back_money']+$amoney+$fee;
				}else{
					$data['back_money'] = $MM['back_money'];
					$data['account_money'] = $MM['account_money']+$amoney+$fee;
				}

				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze']-$amoney;
			break;
			case 4://提现冻结
			//case 5://撤消提现
			case 6://投标冻结
            case 55://发送红包冻结
			case 37://投企业直投冻结
				$data['affect_money'] = $amoney;

				if(($MM['back_money']+$amoney+$fee)<0){//提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
					$data['back_money'] = 0;
					$data['account_money'] = $MM['account_money']+$MM['back_money']+$amoney+$fee;
				}else{
					$data['back_money'] = $MM['back_money']+$amoney+$fee;
					$data['account_money'] = $MM['account_money'];
				}

				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze']-$amoney + $discount_money;
			break;
			case 12://提现失败
				//$data['affect_money'] = $amoney;
			    $data['affect_money'] = $amoney+$fee;
				
				if(($MM['account_money']+$MM['back_money'])>abs($fee)){
					if(($MM['back_money']+$amoney+$fee)<0){//提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
						$data['back_money'] = 0;
						$data['account_money'] = $MM['account_money']+$MM['back_money']+$amoney+$fee;
					}else{
						$data['back_money'] = $MM['back_money']+$withdraw['back_money'];//$MM['back_money']+$amoney+$fee;
						$data['account_money'] = $MM['account_money']+$withdraw['account_money']+$fee;
					}
					$data['collect_money'] = $MM['money_collect'];
					$data['freeze_money'] = $MM['money_freeze']-$amoney;
				}else{
					if(($MM['back_money']+$amoney+$fee)<0){//提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
						$data['back_money'] = 0;
						$data['account_money'] = $MM['account_money']+$MM['back_money']+$amoney;
					}else{
						$data['back_money'] = $MM['back_money']+$withdraw['back_money'];//$MM['back_money']+$amoney;
						$data['account_money'] = $MM['account_money']+$withdraw['account_money'];
					}
					$data['collect_money'] = $MM['money_collect'];
					$data['freeze_money'] = $MM['money_freeze']-$amoney+$fee;
				}
			break;
			
			case 29://提现成功
			    //$data['affect_money'] = $amoney;
				$data['affect_money'] = $fee;
				$data['account_money'] = $MM['account_money'];
				$data['back_money'] = $MM['back_money'];
				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze']+$amoney+$fee;
			break;
			case 36://提现通过，处理中
// 				$data['affect_money'] = $amoney;
			    $data['affect_money'] = $fee;
				if(($MM['account_money']+$MM['back_money'])>abs($fee)){
					if(($MM['back_money']+$fee)<0){//提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
						$data['account_money'] = $MM['account_money']+$MM['back_money']+$fee;
						$data['back_money'] = 0;
					}else{
						$data['account_money'] = $MM['account_money'];
						$data['back_money'] = $MM['back_money']+$fee;
					}
					$data['collect_money'] = $MM['money_collect'];
					$data['freeze_money'] = $MM['money_freeze'];
				}else{
					$data['account_money'] =$MM['account_money'];
					$data['back_money'] = $MM['back_money'];
					$data['collect_money'] = $MM['money_collect'];
					$data['freeze_money'] = $MM['money_freeze']+$fee;
				}
			break;
		////////////////////////////////////////
			
			case 8://流标解冻
			case 19://借款保证金
			case 24://还款完成解冻
			case 57://红包返还
				$data['affect_money'] = $amoney;
				if(($MM['account_money']+$amoney)<0){
					$data['account_money'] = 0;
					$data['back_money'] = $MM['account_money']+$MM['back_money']+$amoney;
				}else{
					$data['account_money'] = $MM['account_money']+$amoney;
					$data['back_money'] = $MM['back_money'];
				}
				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze']-$amoney;
			break;
			case 3://会员充值
			case 17://借款金额入帐
			case 18://借款管理费
			case 20://投标奖励
			case 21://支付投标奖励
			case 40://企业直投续投奖励
			case 41://企业直投投标奖励
			case 42://支付企业直投投标奖励
            case 56://领取红包
				$data['affect_money'] = $amoney;
				if(($MM['account_money']+$amoney)<0){
					$data['account_money'] = 0;
					$data['back_money'] = $MM['account_money']+$MM['back_money']+$amoney;
				}else{
					$data['account_money'] = $MM['account_money']+$amoney;
					$data['back_money'] = $MM['back_money'];
				}
				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze'];
			break;
			case 9://会员还款
			case 10://网站代还
				$data['affect_money'] = $amoney;
				$data['account_money'] = $MM['account_money'];
				$data['collect_money'] = $MM['money_collect']-$amoney;
				$data['freeze_money'] = $MM['money_freeze'];
				$data['back_money'] = $MM['back_money']+$amoney;
			break;
			case 15://投标成功冻结资金转为待收资金
			case 39://企业直投投标成功冻结资金转为待收资金
				$data['affect_money'] = $amoney;
				$data['account_money'] = $MM['account_money'];
				$data['collect_money'] = $MM['money_collect']+$amoney;
				$data['freeze_money'] = $MM['money_freeze']-$amoney;
				$data['back_money'] = $MM['back_money'];
			break;
			case 28://投标成功利息待收
			case 38://企业直投投标成功利息待收
			case 73://单独操作待收金额
				$data['affect_money'] = $amoney;
				$data['account_money'] = $MM['account_money'];
				$data['collect_money'] = $MM['money_collect']+$amoney;
				$data['freeze_money'] = $MM['money_freeze'];
				$data['back_money'] = $MM['back_money'];
			break;
			case 72://单独操作冻结金额
//			case 35://续投奖励(取消)
				$data['affect_money'] = $amoney;
				$data['account_money'] = $MM['account_money'];
				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze']+$amoney;
				$data['back_money'] = $MM['back_money'];
			break;
			case 71://单独操作可用余额
			default:
				$data['affect_money'] = $amoney;
				if(($MM['account_money']+$amoney)<=0){
					$data['account_money'] = 0;
					$data['back_money'] = $MM['account_money']+$MM['back_money']+$amoney;
				}else{
					$data['account_money'] = $MM['account_money']+$amoney;
					$data['back_money'] = $MM['back_money'];
				}
				//$data['account_money'] = $MM['account_money']+$amoney;
				$data['collect_money'] = $MM['money_collect'];
				$data['freeze_money'] = $MM['money_freeze'];
				//$data['back_money'] = $MM['back_money'];
			break;
			
		}
		$newid = M('member_moneylog')->add($data);
		//帐户更新
		$mmoney['money_freeze']=$data['freeze_money'];
		$mmoney['money_collect']=$data['collect_money'];
		$mmoney['account_money']=$data['account_money'];
		$mmoney['back_money']=$data['back_money'];
		if($newid) $xid = M('member_money')->where("uid={$uid}")->save($mmoney);
		if($xid){
			$done = true;
            if( !isset($db) ) $Moneylog->commit();
		}else{
            if( !isset($db) )  $Moneylog->rollback();
		}
	return $done;
}

function memberLimitLog($uid,$type,$alimit,$info=""){
	$xva = floatval($alimit);
	if(empty($xva)) return true;
	$done = false;
	$MM = M("member_money")->field("money_freeze,money_collect,account_money,back_money",true)->find($uid);
	if(!is_array($MM)){
		M("member_money")->add(array('uid'=>$uid));
		$MM = M("member_money")->field("money_freeze,money_collect,account_money,back_money",true)->find($uid);
	}
	$Moneylog = D('member_moneylog');
	if(in_array($type,array("71","72","73"))) $type_save=7;
	else $type_save = $type;
	
	$Moneylog->startTrans();

		$data['uid'] = $uid;
		$data['type'] = $type_save;
		$data['info'] = $info;
		$data['add_time'] = time();
		$data['add_ip'] = get_client_ip();

		$data['credit_limit'] = 0;
		$data['borrow_vouch_limit'] = 0;
		$data['invest_vouch_limit'] = 0;
		
		switch($type){
			case 1://信用标初审通过暂扣
			case 4://信用标复审未通过返回
			case 7://标的完成，返回
			case 12://流标，返回
				$_data['credit_limit'] = $alimit;
			break;
			case 2://担保标初审通过暂扣
			case 5://担保标复审未通过返回
			case 8://标的完成，返回
				$_data['borrow_vouch_limit'] = $alimit;
			break;
			case 3://参与担保暂扣
			case 6://所担保的标初审未通过，返回
			case 9://所担保的标复审未通过，返回
			case 10://标的完成，返回
				$_data['invest_vouch_limit'] = $alimit;
			break;
			case 11://VIP审核通过
				$_data['credit_limit'] = $alimit;
				$mmoney['credit_limit']=$MM['credit_limit'] + $_data['credit_limit'];
			break;
		}
		$data = array_merge($data,$_data);
		$newid = M('member_limitlog')->add($data);
		//帐户更新
		$mmoney['credit_limit']=$MM['credit_limit'] + $data['credit_limit'];
		$mmoney['borrow_vouch_cuse']=$MM['borrow_vouch_cuse'] + $data['borrow_vouch_limit'];
		$mmoney['invest_vouch_cuse']=$MM['invest_vouch_cuse'] + $data['invest_vouch_limit'];
		if($newid) $xid = M('member_money')->where("uid={$uid}")->save($mmoney);
		if($xid){
			$Moneylog->commit();
			$done = true;
		}else{
			$Moneylog->rollback();
		}
	return $done;
}


/**
* 认证，信用积分记录
* 
* @param mixed $uid
* @param mixed $type
* @param mixed $acredits
* @param mixed $info
* @param type：联系方式-31，单位资料-32，财务状况-33，认证各加10分
*/
function memberCreditsLog($uid,$type,$acredits,$info="无", $db = null){
    if($acredits==0) return true;
    $acredits = intval($acredits);
    $done = false;
    $mCredits = M("members")->getFieldById($uid,'credits');
    $Creditslog = D('member_creditslog');
    if( !isset($db) ) $Creditslog->startTrans();
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['affect_credits'] = $acredits;
        $data['account_credits'] = $mCredits + $acredits;
        
        $data['info'] = $info;
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip();
        $newid = $Creditslog->add($data);
        
        if($acredits > 0){
            $xid = M('members')->where("id={$uid}")->setField('credits',$data['account_credits']);    
        }else{
            $used_integral = M("members")->getFieldById($uid,'used_integral');
            $used_integral = $used_integral - $acredits;
            $xid = M('members')->where("id={$uid}")->setField('used_integral',$used_integral);
        }

        if($xid){
            if( !isset($db) ) $Creditslog->commit() ;
            $done = true;
        }else{
            if( !isset($db) ) $Creditslog->rollback() ;
        }
    
    return $done;
}

/**
* 借款人信用积分
* 
* @param mixed $uid
* @param mixed $type
* @param mixed $acredits
* @param mixed $info
*/
function borrowCreditsLog($uid,$type=20,$acredits,$info="无", $db = null){
    if($acredits==0) return true;
    $ret = false;
    $mCredits = M("members")->getFieldById($uid,'credits');
    $data['uid'] = $uid;
    $data['type'] = $type;
    $data['affect_credits'] = $acredits;
    $data['account_credits'] = $mCredits + $acredits;
    $data['info'] = $info;
    $data['add_time'] = time();
    $data['add_ip'] = get_client_ip();
    if( isset($db) ) {
        $result = $db->table(C('DB_PREFIX').'member_creditslog')->add($data);
    }else {
        $Creditslog = D('member_creditslog');
        $Creditslog->startTrans();
        $result = $Creditslog->add($data);
    }
    if( !empty($result) ) {
        $ret = M('members')->where("id={$uid}")->setField('credits',$data['account_credits']);
    }else{
        if(isset($db) ) {
            $db->rollback();
        } else {
            $Creditslog->rollback();
        }
    }

    return $ret;
}




function memberIntegralLog($uid,$type,$integral,$info="无", $Db = null){
	if($integral==0) return true;
	$pre = C('DB_PREFIX');
	$done = false;

	if( !isset($Db)) {
        $Db = new Model();
        $Db->startTrans(); //多表事务
		$commit = true;
    }else {
		$Db = new Model();
	}
	$Member = $Db->table($pre."members")->where("id=$uid")->find();

		$data['uid'] = $uid;
		$data['type'] = $type;
		$data['affect_integral'] = $integral;
		$data['active_integral'] = $integral + $Member['active_integral'];
		$data['account_integral'] = $integral + $Member['integral'];
		$data['info'] = $info;
		$data['add_time'] = time();
		$data['add_ip'] = get_client_ip();

	if ($integral<0 && $data['active_integral']<0){//判断积分是否消费过头
		return false; 
	} elseif ($integral<0 && $data['active_integral']>0){//消费积分只减活跃积分，总积分不变
		$data['account_integral'] = $Member['integral'];
	}

	//消费积分为负数，消费积分只减活跃积分，不减总积分
	$newid = $Db->table($pre.'member_integrallog')->add($data);//积分细则
    if( !empty($newid) ) {
        $xid = $Db->table($pre."members")->where("id=$uid")->setInc('active_integral',$integral);//活跃积分总数
    }

	if($integral>0) $yid = $Db->table($pre."members")->where("id=$uid")->setInc('integral',$integral);//积分总数
	else $yid = true;
		
	if($newid && $xid && $yid){
        if( isset($commit) ) {
            $Db->commit() ;
        }
		$done = true;
	}else{
		$Db->rollback() ;
	}
	
	return $done;
}

function getMemberMoneySummary($uid){  
	$pre = C('DB_PREFIX');
	$umoney = M('member_money')->field(true)->find($uid);

	$withdraw = M('member_withdraw')->field('withdraw_status,sum(withdraw_money) as withdraw_money,sum(second_fee) as second_fee')->where("uid={$uid}")->group("withdraw_status")->select();
	$withdraw_row = array();
	foreach($withdraw as $wkey=>$wv){
		$withdraw_row[$wv['withdraw_status']] = $wv;
	}
	$withdraw0 = $withdraw_row[0];
	$withdraw1 = $withdraw_row[1];
	$withdraw2 = $withdraw_row[2];
	
	$payonline = M('member_payonline')->where("uid={$uid} AND status=1")->sum('money');//累计充值金额
	
	$commission1 = M('borrow_investor')->where("investor_uid={$uid}")->sum('paid_fee');
	$commission2 = M('borrow_info')->where("borrow_uid={$uid} AND borrow_status in(2,4)")->sum('borrow_fee');//累计借款管理费
	
	$uplevefee = M('member_moneylog')->where("uid={$uid} AND type=2")->sum('affect_money');//充值总金额
	
	$czfee = M('member_payonline')->where("uid={$uid} AND status=1")->sum('fee');//在线充值手续费总金额
	
	$toubiaojl =M('borrow_investor')->where("borrow_uid ={$uid}")->sum('reward_money');//累计支付投标奖励
	$tuiguangjl =M('member_moneylog')->where("uid={$uid} and type=13")->sum('affect_money');//推广奖励
	$xianxiajl =M('member_moneylog')->where("uid={$uid} and type=32")->sum('affect_money');//线下充值奖励
	$xtjl = M('member_moneylog')->where("uid={$uid} and type=34")->sum('affect_money');//累计续投奖励  前台已放弃
    
    //企业直投代收金额及利息
	$circulation = M('borrow_investor')
                    ->field('sum(investor_capital)as investor_capital, sum(investor_interest) as investor_interest, sum(invest_fee) as invest_fee')
                    ->where('borrow_type=6 and investor_uid='.$uid.' and status<5')
                    ->find();
	///////////////////
	$moneylog = M("member_moneylog")->field("type,sum(affect_money) as money")->where("uid={$uid}")->group("type")->select();
	$list=array();
	foreach($moneylog as $vs){
		$list[$vs['type']]['money']= ($vs['money']>0)?$vs['money']:$vs['money']*(-1);
	}
	
	$tx = M('member_withdraw')->field("uid,sum(withdraw_money) as withdraw_money,sum(second_fee) as second_fee")->where("uid={$uid} and withdraw_status=2")->group("uid")->select();
	foreach($tx as $vt){
		$list['tx']['withdraw_money']= $vt['withdraw_money'];	//成功提现金额	
		$list['tx']['withdraw_fee']= $vt['second_fee'];	//提现手续费
	}
	
	////////////////////////////
	
	$capitalinfo = getMemberBorrowScan($uid);
	$money['zye'] = $umoney['account_money'] + $umoney['back_money']+$umoney['money_collect'] + $umoney['money_freeze'];//帐户总额
	$money['kyxjje'] = $umoney['account_money']+ $umoney['back_money'];//可用金额
	$money['djje'] = $umoney['money_freeze'];//冻结金额
	$money['jjje'] = 0;//奖金金额
	$money['dsbx'] = $capitalinfo['tj']['dsze']+$capitalinfo['tj']['willgetInterest']
                    +$circulation['investor_capital']+$circulation['investor_interest']-$circulation['invest_fee'];//$umoney['money_collect'];//待收本金+待收利息
	
	$money['dfbx'] = $capitalinfo['tj']['dhze'];//待付本息
	$money['dxrtb'] = $capitalinfo['tj']['dqrtb'];//待确认投标
	$money['dshtx'] = $withdraw0['withdraw_money'];//待审核提现
	$money['clztx'] = $withdraw1['withdraw_money'];//处理中提现  
	$money['total_1'] = $money['kyxjje']+$money['jjje']+$money['dsbx']-$money['dfbx']+$money['dxrtb']+$money['dshtx']+$money['clztx'];
	
	$money['jzlx'] = $capitalinfo['tj']['earnInterest'];//净赚利息
	$money['jflx'] = $capitalinfo['tj']['payInterest'];//净付利息
	//$money['ljjj'] = $umoney['reward_money'];//累计收到奖金
	$money['xtjj'] = $list['34']['money']+$list[40]['money'];//$xtjl;//累计续投奖金
	$money['ljhyf'] = $list['14']['money']+$list['22']['money']+$list['25']['money']+$list['26']['money'];//$uplevefee;//累计支付会员费
	$money['ljtxsxf'] = $list['tx']['withdraw_fee'];//$withdraw2['withdraw_fee'];//累计提现手续费
	$money['ljczsxf'] = $czfee;//累计充值手续费
    
	$money['ljtbjl'] = $list['20']['money']+$list[41]['money'];//$toubiaojl;//累计投标奖励
	$money['ljtgjl'] = $list['13']['money'];//$tuiguangjl;//累计推广奖励
	$money['xxjl'] = $list['32']['money'];//$xianxiajl;//线下充值奖励
	$money['jkglf'] =$list['18']['money'];//借款管理费
	$money['yqf'] = $list['30']['money']+$list['31']['money'];//逾期罚息及催收费
	$money['zftbjl'] = $toubiaojl;//支付投标奖励
	$money['total_2'] = $money['jzlx']
                        -$money['jflx']
                        -$money['ljhyf']
                        -$money['ljtxsxf']
                        -$money['ljczsxf']
                        +$money['ljtbjl']
                        +$money['ljtgjl']
                        +$money['xxjl']
                        +$money['xtjj']
                        -$money['jkglf']
                        -$money['yqf']
                        -$money['zftbjl'];
	
	$money['ljtzje'] = $capitalinfo['tj']['borrowOut'];//累计投资金额  
	$money['ljjrje'] = $capitalinfo['tj']['borrowIn'];//累计借入金额 
	$money['ljczje'] = $payonline;//累计充值金额
	$money['ljtxje'] = $withdraw2['withdraw_money'];//累计提现金额
	$money['ljzfyj'] = $commission1 + $commission2;//累计支付佣金
//
	$money['dslxze'] = $capitalinfo['tj']['willgetInterest'] + $circulation['investor_interest'];//待收利息总额  
	$money['dflxze'] = $capitalinfo['tj']['willpayInterest'];//待付利息总额
	
	return $money;
}

function getBorrowInvest($borrowid=0,$uid){
	if(empty($borrowid)) return;
	$vx = M("borrow_info")->field('id')->where("id={$borrowid} AND borrow_uid={$uid}")->find();
	if(!is_array($vx)) return;

	$binfo = M("borrow_info")->field('borrow_name,borrow_uid,borrow_type,borrow_duration,repayment_type,has_pay,total,deadline')->find($borrowid);
	$list = array();
	switch($binfo['repayment_type']){
                            case 1://一次性还款
                            case 5://一次性还款

                                            $field = "borrow_id,sort_order,sum(capital) as capital,sum(interest) as interest,status,sum(receive_interest+receive_capital+if(receive_capital>=0,interest_fee,0)) as paid,deadline";
                                            $vo = M("investor_detail")->field($field)->where("borrow_id={$borrowid} and status<>14 AND `sort_order`=1")->group('sort_order')->find();
                                            //$status_arr =array('还未还','已还完','已提前还款','逾期还款','网站代还本金');
                                            $status_arr =array('还未还','已还完','已提前还款','迟到还款','网站代还本金','逾期还款','','待还');
                                            ///////////////////
                                            if($vo['deadline']<time() && $vo['status']==7){
                                                    $vo['status'] ='逾期未还';
                                            }else{
                                                    $vo['status'] = $status_arr[$vo['status']];
                                            }
                                            ///////////////////
                                            //$vo['status'] = $status_arr[$vo['status']];
                                            //$vo['needpay'] = getFloatValue(sprintf("%.2f",($vo['interest']+$vo['capital']-$vo['paid'])),2);
                                            $vo['needpay'] = sprintf("%.2f",($vo['interest']+$vo['capital']-$vo['paid']));
                                            $list[] = $vo;
                            break;
                            case 6://利息复投
                                    $field = "borrow_id,sort_order,sum(capital) as capital,sum(interest) as interest,status,sum(receive_interest+receive_capital+if(receive_capital>=0,interest_fee,0)) as paid,deadline";
                                    $array = M("investor_detail")->field($field)->where("borrow_id={$borrowid} and status<>14")->group('sort_order')->select();
                                    $status_arr =array('还未还','已还完','已提前还款','迟到还款','网站代还本金','逾期还款','','待还');
                                    $list=array();
                                    foreach($array  as $key=>$vo){
                                        if($vo['deadline']<time() && $vo['status']==7){
                                            $vo['status'] ='逾期未还';
                                        }else{
                                            $vo['status'] = $status_arr[$vo['status']];
                                        }
                                        $vo['needpay'] = sprintf("%.2f",($vo['interest']+$vo['capital']-$vo['paid']));
                                        $list[$key]=$vo;
                                    }
                            break;   
                            case 4:
                                for($i=1;$i<=$binfo['total'];$i++){
                                    $field = "borrow_id,sort_order,sum(capital) as capital,sum(interest) as interest,status,sum(receive_interest+receive_capital+if(receive_capital>=0,interest_fee,0)) as paid,deadline";
                                    $vo = M("investor_detail")->field($field)->where("borrow_id={$borrowid} and status<>14 AND `sort_order`=$i")->group('sort_order')->find();
                                    $status_arr =array('还未还','已还完','已提前还款','迟到还款','网站代还本金','逾期还款','','待还');
                                    ///////////////////
                                    if($vo['deadline']<time() && $vo['status']==7){
                                        $vo['status'] ='逾期未还';
                                    }else{
                                        $vo['status'] = $status_arr[$vo['status']];
                                    }
                                    ///////////////////
                                    //$vo['status'] = $status_arr[$vo['status']];
                                    $vo['needpay'] = sprintf("%.2f",($vo['interest']+$vo['capital']-$vo['paid']));
                                    $list[] = $vo;
                                }
                            break;
                            default://每月还款
                                    for($i=1;$i<=$binfo['borrow_duration'];$i++){
                                            $field = "borrow_id,sort_order,sum(capital) as capital,sum(interest) as interest,status,sum(receive_interest+receive_capital+if(receive_capital>=0,interest_fee,0)) as paid,deadline";
                                            $vo = M("investor_detail")->field($field)->where("borrow_id={$borrowid} and status<>14 AND `sort_order`=$i")->group('sort_order')->find();
                                            $status_arr =array('还未还','已还完','已提前还款','迟到还款','网站代还本金','逾期还款','','待还');
                                            ///////////////////
                                            if($vo['deadline']<time() && $vo['status']==7){
                                                    $vo['status'] ='逾期未还';
                                            }else{
                                                    $vo['status'] = $status_arr[$vo['status']];
                                            }
                                            ///////////////////
                                            //$vo['status'] = $status_arr[$vo['status']];
                                            $vo['needpay'] = sprintf("%.2f",($vo['interest']+$vo['capital']-$vo['paid']));
                                            $list[] = $vo;
                                    }
                            break;
	}
	$row=array();
	$row['list'] = $list;
	$row['name'] = $binfo['borrow_name'];
	return $row;

}

function getDurationCount($uid=0){
	if(empty($uid)) return;
	$pre = C('DB_PREFIX');
	
	$field = "d.status,d.repayment_time";
	$sql = "select {$field} from {$pre}investor_detail d left join {$pre}borrow_info b ON b.id=d.borrow_id where d.borrow_id in(select tb.id from {$pre}borrow_info tb where tb.borrow_uid={$uid}) group by d.borrow_id, d.sort_order";
	$list = M()->query($sql);

	$week_1 = array(strtotime("-7 day",strtotime(date("Y-m-d",time())." 00:00:00")),strtotime(date("Y-m-d",time())." 23:59:59"));
	$time_1 = array(strtotime("-1 month",strtotime(date("Y-m-d",time())." 00:00:00")),strtotime(date("Y-m-d",time())." 23:59:59"));
	$time_6 = array(strtotime("-6 month",strtotime(date("Y-m-d",time())." 00:00:00")),strtotime(date("Y-m-d",time())." 23:59:59"));
	$row_time_1=array();
	$row_time_2=array();
	$row_time_3=array();
	$row_time_4=array();
	foreach($list as $v){
		switch($v['status']){
			case 1:
				if($v['repayment_time']>$time_6[0] && $v['repayment_time']<$time_6[1]){
					$row_time_3['zc'] = $row_time_3['zc'] + 1;//6个月内
					if($v['repayment_time']>$week_1[0] && $v['repayment_time']<$week_1[1]) $row_time_1['zc'] = $row_time_1['zc'] + 1;//一周内
					if($v['repayment_time']>$time_1[0] && $v['repayment_time']<$time_1[1]) $row_time_2['zc'] = $row_time_2['zc'] + 1;//一个月内
				}
				$row_time_4['zc'] = $row_time_4['zc'] + 1;//所有
			break;
			case 2:
				if($v['repayment_time']>$time_6[0] && $v['repayment_time']<$time_6[1]){
					$row_time_3['tq'] = $row_time_3['tq'] + 1;//6个月内
					if($v['repayment_time']>$week_1[0] && $v['repayment_time']<$week_1[1]) $row_time_1['tq'] = $row_time_1['tq'] + 1;//一周内
					if($v['repayment_time']>$time_1[0] && $v['repayment_time']<$time_1[1]) $row_time_2['tq'] = $row_time_2['tq'] + 1;//一个月内
				}
				$row_time_4['tq'] = $row_time_4['tq'] + 1;//所有
			break;
			case 3:
				if($v['repayment_time']>$time_6[0] && $v['repayment_time']<$time_6[1]){
					$row_time_3['ch'] = $row_time_3['ch'] + 1;//6个月内
					if($v['repayment_time']>$week_1[0] && $v['repayment_time']<$week_1[1]) $row_time_1['ch'] = $row_time_1['ch'] + 1;//一周内
					if($v['repayment_time']>$time_1[0] && $v['repayment_time']<$time_1[1]) $row_time_2['ch'] = $row_time_2['ch'] + 1;//一个月内
				}
				$row_time_4['ch'] = $row_time_4['ch'] + 1;//所有
			break;
			case 5:
				if($v['repayment_time']>$time_6[0] && $v['repayment_time']<$time_6[1]){
					$row_time_3['yq'] = $row_time_3['yq'] + 1;//6个月内
					if($v['repayment_time']>$week_1[0] && $v['repayment_time']<$week_1[1]) $row_time_1['yq'] = $row_time_1['yq'] + 1;//一周内
					if($v['repayment_time']>$time_1[0] && $v['repayment_time']<$time_1[1]) $row_time_2['yq'] = $row_time_2['yq'] + 1;//一个月内
				}
				
				$row_time_4['yq'] = $row_time_4['yq'] + 1;//所有
			break;
			case 6:
				if($v['repayment_time']>$time_6[0] && $v['repayment_time']<$time_6[1]){
					$row_time_3['wh'] = $row_time_3['wh'] + 1;//6个月内
					if($v['repayment_time']>$week_1[0] && $v['repayment_time']<$week_1[1]) $row_time_1['wh'] = $row_time_1['wh'] + 1;//一周内
					if($v['repayment_time']>$time_1[0] && $v['repayment_time']<$time_1[1]) $row_time_2['wh'] = $row_time_2['wh'] + 1;//一个月内
				}
				$row_time_4['wh'] = $row_time_4['wh'] + 1;//所有
			break;
			
		}
	}
	$row['history1'] = $row_time_1;
	$row['history2'] = $row_time_2;
	$row['history3'] = $row_time_3;
	$row['history4'] = $row_time_4;
	return $row;
}


function getMemberBorrow($uid=0,$size=10){
	if(empty($uid)) return;
	$pre = C('DB_PREFIX');
	
	$field = "b.borrow_name,d.total,d.borrow_id,d.sort_order,sum(d.capital) as capital,sum(d.interest) as interest,d.status,sum(d.receive_interest+d.receive_capital+if(d.receive_capital>=0,d.interest_fee,0)) as paid,d.deadline";
	$sql = "select {$field} from {$pre}investor_detail d left join {$pre}borrow_info b ON b.id=d.borrow_id where d.borrow_id in(select tb.id from {$pre}borrow_info tb where tb.borrow_status=6 AND tb.borrow_uid={$uid}) AND d.repayment_time=0 group by d.sort_order, d.borrow_id order by  d.borrow_id,d.sort_order limit 0,10";
	//$sql = "select {$field} from {$pre}investor_detail d left join {$pre}borrow_info b ON b.id=d.borrow_id where d.borrow_uid={$uid} AND d.status=0 group by d.sort_order limit 0,10";
	$list = M()->query($sql);
	$status_arr =array('还未还','已还完','已提前还款','迟到还款','网站代还本金','逾期还款','','待还');
	foreach($list as $key=>$v){
		//$list[$key]['status'] = $status_arr[$v['status']];
		
		if($v['deadline']<time() && $v['status']==7){
			$list[$key]['status'] ='逾期未还';
		}else{
			$list[$key]['status'] = $status_arr[$v['status']];
		}
	}
	$row=array();
	$row['list'] = $list;
	return $row;
}

function getLeftTime($timeend,$type=1){
	if($type==1){
		$timeend = strtotime(date("Y-m-d",$timeend)." 23:59:59");
		$timenow = strtotime(date("Y-m-d",time())." 23:59:59");
		$left = ceil( ($timeend-$timenow)/3600/24 );
	}else{
		$left_arr = timediff(time(),$timeend);
		$left = $left_arr['day']."天 ".$left_arr['hour']."小时 ".$left_arr['min']."分钟 ".$left_arr['sec']."秒";
	}
	return $left;
}

function timediff($begin_time,$end_time )
{
    if ( $begin_time < $end_time ) {
        $starttime = $begin_time;
        $endtime = $end_time;
    } else {
        $starttime = $end_time;
        $endtime = $begin_time;
    }
    $timediff = $endtime - $starttime;
    $days = intval( $timediff / 86400 );
    $remain = $timediff % 86400;
    $hours = intval( $remain / 3600 );
    $remain = $remain % 3600;
    $mins = intval( $remain / 60 );
    $secs = $remain % 60;
    $res = array( "day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs );
    return $res;
}
// $uid为数组时批量添加
function addInnerMsg($uid,$title,$msg, $db = null){
    if( !isset($db) ) $db = new Model();
	if(empty($uid)) return;
    if( !is_array($uid) ) {
        $user_ids[] = $uid;
    } else {
        $user_ids = $uid;
    }
    $data = array();
    foreach( $user_ids as $k=>$v ) {
        $data[$k]['uid'] = $v;
        $data[$k]['title'] = $title;
        $data[$k]['msg'] = $msg;
        $data[$k]['send_time'] = time();
    }
    if( !$db->table(C('DB_PREFIX').'inner_msg')->addAll($data) ) {
        return false;
    }else {
        return true;
    }
}

//获取下级或者同级栏目列表
function getTypeList($parm){
	$Osql="sort_order DESC";
	$field="id,type_name,type_set,add_time,type_url,type_nid,parent_id";
	//查询条件 
	$Lsql="{$parm['limit']}";
	$pc = D('navigation')->where("parent_id={$parm['type_id']} and model='navigation'")->count('id');
	if($pc>0){
		$map['is_hiden'] = 0;
		$map['parent_id'] = $parm['type_id'];
        $map['model']  = 'navigation';
		$data = D('navigation')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
	}elseif(!isset($parm['notself'])){
		$map['is_hiden'] = 0;
		$map['parent_id'] = D('Acategory')->getFieldById($parm['type_id'],'parent_id');
		$data = D('Acategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
	}

	//链接处理
	$typefix = get_type_leve_nid($parm['type_id']);
	$typeu = $typefix[0];
	$suffix=C("URL_HTML_SUFFIX");
	foreach($data as $key=>$v){
		if($v['type_set']==2){
			if(empty($v['type_url'])) $data[$key]['turl']="javascript:alert('请在后台添加此栏目链接');";
			else $data[$key]['turl'] = $v['type_url'];
		}
		elseif($parm['model']=='navigation'||($v['parent_id']==0)) $data[$key]['turl'] = MU("Home/{$v['type_nid']}/index","typelist",array("suffix"=>$suffix));
		elseif($parm['model']=='article'||($v['parent_id']==0)) $data[$key]['turl'] = MU("Home/{$v['type_nid']}/index","typelist",array("suffix"=>$suffix));
		else $data[$key]['turl'] = MU("Home/{$typeu}/{$v['type_nid']}","typelist",array("suffix"=>$suffix));
	}
	$row=array();
	$row = $data;

	return $row;
}

//获取下级或者同级栏目列表 文章栏目
/*
 * @$field 添加type_img  
 */
function getTypeListActa($parm){
	//if(empty($parm['type_id'])) return;
	$Osql="sort_order DESC";
	$field="id,type_name,type_set,add_time,type_url,type_nid,parent_id,type_img";
	//查询条件 
	$Lsql="{$parm['limit']}";
	//$pc = D('Acategory')->where("parent_id={$parm['type_id']} and model='navigation'")->count('id');
	$pc = D('Acategory')->where("parent_id={$parm['type_id']} and model='article'")->count('id');
	if($pc>0){
		$map['is_hiden'] = 0;
		$map['parent_id'] = $parm['type_id'];
        $map['model']  = 'article';
        $map['type_keyword'] = '';
		//$data = D('Acategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
		$data = D('Acategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
	}elseif(!isset($parm['notself'])){
		$map['is_hiden'] = 0;
		$map['parent_id'] = D('Acategory')->getFieldById($parm['type_id'],'parent_id');
		$map['type_keyword'] = '';
		//$data = D('Acategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
		$data = D('Acategory')->field($field)->where($map)->order($Osql)->limit($Lsql)->select();
	}

	//链接处理
	$typefix = get_type_leve_nid($parm['type_id']);
	$typeu = $typefix[0];
	$suffix=C("URL_HTML_SUFFIX");
	foreach($data as $key=>$v){
		if($v['type_set']==2){
			if(empty($v['type_url'])) $data[$key]['turl']="javascript:alert('请在后台添加此栏目链接');";
			else $data[$key]['turl'] = $v['type_url'];
		}
		//elseif($parm['type_id']==0||($v['parent_id']==0&&count($typefix)==1)) $data[$key]['turl'] = MU("Home/{$v['type_nid']}/index","typelist",array("suffix"=>$suffix));
		elseif($parm['model']=='article'||($v['parent_id']==0)) $data[$key]['turl'] = MU("Home/{$v['type_nid']}/index","typelist",array("suffix"=>$suffix));
		else $data[$key]['turl'] = MU("Home/{$typeu}/{$v['type_nid']}","typelist",array("suffix"=>$suffix));
	}
	$row=array();
	$row = $data;
	
	return $row;
}
//新标提醒
function newTip($borrow_id){
    $pre = C('DB_PREFIX');
	$binfo = M("borrow_info")->field('borrow_type,borrow_interest_rate,borrow_duration')->find();
	
	if($binfo['borrow_type']==3) $map['borrow_type'] = 3;
	else $map['borrow_type'] = 0;
	$tiplist = M("borrow_tip")->field(true)->where($map)->select();
	
	foreach($tiplist as $key=>$v){
		$minfo = M('members m')->field('mm.account_money,mm.back_money,m.user_phone')->join($pre.'member_money mm on m.id=mm.uid')->find($v['uid']);
		if(
		$binfo['borrow_interest_rate'] >= $v['interest_rate'] &&
		$binfo['borrow_duration'] >= $v['doration_from'] &&
		$binfo['borrow_duration'] <= $v['doration_to'] &&
		($minfo['account_money']+ $minfo['back_money'])>= $v['account_money']
		){
			(empty($tipPhone))?$tipPhone .="{$v['user_phone']}":$tipPhone .=",{$v['user_phone']}";
		}
	}
	$smsTxt = FS("Webconfig/smstxt");
	$smsTxt=de_xie($smsTxt);
	
	sendsms($tipPhone,$smsTxt['newtip']);
	
}

/**
 * @param $type 还款方式
 * @param $money    借款金额
 * @param $duration 借款期限
 * @param $rate 利率
 * @param bool $durationMonth 借款期限为自然月还是天
 * @param bool $borrowers 是否计算借款人的利息，是的话采取进位法，最多相差0.01
 * @return string
 */
function getBorrowInterest($type,$money,$duration,$rate, $durationMonth = true, $borrowers = false){
	//if(!in_array($type,C('REPAYMENT_TYPE'))) return $money;
	//echo $month_rate."|".$rate."|".$duration."|".$type;
	switch($type){
		case 1://按天到期还款
			$day_rate =  $rate/36500;//计算出天标的天利率
			$interest = getFloatValue($money*$day_rate*$duration ,4);
		break;
		case 2://按月分期还款
			$parm['duration'] = $duration;
			$parm['money'] = $money;
			$parm['year_apr'] = $rate;
			$parm['type'] = "all";
			$intre = EqualMonth($parm);
			$interest = ($intre['repayment_money'] - $money);
		break;
		case 3://按季分期还款
			$parm['month_times'] = $duration;
			$parm['account'] = $money;
			$parm['year_apr'] = $rate;
			$parm['type'] = "all";
			$intre = EqualSeason($parm);
			$interest = $intre['interest'];
		break;
		case 4://每月还息到期还本
			$parm['duration'] = $duration;
			$parm['account'] = $money;
			$parm['year_apr'] = $rate;
			$parm['type'] = "all";
			$intre = EqualEndMonth($parm, $durationMonth);
			$interest = $intre['interest'];
		break;
		case 5://一次性到期还款
			$parm['month_times'] = $duration;
			$parm['account'] = $money;
			$parm['year_apr'] = $rate;
			$parm['type'] = "all";
			$intre = EqualEndMonthOnly($parm, $durationMonth);
			$interest = $intre['interest'];
		break;
                                            case 6://利息复投
			$parm['month_times'] = $duration;
			$parm['account'] = $money;
			$parm['year_apr'] = $rate;
			$parm['type'] = "all";
			$intre = CompoundMonth($parm);
			$interest = $intre['interest'];
		break;
	}
	return $interest;
}

/**
 * //等额本息法
 * 贷款本金×月利率×（1+月利率）还款月数/[（1+月利率）还款月数-1]
 * a*[i*(1+i)^n]/[(1+I)^n-1]
 *（a×i－b）×（1＋i）
 * money,year_apr,duration,borrow_time(用来算还款时间的),type(==all时，返回还款概要)
 * @param array $data
 * @param bool $lastInterest 最后所得利息，如果大于0，说明投完之后满标，利息使用减法以使数据完整
 * @return array
 */
function EqualMonth ($data = array(), $lastInterest = false){
    if (isset($data['money']) && $data['money']>0){
        $account = $data['money'];
    }else{
        return "";
    }

    if (isset($data['year_apr']) && $data['year_apr']>0){
        $year_apr = $data['year_apr'];
    }else{
        return "";
    }

    if (isset($data['duration']) && $data['duration']>0){
        $duration = $data['duration'];
    }
    if (isset($data['borrow_time']) && $data['borrow_time']>0){
        $borrow_time = $data['borrow_time'];
    }else{
        $borrow_time = time();
    }
    $month_apr = $year_apr/(12*100);
    $_li = pow((1+$month_apr),$duration);
    $repayment = getFloatValue($account * ($month_apr * $_li)/($_li-1),4);
    $_result = array();
    if (isset($data['type']) && $data['type']=="all"){
        $_result['repayment_money'] = getFloatValue($repayment*$duration, 2); //  如果这里四舍五入在某种条件下扔有误差，那么这里的值为前n期相加之和
        $_result['monthly_repayment'] = $repayment;
        $_result['month_apr'] = round($month_apr*100, 2);
    }else{
        $interestAll = $capitalAll = 0;
        //$re_month = date("n",$borrow_time);
        for($i=0;$i<$duration;$i++){
            if ($i==0){
                $interest = round(bcmul($account,$month_apr,6),4);
            }else{
                $_lu = pow((1+$month_apr),$i);
                $interest = round(($account*$month_apr - $repayment)*$_lu + $repayment,6);
            }
            $_result[$i]['repayment_money'] = getFloatValue($repayment, 2);
            
            $_result[$i]['repayment_time'] = get_times(array("time"=>$borrow_time,"num"=>$i+1));
            if( $i == $duration -1 ) {
                $_result[$i]['capital'] = $account - $capitalAll;
            } else {
                $_result[$i]['capital'] = getFloatValue($repayment-$interest, 2);
            }
            if( $lastInterest > 0 && $i == $duration - 1 ) {
                $_result[$i]['interest'] = $lastInterest - $interestAll;
            } else {
                $_result[$i]['interest'] = getFloatValue($interest, 2);
            }
            $interestAll += $_result[$i]['interest'];
            $capitalAll += $_result[$i]['capital'];
        }
    }
    return $_result;
}

function EqualEndMonth ($data = array(), $durationMonth = true)
{
    if( isset($data['duration']) && $data['duration']>0
        && isset($data['account']) && $data['account']>0
        && isset($data['year_apr']) && $data['year_apr']>0
    ) {
        $month_times = $data['duration'];
        $account = $data['account']; //借款总金额
        $year_apr = $data['year_apr']; // //年化利率
    } else {
        return '';
    }
    //借款的时间
    if (isset($data['borrow_time']) && $data['borrow_time']>0){
        $borrow_time = strtotime(date('Y-m-d', $data['borrow_time']));
    }else{
        $borrow_time = strtotime(date('Y-m-d', time()));
    }
    if( $durationMonth == true ) { //借款期限为自然月
        //获得借款实际天数
        $month_times = getDaysByMonth($month_times, $borrow_time);
        $repay_times = $data['duration'];
    } else {
        $repay_times = ceil($month_times/28); // 还款的次数，如果一个月以内，那么跟天标一样，一次性还清，如果大于一个月，一次还息，最后一次还本息
    }

    // 小于等于28号时还款日为当月的号数 + 当月总共的天数（比如3月31天，4月30天）。
    // 还款日大于28号时，如果超过下个月的天数，以下个月的最大日为还款日。
    $repay_date = 28;

    //天利率
    $day_apr = bcdiv($year_apr, (365*100), 12);

    //$re_month = date("n",$borrow_time);
    $_all_interest=0;
    $_result = array();
    $interest = bcmul(bcmul($account, $day_apr, 6), $month_times, 2); //总利息 = 应还金额乘天利率乘天数

    // 项目结束日期,最后一期大于等于结束时间，当大于结束日期时，值为结束日期。并且跳出循环
    $finish_date = strtotime("+{$month_times} days", $borrow_time);
    if( ($durationMonth == false && $month_times <= 31) || ($durationMonth == true && $data['duration'] == 1) ) {
        $_result[0]['interest'] = $interest; // 天数 * 利率
        $_result[0]['repayment_account'] = $_result[0]['interest'] + $account;  // 本金加利息
        $_result[0]['repayment_time'] = $finish_date;
        $_result[0]['capital'] = $account;  // 本金
    } else {
        for($i=0;$i<$repay_times;$i++){
            if( $repay_times > 1 ) { // 如果不是最后一期
                // 首跟尾是同一日期，且起息日 <= 28,这是为了排除二月
                $now_day = date('d', $borrow_time); // 当月的多少号
                // 如果起息日大于28号
                if( $now_day > $repay_date )
                {
                    if( $i == 0 ) {
                        // 计算下个还款日月的最大天数
                        $max_days = date('t', strtotime("+10 days", $borrow_time)); // 随便加几天，确保能到下个月就可以
                        $repay_day = min($max_days, $now_day);  // 还款日不能超过下月最大的天数
                        $diff_days = ($repay_day + date('t', $borrow_time)-date('d', $borrow_time));
                        $_result[$i]['interest'] = bcmul(bcmul($account, $day_apr, 6), $diff_days, 2); // 天数 * 利率
                        $_result[$i]['repayment_account'] = $_result[$i]['interest'];  // 本金加利息

                        $_result[$i]['repayment_time'] = strtotime("+{$diff_days} days",$borrow_time );
                        $_result[$i]['capital'] = 0;  // 本金
                        $_all_interest += $_result[$i]['interest'];
                    } else {
                        // 计算下个还款日月的最大天数
                        $max_days = date('t', strtotime("+10 days", $_result[$i-1]['repayment_time'])); // 随便加几天，确保能到下个月就可以
                        $repay_day = min($max_days, $now_day);  // 还款日不能超过下月最大的天数
                        $diff_days = ($repay_day + date('t', $_result[$i-1]['repayment_time'])-date('d', $_result[$i-1]['repayment_time']));
                        $_result[$i]['repayment_time'] = strtotime("+{$diff_days} days" ,$_result[$i-1]['repayment_time']);
                        if( $_result[$i]['repayment_time'] > $finish_date ) {
                            $_result[$i]['repayment_time'] = $finish_date;
                            $diff_days = floor($finish_date - $_result[$i-1]['repayment_time'])/(3600*24);
                        }

                        if( $finish_date == $_result[$i]['repayment_time'] ) { // 最后一期
                            $_result[$i]['interest'] = round($interest - $_all_interest, 2); // 最后一期的利息 = 总利息 - 前面几期的利息，这样误差控制在0.01
                            $_result[$i]['repayment_account'] = $_result[$i]['interest'] + $account;  // 本金加利息
                            $_result[$i]['capital'] = $account;  // 最后一期还本
                            break;
                        }else {
                            $_result[$i]['interest'] = bcmul(bcmul($account, $day_apr, 6), $diff_days, 2); // 天数 * 利率
                            $_result[$i]['repayment_account'] = $_result[$i]['interest'];
                            $_result[$i]['capital'] = 0;
                            $_all_interest += $_result[$i]['interest'];
                        }
                    }
                }
                else // 小于等于28号的，第一次还息是下个月的当前日期
                {
                    if( $i == 0 ) {
                        $diff_days = $now_day + date('t', $borrow_time)-date('d', $borrow_time);
                        $_result[$i]['interest'] = bcmul(bcmul($account, $day_apr, 6), $diff_days, 2); // 天数 * 利率
                        $_result[$i]['repayment_account'] = $_result[$i]['interest'];  // 本金加利息
                        $_result[$i]['repayment_time'] = strtotime("+{$diff_days} days", $borrow_time);
                        $_result[$i]['capital'] = 0;  // 本金
                        $_all_interest += $_result[$i]['interest'];
                    } else {
                        $diff_days = $now_day + date('t', $_result[$i-1]['repayment_time'])-date('d', $_result[$i-1]['repayment_time']);
                        $_result[$i]['repayment_time'] = strtotime("+{$diff_days} days" ,$_result[$i-1]['repayment_time']);
                        if( $_result[$i]['repayment_time'] > $finish_date ) {
                            $_result[$i]['repayment_time'] = $finish_date;
                            $diff_days = floor($finish_date - $_result[$i-1]['repayment_time'])/(3600*24);
                        }
                        if( $finish_date == $_result[$i]['repayment_time'] ) { // 最后一期
                            $_result[$i]['interest'] = $interest - $_all_interest; // 最后一期的利息 = 总利息 - 前面几期的利息，这样误差控制在0.01
                            $_result[$i]['repayment_account'] = $_result[$i]['interest'] + $account;  // 本金加利息
                            $_result[$i]['capital'] = $account;  // 最后一期还本
                            break;
                        }else {
                            $_result[$i]['interest'] = bcmul(bcmul($account, $day_apr, 6), $diff_days, 2); // 天数 * 利率
                            $_result[$i]['repayment_account'] = $_result[$i]['interest'];
                            $_result[$i]['capital'] = 0;
                            $_all_interest += $_result[$i]['interest'];
                        }
                    }
                }
            }
        }
    }

    if (isset($data['type']) && $data['type']=="all"){
        $_resul['repayment_account'] = $account + $interest; // 总的还款额
        $_resul['day_apr'] = round($day_apr*100,4); // 天利率
        $_resul['interest'] = $interest; // 总利息
        return $_resul;
    }else{
        // 测试，格式化出时间戳
        /*for( $i=0; $i<count($_result); $i++ ) {
            $_result[$i]['repayment_time'] = date('Y-m-d H:i:s', $_result[$i]['repayment_time']);
        }*/
        return $_result;
    }
}


// 舍位法保留*位小数， $retain_num实为保留几位小数
function giveDecimal($num, $retain_num = 2)
{
    if( is_numeric($num) ) {
        return floor($num*pow(10, $retain_num))/pow(10, $retain_num);
    }
}

// 进位法保留两位小数
function carryDecimal($num)
{
    if( is_numeric($num) ) {
        return ceil($num*100)/100;
    }
}

/////////////////////////////////////////一次性还款//////////////////////////////////////
/**
 * 到期还本，按天计息，前台提交过来的期限是月，这个月是自然月，遇到特殊的截止日期，以结束日期小于等于开始日期为标准
 * 1)用12进制先计算截止日期的月分，2)初始月的日(D)和结束月最大天数取一个最小值，就是结束月的日(D)
 * 以3个月为例： 11月29日-2月28日；2月28日-5月28日，
 * 参考人人贷：http://www.renrendai.com/financeplan/listPlan!detailPlan.action?financePlanId=121
 * TODO:更改还款方式时需要更改相应的地方，发标，初审，投标，复审，还款！
 */
function EqualEndMonthOnly($data = array(), $durationMonth = true, $lastInterest = false){
    //借款的期限
    if (isset($data['month_times']) && $data['month_times']>0){
      $month_times = $data['month_times'];
    }

    //借款的总金额
    if (isset($data['account']) && $data['account']>0){
      $account = $data['account'];
    }else{
      return "";
    }

    //借款的年利率
    if (isset($data['year_apr']) && $data['year_apr']>0){
      $year_apr = $data['year_apr'];
    }else{
      return "";
    }

    //借款的时间
    if (isset($data['borrow_time']) && $data['borrow_time']>0){
      $borrow_time = $data['borrow_time'];
    }else{
      $borrow_time = strtotime(date('Y-m-d', time()));
    }

    //天利率，按天计息要除以365
    $day_apr = $year_apr/(365*100);

    if( $durationMonth == true ) {
        $diff_days =  getDaysByMonth($month_times, $borrow_time); // 将自然月转换成天
    } else {
        $diff_days = $month_times;
    }
    if( $lastInterest > 0 ) { // 如果是最后一位满标，最后一位的利息已经算好了，不通过再次计算，否则有误差
        $interest = $lastInterest;
    } else {
        $interest = getFloatValue($account*$day_apr*$diff_days,4);//利息等于应还金额*天利率*借款天数
    }

    if (isset($data['type']) && $data['type']=="all"){
      $_resul['repayment_account'] = $account + $interest;
      $_resul['repayment_time'] = strtotime("+{$diff_days} days", $borrow_time); // 还款时间
      $_resul['day_apr'] = round($day_apr,6);
      $_resul['interest'] = $interest;
      $_resul['capital'] = $account;
      return $_resul;
    }
}

/**
 * 计算标的结束时间与借款时间相隔的天数,获得自然月相隔的天数
 * 1)用12进制先计算截止日期的月分，2)初始月的日(D)和结束月最大天数取一个最小值，就是结束月的日(D)
 * @param $duration int 借款期限,自然月
 */
function getDaysByMonth($duration, $borrow_time = false)
{
    $ret = false;
    if( $duration > 0 ) {
        // 数据较正，开始日期从当天的0时0分0秒，复审通过时，当天都算利息
        $borrow_time = $borrow_time === false ? strtotime(date('Y-m-d', time())) : strtotime(date('Y-m-d', $borrow_time)); // 投资或借款当日计算，当日29:59:59秒投资都算一天利息。
        $borrow_day = date('d', $borrow_time); // 借款当天几号
        //结束时间月
        $total_months = date('m', $borrow_time) + $duration;
        if( $total_months <= 12 ) {
            $deadline_month = $total_months;
            $add_year = 0;
        } else {
            $deadline_month = $total_months%12;
            $add_year = floor($total_months/12);
        }
        // 结束时间年
        $deadline_year = date('Y', $borrow_time) + $add_year;
        // 结束时间日
        $deadline_day = min($borrow_day, date('t', strtotime($deadline_year.'-'.$deadline_month.'-01')));
        // 计算起始日期与结束日期的天数
        // 获得某个日期到现在的天数
        $diff_days =  floor((strtotime($deadline_year.'-'.$deadline_month.'-'.$deadline_day) - $borrow_time)/(3600*24));
        $ret = $diff_days;
    }
    return $ret;
}

///////////////////////////////////////////////////////////////////////////////////////////
function getMinfo($uid,$field='m.pin_pass,mm.account_money,mm.back_money'){
	$pre = C('DB_PREFIX');
	$vm = M("members m")->field($field)->join("{$pre}member_money mm ON mm.uid=m.id")->where("m.id={$uid}")->find();
    if( !empty($vm) ) {
        $vm['user_account'] = $vm['account_money'] + $vm['back_money'];
    }
	return $vm;
}


//获取借款列表
function getMemberInfoDone($uid){
	$pre = C('DB_PREFIX');

	$field = "m.id,m.id as uid,m.user_name,mbank.uid as mbank_id,mi.uid as mi_id,mhi.uid as mhi_id,mci.uid as mci_id,mdpi.uid as mdpi_id,mei.uid as mei_id,mfi.uid as mfi_id,s.phone_status,s.id_status,s.email_status,s.safequestion_status";
	$row = M('members m')->field($field)
	->join("{$pre}member_banks mbank ON m.id=mbank.uid")
	->join("{$pre}member_contact_info mci ON m.id=mci.uid")
	->join("{$pre}member_department_info mdpi ON m.id=mdpi.uid")
	->join("{$pre}member_house_info mhi ON m.id=mhi.uid")
	->join("{$pre}member_ensure_info mei ON m.id=mei.uid")
	->join("{$pre}member_info mi ON m.id=mi.uid")
	->join("{$pre}member_financial_info mfi ON m.id=mfi.uid")
	->join("{$pre}members_status s ON m.id=s.uid")
	->where("m.id={$uid}")->find();
	$is_data = M('member_data_info')->where("uid={$row['uid']}")->count("id");
	$i==0;
	if($row['mbank_id']>0){
		$i++;
		$row['mbank'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mbank'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mci_id']>0){
		$i++;
		$row['mci'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mci'] = "<span style='color:black'>未填写</span>";
	}
	
	if($is_data>0){
		$row['mdi_id'] = $is_data;
		$row['mdi'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mdi'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mhi_id']>0){
		$i++;
		$row['mhi'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mhi'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mdpi_id']>0){
		$i++;
		$row['mdpi'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mdpi'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mei_id']>0){
		$i++;
		$row['mei'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mei'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mfi_id']>0){
		$i++;
		$row['mfi'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mfi'] = "<span style='color:black'>未填写</span>";
	}
	
	if($row['mi_id']>0){
		$i++;
		$row['mi'] = "<span style='color:green'>已填写</span>";
	}else{
		$row['mi'] = "<span style='color:black'>未填写</span>";
	}
	
	$row['i'] = $i;//7为完成
	return $row;
}

function getMemberBorrowScan($uid){
	//借款次数相关
	$field="borrow_status,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money";
	$borrowNum=M('borrow_info')->field($field)->where("borrow_uid = {$uid}")->group('borrow_status')->select();
	foreach($borrowNum as $v){
		$borrowCount[$v['borrow_status']] = $v;
	}
	//借款次数相关
	//还款情况相关
	$field="status,sort_order,borrow_id,sum(capital) as capital,sum(interest) as interest";
	$repaymentNum=M('investor_detail')->field($field)->where("borrow_uid = {$uid}")->group('sort_order,borrow_id')->select();
	foreach($repaymentNum as $v){
		$repaymentStatus[$v['status']]['capital']+=$v['capital'];//当前状态下的数金额
		$repaymentStatus[$v['status']]['interest']+=$v['interest'];//当前状态下的数金额
		$repaymentStatus[$v['status']]['num']++;//当前状态下的总笔数
	}
	//还款情况相关
	//借出情况相关
	$field="status,count(id) as num,sum(investor_capital) as investor_capital,sum(reward_money) as reward_money,sum(investor_interest) as investor_interest,sum(receive_capital) as receive_capital,sum(receive_interest) as receive_interest,sum(invest_fee) as invest_fee";
	$investNum=M('borrow_investor')->field($field)->where("investor_uid = {$uid}")->group('status')->select();
	$_reward_money = 0;
	foreach($investNum as $v){
		$investStatus[$v['status']]=$v;
		$_reward_money+=floatval($v['reward_money']);
	}
	//借出情况相关
	//逾期的借入
	$field="borrow_id,sort_order,sum(`capital`) as capital,count(id) as num";
	$expiredNum=M('investor_detail')->field($field)->where("`repayment_time`=0 and borrow_uid={$uid} AND status=7 and `deadline`<".time()." ")->group('borrow_id,sort_order')->select();
	$_expired_money = 0;
	foreach($expiredNum as $v){
		$expiredStatus[$v['borrow_id']][$v['sort_order']]=$v;
		$_expired_money+=floatval($v['capital']);
	}
	$rowtj['expiredMoney'] = getFloatValue($_expired_money,2);//逾期金额
	$rowtj['expiredNum'] = count($expiredNum);//逾期期数
	//逾期的借入
	//逾期的投资
	$field="borrow_id,sort_order,sum(`capital`) as capital,count(id) as num";
	$expiredInvestNum=M('investor_detail')->field($field)->where("`repayment_time`=0 and `deadline`<".time()." and investor_uid={$uid} AND status <> 0")->group('borrow_id,sort_order')->select();
	$_expired_invest_money = 0;
	foreach($expiredInvestNum as $v){
		$expiredInvestStatus[$v['borrow_id']][$v['sort_order']]=$v;
		$_expired_invest_money+=floatval($v['capital']);
	}
	$rowtj['expiredInvestMoney'] = getFloatValue($_expired_invest_money,2);//逾期金额
	$rowtj['expiredInvestNum'] = count($expiredInvestNum);//逾期期数
	//逾期的投资
	
	$rowtj['jkze'] = getFloatValue(floatval($borrowCount[6]['money']+$borrowCount[7]['money']+$borrowCount[8]['money']+$borrowCount[9]['money']),2);//借款总额
	$rowtj['yhze'] = getFloatValue(floatval($borrowCount[6]['repayment_money']+$borrowCount[7]['repayment_money']+$borrowCount[8]['repayment_money']+$borrowCount[9]['repayment_money']),2);//应还总额
	$rowtj['dhze'] = getFloatValue($rowtj['jkze']-$rowtj['yhze'],2);//待还总额
	$rowtj['jcze'] = getFloatValue(floatval($investStatus[4]['investor_capital']),2);//借出总额
	$rowtj['ysze'] = getFloatValue(floatval($investStatus[4]['receive_capital']),2);//应收总额
	$rowtj['dsze'] = getFloatValue($rowtj['jcze']-$rowtj['ysze'],2);
	$rowtj['fz'] = getFloatValue($rowtj['jcze']-$rowtj['jkze'],2);
	
	$rowtj['dqrtb'] = getFloatValue($investStatus[1]['investor_capital'],2);//待确认投标
    //净赚利息      
    $circulation = M('borrow_investor')->field('sum(investor_interest)as investor_interest, sum(invest_fee) as invest_fee')
                                                ->where('borrow_type=6 and investor_uid='.$uid.' and status<5')
                                                ->find();
	$rowtj['earnInterest'] = getFloatValue(floatval($investStatus[5]['receive_interest']
                                                    +$investStatus[6]['receive_interest']
                                                    +$circulation['investor_interest']
                                                    -$investStatus[5]['invest_fee']
                                                    -$investStatus[6]['invest_fee']
                                                    -$circulation['invest_fee']
                                                    ),2);//净赚利息
    $receive_interest = M('borrow_investor')->where('borrow_type=6 and investor_uid='.$uid)->sum('investor_capital');
	$rowtj['payInterest'] = getFloatValue(floatval($repaymentStatus[1]['interest']+$repaymentStatus[2]['interest']+$repaymentStatus[3]['interest']),2);//净付利息
	$rowtj['willgetInterest'] = getFloatValue(floatval($investStatus[4]['investor_interest']-$investStatus[4]['receive_interest']),2);//待收利息
	$rowtj['willpayInterest'] = getFloatValue(floatval($repaymentStatus[7]['interest']),2);//待确认支付管理费
	$rowtj['borrowOut'] = getFloatValue(floatval($investStatus[4]['investor_capital']+$investStatus[5]['investor_capital']+$investStatus[6]['investor_capital']+$receive_interest),2);//借出总额
	$rowtj['borrowIn'] = getFloatValue(floatval($borrowCount[6]['money']+$borrowCount[7]['money']+$borrowCount[8]['money']+$borrowCount[9]['money']),2);//借入总额
	
	$rowtj['jkcgcs'] = $borrowCount[6]['num']+$borrowCount[7]['num']+$borrowCount[8]['num']+$borrowCount[9]['num'];//借款成功次数
	$rowtj['tbjl'] = $_reward_money;//投标奖励

    //处理企业直投的相关数据
    //企业直投借出未确定的金额及数量
    $circulation_bor = M('borrow_investor')->field('sum(investor_capital) as investor_capital, count(id) as num')
                                                        ->where('borrow_type=6 and investor_uid='.$uid.' and status<5')
                                                        ->find();
    $investStatus[8]['investor_capital'] += $circulation_bor['investor_capital'];
	$investStatus[8]['num'] += $circulation_bor['num'];
    unset($circulation_bor);
    //企业直投已回收的投资及数量
    $circulation_bor = M('borrow_investor')->field('sum(investor_capital) as investor_capital, count(id) as num')
                                                        ->where('borrow_type=6 and investor_uid='.$uid.' and status=5')
                                                        ->find();
    $investStatus[9]['investor_capital'] += $circulation_bor['investor_capital'];
    $investStatus[9]['num'] += $circulation_bor['num'];
    $pre = C('DB_PREFIX');
    //完成的投资
    $circulation_bor = M("borrow_investor i")
                        ->field('sum(i.investor_capital) as investor_capital, count(i.id) as num')
                        ->where('i.borrow_type=6 and i.status=5 and i.investor_uid='.$uid)
                        ->join("{$pre}borrow_info b ON b.id=i.borrow_id")
                        ->order("i.id DESC")
                        ->find();
    
	$row=array();
	$row['tborrowOut']=$receive_interest;//企业直投借出总额
	$row['borrow'] = $borrowCount;
	$row['repayment'] = $repaymentStatus;
	$row['invest'] = $investStatus;
	$row['tj'] = $rowtj;
    $row['circulation_bor'] = $circulation_bor;
	return $row;
}

function getUserWC($uid){
	$row=array();
	$field="count(id) as num,sum(withdraw_money) as money";
	$row["W"] = M('member_withdraw')->field($field)->where("uid={$uid} AND withdraw_status=2")->find();
	$field="count(id) as num,sum(money) as money";
	$row["C"] = M('member_payonline')->field($field)->where("uid={$uid} AND status=1")->find();
	return $row;
}
function getExpiredDays($deadline){
	if($deadline<1000) return "数据有误";
	return ceil( (time()-$deadline)/3600/24 );
}
function getExpiredMoney($expired,$capital,$interest){
	$glodata = get_global_setting();
	$expired_fee = explode("|",$glodata['fee_expired']);

	if($expired<=$expired_fee[0]) return 0;
	return getFloatValue(($capital+$interest)*$expired*$expired_fee[1]/1000,2);
}
function getExpiredCallFee($expired,$capital,$interest){
	$glodata = get_global_setting();
	$call_fee = explode("|",$glodata['fee_call']);
	
	if($expired<=$call_fee[0]) return 0;
	return getFloatValue(($capital+$interest)*$expired*$call_fee[1]/1000,2);
}


function getNet($uid){
	//return getFloatValue($minfo['account_money'] + $minfo['money_freeze'] + $minfo['money_collect'] - intval($capitalinfo['borrow'][6]['money'] - $capitalinfo['borrow'][6]['repayment_money']),2);
	$_minfo = getMinfo($uid,"m.pin_pass,mm.account_money,mm.back_money,mm.credit_cuse,mm.money_collect");
	$borrowNum=M('borrow_info')->field("borrow_type,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money")->where("borrow_uid = {$uid} AND borrow_status=6 ")->group("borrow_type")->select();
	$borrowDe = array();
	foreach ($borrowNum as $k => $v) {
		$borrowDe[$v['borrow_type']] = $v['money'] - $v['repayment_money'];
	}	
	$_netMoney = getFloatValue(0.9*$_minfo['money_collect']-$borrowDe[4],2);
	return $_netMoney;	
}

function setBackUrl($per="",$suf=""){
	$url = $_SERVER['HTTP_REFERER'];
	$urlArr = parse_url($url);
	$query = $per."?1=1&".$urlArr['query'].$suf;
	session('listaction',$query);
}

//是否生日
function isBirth($uid){
	$pre = C('DB_PREFIX');
	$id = M("member_info i")->field("i.idcard")->join("{$pre}members_status s ON s.uid=i.uid")->where("i.uid = $uid AND s.id_status=1 ")->find();
	if(!$id)		return false;

	$bir = substr($id['idcard'], 10, 4);
	$now = date("md");

	if( $bir==$now )	return true;
	else 		return false;
}

function sendemail($to,$subject,$body){
    //邮件
    header("content-type:text/html;charset=utf-8");
    import("ORG.Net.Phpmailer");
    
    $msgconfig = FS("Webconfig/msgconfig");
    $stmpport = $msgconfig['stmp']['port'];//25;
    $stmphost = $msgconfig['stmp']['server'];
    $stmpuser = $msgconfig['stmp']['user'];
    $stmppass = $msgconfig['stmp']['pass'];
    
    $mail = new PHPMailer(true);
    $mail->IsSMTP();
    $mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
    $mail->SMTPAuth   = true;                  //开启认证
    $mail->Port       = $stmpport;//25;
    $mail->Host       = $stmphost;
    $mail->Username   = $stmpuser;
    $mail->Password   = $stmppass;
    $mail->AddReplyTo($stmpuser,$stmpuser);//回复地址
    $mail->From       = $stmpuser;
    $mail->FromName   = $stmpuser;
    $mail->AddAddress($to);
    $mail->Subject  = $subject;
    $mail->Body = $body;
    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
    $mail->WordWrap   = 80; // 设置每行字符串的长度
    //$mail->AddAttachment("f:/test.png");  //可以添加附件
    $mail->IsHTML(true);
    $send = $mail->Send();
    return $send;
}

//企业直投投标处理方法
function getTInvestUrl($id){
	return __APP__."/tinvest/{$id}".C("URL_HTML_SUFFIX");
	//return __APP__."/tinvest/tdetail?id={$id}";
}

/**
 * 投资的时候得考虑即投计息和满标计息
 * @param $uid int 用户user_id
 * @param $borrow_id  int 借款表id
 * @param $num  int $invest_type=0时用户投资份数|$invest_type=1时用户投资金额
 * @param $duration  int 用户投资期限,可不填，可以通过borrow_info直接获得，删除之后对定投宝影响不详？ (定投宝不使用这个字段，是不能不填)
 * @param int $_is_auto  是否是自动投标
 * @param int $repayment_type  定投宝还款方式
 * @param int $invest_type  0:按份数 1：按金额
 * @param array $coupon_ids 优惠券ids
 * @return bool
 * @throws Exception
 * i> 满标或募集期截止时更改状态
 * ii> 即投计息投资的时候，借款人的总利息 = 每个人的投资额*按天计算的利息 之和，所以在满标或者募集期截止时计算总利息
 */
function TinvestMoney($uid,$borrow_id, $num, $duration = false,$_is_auto = 0,$repayment_type=4, $invest_type=1, $coupon_ids=false, $invest_repayment_type = 0){
	$pre = C("DB_PREFIX");
	$done = false;
	$datag = get_global_setting();
	$invest_integral = $datag['invest_integral'];//投资积分
	$fee_rate = $datag['fee_invest_manage'];//投资者成交管理费费率
    $field = "b.id,b.borrow_uid,b.borrow_type,b.borrow_money,b.borrow_interest_rate,b.borrow_duration,b.duration_unit,b.repayment_type,b.add_time,b.has_borrow,b.rate_type,b.repayment_money,b.on_off,b.collect_time,b.reward_num,b.borrow_fee,b.online_time";
    $binfo = TborrowModel::get_borrow_info($borrow_id, $field);
	$vminfo = getMinfo($uid,'m.user_leve,m.time_limit,mm.account_money,mm.back_money,mm.money_collect');

    $discount_money = $taste_money = 0;
    //判断是不是体验标
    $is_taste = M("borrow_info")->getFieldById($borrow_id, "is_taste");
    // 如果使用优惠券
    if( !empty($coupon_ids) ) {
        $expand_money = ExpandMoneyModel::get_discount_money($coupon_ids, $num, $uid, $is_taste);
        if( $expand_money === false ) {
            $this->error('非法请求');
        }else {
            $discount_money = $expand_money['discount_money'];
            $taste_money = $expand_money['taste_money'];
        }
		if( is_array($coupon_ids) ) $coupon_idss = ",".implode(',', $coupon_ids).",";
			else $coupon_idss = $coupon_ids;
		$investinfo['coupon_id'] = $coupon_idss;
    }
    

	//不同会员级别的费率
	//($vminfo['user_leve']==1 && $vminfo['time_limit']>time())?$fee_rate=($fee_invest_manage[1]/100):$fee_rate=($fee_invest_manage[0]/100);
    $havemoney = $binfo['has_borrow'];
    if( $invest_type == 1 ) { //按金额投资
        $money = $num;
        if( $money < $binfo['per_transfer'] ) {
            return "对不起,您投资的金额小于最低允许投资金额,请重新填写！";
        }
        if(($vminfo['account_money']+$vminfo['back_money']+$binfo['reward_money'] + $discount_money) < $money) {
            return "您当前的可用金额为：".($vminfo['account_money']+$vminfo['back_money']+$binfo['reward_money'])." 对不起，可用余额不足，不能投标";
        }
        if(($binfo['borrow_money'] - $havemoney -$money)<0)
        {
            return "对不起，此标还差".($binfo['borrow_money'] - $havemoney)."元满标，您最多投标".($binfo['borrow_money'] - $havemoney)."元";
        }
    } else { //按份数投资
        if($num<1){
            return "对不起,您购买的份数小于最低允许购买份数,请重新输入认购份数！";
        }
        if(($binfo['transfer_total']-$binfo['has_borrow'])<$num){
            return "对不起,您购买的份数已超出当前可供购买份数,请重新输入认购份数！";
        }

        $money = $binfo['per_transfer'] * $num;
        if(($vminfo['account_money']+$vminfo['back_money'])<$money){
            return "对不起，您的可用余额不足,不能投标";
        }
    }

    // 如果刚好满标，说明是最后一个，利息计算方式为总利息减去已经投标计算的利息总和
    $lastInvest = false;
    if( $money == $binfo['borrow_money'] - $havemoney ) {
        // 最后投资的本金 为 $money, 最后投资的利息为 自身borrow_investor里面的interest
        $lastInvest = true;
    }

	$investMoney =D("borrow_investor");
	$investMoney->startTrans();
    // 根据借款期限单位计算借款天数
    $duration_unit = $binfo['duration_unit'];
    if( $duration_unit == BorrowModel::BID_CONFIG_DURATION_UNIT_DAY ) { // 单位天
        if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE && $binfo['repayment_type'] != BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) {
			//从上线时间开始算
            $duration = floor((strtotime("+{$binfo['borrow_duration']} days", strtotime($binfo['online_time'])) - strtotime(date('Y-m-d',time())))/3600/24); //如果今天也算，则取今天0时0分0秒
        } else {
            $duration = $binfo['borrow_duration'];
        }
    } else {
        $duration = getDaysByMonth($binfo['borrow_duration'], strtotime($binfo['online_time']));// 获取自然月的天数
        if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE && $binfo['repayment_type'] != BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH ) {
            $duration = floor((strtotime("+{$duration} days", strtotime($binfo['online_time'])) - strtotime(date('Y-m-d',time())))/3600/24); //如果今天也算，则取今天0时0分0秒
        }

    }

    //$endTime = strtotime(date("Y-m-d",time())." 11:59:59");
    if( isset($datag['auto_back_time']) )
        $auto_back_time = $datag['auto_back_time'];
    else
        $auto_back_time = '11:59:59';

    $endTime = strtotime(date("Y-m-d",time())." ".$auto_back_time);//企业直投自动还款时间设置

		$investinfo['status'] = BorrowInvestorModel::BID_INVEST_STATUS_WAIT_REVIEW;
		$investinfo['borrow_id'] = $borrow_id;
		$investinfo['investor_uid'] = $uid;
		$investinfo['borrow_uid'] = $binfo['borrow_uid'];
		$investinfo['investor_capital'] = $money;
		$investinfo['is_auto'] = $_is_auto;
		$investinfo['add_time'] = time();
		$investinfo['invest_repayment_type'] = $invest_repayment_type;
        $investinfo['transfer_duration'] = $duration; // 单位天
        $investinfo['deadline'] = strtotime("+{$duration} days",$endTime);
        $investinfo['reward_money'] = getFloatValue($binfo['reward_num'] * $money/100, 2);//奖励会在会员投标后一次性发放
        $investinfo['taste_money'] = $taste_money;
        $interest_rate = $binfo['borrow_interest_rate']; // 去除递增利率

        if($binfo['borrow_type'] == 7 && $repayment_type==6){//利息复投计算
                  if($duration_unit == BorrowModel::BID_CONFIG_DURATION_UNIT_DAY ){
                           $monthData['month_times'] = $duration;   //TODO: 如果是天，这里需要转化
                  }else{
                         $monthData['month_times'] =$binfo['borrow_duration'];     
                  }
            $monthData['account'] = $money;
            $monthData['year_apr'] = $interest_rate;
            $monthData['type'] = "all";
            $repay_detail = CompoundMonth($monthData);
            $investinfo['investor_interest'] = getFloatValue($repay_detail['interest'], 2);
            $investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest']/100, 2);
            $investinfo['borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID;
       }else{
            if( $binfo['repayment_type'] != BorrowModel::BID_SINGLE_CONFIG_REPAY_TYPE_MONTH  ) {
                $investinfo['investor_interest'] = bcdiv(bcmul(bcmul($interest_rate, $money, 6) , $duration, 6), 36500, 4); // 按天计息
                $investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest']/100, 4);
            }
        }
        if($binfo['borrow_type'] == 7){
            $binfo['repayment_type'] = $repayment_type;
            $investinfo['borrow_type'] = BorrowModel::BID_CONFIG_TYPE_FINANCIAL;
        }else{
            $investinfo['borrow_type'] = BorrowModel::BID_CONFIG_TYPE_TRANSFER_BID; // 企业直投
        }
        if( $binfo['repayment_type'] != 2 ) {
            $invest_info_id = M("borrow_investor")->add($investinfo);
        }

    switch($binfo['repayment_type']){
        case 2://每月还款 等额本息
            //还款概要START
            $monthData['type'] = "all";
            $monthData['money'] = $investinfo['investor_capital'];
            $monthData['year_apr'] = $binfo['borrow_interest_rate'];
            $monthData['duration'] = $binfo['borrow_duration'];
            $repay_detail = EqualMonth($monthData);
            /**
             * 如果是即投计息，利息 = 总利息 - 少投的几天利息（按天计算）
             */
            $investinfo['investor_interest'] = bcsub($repay_detail['repayment_money'], $investinfo['investor_capital'], 4);
            if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                // 当前时间 - 标的上线时间 = 迟投的天数
                $late_day = ceil((strtotime(date('Y-m-d', time())) - strtotime(date('Y-m-d', strtotime($binfo['online_time']))))/3600/24);
                // 扣除的利息
                $deduction = getFloatValue($investinfo['investor_capital']*$late_day*$binfo['borrow_interest_rate']/100/365, 4);
                // 实际获得利息
                $investinfo['investor_interest'] = getFloatValue($investinfo['investor_interest'] - $deduction, 4);
            }

            $investinfo['invest_fee'] = getFloatValue($fee_rate * $investinfo['investor_interest']/100,4);
            $invest_info_id = M('borrow_investor')->add($investinfo);
            //还款概要END

            $monthDataDetail['money'] = $investinfo['investor_capital'];
            $monthDataDetail['year_apr'] = $binfo['borrow_interest_rate'];
            $monthDataDetail['duration'] = $binfo['borrow_duration'];
            $repay_list = EqualMonth($monthDataDetail, false);
            // 体验金每期还款本金
            if( $taste_money > 0 ) {
                $monthDataDetail['money'] = $taste_money;
                $taste_list = EqualMonth($monthDataDetail, false);
            }
            $i=1;
            $detail_capital = 0;
            // 如果为即投计息，第一期的利息要扣除掉迟投的天数所应得的利息
            foreach($repay_list as $key=>$v){
                $investdetail['borrow_id'] = $borrow_id;
                $investdetail['invest_id'] = $invest_info_id;
                $investdetail['investor_uid'] = $uid;
                $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                if( $lastInvest === true && $key == count($repay_list) -1 ) {
                    $investdetail['capital'] = $money - $detail_capital;
                } else {
                    $investdetail['capital'] = $v['capital'];
                }

                if( $key == 0 && isset($deduction) && $deduction > 0 ) {
                    $v['interest'] = $v['interest'] - $deduction;
                }
                $investdetail['interest'] = $v['interest'];
                $investdetail['interest_fee'] = getFloatValue($fee_rate*$v['interest']/100,4);
                $investdetail['status'] = 0;
                $investdetail['sort_order'] = $i;
                $investdetail['total'] = $binfo['borrow_duration'];
                if( !empty($taste_list) ) {
                    foreach( $taste_list as $kk=>$val ) {
                        if( $kk == $key ) {
                            $investdetail['taste_money'] = $val['capital'];
                            break;
                        }
                    }
                }
                // 即投计息,直接创建deadline
                if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                    $investdetail['deadline'] = $v['repayment_time'];
                }
                $i++;
                $detail_capital += $investdetail['capital'];
                $savedetail[] = $investdetail;
            }
            if( !empty($savedetail) ) {
                // batch insert
                $Tinvest_defail_id = M('investor_detail')->addAll($savedetail); //保存还款详情
            }


            break;
        case 4://按天计息，每月还息，到期还本, 满标计息方式复审时再创建investor_detail，计投即息直接创建
            if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                //interest,interest_fee,status,deadline
                $fee_rate=$datag['fee_invest_manage']/100;
                // 即投即息的借款期限为总期限 - 已过天数
                $borrowScan = EqualEndMonth(array(
                    'duration' => $binfo['borrow_duration'],
                    'account' => $investinfo['investor_capital'],
                    'year_apr' => $binfo['borrow_interest_rate']
                ), $binfo['duration_unit']);

                // 体验金每期还款本金
                if( $taste_money > 0 ) {
                    $monthDataDetail['money'] = $taste_money;
                    $taste_list = EqualEndMonth(array(
                        'duration' => $binfo['borrow_duration'],
                        'account' => $taste_money,
                        'year_apr' => $binfo['borrow_interest_rate']
                    ), $binfo['duration_unit']);
                }

                $i=1;
                $detail_interest = 0;
                foreach($borrowScan as $key=>$v){
                    $investdetail['borrow_id'] = $borrow_id;
                    $investdetail['invest_id'] = $invest_info_id;
                    $investdetail['investor_uid'] = $uid;
                    $investdetail['borrow_uid'] = $binfo['borrow_uid'];
                    $investdetail['capital'] = $v['capital'];
                    if( $key == count($borrowScan) -1 ) {
                        $investdetail['interest'] = bcsub($investinfo['investor_interest'], $detail_interest, 4);
                    } else {
                        $investdetail['interest'] = $v['interest'];
                    }
                    $investdetail['interest_fee'] = getFloatValue($fee_rate*$v['interest'],4);
                    $investdetail['status'] = 7;
                    $investdetail['sort_order'] = $i;
                    $investdetail['total'] = count($borrowScan); // 总基数
                    $investdetail['deadline'] = $v['repayment_time'];
                    if( !empty($taste_list) ) {
                        foreach( $taste_list as $kk=>$val ) {
                            if( $kk == $key ) {
                                $investdetail['taste_money'] = $val['capital'];
                                break;
                            }
                        }
                    }
                    $i++;
                    $detail_interest = bcadd($detail_interest, $investdetail['interest'], 4);
                    $savedetail[] = $investdetail;
                }
                if( !empty($savedetail) ) {
                    // batch insert
                    $Tinvest_defail_id = M('investor_detail')->addAll($savedetail); //保存还款详情
                }
            } else {
                $Tinvest_defail_id = true; // 还款方式为4时，不创建详情表，在复审通过的时候创建详情表
            }
            break;
        case 5://一次性还款
            //还款概要END
		$investDetail['borrow_id'] = $borrow_id;
		$investDetail['invest_id'] = $invest_info_id;
		$investDetail['investor_uid'] = $uid;
		$investDetail['borrow_uid'] = $binfo['borrow_uid'];
		$investDetail['capital'] = $money;
		$investDetail['interest'] = getFloatValue($investinfo['investor_interest'],2);
		$investDetail['interest_fee'] = $investinfo['invest_fee'];
		$investDetail['status'] = InvestorDetailModel::INVEST_DETAIL_STATUS_WAIT_REVIEW; // 等待复审
		$investDetail['sort_order'] = 1;
		$investDetail['total'] = 1;
		$investDetail['deadline'] = $investinfo['deadline'];
		$investDetail['taste_money'] = $taste_money;
        $Tinvest_defail_id = M("investor_detail")->add($investDetail);
        break;
        case 6://利息复投
            //查看总共需要多少期，这个可能跟期限不一致
            $borrowScan = EqualEndMonth(array(
                'duration' => $binfo['borrow_duration'],
                'account' => $investinfo['investor_capital'],
                'year_apr' => $binfo['borrow_interest_rate']
            ), $binfo['duration_unit']);
            if( !empty($borrowScan) ) {
                $total_sort = count($borrowScan);
                $investDetail['repayment_time'] = 0;
                $investDetail['borrow_id'] = $borrow_id;
                $investDetail['invest_id'] = $invest_info_id;
                $investDetail['investor_uid'] = $uid;
                $investDetail['borrow_uid'] = $binfo['borrow_uid'];
                $investDetail['capital'] = $money;
                $investDetail['interest'] = getFloatValue($investinfo['investor_interest'],2);
                $investDetail['interest_fee'] = $investinfo['invest_fee'];
                $investDetail['status'] = InvestorDetailModel::INVEST_DETAIL_STATUS_WAIT_REVIEW; // 等待复审
                $investDetail['sort_order'] = $total_sort;
                $investDetail['total'] = $total_sort;
                $investDetail['deadline'] = $investinfo['deadline'];
                $investDetail['taste_money'] = $taste_money;
                $IDetail[] = $investDetail;
                $Tinvest_defail_id = M("investor_detail")->add($investDetail);
            }
        break;
    }

		if($invest_info_id && $Tinvest_defail_id){
			$investMoney->commit();
			$type = BorrowModel::get_borrow_type($binfo['borrow_type']);
            $res = memberMoneyLog($uid,37,-($money-$discount_money),"对{$borrow_id}号{$type}进行了投标",$binfo['borrow_uid'],'',0,null, $discount_money);


            if( $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                //借款人资金增加 满标计息这些不能打开
                $_borraccount = memberMoneyLog($binfo['borrow_uid'],17,$money,"第{$borrow_id}号{$type}已被认购{$money}元，{$money}元已入帐");//借款入帐
                if(!$_borraccount) return false;//借款者帐户处理出错
                if((intval($binfo['has_borrow'])+$money)==$binfo['borrow_money'])
                {//如果企业直投被认购完毕，则扣除借款人借款管理费
                    $_borrfee = memberMoneyLog($binfo['borrow_uid'],18,-$binfo['borrow_fee'],"第{$borrow_id}号{$type}被认购完毕，扣除借款管理费{$binfo['borrow_fee']}元");//借款管理费扣除
                    if(!$_borrfee) {
                        error_log('x|xx|xx :借款者帐户处理出错');
                        return false;//借款者帐户处理出错
                    }
                }

                //借款天数、还款时间
                //////////////////////////增加投资者的投资积分 2013-08-28 fans////////////////////////////////////

                if ($binfo['duration_unit']=='1') {
                	$integ = intval($investinfo['investor_capital']*getDaysByMonth($binfo['borrow_duration'])*$invest_integral/1000);
                	$comp = "个月";
                }else{
                	$integ = intval($investinfo['investor_capital']*$binfo['borrow_duration']*$invest_integral/1000);
                	$comp = "天";
                }
                if($integ>0){
                    $reintegral = memberIntegralLog($uid,2,$integ,"对{$borrow_id}号{$type}进行投标，应获积分：".$integ."分,投资金额：".$investinfo['investor_capital']."元,投资期限：".$binfo['borrow_duration'].$comp);
                    if(isBirth($uid)){
                        $reintegral = memberIntegralLog($uid,2,$integ,"亲，祝您生日快乐，本站特赠送您{$integ}积分作为礼物，以表祝福。");
                    }
                }
            }

			//////////////////////////增加投资者的投资积分 2013-08-28 fans////////////////////////////////////
            if(  $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                // 如果是即投计息，下面的注释打开，满标计息时，否则这些在复审操作里面
                $res1 = memberMoneyLog($uid,39,$investinfo['investor_capital'],"您对第{$borrow_id}号{$type}投标成功，冻结本金成为待收金额",$binfo['borrow_uid']);
                $res2 = memberMoneyLog($uid,38,$investinfo['investor_interest'] - $investinfo['invest_fee'], "第{$borrow_id}号{$type}应收利息成为待收利息", $binfo['borrow_uid']);

                //投标奖励
                if($investinfo['reward_money']>0){
                    $_remoney_do = false;
                    $_reward_m = memberMoneyLog($uid,41,$investinfo['reward_money'],"第{$borrow_id}号{$type}认购成功，获取投标奖励",$binfo['borrow_uid']);
                    $_reward_m_give = memberMoneyLog($binfo['borrow_uid'],42,-$investinfo['reward_money'],"第{$borrow_id}号{$type}已被认购，支付投标奖励",$uid);
                    if($_reward_m && $_reward_m_give) $_remoney_do = true;
                }
                ////////TODO:特殊奖励开始，原奖励金额去掉，现奖励优惠券，复审操作的时候有特殊奖励，这里只计即投即息且满标的情况，防止部分情况下即投即投有/////////
                if ((intval($binfo['has_borrow']) + $money) === intval($binfo['borrow_money'])){
                    special_award($borrow_id);
                }
            } else {
                $res1 = $res2 = true;
            }

			if(intval($binfo['has_borrow'])==0){
				$binfo['has_borrow'] +=$num;
			}
			$progress = getfloatvalue($binfo['has_borrow'] / $binfo['transfer_total'] * 100, 2);
			$upborrowsql = "update `{$pre}borrow_info` set ";
			$upborrowsql .= "`has_borrow` = `has_borrow` + {$num} ";
			$upborrowsql .= ",`borrow_times` = `borrow_times` + 1 ";
            // 满标更改状态
            if((intval($havemoney)+$money)==$binfo['borrow_money']) {
                if($binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                    $upborrowsql .= ",`borrow_status` = ".BorrowModel::BID_SINGLE_CONFIG_STATUS_REPAYMENT;
                } else {
                    $upborrowsql .= ",`borrow_status` = ".BorrowModel::BID_SINGLE_CONFIG_STATUS_WAIT_REVIEW;
                }
                $upborrowsql .= ",`full_time` = ".time();
            }
			$upborrowsql .= " WHERE `id`={$borrow_id}";
			
			$upborrow_res = M()->execute($upborrowsql);
			if(!$res || !$res1 || !$res2){ // 未用事务处理
				M("borrow_investor")->where("borrow_type=6 and id={$invest_info_id}")->delete();
				M("investor_detail")->where("invest_id={$invest_info_id}")->delete();
				$upborrowsql = "update `{$pre}borrow_info` set ";
				$upborrowsql .= "`has_borrow` = `has_borrow` - {$num}";
				if($binfo['has_borrow'] + $money == $binfo['borrow_money']){
					$upborrowsql .= ",`on_off` = 1";
				}
				$upborrowsql .= " WHERE `id`={$borrow_id}";
				$upborrow_res = M()->execute($upborrowsql);
				$done = false;
			}else{
                if(  $binfo['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
                    $vd['add_time'] = array("lt",time());
                    $vd['investor_uid'] = $uid;
                    $borrow_invest_count = M("borrow_investor")->where($vd)->count('id');//检测是否投过标且大于一次
                    //dump($borrow_invest_count);exit;
                    $reward_rate = explode("|",$datag['today_reward']);   //floatval($datag['today_reward']);//当日回款续投奖励利率
                    if($binfo['borrow_type']!=3){//如果是秒标(borrow_type==3)，则没有续投奖励这一说
                             $vd['add_time'] = array("lt",time());
                              $vd['investor_uid'] = $uid;
                              $borrow_invest_count = M("borrow_investor")->lock(true)->where($vd)->count('id');//检测是否投过标且大于一次
                              if($vminfo['back_money']>0 && $borrow_invest_count>0){//首次投标不给续投奖励
                                               if($money>$vminfo['back_money']){//如果投标金额大于回款资金池金额，有效续投奖励以回款金额资金池总额为标准，否则以投标金额为准
                                                         $reward_money_s = $vminfo['back_money'];
                                               }else{
                                                         $reward_money_s = $money;
                                               }
                                               if($binfo['duration_unit']==1){  //单位为月
                                                         if($binfo['borrow_duration']==1){
                                                               $integ   =   $reward_money_s*$reward_rate[0]/100;
                                                         }
                                                         if($binfo['borrow_duration']==2){
                                                               $integ   =   $reward_money_s*$reward_rate[1]/100;
                                                         }
                                                         if($binfo['borrow_duration']>=3){
                                                               $integ   =   $reward_money_s*$reward_rate[2]/100;
                                                         }
                                               }else{
                                                         if($binfo['borrow_duration']<=30){
                                                               $integ   =   $reward_money_s*$reward_rate[0]/100;
                                                         }
                                                         if($binfo['borrow_duration']>30 && $binfo['borrow_duration']<=60){
                                                               $integ   =   $reward_money_s*$reward_rate[1]/100;
                                                         }
                                                         if($binfo['borrow_duration']>60){
                                                               $integ   =   $reward_money_s*$reward_rate[2]/100;
                                                         }   
                                               }
                                        $result = memberIntegralLog($uid,2,$integ,"续投有效金额({$reward_money_s})的奖励({$borrow_id})号标投资积分奖励");
                               } 
                    }

                    // 更改borrow_investor和investor_detail状态，投的时候就更改，无满标 20140306 minister
                    $data = array(
                        'status' => BorrowInvestorModel::BID_INVEST_STATUS_REPAYMENT
                    );
                    $res = M('borrow_investor')->where(array('borrow_id'=>$borrow_id))->save($data);
                    if( $res ) {
                        $data = array(
                            'status' => InvestorDetailModel::INVEST_DETAIL_STATUS_REPAYING
                        );
						$ret = false;
                        $ret = M('investor_detail')->where(array('borrow_id'=>$borrow_id))->save($data);
						if( $ret !== false ) {
							$investMoney->commit();
						}
                    }
                }
				$done = true;
                //////////////////////邀请奖励开始,原奖励金额去掉，现奖励优惠券////////////////////////////////////////
                invite_reward($uid);
                /////////////////////邀请奖励结束/////////////////////////////////////////
			}
		}else{
			$investMoney->rollback();
		}
		return $done;
}


function getTransferLeftmonth($deadline){
	$lefttime = $deadline-time();
	if($lefttime<=0) return 0;
	//echo $lefttime/(24*3600*30);
	$leftMonth = floor($lefttime/(24*3600*30));
	return $leftMonth;
}

//后台管理员登陆日志
function alogs($type,$tid,$tstatus,$deal_info='',$deal_user='' ,$db = null){
    if( !isset($db) ) $db = new Model();
	$arr = array();
	$arr['type'] = $type;
	$arr['tid'] = $tid;
	$arr['tstatus'] = $tstatus;
	$arr['deal_info'] = $deal_info;

	$arr['deal_user'] = ($deal_user)?$deal_user:session('adminname');
	$arr['deal_ip'] = get_client_ip();
	$arr['deal_time'] = time();
	//dump($arr);exit;
    $newid = $db->table(C('DB_PREFIX').'auser_dologs')->add($arr);
	return $newid;
}
function getMarketUrl($id){
	return __APP__."/Market/{$id}".C('URL_HTML_SUFFIX');
}
function cnsubstr2($str, $length, $start=0, $charset="utf-8", $suffix=true)
{
	   $str = strip_tags($str);
	   if(function_exists("mb_substr"))
	   {
			   if(mb_strlen($str, $charset) <= $length) return $str;
			   $slice = mb_substr($str, $start, $length, $charset);
	   }
	   else
	   {
			   $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			   $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			   $re['gbk']          = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			   $re['big5']          = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			   preg_match_all($re[$charset], $str, $match);
			   if(count($match[0]) <= $length) return $str;
			   $slice = join("",array_slice($match[0], $start, $length));
	   }
	   if($suffix) return $slice;
	   return $slice;
}

function CompoundMonth($data = array()){
  
  //借款的月数
  if (isset($data['month_times']) && $data['month_times']>0){
	  $month_times = $data['month_times'];
  }

  //借款的总金额
  if (isset($data['account']) && $data['account']>0){
	  $account = $data['account'];
  }else{
	  return "";
  }
  
  //借款的年利率
  if (isset($data['year_apr']) && $data['year_apr']>0){
	  $year_apr = $data['year_apr'];
  }else{
	  return "";
  }
  
  //借款的时间
  if (isset($data['borrow_time']) && $data['borrow_time']>0){
	  $borrow_time = $data['borrow_time'];
  }else{
	  $borrow_time = time();
  }
  
  //月利率
  $month_apr = $year_apr/(12*100);
  $mpow = pow((1 + $month_apr),$month_times);
  $repayment_account = getFloatValue($account*$mpow,4);//利息等于应还金额*月利率*借款月数

  if (isset($data['type']) && $data['type']=="all"){
	  $_resul['repayment_account'] = $repayment_account;
	  $_resul['month_apr'] = round($month_apr*100,4);
	  $_resul['interest'] = $repayment_account - $account;
	  $_resul['capital'] = $account;
	  $_resul['shouyi'] = round($_resul['interest']/$account*100,2);
	  return $_resul;
  }
}

//借款计算器用
function CompoundMonths($data = array(), $durationMonth = true){

    //借款的月数
    if (isset($data['month_times']) && $data['month_times']>0){
        $month_times = $data['month_times'];
    }

    //借款的总金额
    if (isset($data['account']) && $data['account']>0){
        $account = $data['account'];
    }else{
        return "";
    }

    //借款的年利率
    if (isset($data['year_apr']) && $data['year_apr']>0){
        $year_apr = $data['year_apr'];
    }else{
        return "";
    }

    //借款的时间
    if (isset($data['borrow_time']) && $data['borrow_time']>0){
        $borrow_time = $data['borrow_time'];
    }else{
        $borrow_time = strtotime(date('Y-m-d', time()));
    }
    if( $durationMonth == true ) {
        $diff_days =  getDaysByMonth($month_times, $borrow_time); // 将自然月转换成天
    } else {
        $diff_days = $month_times;
    }

    //月利率
    $month_apr = $year_apr/(12*100);
    $mpow = pow((1 + $month_apr),$month_times);
    $repayment_account = getFloatValue($account*$mpow,4);//利息等于应还金额*月利率*借款月数

    if (isset($data['type']) && $data['type']=="all"){
        $_resul['repayment_account'] = $repayment_account;
        $_resul['month_apr'] = round($month_apr*100,4);
        $_resul['interest'] = $repayment_account - $account;
        $_resul['capital'] = $account;
        $_resul['shouyi'] = round($_resul['interest']/$account*100,2);
        $_resul['repayment_time'] = strtotime("+{$diff_days} days", $borrow_time); // 还款时间
        return $_resul;
    }
}

/**
 * 复利计算通用公式，可按年、半年、季、月或日等
 * @param float $p 现值
 * @param float  $i 利率或折现率,如果是天，则为天利率，是年则为年利率，是月则为月利率...
 * @param int $n 计息期数
 * 利息复投计算公式 = 现值 * （1+利率）^计息期数
 * return 本金+利息
 */
function compound($p, $i, $n)
{
    $f = false;
    if( $p > 0 && $i>0 && $n>0 ) {
        $f = bcmul($p , pow((1+$i), $n), 4);
    }
    return $f;
}

function genRandChars($length)
{
    $ret = '';
    $src = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $max = strlen($src) - 1;

    for ($i = 0; $i < $length; $i++) {
        $ret .= $src[rand(0, $max)];
    }

    return $ret;
}

/**
 * 返回一字符串，十进制 number 以 radix 进制的表示。
 * @param dec 需要转换的数字
 * @param toRadix 输出进制。当不在转换范围内时，此参数会被设定为 2，以便及时发现
 * @return 指定输出进制的数字
 */
function dec2Any($dec, $toRadix)
{
    $MIN_RADIX = 2;
    $MAX_RADIX = 62;
    if ($toRadix == 35)
        $num = '123456789abcdefghijklmnopqrstuvwxyz';
    else
        $num = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ($toRadix < $MIN_RADIX || $toRadix > $MAX_RADIX) {
        $toRadix = 2;
    }
    if ($toRadix == 10) {
        return $dec;
    }
    // -Long.MIN_VALUE 转换为 2 进制时长度为65
    $buf = array();
    $charPos = 64;
    $isNegative = $dec < 0; //(bccomp($dec, 0) < 0);
    if (!$isNegative) {
        $dec = -$dec; // bcsub(0, $dec);
    }
    while (bccomp($dec, -$toRadix) <= 0) {
        $buf[$charPos--] = $num[-bcmod($dec, $toRadix)];
        $dec = bcdiv($dec, $toRadix);
    }
    $buf[$charPos] = $num[-$dec];
    if ($isNegative) {
        $buf[--$charPos] = '-';
    }
    $_any = '';
    for ($i = $charPos; $i < 65; $i++) {
        $_any .= $buf[$i];
    }
    return $_any;
}

/**
 * 返回一字符串，包含 number 以 10 进制的表示
 * fromBase 只能在 2 和 62 之间（包括 2 和 62）
 * @param number 输入数字
 * @param fromRadix 输入进制
 * @return 十进制数字
 */
function any2Dec($number, $fromRadix)
{
    if ($fromRadix == 35)
        $num = '123456789abcdefghijklmnopqrstuvwxyz';
    else
        $num = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $dec = 0;
    $digitValue = 0;
    $len = strlen($number) - 1;
    for ($t = 0; $t <= $len; $t++) {
        $digitValue = strpos($num, $number[$t]);
        $dec = bcadd(bcmul($dec, $fromRadix), $digitValue);
    }
	$dec = bcadd($dec, 0, 0);
    return $dec;
}

/**
 * 把一个数组的指定字段组合成一维数组
 */
function only_array($array, $field, $quotes = '')
{
    $arr = array();
    if( is_array($array) ) {
        foreach ($array as $v) {
            if ($v[$field] != null && $v[$field] != '') {
                $arr[] = $quotes . $v[$field] . $quotes;
            }
        }
    }

    return $arr;
}

//****** 一锤定音，一马当先，一鸣惊人奖励*********************************//
function special_award($borrow_id) {
    if( $borrow_id > 0 ) {
        $ymdx = M('borrow_investor')->field("id,investor_uid, investor_capital ")->where("borrow_id=".$borrow_id)->order("add_time asc,investor_capital desc")->find(); // 一马当先
        addCoupon($ymdx['investor_uid'], 5, $borrow_id."号标，一马当先获得奖励", 0, $borrow_id, $ymdx['id']);
        $ymjr = M('borrow_investor')->field("id,investor_uid, investor_capital ")->where("borrow_id=".$borrow_id)->order("investor_capital desc,add_time asc")->find(); // 一鸣惊人
        addCoupon($ymjr['investor_uid'], 7, $borrow_id."号标，一鸣惊人获得奖励", 0, $borrow_id, $ymjr['id']);
        $ycdy = M('borrow_investor')->field("id,investor_uid, investor_capital ")->where("borrow_id=".$borrow_id)->order("add_time desc,investor_capital desc")->find(); // 一锤定音
        addCoupon($ycdy['investor_uid'], 6, $borrow_id."号标，一锤定音获得奖励", 0, $borrow_id, $ycdy['id']);
    }
}

/**
 * 回款续投奖励 2015-04-24 奖励积分 //如果是秒标(borrow_type==3)，则没有续投奖励这一说
 * @param $borrow_id  借款标标号
 * @param $uid  投资人uid
 * @param $back_money    投资人回款资金池的回款金额
 * @param $invest_money   投资人此标投资的金额
 */
function return_reward($borrow_id, $uid, $back_money, $invest_money, $duration_unit, $borrow_duration, $db=null)
{
    $ret = false;
    $reward_rate = get_global_setting('today_reward');

    $vd['add_time'] = array("lt",time());
    $vd['investor_uid'] = $uid;
    $borrow_invest_count = M("borrow_investor")->lock(true)->where($vd)->count('id');//检测是否投过标且大于一次
    if($back_money>0 && $borrow_invest_count>0){//首次投标不给续投奖励
        if($invest_money>$back_money){//如果投标金额大于回款资金池金额，有效续投奖励以回款金额资金池总额为标准，否则以投标金额为准
            $reward_money_s = $back_money;
        }else{
            $reward_money_s = $invest_money;
        }
        if($duration_unit==1){  //单位为月
            if($borrow_duration==1){
                $integ   =   $reward_money_s*$reward_rate[0]/100;
            }
            if($borrow_duration==2){
                $integ   =   $reward_money_s*$reward_rate[1]/100;
            }
            if($borrow_duration>=3){
                $integ   =   $reward_money_s*$reward_rate[2]/100;
            }
        }else{
            if($borrow_duration<=30){
                $integ   =   $reward_money_s*$reward_rate[0]/100;
            }
            if($borrow_duration>30 && $borrow_duration<=60){
                $integ   =   $reward_money_s*$reward_rate[1]/100;
            }
            if($borrow_duration>60){
                $integ   =   $reward_money_s*$reward_rate[2]/100;
            }
        }
        $ret = memberIntegralLog($uid,2,$integ,"续投有效金额({$reward_money_s})的奖励({$borrow_id})号标投资积分奖励", $db);
    }
    return $ret;
}

//******邀请奖励  第一次投资成功赠送邀请奖励  statar 张继立**********
/**
 * 业务逻辑：如果是被人推荐的，并且是第一次投标，那么给推荐人发送优惠券
 * i>最新需求为不管流不流标都给，所以判断的时修改只要borrow_investor查询的数据为空，都算第一次（暂没有删除标记）
 * ii>如果改成复审通过才送优惠券，此代码无需更改
 * @param array $investor_uids
 */
function invite_reward($investor_uids) {
    if( !empty($investor_uids) ) {
        if( !is_array($investor_uids) ) $investor_uids = array($investor_uids);
        // 推荐人uid
        $recommend_ids = M('members')->field('recommend_id, id')->where("id in(". implode(',', $investor_uids).") and recommend_id > 0")->select();
        if( !empty($recommend_ids) ) {
            $recommend_uids = only_array($recommend_ids, 'id');
            // 判断投资者是不是第一次投标
            $sql = "select count(*) as count,investor_uid from lzh_borrow_investor where investor_uid in ("
                . implode(',',$recommend_uids).")  group by investor_uid";
            $borrow_investor = M('borrow_investor')->query($sql);
            if( !empty($borrow_investor) ) {
                //如果当前的$recommend_ids里的id存在于$investor_exist数组里，则删掉其投资人
                foreach( $recommend_ids as $k=>$val ) {
                    foreach( $borrow_investor as $v ) {
                        if( $val['id'] == $v['investor_uid'] ) {
                            if( $v['count'] == 1 ) {
                                addCoupon($val['recommend_id'], 4, "邀请会员奖励", $val['id']); //TODO: 可批量写入
                            }
                            break;
                        }
                    }
                }
            }


        }
    }

}

/**
 * 过滤数据，默认将数据里面的值intval,过滤参数可选
 * @param $arr
 */
function filter_array($arr, $filter = 'intval') {
    $ret = false;
    if( !empty($arr) ) {
        if(!is_array($arr)) $arr = array($arr);
        for($i=0; $i<count($arr); $i++ ) {
            $ret[] = call_user_func($filter, $arr[$i]);
        }
    }
    return $ret;
}

/**
 * 剔除数组的value为null的key
 *
 * @param array $arr_para
 * @return array
 */
function filter_null(array $arr_para)
{
    foreach ($arr_para as $k => &$v) {
        if (is_null($v)) {
            unset($arr_para[$k]);
        }
    }
    return $arr_para;
}

/**
 * 剔除数组的value为空字符串|0|'0' 等的key
 *
 * @param array $arr_para
 * @return array
 */
function filter_empty(array $arr_para)
{
    foreach ($arr_para as $k => &$v) {
        if ('' === $v) {
            unset($arr_para[$k]);
        }
    }
    return $arr_para;
}


/**
 * 私钥加密数据
 * @param string $str 需要加密的数据
 * @return bool|string
 */
function get_rsa_private_key($str){
    $ret = false;
    if( !empty($str) ) {
        $encrypted = '';
        $pi_key = get_pi_key();
        if( $pi_key !== false ) {
            openssl_private_encrypt($str,$encrypted,$pi_key);//私钥加密
            $ret = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url
        }
    }
    return $ret;
}

/**
 * 公钥加密数据
 * @param $str
 * @return bool|string
 */
function get_rsa_public_key($str){
    $ret = false;
    if( !empty($str) ) {
        $encrypted = '';
        $pu_key = get_pu_key();
        if( $pu_key !== false ) {
            openssl_public_encrypt($str,$encrypted,$pu_key);//私钥加密
            $ret = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url
        }
    }
    return $ret;
}

/**
 * 私钥解密公钥
 * @param string $encrypted 需要解密的数据
 * @return bool
 */
function get_decrypt_public_key($encrypted) {
    $pi_key = get_pi_key();
    openssl_private_decrypt(base64_decode($encrypted),$decrypted,$pi_key);//私钥解密
    return $decrypted;
}

/**
 * 公钥解密私钥
 * @param $encrypted
 */
function get_decrypt_private_key($encrypted){
    $pu_key = get_pu_key();
    openssl_public_decrypt(base64_decode($encrypted),$decrypted,$pu_key);//私钥加密的内容通过公钥可用解密出来
    return $decrypted;
}

// 获得公钥
function get_pu_key() {
    $public_key = S('rsa_public_key');
    if( empty($public_key) ) {
        $path = C("WEB_ROOT"). "Webconfig/rsa_public_key.pem";
        $public_key = file_get_contents($path);
        if( !empty($public_key) ) {
            S('rsa_public_key', $public_key);
        }
    }
    $pu_key =  openssl_pkey_get_public($public_key);
    return $pu_key;
}

function get_pi_key() {
    $private_key = S('rsa_private_key');
    if( empty($private_key) ) {
        $path = C("WEB_ROOT"). "Webconfig/rsa_private_key.pem";
        $private_key = file_get_contents($path);
        if( !empty($private_key) ) {
            S('rsa_private_key', $private_key);
        }
    }
    $pi_key =  openssl_pkey_get_private($private_key);
    return $pi_key;
}

function is_linux()
{
    return PATH_SEPARATOR == ':';
}

/**
 * 判断php宿主环境是否是64bit
 *
 * ps: 在64bit下，php有诸多行为与32bit不一致，诸如mod、integer、json_encode/decode等，具体请自行google。
 *
 * @return bool
 */
function is_64bit()
{
    return (int)0xFFFFFFFF !== -1;
}

//util
function getMillisecond()
{
    list($sec, $msec) = explode(' ', microtime());
    $ret = (float)sprintf('%.0f', (floatval($sec) + floatval($msec)) * 1000);
    return is_64bit() ? $ret : (string)$ret;
}