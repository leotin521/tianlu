<?php
// х╚╬жиХжц
class ShowimgAction extends ACommonAction{
	public function index(){
		session_start();
		header('Content-Type: image/jpeg');
		$file=urldecode($_REQUEST['xp']);
		$content = file_get_contents($file);
		echo base64_decode($content);
	}
}