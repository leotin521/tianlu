<?php


$v_oid   = trim($_POST['v_oid']);     //接收传递过来的订单号 
$v_mid   = "22734325"; //merchant number  此处填写商户号    
$v_url   = "http://www.***.com/Pay/paynotice?payid=chinabank"; //用“异步接收地址”就可以   

$key='dedaozhi';       //merchant MD5KEY   商户密钥                         

$billNo_md5=strtoupper(md5($v_oid.$key));

?>


<Form name=wq Action=https://pay3.chinabank.com.cn/receiveorder.jsp method=post>

<input type=hidden name="v_oid" value="<? echo $v_oid;?>">
<input type=hidden name="v_mid" value="<? echo $v_mid;?>">
<input type=hidden name="billNo_md5" value="<? echo $billNo_md5;?>">
<input type=hidden name="v_url" value="<? echo $v_url;?>">


</form>

<script language="javascript">

document.forms[0].submit();

</script>


