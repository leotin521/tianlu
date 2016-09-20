<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$
class TagLibHtmlA extends TagLib{
    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1不闭合） alias 标签别名 level 嵌套层次
        'commonBtn'=>array('attr'=>'value,style,action,type','close'=>0),
        'input'=>array('attr'=>'id,style,value,tip','close'=>0),
        'radio'=>array('attr'=>'id,style,value,datakey,vt,tip,separator','close'=>0),
        'text'=>array('attr'=>'id,style,value,tip','close'=>0),
        'editor'=>array('attr'=>'id,style,w,h,type,value,type','close'=>0),
        'select'=>array('attr'=>'id,style,value,datakey,vt,tip,default','close'=>0),
        'grid'=>array('attr'=>'id,pk,style,action,actionlist,show,datasource','close'=>0),
        'list'=>array('attr'=>'id,pk,style,action,actionlist,show,datasource,checkbox','close'=>0),
        'checkbox'=>array('attr'=>'name,checkboxes,checked,separator','close'=>0),
        );
    /**
     +----------------------------------------------------------
     * commonBtn标签解析
     * 格式： <html:commonBtn type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _commonBtn($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'commonBtn');
        $value      = $tag['value'];                //文字
        $style      = $tag['style'];                //样式名
        $action     = $tag['action'];                //点击
        $type       = $tag['type'];                //按钮类型
		
		$parseStr="";
		
        if($type=="jsfun") {
			$parseStr = '<a onclick="'.$action.'" class="btn_a" href="javascript:void(0);">';
			if(!empty($style)) $parseStr .= '<span class="'.$style.'">'.$value.'</span>';
			else  $parseStr .= '<span>'.$value.'</span>';
            $parseStr .= '</a>';
        }else {
			$parseStr = '<a class="btn_a" href="'.$action.'">';
			if(!empty($style)) $parseStr .= '<span class="'.$style.'">'.$value.'</span>';
			else  $parseStr .= '<span>'.$value.'</span>';
            $parseStr .= '</a>';
        }

        return $parseStr;
    }
    /**
     +----------------------------------------------------------
     * input标签解析
     * 格式： <html:input type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _input($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'input');
        $id      	= $tag['id'];                //name 和 id
        $value      = $tag['value']?$tag['value']:'';  //文本框值
        $tip     	= $tag['tip'];                //span tip提示内容
        $style      = $tag['style'];                //附加样式 style="widht:100"
		
		$parseStr="";
		
        if($tip) {
			if($style) $style='style="'.$style.'"';
			$parseStr = '<input name="'.$id.'" id="'.$id.'" '.$style.' class="input" type="text" value="'.$value.'"><span id="tip_'.$id.'" class="tip">'.$tip.'</span>';
        }else {
			if($style) $style='style="'.$style.'"';
			$parseStr = '<input name="'.$id.'" id="'.$id.'" '.$style.' class="input" type="text" value="'.$value.'">';
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * radio标签解析
     * 格式： <html:radio type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _radio($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'radio');
        $id      	= $tag['id'];                //name 和 id
        $style      = $tag['style'];                //附加样式 style="widht:100"
        $tip      	= $tag['tip'];                //附加样式 style="widht:100"
        $value      = $tag['value']?$tag['value']:'';  //(key|value)|text,当前默认选中一维时key指键,value指值
        $datakey    = $tag['datakey'];                //要排列的内容以模板内赋值的名称传入,支持一维和二维
        $key     	= $tag['vt'];                //  valuekey|textkey,值键和文本健//二维数组时才需要
        $separator  = $tag['separator']?$tag['separator']:"&nbsp;&nbsp;&nbsp;&nbsp;";			//分隔符
		$data = $this->tpl->get($datakey);//以名称获取模板变量

		$valueto = explode("|",$value);
		$parseStr="";
		if($style) $style='style="'.$style.'"';
        if($key) {
			$i=0;
			$keyto = explode("|",$key);
			foreach($data as $k => $v){
				if(!$valueto[0] && $i==0) $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'s" checked="checked" />';
				elseif($valueto[1]&&$v[$valueto[0]]) $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'" checked="checked"/>';
				else $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'" />';
				$parseStr.='<label for="'.$id.'_'.$i.'">'.$v[$keyto[1]].'</label>'.$separator;
				$i++;
			}
        }else {
			$i=0;
			foreach($data as $k => $v){
				if(!$valueto[0] && $i==0) $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" checked="checked" />';
				elseif(($valueto[0]=='key'&&$valueto[1]==$k)||($valueto[0]=='value'&&$valueto[1]==$v)) $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" checked="checked"/>';
				else $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" />';
				$parseStr.='<label for="'.$id.'_'.$i.'">'.$v.'</label>'.$separator;
				$i++;
			}
        }
		if($tip) $parseStr.='<span id="tip_'.$id.'" class="tip">'.$tip.'</span>';
        return $parseStr;
    }
     /**
     +----------------------------------------------------------
     * text标签解析
     * 格式： <html:text type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _text($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'input');
        $id      	= $tag['id'];                //name 和 id
        $value      = $tag['value']?$tag['value']:'';  //文本框值
        $tip     	= $tag['tip'];                //span tip提示内容
        $style      = $tag['style'];                //附加样式 style="widht:100"
		
		$parseStr="";
		
        if($tip) {
			if($style) $style='style="'.$style.'"';
			$parseStr = '<textarea name="'.$id.'" id="'.$id.'" '.$style.' class="areabox">'.$value.'</textarea><span id="tip_'.$id.'" class="tip">'.$tip.'</span>';
        }else {
			if($style) $style='style="'.$style.'"';
			$parseStr = '<textarea name="'.$id.'" id="'.$id.'" '.$style.' class="areabox">'.$value.'</textarea>';
        }

        return $parseStr;
    }

   /**
     +----------------------------------------------------------
     * editor标签解析 插入可视化编辑器
     * 格式： <html:editor id="editor" name="remark" type="FCKeditor" style="" >{$vo.remark}</html:editor>
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _editor($attr,$content)
    {
        $tag        =	$this->parseXmlAttr($attr,'editor');
        $id			=	$tag['id'];
        $style   	=	$tag['style'];
        $value   	=	$tag['value']?$tag['value']:'';
        $type   	=	$tag['type'];
        $width		=	!empty($tag['w'])?$tag['w']: '100%';
        $height     =	!empty($tag['h'])?$tag['h'] :'320px';
        $type       =   $tag['type'] ;
        switch(strtoupper($type)) {
            case 'KISSY':
                $parseStr   =	'<!-- 编辑器调用开始 -->
				<textarea name="'.$id.'" id="'.$id.'" style="width:'.$width.';height:'.$height.';'.$style.'">'.$value.'</textarea>
				<script>
				$(document).ready(function(){
					loadEditor("'.$id.'");
				});
				</script>
				<!-- 编辑器调用结束 -->';
                break;
            default :
                $parseStr  =  '<textarea  name="'.$id.'" id="'.$id.'" style="width:'.$width.';height:'.$height.';'.$style.'" >'.$value.'</textarea>';
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * select标签解析
     * 格式： <html:select options="name" selected="value" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _select($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'radio');
        $id      	= $tag['id'];                //name 和 id
        $style      = $tag['style'];                //附加样式 style="widht:100"
        $tip      	= $tag['tip'];                //附加样式 style="widht:100"
        $value      = $tag['value']?$tag['value']:'';  //(key|value)|text,当前默认选中一维时'key'指键,'value'指值
        $default    = $tag['default']?$tag['default']:'--请选择--';  //(key|value)|text,当前默认选中一维时'key'指键,'value'指值
        $datakey    = $tag['datakey'];                //要排列的内容以模板内赋值的名称传入,支持一维和二维
        $vt     	= $tag['vt'];                //  valuekey|textkey,值键和文本健//二维数组时才需要
		$data = $this->tpl->get($datakey);//以名称获取模板变量
		
		$parseStr="";
 		$valueto = explode("|",$value);
        if($vt) {
			$keyto = explode("|",$vt);
			$parseStr .='<select name="'.$id.'" id="'.$id.'" class="c_select">';
			$parseStr .='<option value="">'.$default.'</option>';
			$parseStr .='<volist name="'.$datakey.'" id="v" key="k" >';
			foreach($data as $k => $v){
				if($valueto[0] && $v[$valueto[0]]==$valueto[1]) $parseStr .='<option value="'.$v[$keyto[0]].'" selected="selected">'.$v[$keyto[1]].'</option>';
				else $parseStr .='<option value="'.$v[$keyto[0]].'">'.$v[$keyto[1]].'</option>';
			}
        }else{
			$parseStr .='<select name="'.$id.'" id="'.$id.'" class="c_select">';
			$parseStr .='<option value="">'.$default.'</option>';
			foreach($data as $k => $v){
				if(($valueto[0]=='key'&&$valueto[1]==$k)||($valueto[0]=='value'&&$valueto[1]==$v)) $parseStr .='<option value="'.$k.'" selected="selected">'.$v.'</option>';
				else $parseStr .='<option value="'.$k.'">'.$v.'</option>';
			}
		}
		
        $parseStr   .= '</select>';
		if($tip) $parseStr.='<span id="tip_'.$id.'" class="tip">'.$tip.'</span>';
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * checkbox标签解析
     * 格式： <htmlA:checkbox type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _checkbox($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'checkbox');
        $id      	= $tag['id'];                //name 和 id
        $style      = $tag['style'];                //附加样式 style="widht:100"
        $tip      	= $tag['tip'];                //附加样式 style="widht:100"
        $value      = $tag['value']?$tag['value']:'';  //(key|value)|text,当前默认选中一维时key指键,value指值
        $datakey    = $tag['datakey'];                //要排列的内容以模板内赋值的名称传入,支持一维和二维
        $key     	= $tag['vt'];                //  valuekey|textkey,值键和文本健//二维数组时才需要
        $separator  = $tag['separator']?$tag['separator']:"&nbsp;&nbsp;&nbsp;&nbsp;";			//分隔符
		$data = $this->tpl->get($datakey);//以名称获取模板变量

		$valueto = explode("|",$value);
		$parseStr="";
		if($style) $style='style="'.$style.'"';
        if($key) {
			$i=0;
			$keyto = explode("|",$key);
			foreach($data as $k => $v){
				if(!$valueto[0] && $i==0) $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'s" checked="checked" />';
				elseif($valueto[1]&&$v[$valueto[0]]) $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'" checked="checked"/>';
				else $parseStr.='<input type="radio" name="'.$id.'" value="'.$v[$keyto[0]].'" id="'.$id.'_'.$i.'" />';
				$parseStr.='<label for="'.$id.'_'.$i.'">'.$v[$keyto[1]].'</label>'.$separator;
				$i++;
			}
        }else {
			$i=0;
			foreach($data as $k => $v){
				if(!$valueto[0] && $i==0) $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" checked="checked" />';
				elseif(($valueto[0]=='key'&&$valueto[1]==$k)||($valueto[0]=='value'&&$valueto[1]==$v)) $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" checked="checked"/>';
				else $parseStr.='<input type="radio" name="'.$id.'" value="'.$k.'" id="'.$id.'_'.$i.'" />';
				$parseStr.='<label for="'.$id.'_'.$i.'">'.$v.'</label>'.$separator;
				$i++;
			}
        }
		if($tip) $parseStr.='<span id="tip_'.$id.'" class="tip">'.$tip.'</span>';
        return $parseStr;
    }

}
?>