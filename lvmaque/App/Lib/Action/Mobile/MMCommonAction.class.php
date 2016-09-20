<?php
// 全局设置
class MMCommonAction extends Action
{
    var $glo=NULL;
    var $uid=0;
    //上传参数
    var $savePathNew=NULL;
    var $thumbMaxWidthNew="10,50";
    var $thumbMaxHeightNew="10,50";
    var $thumbNew=NULL;
    var $allowExtsNew=NULL;
    var $saveRule=NULL;
    //验证身份
    protected function _initialize(){
        $datag = get_global_setting();
        $this->glo = $datag;//供PHP里面使用
        $this->assign("glo",$datag);//公共参数
        $bconf = get_bconf_setting();
        $this->gloconf = $bconf;//供PHP里面使用
        $hetong = M('hetong')->field('name,dizhi,tel')->find();
        $this->assign("web",$hetong);
        if($this->notneedlogin === true){
            if(session("u_id")){
                $this->uid = session("u_id");
                $this->assign('UID',$this->uid);
                $unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
                $this->assign('unread',$unread);
                //if(!in_array(strtolower(ACTION_NAME),array("actlogout","mactlogout",'regsuccess','emailverify','verify'))) redirect(__APP__."/member/");
            }else{
                $loginconfig = FS("Webconfig/loginconfig");
                $de_val = $this->_authcode(cookie('UKey'),'DECODE',$loginconfig['cookie']['key']);
                if(substr(md5($loginconfig['cookie']['key'].$de_val),14,10) == cookie('Ukey2')){
                    $vo = M('members')->field("id,user_name")->find($de_val);
                    if(is_array($vo)){
                        foreach($vo as $key=>$v){
                            session("u_{$key}",$v);
                        }
                        $this->uid = session("u_id");
                        $this->assign('UID',$this->uid);
                        $unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
                        $this->assign('unread',$unread);
                        //if(!in_array(strtolower(ACTION_NAME),array("actlogout","mactlogout",'regsuccess','emailverify','verify'))) redirect(__APP__."/member/");
                    }else{
                        cookie("Ukey",NULL);
                        cookie("Ukey2",NULL);
                    }
                }
            }
        }elseif(session("u_user_name")){
            $this->uid = session("u_id");
            $unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
            $this->assign('unread',$unread);
            $this->assign('UID',$this->uid);
        }else{
            $loginconfig = FS("Webconfig/loginconfig");
            $de_val = $this->_authcode(cookie('UKey'),'DECODE',$loginconfig['cookie']['key']);
            if(substr(md5($loginconfig['cookie']['key'].$de_val),14,10) == cookie('Ukey2')){
				
                $vo = M('members')->field("id,user_name")->find($de_val);
                if(is_array($vo)){
                    foreach($vo as $key=>$v){
                        session("u_{$key}",$v);
                    }
                    $this->uid = session("u_id");
                    $this->assign('UID',$this->uid);
                    $unread=M("inner_msg")->where("uid={$this->uid} AND status=0")->count('id');
                    $this->assign('unread',$unread);
                }else{
                    cookie("Ukey",NULL);
                    cookie("Ukey2",NULL);
                }
            }else{
                //redirect(__APP__."/member/Mobilecommon/login/");
                $data['session_expired']=1;
				$data['is_jumpmsg'] = '您还未登陆,请点击确定登陆后操作!';
                ajaxmsg($data,1003);
                exit;
            }
        }
        $time = time()*1000;
        $this->assign('time',$time);
        if (method_exists($this, '_MyInit')) {
            $this->_MyInit();
        }
    }

    public function memberheaderuplad(){
        if($this->uid <> $_GET['uid'] || !$this->uid) exit;
        else redirect(__ROOT__."/Style/header/upload.php?uid={$this->uid}");
        exit;
    }
    //上传图片
    protected function CUpload(){
        if(!empty($_FILES)){
            return $this->_Upload();
        }
    }

    protected function _Upload(){
        import("ORG.Net.UploadFile");
        $upload = new UploadFile();

        $upload->thumb = true;
        $upload->saveRule = $this->saveRule;//图片命名规则
        $upload->thumbMaxWidth = $this->thumbMaxWidth;
        $upload->thumbMaxHeight = $this->thumbMaxHeight;
        $upload->maxSize  = C('MEMBER_MAX_UPLOAD') ;// 设置附件上传大小
        $upload->allowExts  = C('MEMBER_ALLOW_EXTS');// 设置附件上传类型
        $upload->savePath =  $this->savePathNew?$this->savePathNew:C('MEMBER_MAX_UPLOAD');// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            //$this->error($upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }

        return $info;
    }
    //上传图片END
    //会员登陆
    protected function _memberlogin($uid){
        $vo = M('members')->field("id,user_name,user_phone")->find($uid);
        if(is_array($vo)){
            foreach($vo as $key=>$v){
                session("u_{$key}",$v);
            }
            $up['uid'] = $vo['id'];
            $up['add_time'] = time();
            $up['ip'] = get_client_ip();
            M('member_login')->add($up);

            if(intval($_POST['Keep'])>0){
                $time = intval($_POST['Keep'])*3600*24;
                $loginconfig = FS("Webconfig/loginconfig");
                $cookie_key = substr(md5($loginconfig['cookie']['key'].$uid),14,10);
                $cookie_val = $this->_authcode($uid,'ENCODE',$loginconfig['cookie']['key']);
                cookie("UKey",$cookie_val,$time);
                cookie("Ukey2",$cookie_key,$time);
            }
        }
    }

    protected function _memberloginout(){
        $vo = array("id","user_name");
        foreach($vo as $v){
            session("u_{$v}",NULL);
        }
        cookie("Ukey",NULL);
        cookie("Ukey2",NULL);
        $this->assign("waitSecond",3);
    }

    protected function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;
        // 密匙
        $key = md5($key ? $key : "lzh_jiedai");
        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();

        // 产生密匙簿
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        // 核心加解密部分
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }


        if($operation == 'DECODE') {
            // substr($result, 0, 10) == 0 验证数据有效性
            // substr($result, 0, 10) - time() > 0 验证数据有效性
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
            // 验证数据有效性，请看未加密明文的格式
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }


}
?>