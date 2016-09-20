<?php
//解决火狐swfupload的session bug
if (isset($_POST[session_name()]) && empty($_SESSION)) {
    session_destroy();
    session_id($_POST[session_name()]);
    session_start();
}
class AdAction extends ACommonAction
{

	public function index()
	{
		$field = "id,content,start_time,end_time,add_time,title";
		$this->_list( M("ad"), $field, "", "id", "DESC" );
		$this->display();
	}

	public function edit()
	{
		$id = intval( $_GET['id'] );
		$vo =M( "ad" )->find( $id );
		$this->assign( "vo", $vo );
		if ( $vo['ad_type'] == 1 )
		{
			$this->display( "editimg" );
		}
		else
		{
			$this->display();
		}
	}

	public function _doAddFilter($m)
	{
	    $m->title = htmlspecialchars($m->title, ENT_QUOTES);
		if ( $_POST['remove_p'] == 0 )
		{
			$m->content = preg_replace( "/<p[^>]*>/i", "", $m->content );
			$m->content = preg_replace( "/<\\/p[^<]*>/i", "", $m->content );
		}
		$m->add_time = time( );
		//$m->start_time = strtotime( $m->start_time );
		//$m->end_time = strtotime( $m->end_time );
		return $m;
	}

	public function _doEditFilter($m)
	{
		$m->title = htmlspecialchars($m->title, ENT_QUOTES);
		if ( $m->ad_type == 0 )
		{
			if ( $_POST['remove_p'] == 0 )
			{
				$m->content = preg_replace( "/<p[^>]*>/i", "", $m->content );
				$m->content = preg_replace( "/<\\/p[^<]*>/i", "", $m->content );
			}
		}
		else
		{
			foreach ( $GLOBALS['_POST']['swfimglist'] as $key => $v )
			{
				$row[$key]['img'] = substr( $v, 1 );
				$row[$key]['info'] = $_POST['picinfo'][$key];
				$row[$key]['url'] = $_POST['urlinfo'][$key];
			}
			$m->content = serialize( $row );
		}
		//$m->start_time = strtotime( $m->start_time );
		//$m->end_time = strtotime( $m->end_time );
		return $m;
	}

	public function swfupload( )
	{
		if ( $_POST['picpath'] )
		{
			$imgpath = substr( $_POST['picpath'], 1 );
			if ( in_array( $imgpath, $_SESSION['imgfiles'] ) )
			{
				unlink( C( "WEB_ROOT" ).$imgpath );
				$thumb = get_thumb_pic( $imgpath );
				$res = unlink(C("WEB_ROOT").$thumb );
				if ( $res )
				{
					$this->success("删除成功", "", $_POST['oid'] );
				}
				else
				{
					$this->error( "删除失败", "", $_POST['oid'] );
				}
			}
			else
			{
				$this->error( "图片不存在", "", $_POST['oid'] );
			}
		}
		else
		{
			$this->savePathNew = C( "ADMIN_UPLOAD_DIR" )."Ad/";
			$this->thumbMaxWidth = "700";
			$this->thumbMaxHeight = "120";
			$this->saveRule = date( "YmdHis", time()).rand(0,1000);
			$info = $this->CUpload();
			$data['product_thumb'] = $info[0]['savepath'].$info[0]['savename'];
			if ( !isset( $_SESSION['count_file'] ) )
			{
				$_SESSION['count_file'] = 1;
			}
			else
			{
				++$_SESSION['count_file'];
			}
			$_SESSION['imgfiles'][$_SESSION['count_file']] = $data['product_thumb'];
			echo "{$_SESSION['count_file']}:".__ROOT__."/".$data['product_thumb'];
		}
	}

}

?>
