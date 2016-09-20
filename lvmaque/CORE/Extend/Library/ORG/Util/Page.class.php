<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Page.class.php 2712 2012-02-06 10:12:49Z liu21st $

class Page {
    // 分页栏每页显示的页数
    public $rollPage = 8;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 默认列表每页显示行数
    public $listRows = 5;
    // 起始行数
    public $firstRow	;
    // 分页总页面数
    protected $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage    ;
    // 分页的栏的总页数
    protected $coolPages   ;
    // 分页显示定制
    //protected $config  =	array('header'=>'','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>'%header% %upPage% %first%  %prePage%  %linkPage%  %nextPage% %downPage% %end%');
    protected $config  =	array('header'=>'条','prev'=>'上一页','next'=>'下一页','first'=>'首页','last'=>'尾页','theme'=>'共%totalRow% %header% %totalPage% 页&nbsp;当前第&nbsp;%nowPage%&nbsp;页&nbsp;&nbsp; %first% %upPage% %downPage% %end% %form%');
    // 默认分页变量名
    protected $varPage;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     +----------------------------------------------------------
     */
    public function __construct($totalRows,$listRows='',$parameter='') {
        $this->totalRows = $totalRows;
        $this->parameter = $parameter;
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = !empty($_GET[$this->varPage])?intval($_GET[$this->varPage]):1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    /**
     +----------------------------------------------------------
     * 分页显示输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show() {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
		$idtagert = ($parse['fragment'])?"#".$parse['fragment']:"";
        if(isset($parse['query']) || isset($parse['fragment'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
			$querycount = count($params);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }else{
			$querycount = 0;
		}
		$pspan = ($querycount==0)?"":"&";
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='".$url.$pspan.$p."=$upRow{$idtagert}' class='prevnext delcolor'>".$this->config['prev']."</a>";
        }else{
            $upPage="<a href='javascript:void(0);' class='prevnext delcolor'>".$this->config['prev']."</a>";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a href='".$url.$pspan.$p."=$downRow{$idtagert}' class='prevnext delcolor'>".$this->config['next']."</a>";
        }else{
            $downPage="<a href='javascript:void(0);' class='prevnext delcolor'>".$this->config['next']."</a>";
        }
        // << < > >>
        if(0){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a href='".$url.$pspan.$p."=$preRow{$idtagert}'  class='prevnext delcolor'>上".$this->rollPage."页</a>";
            $theFirst = "<a href='".$url.$pspan.$p."=1' class='prevnext delcolor'>".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='".$url.$pspan.$p."=$nextRow{$idtagert}'  class='prevnext delcolor'>下".$this->rollPage."页</a>";
            $theEnd = "<a href='".$url.$pspan.$p."=$theEndRow{$idtagert}'  class='prevnext delcolor'>".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a href='".$url.$pspan.$p."=$page{$idtagert}'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }
        //update after
        if(GROUP_NAME == Admin || GROUP_NAME == Member || GROUP_NAME == Agility){
            if( GROUP_NAME == Admin || GROUP_NAME == Agility ) {
                $form = "转到&nbsp;<input type='text' name='pages' id='pages' value='".$this->nowPage."' class='page_pages' style='width:48px; display: inline-block;color: #949494; margin:0; padding:0; border:#ccc solid 1px; height:21px; line-height:22px;' />&nbsp;页&nbsp;&nbsp;<a href='#' class='prevnext delcolor' onclick='go()'>确定</a>
		<script type='text/javascript'>
          function go(){
               var pages = document.getElementById('pages').value;
               location.href = '".$url.$pspan.$p."='+pages;
          }
        </script>";
            }else {
                $form = "转到&nbsp;<input type='text' name='pages' id='pages' value='".$this->nowPage."' class='page_pages' style='width:48px; display: inline-block;color: #949494; margin:0; padding:0; border:#ccc solid 1px; height:21px; line-height:22px;' />&nbsp;页&nbsp;&nbsp;<a href='#' id='jump_point' total-page='".$this->totalPages."' class='prevnext delcolor' onclick='go()'>确定</a>
		<script type='text/javascript'>
          function go(){
               var pageHandle = $('input[name=pages]');
               var pages = pageHandle.val();
               if( isNaN(pages) ) pages = 1;
               var totalPage = $('#jump_point').attr('total-page');
               if(pages > totalPage) pages = totalPage;
               var headHref = pageHandle.parent().find('a:first').attr('href');
               var indexPre = headHref.lastIndexOf('=') + 1;
               var newUrl = headHref.substring(0,indexPre)+pages;
               $('#jump_point').attr('href', newUrl);
          }
        </script>";
            }
        }else{
            $form = "";
        }
        //update before
        /* 
		if(GROUP_NAME == Admin || GROUP_NAME == Member){
		    $form = "";
		}else{
		$form = "转到&nbsp;<input type='text' name='pages' id='pages' value='".$this->nowPage."' class='page_pages' style='width:48px; display: inline-block;color: #949494; margin:0; padding:0; border:#ccc solid 1px; height:21px; line-height:22px;' />&nbsp;页&nbsp;&nbsp;<a href='#' class='prevnext delcolor' onclick='go()'>确定</a>
		<script type='text/javascript'>
          function go(){
               var pages = document.getElementById('pages').value; 
               location.href = '".$url.$pspan.$p."='+pages;
}
</script>
";
		}
		*/
        $pageStr	 =	 str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%form%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$form),$this->config['theme']);
        return $pageStr;
    }
    public function ajax_show()
    {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
        $idtagert = ($parse['fragment'])?"#".$parse['fragment']:"";
        if(isset($parse['query']) || isset($parse['fragment'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $querycount = count($params);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }else{
            $querycount = 0;
        }
        $pspan = ($querycount==0)?"":"&";
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='javascript:void(0);' onclick=\"ajax_show($upRow{$idtagert})\" >".$this->config['prev']."</a>";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a javascript:void(0);' onclick=\"ajax_show($downRow{$idtagert})\"  style=\"display:none\">".$this->config['next']."</a>";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
           // $prePage = "<a href='javascript:void(0);' onclick=\"ajax_show($preRow{$idtagert})\"  >上".$this->rollPage."页</a>";
            $theFirst = "<a href='javascript:void(0);' onclick=\"ajax_show(1)\" >".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='javascript:void(0);' onclick=\"ajax_show($nextRow{$idtagert})\"  >下".$this->rollPage."页</a>";
            $theEnd = "<a href='javascript:void(0);' onclick=\"ajax_show($theEndRow{$idtagert})\"  >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
		for($i=1;$i<=$this->totalPages;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a href='javascript:void(0);' onclick=\"ajax_show($page{$idtagert})\" >&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<a  href='javascript:void(0);' onclick=\"ajax_show($page)\">&nbsp;".$page."&nbsp;</a>";
                }
            }
        }
        $theme = '共 %totalPage% 页 %upPage% %downPage% %first%  %prePage%  %linkPage%';
        $pageStr     =     str_replace(
            array('%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%'),
            array($this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage),$theme);
        return $pageStr;
    }

	 /**
     +----------------------------------------------------------
     * 分页显示输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show_comment() {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
		$idtagert = ($parse['fragment'])?"#".$parse['fragment']:"";
        if(isset($parse['query']) || isset($parse['fragment'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
			$querycount = count($params);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }else{
			$querycount = 0;
		}
		$pspan = ($querycount==0)?"":"&";
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='".$url.$pspan.$p."=$upRow{$idtagert}' class='prevnext delcolor'>".$this->config['prev']."</a>";
        }else{
            $upPage="<a href='javascript:void(0);' class='prevnext delcolor'>".$this->config['prev']."</a>";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a href='".$url.$pspan.$p."=$downRow{$idtagert}' class='prevnext delcolor'>".$this->config['next']."</a>";
        }else{
            $downPage="<a href='javascript:void(0);' class='prevnext delcolor'>".$this->config['next']."</a>";
        }
        // << < > >>
        if(0){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a href='".$url.$pspan.$p."=$preRow{$idtagert}'  class='prevnext delcolor'>上".$this->rollPage."页</a>";
            $theFirst = "<a href='".$url.$pspan.$p."=1' class='prevnext delcolor'>".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='".$url.$pspan.$p."=$nextRow{$idtagert}'  class='prevnext delcolor'>下".$this->rollPage."页</a>";
            $theEnd = "<a href='".$url.$pspan.$p."=$theEndRow{$idtagert}'  class='prevnext delcolor'>".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a href='".$url.$pspan.$p."=$page{$idtagert}'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }
		
        $pageStr	 =	 str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%form%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$form),$this->config['theme']);
        return $pageStr;
    }
    
    public function ajax_show2($type='')
    {
        //$this->nowPage  =  $this->nowPage > 0 ?$this->nowPage :1;
        $this->rollPage = 5;
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
        $idtagert = ($parse['fragment'])?"#".$parse['fragment']:"";
        if(isset($parse['query']) || isset($parse['fragment'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $querycount = count($params);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }else{
            $querycount = 0;
        }
        $pspan = ($querycount==0)?"":"&";

        $upPage='<a  class="prev" onclick="'.$type.'prev()">'.$this->config['prev'].'</a>';
        $downPage='<a  class="next" onclick="'.$type.'next()">'.$this->config['next'].'</a>';
      
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }

        $linkPage = "";

        if($this->nowPage-2 > 1){
            $i=$this->nowPage-2;
            $totalPages = $this->nowPage+2;
            if($totalPages > $this->totalPages){
                $i = $i-($totalPages-$this->totalPages);
                $i<1 && $i=1;
                $totalPages =  $this->totalPages;    
            }
        }else{
            $i=1;
            $totalPages = $this->rollPage > $this->totalPages? $this->totalPages: $this->rollPage;
        }
        
        

        for(;$i<=$totalPages;$i++){
            if($i!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= '<a onclick="'.$type.'page('.$i.')">'.$i."</a>";
                }else{
                    break;
                }
            }else{
            $linkPage .= '<span class="current p'.$type.'" >'.$i."</span>";
            }
        }
      
        $theme = '%upPage% %first%  %prePage%  %linkPage% %downPage% '; 
        $pageStr     =     str_replace(
            array('%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%form%'),
            array($this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$form),$theme);
        return $pageStr;
    }
    
}