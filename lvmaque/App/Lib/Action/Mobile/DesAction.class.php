<?php
// 本类由系统自动生成，仅供测试用途
class DesAction extends HCommonAction {


    public function index(){


        $id = $_GET['id'];
        
        $borrowinfo = TborrowModel::get_format_borrow_info($id, "b.*, bwd.*, bd.bianhao");
     

        $this->assign('list',$borrowinfo);

        $this->display();



    }


















}