




<?

$http='http://211.88.7.30/PGServer/Trans/WebClientAction.do'

?>

<form action="<?echo "$http"; ?>" name="returnfunc" id= "returnfunc" method="POST">
		<? 
		
		$version = $_POST["version"];          
		$charset = $_POST["charset"];          
		$language = $_POST["language"];         
		$signType = $_POST["signType"];         
		$tranCode = $_POST["tranCode"];         
		$merchantID = $_POST["merchantID"];       
		$merOrderNum = $_POST["merOrderNum"];      
		$tranAmt = $_POST["tranAmt"];          
		$feeAmt = $_POST["feeAmt"];           
		$currencyType = $_POST["currencyType"];     
		$frontMerUrl = $_POST["frontMerUrl"];      
		$backgroundMerUrl = $_POST["backgroundMerUrl"]; 
		$tranDateTime = $_POST["tranDateTime"];     
		$virCardNoIn = $_POST["virCardNoIn"];      
		$tranIP = $_POST["tranIP"];           
		$isRepeatSubmit = $_POST["isRepeatSubmit"];   
		$goodsName = $_POST["goodsName"];        
		$goodsDetail = $_POST["goodsDetail"];      
		$buyerName = $_POST["buyerName"];        
		$buyerContact = $_POST["buyerContact"];     
		$merRemark1 = $_POST["merRemark1"];       
		$merRemark2 = $_POST["merRemark2"];      
		$bankCode = $_POST["bankCode"];         
		$userType = $_POST["userType"];         


$signValue='version=['.$version.']tranCode=['.$tranCode.']merchantID=['.$merchantID.']merOrderNum=['.$merOrderNum.']tranAmt=['.$tranAmt.']feeAmt=['.$feeAmt.']tranDateTime=['.$tranDateTime.']frontMerUrl=['.$frontMerUrl.']backgroundMerUrl=['.$backgroundMerUrl.']orderId=[]gopayOutOrderId=[]tranIP=['.$tranIP.']respCode=[]VerficationCode=[12345678]';
       

		$signValue = md5($signValue);
		?>
		
		<input type="hidden" id="version" name="version" value="<?echo "$version"; ?>" size="50"/>
		<input type="hidden" id="charset" name="charset" value="<?echo "$charset"; ?>"  size="50"/>
		<input type="hidden" id="language" name="language" value="<?echo "$language"; ?>"  size="50"/>
		<input type="hidden" id="signType" name="signType" value="<?echo "$signType"; ?>"  size="50"/>
		<input type="hidden" id="tranCode" name="tranCode" value="<?echo "$tranCode"; ?>"  size="50"/>
		<input type="hidden" id="merchantID" name="merchantID" value="<?echo "$merchantID"; ?>"  size="50"/>
		<input type="hidden" id="merOrderNum" name="merOrderNum" value="<?echo "$merOrderNum"; ?>"  size="50" />
		<input type="hidden" id="tranAmt" name="tranAmt" value="<?echo "$tranAmt"; ?>"  size="50"/>
		<input type="hidden" id="feeAmt" name="feeAmt" value="<?echo "$feeAmt"; ?>"  size="50"/>
		<input type="hidden" id="currencyType" name="currencyType" value="<?echo "$currencyType"; ?>"  size="50"/>
		<input type="hidden"  id="frontMerUrl" name="frontMerUrl" value="<?echo "$frontMerUrl"; ?>"  size="50"/>
		<input type="hidden"  id="backgroundMerUrl" name="backgroundMerUrl" value="<?echo "$backgroundMerUrl"; ?>"  size="50"/>
		<input type="hidden"  id="tranDateTime" name="tranDateTime" value="<?echo "$tranDateTime"; ?>"  size="50"/>
		<input type="hidden"  id="virCardNoIn" name="virCardNoIn" value="<?echo "$virCardNoIn"; ?>"  size="50"/>
		<input type="hidden"  id="tranIP" name="tranIP" value="<?echo "$tranIP"; ?>"  size="50"/>
		<input type="hidden"  id="isRepeatSubmit" name="isRepeatSubmit" value="<?echo "$isRepeatSubmit"; ?>"  size="50"/>
		<input type="hidden"  id="goodsName" name="goodsName" value="<?echo "$goodsName"; ?>"  size="50"/>
		<input type="hidden"  id="goodsDetail" name="goodsDetail" value="<?echo "$goodsDetail"; ?>"  size="50"/>
		<input type="hidden"  id="buyerName" name="buyerName" value="<?echo "$buyerName"; ?>"  size="50"/>
		<input type="hidden"  id="buyerContact" name="buyerContact" value="<?echo "$buyerContact"; ?>"  size="50"/>
		<input type="hidden"  id="merRemark1" name="merRemark1" value="<?echo "$merRemark1"; ?>"  size="50"/>
		<input type="hidden"  id="merRemark2" name="merRemark2" value="<?echo "$merRemark2"; ?>"  size="50"/>
		<input type="hidden"  id="signValue" name="signValue" value="<?echo "$signValue"; ?>"  size="50"/>
		<input type="hidden"  id="bankCode" name="bankCode" value="<?echo "$bankCode"; ?>"  size="50"/>
		<input type="hidden"  id="userType" name="userType" value="<?echo "$userType"; ?>"  size="50"/>
	
	<input type="submit" name="submit" id="submit" value="¿ªÊ¼²âÊÔ"/>
</form>





