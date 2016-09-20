<?php
return array(
	//'配置项'=>'配置值'
    'HOME_CACHE_TIME'     =>'3600',//前台数据缓存时间，以秒为单位
	'URL_HTML_SUFFIX'	=>'.html',//文件后缀
	
	'HOME_PAGE_SIZE'=>10,//前台列表默认显示条数
	'HOME_SHOP_PAGE_SIZE'=>12,//前台积分商城列表默认显示条数
	'HOME_MAX_UPLOAD'=>2000000,//前台上传文件最大限制2M
	'HOME_UPLOAD_DIR'=>'UF/Uploads/',//前台上传目录
	'HOME_ALLOW_EXTS'=>array('jpg', 'gif', 'png', 'jpeg'),//允许上传的附件类型
	//'HTML_CACHE_ON'=>false,文章缩图图宽度
	//产品缩图图
	'PRODUCT_UPLOAD_H'=>'225,1000',//产品缩图图高度
	'PRODUCT_UPLOAD_W'=>'225,1000',//产品缩图图宽度
	
    'TOKEN_ON'=>false,  // 是否开启令牌验证
    'TOKEN_NAME'=>'__hash__',    // 令牌验证的表单隐藏字段名称
    'TOKEN_TYPE'=>'md5',  //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET'=>false,  //令牌验证出错后是否重置令牌 默认为true	
);
?>