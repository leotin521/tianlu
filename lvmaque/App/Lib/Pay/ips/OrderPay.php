<?php
header("Content-type:text/html; charset=utf-8");

//商户交易日期
$pMerchantTransactionTime = date('YmdHis');

//商户订单号
$pMerchantOrderNum = $pMerchantTransactionTime . mt_rand(100000,999999);

//商户返回地址
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$url .= str_replace('localhost', '127.0.0.1', $_SERVER['HTTP_HOST']) . $_SERVER['SCRIPT_NAME'];
$url = str_replace(array('OrderPay', 'orderpay'), 'OrderReturn', $url);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-Type" content="text/html; charset=utf-8" />
    <title>国际信用卡商户订单支付接口(新接口)</title>
    <style type="text/css">
      <!--
      TD {FONT-SIZE: 9pt}
      SELECT {FONT-SIZE: 9pt}
      OPTION {COLOR: #5040aa; FONT-SIZE: 9pt}
      INPUT {FONT-SIZE: 9pt}
      -->
    </style>
  </head>
  <body>
  	<form action="redirect.php" method="post" name="frm">
		<table width="420" border="1" cellspacing="0" cellpadding="3" bordercolordark="#FFFFFF" bordercolorlight="#333333" bgcolor="#F0F0FF" align="center">
					<tr bgcolor="#8070FF">
						<td colspan="2" align="center">
							<font color="#FFFF00"><b>商户模拟测试(模拟交易接口)</b></font>
						</td>
					</tr>
					<tr>
					  <td style="width: 140px">提交地址</td>
					  <td style="width: 260px">
						<select name="test">
							<option value="1" selected="selected">测试环境</option>
							<option value="0">正式环境</option>
						</select>
					  </td>
					</tr>				
					<tr>
						<td>商户号</td>
						<td>
							<input name="pMerchantCode" type="text" value="222378" /><!--测试商户号-->
						</td>
					</tr>
					<tr>
					  <td>商户证书</td>
					  <td>
						<input type="text" name="pMerchantKey" size="40" value="00518847228994856151214381286034373160268923638865209509623755128452179689329064232083487454640280528679651027955842303507571503" /><!--测试商户号-->
					  </td>
					</tr>
					<tr>
						<td>订单号</td>
						<td>
							<input name="pMerchantOrderNum" type="text" size="23" value="<?php echo $pMerchantOrderNum; ?>" />
						</td>
					</tr>
					<tr>
						<td>币种
						</td>
						<td>
							<select name="pOrderCurrency">
								<option selected="selected" value="RMB">人民币</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>订单金额</td>
						<td>
							<input name="pOrderAmount" size="5" type="text" value="0.68" /><font style="color: red;"> *其他币种须根据汇率转为人民币金额</font>
						</td>
					</tr>
					<tr>
						<td>订单日期</td>
						<td>
							<input name="pMerchantTransactionTime" type="text" value="<?php echo $pMerchantTransactionTime; ?>" />
						</td>
					</tr>
					<tr>
						<td>语言</td>
						<td>
							<select name="pLanguage">
							<option value="GB">简体中文</option>
							<option value="EN" selected="selected">英文</option>
							<option value="FR">法语</option>
							<option value="JP">日文</option>
							<option value="BIG5">繁体中文</option>
							<option value="">value为空</option>
						</select>
						</td>
					</tr>
					<tr>
						<td>商户返回地址</td>
						<td>
							<input name="pSuccessReturnUrl" type="text" value="<?php echo $url; ?>" />
						</td>
					</tr>				
					<tr>
						<td>交易返回加密方式</td>
						<td>
							<select name="pResHashArithmetic">
								<option selected="selected" value="12">md5摘要</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>是否提供Server返回方式</td>
						<td>
							<select name="pResType">
								<option value="0">无Server to Server</option>
								<option selected="selected" value="1">有Server to Server</option>
								<option value="">value为空</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>商户S2S地址</td>
						<td>
							<input name="pS2SReturnUrl" type="text" value="<?php echo $url; ?>" />
						</td>
					</tr>
					<tr>
						<td>显示金额</td>
						<td>
							<input name="pDisplayAmount" type="text" value="$0.10" />
						</td>
					</tr>
					<tr>
						<td>产品名称</td>
						<td>
							<input name="pProductName" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品描述</td>
						<td>
							<input name="pProductDescription" type="text" />
						</td>
					</tr>
					<tr>
						<td>备注</td>
						<td>
							<input name="pAttach" type="text" />
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center">反欺诈参数</td>
					</tr>
					<tr>
						<td>是否进行反欺诈验证</td>
						<td>
							<select name="pEnableFraudGuard">
								<option selected="selected" value="1">进行反欺诈验证</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>规则库ID</td>
						<td>
							<input name="pCheckRuleBaseID" type="text" value="1" />
						</td>
					</tr>
					<tr>
						<td>产品类型</td>
						<td>
							<input name="pProductType" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据1</td>
						<td>
							<input name="pProductData1" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据2</td>
						<td>
							<input name="pProductData2" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据3</td>
						<td>
							<input name="pProductData3" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据4</td>
						<td>
							<input name="pProductData4" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据5</td>
						<td>
							<input name="pProductData5" type="text" />
						</td>
					</tr>
					<tr>
						<td>产品数据6</td>
						<td>
							<input name="pProductData6" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址所在街道</td>
						<td>
							<input name="pBillStreet" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址所在城市</td>
						<td>
							<input name="pBillCity" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址所在省州</td>
						<td>
							<input name="pBillState" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址所在国家/地区</td>
						<td>
							<select name="pBillCountry" style="width: 215px">
								<option value="af">Afghanistan</option>
								<option value="al">Albania</option>
								<option value="dz">Algeria</option>
								<option value="as">American Samoa (US)</option>
								<option value="ad">Andorra</option>
								<option value="ao">Angola</option>
								<option value="ai">Anguilla (UK)</option>
								<option value="ag">Antigua and Barbuda</option>
								<option value="ar">Argentina</option>
								<option value="am">Armenia</option>
								<option value="aw">Aruba</option>
								<option value="au">Australia</option>
								<option value="at">Austria</option>
								<option value="az">Azerbaijan</option>
								<option value="bs">Bahamas</option>
								<option value="bh">Bahrain</option>
								<option value="bd">Bangladesh</option>
								<option value="bb">Barbados</option>
								<option value="by">Belarus</option>
								<option value="be">Belgium</option>
								<option value="bz">Belize</option>
								<option value="bj">Benin</option>
								<option value="bm">Bermuda (UK)</option>
								<option value="bt">Bhutan</option>
								<option value="bo">Bolivia</option>
								<option value="ba">Bosnia and Herzegovina</option>
								<option value="bw">Botswana</option>
								<option value="br">Brazil</option>
								<option value="vg">British Virgin Islands (UK)</option>
								<option value="bn">Brunei Darussalam</option>
								<option value="bg">Bulgaria</option>
								<option value="bf">Burkina Faso</option>
								<option value="mm">Myanmar</option>
								<option value="bi">Burundi</option>
								<option value="kh">Cambodia</option>
								<option value="cm">Cameroon</option>
								<option value="ca">Canada</option>
								<option value="cv">Cape Verde</option>
								<option value="ky">Cayman Islands (UK)</option>
								<option value="cf">Central African Republic</option>
								<option value="td">Chad</option>
								<option value="cl">Chile</option>
								<option value="cn">China</option>
								<option value="cx">Christmas Island (AU)</option>
								<option value="cc">Cocos (Keeling) Islands (AU)</option>
								<option value="co">Colombia</option>
								<option value="km">Comoros</option>
								<option value="cd">Congo, Democratic Republic of the</option>
								<option value="cg">Congo, Republic of the</option>
								<option value="ck">Cook Islands (NZ)</option>
								<option value="cr">Costa Rica</option>
								<option value="ci">Cote d'Ivoire</option>
								<option value="hr">Croatia</option>
								<option value="cu">Cuba</option>
								<option value="cy">Cyprus</option>
								<option value="cz">Czech Republic</option>
								<option value="dk">Denmark</option>
								<option value="dj">Djibouti</option>
								<option value="dm">Dominica</option>
								<option value="do">Dominican Republic</option>
								<option value="ec">Ecuador</option>
								<option value="eg">Egypt</option>
								<option value="sv">El Salvador</option>
								<option value="gq">Equatorial Guinea</option>
								<option value="er">Eritrea</option>
								<option value="ee">Estonia</option>
								<option value="et">Ethiopia</option>
								<option value="fk">Falkland Islands (UK)</option>
								<option value="fo">Faroe Islands (DK)</option>
								<option value="fj">Fiji</option>
								<option value="fi">Finland</option>
								<option value="fr">France</option>
								<option value="gf">French Guiana (FR)</option>
								<option value="pf">French Polynesia (FR)</option>
								<option value="ga">Gabon</option>
								<option value="gm">Gambia</option>
								<option value="ge">Georgia</option>
								<option value="de">Germany</option>
								<option value="gh">Ghana</option>
								<option value="gi">Gibraltar (UK)</option>
								<option value="gr">Greece</option>
								<option value="gl">Greenland (DK)</option>
								<option value="gd">Grenada</option>
								<option value="gp">Guadeloupe (FR)</option>
								<option value="gu">Guam (US)</option>
								<option value="gt">Guatemala</option>
								<option value="gg">Guernsey (UK)</option>
								<option value="gn">Guinea</option>
								<option value="gw">Guinea-Bissau</option>
								<option value="gy">Guyana</option>
								<option value="ht">Haiti</option>
								<option value="va">Holy See (Vatican City)</option>
								<option value="hn">Honduras</option>
								<option value="hk">Hong Kong (CN)</option>
								<option value="hu">Hungary</option>
								<option value="is">Iceland</option>
								<option value="in">India</option>
								<option value="id">Indonesia</option>
								<option value="ir">Iran</option>
								<option value="iq">Iraq</option>
								<option value="ie">Ireland</option>
								<option value="im">Isle of Man (UK)</option>
								<option value="il">Israel</option>
								<option value="it">Italy</option>
								<option value="jm">Jamaica</option>
								<option value="jp">Japan</option>
								<option value="je">Jersey (UK)</option>
								<option value="jo">Jordan</option>
								<option value="kz">Kazakstan</option>
								<option value="ke">Kenya</option>
								<option value="ki">Kiribati</option>
								<option value="kp">Korea, Democratic People's Republic (North)</option>
								<option value="kr">Korea, Republic of (South)</option>
								<option value="kw">Kuwait</option>
								<option value="kg">Kyrgyzstan</option>
								<option value="la">Laos</option>
								<option value="lv">Latvia</option>
								<option value="lb">Lebanon</option>
								<option value="ls">Lesotho</option>
								<option value="lr">Liberia</option>
								<option value="ly">Libya</option>
								<option value="li">Liechtenstein</option>
								<option value="lt">Lithuania</option>
								<option value="lu">Luxembourg</option>
								<option value="mo">Macau (CN)</option>
								<option value="mk">Macedonia</option>
								<option value="mg">Madagascar</option>
								<option value="mw">Malawi</option>
								<option value="my">Malaysia</option>
								<option value="mv">Maldives</option>
								<option value="ml">Mali</option>
								<option value="mt">Malta</option>
								<option value="mh">Marshall islands</option>
								<option value="mq">Martinique (FR)</option>
								<option value="mr">Mauritania</option>
								<option value="mu">Mauritius</option>
								<option value="yt">Mayotte (FR)</option>
								<option value="mx">Mexico</option>
								<option value="fm">Micronesia, Federated States of</option>
								<option value="md">Moldova</option>
								<option value="mc">Monaco</option>
								<option value="mn">Mongolia</option>
								<option value="me">Montenegro</option>
								<option value="ms">Montserrat (UK)</option>
								<option value="ma">Morocco</option>
								<option value="mz">Mozambique</option>
								<option value="na">Namibia</option>
								<option value="nr">Nauru</option>
								<option value="np">Nepal</option>
								<option value="nl">Netherlands</option>
								<option value="an">Netherlands Antilles (NL)</option>
								<option value="nc">New Caledonia (FR)</option>
								<option value="nz">New Zealand</option>
								<option value="ni">Nicaragua</option>
								<option value="ne">Niger</option>
								<option value="ng">Nigeria</option>
								<option value="nu">Niue</option>
								<option value="nf">Norfolk Island (AU)</option>
								<option value="mp">Northern Mariana Islands (US)</option>
								<option value="no">Norway</option>
								<option value="om">Oman</option>
								<option value="pk">Pakistan</option>
								<option value="pw">Palau</option>
								<option value="pa">Panama</option>
								<option value="pg">Papua New Guinea</option>
								<option value="py">Paraguay</option>
								<option value="pe">Peru</option>
								<option value="ph">Philippines</option>
								<option value="pn">Pitcairn Islands (UK)</option>

								<option value="pl">Poland</option>
								<option value="pt">Portugal</option>
								<option value="pr">Puerto Rico (US)</option>
								<option value="qa">Qatar</option>
								<option value="re">Reunion (FR)</option>
								<option value="ro">Romania</option>
								<option value="ru">Russia</option>
								<option value="rw">Rwanda</option>
								<option value="sh">Saint Helena (UK)</option>
								<option value="kn">Saint Kitts and Nevis</option>
								<option value="lc">Saint Lucia</option>
								<option value="pm">Saint Pierre and Miquelon (FR)</option>
								<option value="vc">Saint Vincent and the Grenadines</option>
								<option value="ws">Samoa</option>
								<option value="sm">San Marino</option>
								<option value="st">Sao Tome and Principe</option>
								<option value="sa">Saudi Arabia</option>
								<option value="sn">Senegal</option>
								<option value="rs">Serbia</option>
								<option value="cs">Serbia and Montenegro</option>
								<option value="sc">Seychelles</option>
								<option value="sl">Sierra Leone</option>
								<option value="sg">Singapore</option>
								<option value="sk">Slovakia</option>
								<option value="si">Slovenia</option>
								<option value="sb">Solomon Islands</option>
								<option value="so">Somalia</option>
								<option value="za">South Africa</option>
								<option value="gs">South Georgia &amp; South Sandwich Islands (UK)</option>
								<option value="es">Spain</option>
								<option value="lk">Sri Lanka</option>
								<option value="sd">Sudan</option>
								<option value="sr">Suriname</option>
								<option value="sz">Swaziland</option>
								<option value="se">Sweden</option>
								<option value="ch">Switzerland</option>
								<option value="sy">Syria</option>
								<option value="tw">Taiwan</option>
								<option value="tj">Tajikistan</option>
								<option value="tz">Tanzania</option>
								<option value="th">Thailand</option>
								<option value="tl">Timor-Leste</option>
								<option value="tg">Togo</option>
								<option value="tk">Tokelau</option>
								<option value="to">Tonga</option>
								<option value="tt">Trinidad and Tobago</option>
								<option value="tn">Tunisia</option>
								<option value="tr">Turkey</option>
								<option value="tm">Turkmenistan</option>
								<option value="tc">Turks and Caicos Islands (UK)</option>
								<option value="tv">Tuvalu</option>
								<option value="ug">Uganda</option>
								<option value="ua">Ukraine</option>
								<option value="ae">United Arab Emirates</option>
								<option value="gb">United Kingdom</option>
								<option value="us">United States</option>
								<option value="uy">Uruguay</option>
								<option value="uz">Uzbekistan</option>
								<option value="vu">Vanuatu</option>
								<option value="ve">Venezuela</option>
								<option value="vn">Vietnam</option>
								<option value="vi">Virgin Islands (US)</option>
								<option value="wf">Wallis and Futuna (FR)</option>
								<option value="eh">Western Sahara</option>
								<option value="ye">Yemen</option>
								<option value="zm">Zambia</option>
								<option value="zw">Zimbabwe</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>账单地址邮编</td>
						<td>
							<input name="pBillZIP" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址 FirstName</td>
						<td>
							<input name="pBillFName" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址 MiddleName</td>
						<td>
							<input name="pBillMName" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址 LastName</td>
						<td>
							<input name="pBillLName" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址Email</td>
						<td>
							<input name="pBillEmail" type="text" />
						</td>
					</tr>
					<tr>
						<td>账单地址Phone</td>
						<td>
							<input name="pBillPhone" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址所在街道</td>
						<td>
							<input name="pShipStreet" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址所在城市</td>
						<td>
							<input name="pShipCity" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址所在省州</td>
						<td>
							<input name="pShipState" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址所在国家/地区</td>
						<td>
							<select name="pShipCountry" style="width: 215px">
								<option value="af">Afghanistan</option>
								<option value="al">Albania</option>
								<option value="dz">Algeria</option>
								<option value="as">American Samoa (US)</option>
								<option value="ad">Andorra</option>
								<option value="ao">Angola</option>
								<option value="ai">Anguilla (UK)</option>
								<option value="ag">Antigua and Barbuda</option>
								<option value="ar">Argentina</option>
								<option value="am">Armenia</option>
								<option value="aw">Aruba</option>
								<option value="au">Australia</option>
								<option value="at">Austria</option>
								<option value="az">Azerbaijan</option>
								<option value="bs">Bahamas</option>
								<option value="bh">Bahrain</option>
								<option value="bd">Bangladesh</option>
								<option value="bb">Barbados</option>
								<option value="by">Belarus</option>
								<option value="be">Belgium</option>
								<option value="bz">Belize</option>
								<option value="bj">Benin</option>
								<option value="bm">Bermuda (UK)</option>
								<option value="bt">Bhutan</option>
								<option value="bo">Bolivia</option>
								<option value="ba">Bosnia and Herzegovina</option>
								<option value="bw">Botswana</option>
								<option value="br">Brazil</option>
								<option value="vg">British Virgin Islands (UK)</option>
								<option value="bn">Brunei Darussalam</option>
								<option value="bg">Bulgaria</option>
								<option value="bf">Burkina Faso</option>
								<option value="mm">Myanmar</option>
								<option value="bi">Burundi</option>
								<option value="kh">Cambodia</option>
								<option value="cm">Cameroon</option>
								<option value="ca">Canada</option>
								<option value="cv">Cape Verde</option>
								<option value="ky">Cayman Islands (UK)</option>
								<option value="cf">Central African Republic</option>
								<option value="td">Chad</option>
								<option value="cl">Chile</option>
								<option value="cn">China</option>
								<option value="cx">Christmas Island (AU)</option>
								<option value="cc">Cocos (Keeling) Islands (AU)</option>
								<option value="co">Colombia</option>
								<option value="km">Comoros</option>
								<option value="cd">Congo, Democratic Republic of the</option>
								<option value="cg">Congo, Republic of the</option>
								<option value="ck">Cook Islands (NZ)</option>
								<option value="cr">Costa Rica</option>
								<option value="ci">Cote d'Ivoire</option>
								<option value="hr">Croatia</option>
								<option value="cu">Cuba</option>
								<option value="cy">Cyprus</option>
								<option value="cz">Czech Republic</option>
								<option value="dk">Denmark</option>
								<option value="dj">Djibouti</option>
								<option value="dm">Dominica</option>
								<option value="do">Dominican Republic</option>
								<option value="ec">Ecuador</option>
								<option value="eg">Egypt</option>
								<option value="sv">El Salvador</option>
								<option value="gq">Equatorial Guinea</option>
								<option value="er">Eritrea</option>
								<option value="ee">Estonia</option>
								<option value="et">Ethiopia</option>
								<option value="fk">Falkland Islands (UK)</option>
								<option value="fo">Faroe Islands (DK)</option>
								<option value="fj">Fiji</option>
								<option value="fi">Finland</option>
								<option value="fr">France</option>
								<option value="gf">French Guiana (FR)</option>
								<option value="pf">French Polynesia (FR)</option>
								<option value="ga">Gabon</option>
								<option value="gm">Gambia</option>
								<option value="ge">Georgia</option>
								<option value="de">Germany</option>
								<option value="gh">Ghana</option>
								<option value="gi">Gibraltar (UK)</option>
								<option value="gr">Greece</option>
								<option value="gl">Greenland (DK)</option>
								<option value="gd">Grenada</option>
								<option value="gp">Guadeloupe (FR)</option>
								<option value="gu">Guam (US)</option>
								<option value="gt">Guatemala</option>
								<option value="gg">Guernsey (UK)</option>
								<option value="gn">Guinea</option>
								<option value="gw">Guinea-Bissau</option>
								<option value="gy">Guyana</option>
								<option value="ht">Haiti</option>
								<option value="va">Holy See (Vatican City)</option>
								<option value="hn">Honduras</option>
								<option value="hk">Hong Kong (CN)</option>
								<option value="hu">Hungary</option>
								<option value="is">Iceland</option>
								<option value="in">India</option>
								<option value="id">Indonesia</option>
								<option value="ir">Iran</option>
								<option value="iq">Iraq</option>
								<option value="ie">Ireland</option>
								<option value="im">Isle of Man (UK)</option>
								<option value="il">Israel</option>
								<option value="it">Italy</option>
								<option value="jm">Jamaica</option>
								<option value="jp">Japan</option>
								<option value="je">Jersey (UK)</option>
								<option value="jo">Jordan</option>
								<option value="kz">Kazakstan</option>
								<option value="ke">Kenya</option>
								<option value="ki">Kiribati</option>
								<option value="kp">Korea, Democratic People's Republic (North)</option>
								<option value="kr">Korea, Republic of (South)</option>
								<option value="kw">Kuwait</option>
								<option value="kg">Kyrgyzstan</option>
								<option value="la">Laos</option>
								<option value="lv">Latvia</option>
								<option value="lb">Lebanon</option>
								<option value="ls">Lesotho</option>
								<option value="lr">Liberia</option>
								<option value="ly">Libya</option>
								<option value="li">Liechtenstein</option>
								<option value="lt">Lithuania</option>
								<option value="lu">Luxembourg</option>
								<option value="mo">Macau (CN)</option>
								<option value="mk">Macedonia</option>
								<option value="mg">Madagascar</option>
								<option value="mw">Malawi</option>
								<option value="my">Malaysia</option>
								<option value="mv">Maldives</option>
								<option value="ml">Mali</option>
								<option value="mt">Malta</option>
								<option value="mh">Marshall islands</option>
								<option value="mq">Martinique (FR)</option>
								<option value="mr">Mauritania</option>
								<option value="mu">Mauritius</option>
								<option value="yt">Mayotte (FR)</option>
								<option value="mx">Mexico</option>
								<option value="fm">Micronesia, Federated States of</option>
								<option value="md">Moldova</option>
								<option value="mc">Monaco</option>
								<option value="mn">Mongolia</option>
								<option value="me">Montenegro</option>
								<option value="ms">Montserrat (UK)</option>
								<option value="ma">Morocco</option>
								<option value="mz">Mozambique</option>
								<option value="na">Namibia</option>
								<option value="nr">Nauru</option>
								<option value="np">Nepal</option>
								<option value="nl">Netherlands</option>
								<option value="an">Netherlands Antilles (NL)</option>
								<option value="nc">New Caledonia (FR)</option>
								<option value="nz">New Zealand</option>
								<option value="ni">Nicaragua</option>
								<option value="ne">Niger</option>
								<option value="ng">Nigeria</option>
								<option value="nu">Niue</option>
								<option value="nf">Norfolk Island (AU)</option>
								<option value="mp">Northern Mariana Islands (US)</option>
								<option value="no">Norway</option>
								<option value="om">Oman</option>
								<option value="pk">Pakistan</option>
								<option value="pw">Palau</option>
								<option value="pa">Panama</option>
								<option value="pg">Papua New Guinea</option>
								<option value="py">Paraguay</option>
								<option value="pe">Peru</option>
								<option value="ph">Philippines</option>
								<option value="pn">Pitcairn Islands (UK)</option>
								<option value="pl">Poland</option>
								<option value="pt">Portugal</option>
								<option value="pr">Puerto Rico (US)</option>
								<option value="qa">Qatar</option>
								<option value="re">Reunion (FR)</option>
								<option value="ro">Romania</option>
								<option value="ru">Russia</option>
								<option value="rw">Rwanda</option>
								<option value="sh">Saint Helena (UK)</option>
								<option value="kn">Saint Kitts and Nevis</option>
								<option value="lc">Saint Lucia</option>
								<option value="pm">Saint Pierre and Miquelon (FR)</option>
								<option value="vc">Saint Vincent and the Grenadines</option>
								<option value="ws">Samoa</option>
								<option value="sm">San Marino</option>
								<option value="st">Sao Tome and Principe</option>
								<option value="sa">Saudi Arabia</option>
								<option value="sn">Senegal</option>
								<option value="rs">Serbia</option>
								<option value="cs">Serbia and Montenegro</option>
								<option value="sc">Seychelles</option>
								<option value="sl">Sierra Leone</option>
								<option value="sg">Singapore</option>
								<option value="sk">Slovakia</option>
								<option value="si">Slovenia</option>
								<option value="sb">Solomon Islands</option>
								<option value="so">Somalia</option>
								<option value="za">South Africa</option>
								<option value="gs">South Georgia &amp; South Sandwich Islands (UK)</option>
								<option value="es">Spain</option>
								<option value="lk">Sri Lanka</option>
								<option value="sd">Sudan</option>
								<option value="sr">Suriname</option>
								<option value="sz">Swaziland</option>
								<option value="se">Sweden</option>
								<option value="ch">Switzerland</option>
								<option value="sy">Syria</option>
								<option value="tw">Taiwan</option>
								<option value="tj">Tajikistan</option>
								<option value="tz">Tanzania</option>
								<option value="th">Thailand</option>
								<option value="tl">Timor-Leste</option>
								<option value="tg">Togo</option>
								<option value="tk">Tokelau</option>
								<option value="to">Tonga</option>
								<option value="tt">Trinidad and Tobago</option>
								<option value="tn">Tunisia</option>
								<option value="tr">Turkey</option>
								<option value="tm">Turkmenistan</option>
								<option value="tc">Turks and Caicos Islands (UK)</option>
								<option value="tv">Tuvalu</option>
								<option value="ug">Uganda</option>
								<option value="ua">Ukraine</option>
								<option value="ae">United Arab Emirates</option>
								<option value="gb">United Kingdom</option>
								<option value="us">United States</option>
								<option value="uy">Uruguay</option>
								<option value="uz">Uzbekistan</option>
								<option value="vu">Vanuatu</option>
								<option value="ve">Venezuela</option>
								<option value="vn">Vietnam</option>
								<option value="vi">Virgin Islands (US)</option>
								<option value="wf">Wallis and Futuna (FR)</option>
								<option value="eh">Western Sahara</option>
								<option value="ye">Yemen</option>
								<option value="zm">Zambia</option>
								<option value="zw">Zimbabwe</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>送货地址邮编</td>
						<td>
							<input name="pShipZIP" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址 FirstName</td>
						<td>
							<input name="pShipFName" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址 MiddleName</td>
						<td>
							<input name="pShipMName" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址 LastName</td>
						<td>
							<input name="pShipLName" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址Email</td>
						<td>
							<input name="pShipEmail" type="text" />
						</td>
					</tr>
					<tr>
						<td>送货地址 Phone</td>
						<td>
							<input name="pShipPhone" type="text" />
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<input type="submit" value="提交"/>
							<input type="reset" value="重置"/>
						</td>
					</tr>
				</table>
			</form>
		</body>
	</html>