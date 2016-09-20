<?php
    class InvestadmAction extends MMCommonAction
{

    function __construct()
    {
        parent::__construct();
        $this->uid = session('u_id');
        D("AgilityBehavior");
        $this->AgilityBehavior = new AgilityBehavior();
        $this->Model = M('debt');
    }


    public function investadmlist(){
        $this->display();
    }

    public function ling_info(){

        if(!$this->uid){
            $this->error("请先登录后进行操作","__APP__/m/common/logins");
        }
        $interest = BaoInvestModel::get_sum_interest($this->uid);

        $assets = BaoInvestModel::get_sum_money($this->uid);

        $recently = M('bao_record')->where("uid={$this->uid}")->order('id desc')->getField('money');

        $data = $this->getMyItem($this->uid); // 投资中的项目

        //dump($data);
        if(empty($data)){
            $list = array();
            //$list['list'] = $data;
            $list['interest'] = ceil($interest);  // 总收益
            $list['assets'] = ceil($assets);      //资产总额
            $list['recently'] = $recently;  // 最近收益
        }else{
            $list = array();
            $list['list'] = $data;
            $list['interest'] = ceil($interest);  // 累计收益
            $list['assets'] = ceil($assets);      //资产总额
            $list['recently'] = $recently;  // 最近收益
        }

        $this->assign("info",$list);
        $this->assign("listinfo",$list['list']);
        $this->assign("page",$data['pages']);
        $this->display();
    }

    public function ling_info2(){
        if(!$this->uid){
            $this->error("请先登录后进行操作","__APP__/m/common/logins");
        }
        $interest = BaoInvestModel::get_sum_interest($this->uid);

        $assets = BaoInvestModel::get_sum_money($this->uid);

        $recently = M('bao_record')->where("uid={$this->uid}")->order('id desc')->getField('money');

        $data = $this->getMyItem($this->uid); // 投资中的项目
        //dump($data);
        if(empty($data)){
            $list = array();
            //$list['list'] = $data;
            $list['interest'] = ceil($interest);  // 总收益
            $list['assets'] = ceil($assets);      //资产总额
            $list['recently'] = $recently;  // 最近收益
        }else{
            $list = array();
            $list['list'] = $data;
            $list['interest'] = ceil($interest);  // 累计收益
            $list['assets'] = ceil($assets);      //资产总额
            $list['recently'] = $recently;  // 最近收益
        }

        $this->assign("info",$list);
        $condition =  " i.uid={$uid} and b.status=4 ";
        import("ORG.Util.Page");
        $count = M("bao as b")->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")->where($condition)->count('b.id');
        $p = new Page($count, 10);
        $show = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";

        $list =  M("bao as b")
            ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
            ->field("b.batch_no, i.add_time, b.interest_rate, i.deadline, i.interest")
            ->where($condition)
            ->order('i.add_time')
            ->limit($Lsql)
            ->select();
        foreach($list as $k=>$v){
            $e_time = M('bao_record')->where("batch_no='{$v['batch_no']}'")->order("e_time asc")->getField('e_time');
            $money = M('bao_log')->where("batch_no='{$v['batch_no']}' and type=1 and status=1")->sum('money');

            $_list[$k]['batch_no'] = $v['batch_no'];
            $_list[$k]['interest_rate'] = $v['interest_rate'];
            $_list[$k]['e_time'] = date("Y-m-d", $e_time);
            $_list[$k]['deadline'] = date("Y-m-d", $v['deadline']);
            $_list[$k]['money'] = $money;
            $_list[$k]['interest'] = $v['interest'];

        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = 0;
        }
        $this->assign("overlistinfo",$data['list']);
        $this->assign("page",$show);
        $this->display();
    }
        /**
         * 获取投资中，还款中的项目
         *
         * @param mixed $uid
         */
        private function getMyItem($uid)
        {

            $condition =  " i.uid={$uid} and b.status in (1,2)";
            $item_list = M("bao as b")
                ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
                ->field("b.batch_no, b.interest_rate, i.deadline, i.interest, i.money")
                ->where($condition)
                ->order('i.add_time')
                ->select();
            foreach($item_list as $key=>$val){
                $out_money = M('bao_log')->where("type=2 and status=1 and uid={$uid}  and batch_no='{$val['batch_no']}'")->sum('money');
                $capital = M('bao_log')->where("type=1 and status=1 and uid={$uid}  and batch_no='{$val['batch_no']}'")->sum('money');

                $item_list[$key]['out_money']   = number_format($out_money,2);        // 已赎本息
                $item_list[$key]['capital'] = floatval($capital);//当前本金
                $item_list[$key]['deadline'] = date('Y-m-d',$val['deadline']);
            }
            if(empty($item_list)){
                return '';
            }else{
                return $item_list;
            }

        }

        /**
         * 获取已结束项目，带分页
         *
         * @param mixed $uid
         */
//        public function ling_info_over()
//        {
//            $uid = intval($this->uid);
//
//            if($uid != $this->uid){
//                $this->error("请先登录后进行操作","__APP__/m/common/logins");
//            }
//            //$_GET['p'] = intval($page);
//            $condition =  " i.uid={$uid} and b.status=4 ";
//            import("ORG.Util.Page");
//            $count = M("bao as b")->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")->where($condition)->count('b.id');
//
//            $totalPage = ceil($count/$limit);
//            $p = new Page($count, $limit);
//            $Lsql = "{$p->firstRow},{$p->listRows}";
//
//            $list =  M("bao as b")
//                ->join(C('DB_PREFIX')."bao_invest as i ON b.batch_no=i.batch_no")
//                ->field("b.batch_no, i.add_time, b.interest_rate, i.deadline, i.interest")
//                ->where($condition)
//                ->order('i.add_time')
//                ->limit($Lsql)
//                ->select();
//
//            foreach($list as $k=>$v){
//                $e_time = M('bao_record')->where("batch_no='{$v['batch_no']}'")->order("e_time asc")->getField('e_time');
//                $money = M('bao_log')->where("batch_no='{$v['batch_no']}' and type=1 and status=1")->sum('money');
//
//                $_list[$k]['batch_no'] = $v['batch_no'];
//                $_list[$k]['interest_rate'] = $v['interest_rate'];
//                $_list[$k]['e_time'] = date("Y-m-d", $e_time);
//                $_list[$k]['deadline'] = date("Y-m-d", $v['deadline']);
//                $_list[$k]['money'] = $money;
//                $_list[$k]['interest'] = $v['interest'];
//
//            }
//
//            if(is_array($_list)){
//                $data['list'] = $_list;
//                $data['totalPage'] = $totalPage;
//                $data['nowPage'] =  $page;
//            }else{
//                $data = 0;
//            }
//            //dump($data['list']);
//            $this->assign("overlistinfo",$data['list']);
//            $this->display();
//        }

        //灵活宝回款列表详情
    public function iteminfo()
    {
//        $jsoncode = file_get_contents("php://input");
//        $arr = array();
//        $arr = json_decode($jsoncode,true);
//        $arr = AppCommonAction::get_decrypt_json($arr);
        $batch_no = text($_GET['batch']);
        $time = time();
        if(empty($batch_no)){
            $this->error('参数错误！');
        }
        $bao_invest = $this->getBaoInvest($batch_no, $this->uid);
        $bao_info = M('bao')->field(true)->where("batch_no='{$batch_no}'")->find();
        //$this->assign('bao_info', $bao_info);
        //$this->assign('bao_invest', $bao_invest);
        $data['interest_rate'] = $bao_info['interest_rate'];//年化收益
        $data['batch_no'] = $bao_info['batch_no'];//项目编号
        $data['money'] = number_format($bao_invest['money'],2);//在投金额
        $data['term'] = $bao_info['term']; //封存期限
        $data['start_funds'] = floatval($bao_info['start_funds']);//部分赎回后剩余金额不得小于
        $data['deadline'] = date('Y-m-d',$bao_invest['deadline']);


        $archive_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid}")->order("archive_time desc")->getField('archive_time');
        //$this->assign('archive_time', $archive_time);
        $data['archive_time'] = date('Y-m-d',$archive_time);//可随时一次性或部分赎回本息

        $add_time = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and status=1" )->order("add_time asc")->getField('add_time');
        //$this->assign('add_time', $add_time);
        $data['add_time'] = date('Y-m-d',$add_time);//投资日期

        if($archive_time>time()){
            $data['is_shuhui'] = 0;
        }else{
            $data['is_shuhui'] = 1;
        }
        $data['tip'] = $data['add_time'].'后，可随时一次性或部分赎回本息部分赎回后剩余金额不得小于'.$data['start_funds'].'元';

        $e_time = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and  status=1" )->order("e_time asc")->getField('e_time');

        //$this->assign('e_time', $e_time);
        $data['e_time'] = date('Y-m-d',$e_time);//起息日期
        $data['fenpei_style'] = '收益复投';



        //统计收益记录
        $record['count'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->count('id');
        $record['money'] = M('bao_record')->where("batch_no='{$batch_no}' and uid={$this->uid} and status=1")->sum('money');
        //已赚收益
        $record['incoming'] = BaoInvestModel::get_sum_interest($this->uid, $batch_no);

        //$this->assign('record', $record);
        $data['record_money'] = number_format($record['money'],2);//已赚收益


        $archive_money = M('bao_log')->where("batch_no='{$batch_no}' and uid={$this->uid} and type=1 and archive_time >= {$time} and status=1")->sum('money');// 封存本金

        $bao['money'] = bcsub($bao_invest['money'], $archive_money, 2);

        //$this->assign('bao', $bao);
        if($bao['money'] > 0){  //赎回状态
            $data['bao_status'] = 1;
        }else{
            $data['bao_status'] = 0;
        }
        //AppCommonAction::ajax_encrypt($data,1);
        //dump($data);
        $this->assign("vo",$data);
        $this->assign("bao",$bao);
        $this->display();
    }



    /**
     * 获取收益记录，带分页
     *
     * @param mixed $batch
     * @param mixed $uid
     */

    public function shouyijilu(){
        $batch = text($_GET['batch']);
        $this->assign("on_bc",$batch);
        $this->display();
    }
//    public function shouyijilus()
//    {
//        //dump($_GET['batch']);
//        $page = intval($arr['page'])? intval($arr['page']):1;
//        $limit = intval($arr['limit'])? intval($arr['limit']):10;
//        $uid = intval($this->uid);
//        $batch = $_GET['batch'];
//        //ajaxmsg($batch);
//        $_GET['p'] = intval($page);
//        $Page = D('Page');
//        $condition =  "batch_no='".$batch."' and uid={$uid}";
//        import("ORG.Util.Page");
//
//
//        $count = M("bao_record")->where($condition)->count('id');
//
//        $totalPage = ceil($count/$limit);
//
//        $p     = new Page($count,$limit);
//
//        $Lsql = "{$p->firstRow},{$p->listRows}";
//
//
//        $list = M('bao_record')
//            ->field(true)
//            ->where($condition)
//            ->order('e_time desc')
//            ->limit($Lsql)
//            ->select();
//
//
//        $string = '<li><tbody >';
//        foreach($list as $k=>$v){
//            $_list[$k]['e_time'] = $v['e_time'];
//            $_list[$k]['money'] = $v['money'];
//            $_list[$k]['funds'] = $v['funds'];
//            $_list[$k]['yifutou'] = '已复投';
//        $string .= '<tr class="yepageid"><td>'.date("Y-m-d", $v['e_time']).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'.$v['money'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'.$v['funds'].'&nbsp;&nbsp;&nbsp;</td><td>已复投</td></tr>';
//
//        }
//        $string.="</tbody></li>";
//        if(is_array($_list)){
//            $data['lists'] = $string;
//            $data['totalPage'] = $totalPage;
//            $data['nowPage'] =  $page;
//        }else{
//            $data['message'] = '暂无项目记录';
//        }
//        //dump($data['totalPage']);
//        //$this->assign("pages",$data['totalPage']);
//        ajaxmsg($data['nowPage']);
//
//    }

    public function shouyijilus()
    {
        //ajaxmsg($_GET['batch']);
        $batch = $_GET['batch'];
        $uid = intval($this->uid);
        $Page = D('Page');
        $condition =  "batch_no='".$batch."' and uid={$uid}";
        import("ORG.Util.Page");
        $count = M("bao_record")->where($condition)->count('id');
        $Page     = new Page($count,4);

        $show = $Page->ajax_show2('record');
        $this->assign('page', $show);

        $list = M('bao_record')
            ->field(true)
            ->where($condition)
            ->order('e_time desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        $string = '';
        foreach($list as $k=>$v){
            $string .= '<tr class="yepageid"><td>'.date("Y-m-d", $v['e_time']).'</td><td>'.$v['money'].'</td><td>'.$v['funds'].'</td><td>已复投</td></tr>';

        }

        echo json_encode(array($show, $string));

    }


    public function jiaoyijilv()
    {

        $status = array(0=>'审核中',1=>'成功',2=>'退回');
        $uid = intval($this->uid);
        import("ORG.Util.Page");
        $count = M("bao_log")->where($condition)->count('id');
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = M('bao_log')
            ->field(true)
            ->where($condition)
            ->order('add_time desc, auditors_time desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        foreach($list as $k=>$v){

            if($v['type']==2){
                $list[$k]['money'] = $v['money'];//影响金额
                $list[$k]['Redeem'] = 0;   // 赎回
            }else{
                $list[$k]['money'] = $v['money'];//影响金额
                $list[$k]['Redeem'] = 1;   //投资
            }

        }
        $this->assign('lists',$list);
        $this->assign('page',$show);
        $this->display();
    }

    public function linghuobaomoneylog(){
        $id = intval($_GET['id']);
        if(!id)$this->error("非法参数！");
        $list = M('bao_log')
            ->field("batch_no,money,type,add_time,remark")
            ->where("id = {$id}")
            ->find();
        //dump($list);
        $this->assign("list",$list);
        $this->display();
    }



    public function getLog2()
    {

        $status = array(0=>'审核中',1=>'成功',2=>'退回');
        $uid = intval($this->uid);
        $batch = $_GET['batch'];
        $type = intval($_GET['type']);
        $Page = D('Page');
        $condition =  " uid={$uid}";
        $type && $condition .= " and type=".$type;
        $batch && $condition .= " and batch_no='".$batch."'";

        import("ORG.Util.Page");
        $count = M("bao_log")->where($condition)->count('id');
        $Page     = new Page($count,10);


        $show = $Page->ajax_show2('log');
        $this->assign('page', $show);

        $list = M('bao_log')
            ->field(true)
            ->where($condition)
            ->order('add_time desc, auditors_time desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        $string = '';
        foreach($list as $k=>$v){
            $v['remark']=='' && $v['remark'] = '无';
            $string .= '<tr>
                            <td>'.date("Y-m-d", $v['add_time']).'</td>';
            if($v['type']==2){
                $string .= '<td >'.$v['money'].'元</td>';
            }else{
                $string .= '<td >+'.$v['money'].'</td><td >投资</td>';
            }


            $string .= '<td >&nbsp;&nbsp;&nbsp;&nbsp;'.$status[$v['status']].'</td><td >'.$v['remark'].'</td></tr>';

        }
        if($string==''){
            $string = '<tr><td colspan="5"> 无记录</td></tr>';
        }
        $arr =  array($show, $string);
        echo json_encode($arr);
    }

    public function redeemSave()
    {
        if(!$this->uid){
            echo json_encode(array("0","请登录后操作"));
            exit;
        }
        $batch = text($_POST['batch']);
        $out_money = floatval($_POST['fredeemamount']);
        $uid = $this->uid;
        $time =  time();

        $bao_info = M('bao')->field(true)->where("batch_no='{$batch}'")->find();
        $archive_money = M('bao_log')->where("batch_no='{$batch}' and uid={$uid} and type=1 and archive_time >= {$time}")->sum('money');// 封存本金
        $invest_money = M('bao_invest')->where("batch_no='{$batch}' and uid={$uid}")->getField("money");

        if(bcsub($invest_money, $archive_money, 2) < $out_money){
            echo json_encode(array("0","赎回金额大于可赎回金额"));
            exit;
        }
        if(bcsub($invest_money, $out_money, 2) && bcsub($invest_money, $out_money, 2) < $bao_info['start_funds']){
            echo json_encode(array("0","赎回后剩余金额不得小于最低投资金额"));
            exit;
        }

        D("AgilityBehavior");
        $AgilityBehavior = new AgilityBehavior();
        $out_money_res = $AgilityBehavior->outMoney($batch, $out_money, $uid); // 赎回资金

        if($out_money_res){
            echo json_encode(array("1","赎回提交成功，请查证账户！"));
        }else{
            echo json_encode(array("0","赎回提交失败，请重试！"));
        }

    }


        private function getBaoInvest($batch, $uid)
        {
            $bao_invest = M('bao_invest')->field(true)->where("batch_no='{$batch}' and uid={$uid} ")->find();
            return $bao_invest;
        }


    //投资管理首页

        public function tou_manage(){
            $designer = FS("Webconfig/designer");
            $version = FS("Webconfig/version");
            #-----------默认条件--------------------------
            $map['i.investor_uid'] = $this->uid;
            $map['b.borrow_status'] = array('in','6,8');   //默认还款中
            #-----------保障机构----------------------------------
            $danbao = M('article') -> field('id,title') -> where('type_id=7') -> select();
            $this -> assign("danbao_list", $danbao);
            #*-----------搜索条件-----------------
            $curl = $_SERVER['REQUEST_URI'];
            $urlarr = parse_url($curl);
            parse_str($urlarr['query'],$surl); //array获取当前链接参数
            //dump($surl);
            //$surl = array('t'=>$_GET['t'],'production'=>$_GET['production'],'k'=>$_GET['k']);
            $urlArr = array('production','t','k','guarantors','as','ae','es','ee');
            //dump($urlArr);
            foreach($urlArr as $v){
                $newpars = $surl;  //用新变量避免后面的连接受影响
                //dump($newpars);
                //dump($newpars[$v]);
                unset($newpars[$v],$newpars['type_list'],$newpars['order_sort'],$newpars['orderby']);   //去掉公共参数，对掉当前参数
                foreach($newpars as $skey=>$sv){
                    if($sv=="0") unset($newpars[$skey]); //去掉"全部"状态的参数,避免地址栏全满
                }
                $newurl = http_build_query($newpars);  //生成此值的链接,生成必须是即时生成
                //dump($newurl);
                $searchUrl[$v]['url'] = $newurl;
                $searchUrl[$v]['cur'] = text($_GET[$v]);
            }
            if (empty($searchUrl['k']['cur'])){
                $searchUrl['k']['cur'] = 4; //保证进入页面，还款状态为复审中
            }

            foreach($urlArr as $v){
                if($_GET[$v]){
                    switch($v){
                        case 'production':  //产品类型
                            $borrow_type = text($_GET[$v]);
                            $map["i.borrow_type"] = $borrow_type;
                            break;
                        case 'guarantors':  //保障机构
                            $guarantors = text($_GET[$v]);
                            $map['b.danbao'] = $guarantors;
                            break;
                        case 'as':  //投资时间
                            $as = strtotime($_GET[$v]);
                        case 'ae':
                            $ae = strtotime($_GET[$v]);
                            $map["i.add_time"] = array('between',array($as,$ae));
                            break;
                        case 'es':  //到期时间
                            $es = strtotime($_GET[$v]);
                        case 'ee':
                            $ee = strtotime($_GET[$v]);
                            $map["i.deadline"] = array('between',array($es,$ee));
                            break;
                        case 't':  //保障机构   //parent_invest_id，有值，认购债权。0，直投
                            $t = text($_GET[$v]);
                            if ($t=='1'){
                                $map['i.parent_invest_id'] = 0;
                            }elseif ($t=='2'){
                                $map['i.parent_invest_id'] = array('neq',0);
                            }
                            break;
                        case 'k':   //还款状态
                            $k = text($_GET[$v]);
                            if ($k=='4') {
                                $map['b.borrow_status'] = array('in','6,8');  //还款中
                            }elseif ($k=='1'){
                                $map['b.borrow_status'] = array('in','7,9,10');   //已完成
                            }elseif ($k=='14'){
                                $map['d.status'] = 4;   //已转让
                            }elseif ($k=='2'){
                                $map['b.borrow_status'] = array('in','0,2,4');   //竞标中
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            $list = getTTenderList($map, 10);
            $this->assign("list", $list['list']);
            $this->assign("pagebar", $list['page']);

            array_unshift($designer, "全部");
            if ($version['single']==0) unset($designer[1], $designer[2], $designer[3], $designer[4], $designer[5]);
            if ($version['business']==0) unset($designer[6]);
            if ($version['fund']==0) unset($designer[7]);

            $searchCnf['production'] = $designer;
            $searchCnf['t'] = array("全部","直接投资","认购债权");
            $searchCnf['k'] = array('2'=>'竞标中','4'=>"还款中",'1'=>"已完成",'14'=>"已转让");
            $this->assign("searchCnf",$searchCnf);
            $this->assign("searchUrl",$searchUrl);
            $this->assign("designer",$designer);
            $this->assign("page",$list['page']);
            $this->display();
        }

        function huan_info(){
            //还款详情
            //dump($_GET);
            $uid = $this->uid;
            $page = intval($arr['page'])? intval($arr['page']):1;
            $limit = intval($arr['limit'])? intval($arr['limit']):5;
            $pre = C("DB_PREFIX");
            $_GET['p'] = $page;
            //$uid = intval($this->uid);
            if($uid != $this->uid){
                $this->error('请登陆后查看!');
            }

            $map['d.investor_uid'] = $this->uid;
            #$map['d.status'] = 7;   //未还款
            $map['d.invest_id'] = intval($_GET['id']);
            $list = getTDTenderList($map,$limit);
            //echo M()->getLastSql();
            if (empty($list['have_pay'])){
                $list['have_pay'] = '0.00';
            }
            if (empty($list['fail_pay'])){
                $list['fail_pay'] = '0.00';
            }
            $_list = null;
            foreach($list['list'] as $k => $v){
                $_list[$k]['deadline'] = date('Y-m-d',$v['deadline']); //预计支付时间
                $_list[$k]['yj_money'] = $v['capital'] + $v['interest'] - $v['interest_fee'];  //预计支付金额
                $_list[$k]['sj_type'] = in_array($v['status'], array(6,7,14))? '未支付':'已支付';  //实际支付状态
            }
            $count = M("investor_detail d")->where($map)->count("d.id");
            $totalPage = ceil($count/$limit);
            if(is_array($_list)){
                $data['list'] = $_list;
                $data['totalPage'] = $totalPage;
                $data['nowPage'] =  $page;
                $data['have_pay'] =  $list['have_pay'];//已支付本息
                $data['fail_pay'] =  $list['fail_pay'];//未支付本息
            }else{
                $data = '暂无投资记录!';
               // AppCommonAction::ajax_encrypt($data,0);
            }
            //AppCommonAction::ajax_encrypt($data,1);
            //dump($data);
            $this->assign("datas",$data['list']);
            $this->assign("lixi",$data);
            $this->display();
        }

    /**
     * 可以流转的普通标
     */
    public function debt_1()
    {

        $pre = C("DB_PREFIX");
        if(!$this->uid){
            $this->error('请登陆后查看!');
        }
        import("ORG.Util.Page");
        $count = M('borrow_investor')->where("investor_uid = ".$this->uid."  and status = 4")->count(); // 必须还款中状态
        $p = new Page($count, 10);
        $show = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";



        $transfer = M('borrow_investor i')
            ->join("{$pre}borrow_info b ON b.id = i.borrow_id")
            ->join("{$pre}members m ON i.borrow_uid = m.id")
            ->field("i.id,b.borrow_type, i.borrow_id, i.add_time, i.deadline, b.borrow_name, i.invest_fee, b.borrow_interest_rate, m.user_name, i.debt_interest_rate, i.debt_time")
            ->where("i.investor_uid = ".$this->uid."  and i.status = 4")
            ->limit($Lsql)
            ->order('i.id')
            ->select();

        if( !empty($transfer) ) {
            $ids = only_array($transfer, 'id');
            $investor_detail = M('investor_detail')->field("sum(capital) as capital,sum(interest) as interest,invest_id")
                ->where(array('invest_id'=>array('in',implode(',', $ids)), 'status' => 7))->group("invest_id")->select();
            if( !empty($investor_detail) ) {
                foreach($transfer as $k=>$v){
                    foreach( $investor_detail as $val ) {
                        if( $val['invest_id'] == $v['id'] ) {
                            $v['investor_capital'] = $val['capital'];
                            $v['investor_interest'] = $val['interest'];
                            break;
                        }
                    }
                    $arr = $this->countDebt($v['id']);
                    $transfers['data'][$k] = $arr+ $v;
                }
            }

            // $transfers['page'] = $Page->show();
        }
        $_list = array();
        foreach($transfers['data'] as $k=>$value){
            $_list[$k]['id'] = $value['id']; //项目id
            $_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
            $_list[$k]['interest_rate'] = $value['debt_interest_rate'] > 0? $value['debt_interest_rate']:$value['borrow_interest_rate']; //利率
            $_list[$k]['investor_capital'] = $value['investor_capital'].'/'.($value['investor_interest']-$value['invest_fee']); //待收本金/待收利息
            $_list[$k]['addtime'] = date('Y-m-d',$value['add_time']); //投资时间
            $_list[$k]['deadline'] = date('Y-m-d',$value['deadline']); //投资时间
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
        }
        $this->assign("datas",$data['list']);
        $this->assign("page",$show);
        $this->display();
    }


    //转让债券显示页
    public function debt_2(){
        $datag = get_global_setting();

        $invest_id = isset($_GET['ids'])? intval($_GET['ids']):0;
        if(!$invest_id){
            $this->error("参数错误");
        }
        $info = $this->countDebt($invest_id);
//        $price = $info['capital']+$info['interest'];
        //$debt_fee_rate = $datag['debt_fee']; // 手续费率
        $data['capital'] = $info['capital'];  //转让本金：
//        $data['price'] = $price;   //手续费：
//        $data['debt_fee_rate'] = $debt_fee_rate;   //手续费率
//        $data['uncollect'] = $info['uncollect'];  //本期应收利息
//        $data['invest_id'] = $info['invest_id'];  //债权id
//        $data['feilv'] = round($data['price']*$data['debt_fee_rate'],2);//手续费
//        $data['yuqi'] = $data['capital'] - $data['feilv'] + $data['uncollect'];
//        //判断支付密码
//        $vm = getMinfo($this->uid,'m.pin_pass');
//        $pin_pass = $vm['pin_pass'];
//        $data['has_pin'] = (empty($pin_pass))?"0":"1";
        //dump($data);
        $this->assign("vo",$data);
        $this->assign("invest_id",$invest_id);

        $this->display();
    }

    public function jisuan(){
        $datag = get_global_setting();
        $arr = array();
        $arr['capital'] = intval($_POST['money']);  //本金
        $arr['zherang'] = $_POST['zherang']; //折让价格
        $arr['id'] = intval($_POST['dis'])? intval($_POST['dis']):0;

		$arr['zherang_2'] = 0; //初始 折让价格
        if(!$arr['id'])ajaxmsg("参数错误",0);
        $info = $this->countDebt($arr['id']);
        $debt_fee_rate = $datag['debt_fee']; // 手续费率
        $data['uncollect'] = $info['uncollect'];  //本期应收利息
        $data['debt_fee_rate'] = $debt_fee_rate; //手续费率
        $data['zhuanrang_money'] = round($arr['capital']+$data['uncollect']-($arr['capital']*$arr['zherang']/100),2);//转让价格
        $data['yuqi_money'] = round($data['zhuanrang_money']*((100-$data['debt_fee_rate'])/100),2);//预期到账金额
        $data['fee_in'] = round($data['zhuanrang_money'] - $data['yuqi_money'],2);//手续费

		$data['zhuanrang_money'] = round($arr['capital']+$data['uncollect']-($arr['capital']*$arr['zherang_2']/100),2);//初始 转让价格
		$data['yuqi_money_2'] = round($data['zhuanrang_money']*((100-$data['debt_fee_rate'])/100),2);//初始 预期到账金额
		$data['fee_in_2'] = round($data['zhuanrang_money'] - $data['yuqi_money'],2);//初始 手续费
        ajaxmsg($data,1);
    }

    /**
     * 转让中的债权
     *
     */
    public function debt_3()
    {
        $uid = $this->uid;
        $pre = C("DB_PREFIX");
        if($uid != $this->uid){
           $this->error('请登陆后查看!');
        }
        import("ORG.Util.Page");
        $count = M('debt')->where("sell_uid = ".$this->uid."  and status in (2,99)")->count();
        $p = new Page($count, 10);
        $show = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $Bonds['data'] = M('debt d')
            ->join("{$pre}borrow_investor i ON d.invest_id = i.id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->field("d.id,d.invest_id, d.status, i.borrow_id, d.money, d.addtime, d.period, d.total_period, b.borrow_name, b.borrow_interest_rate, b.total, b.has_pay")
            ->where("d.sell_uid = ".$this->uid."  and d.status in (2,99) ")
            ->limit($Lsql)
            ->order('d.id')
            ->select();

        $_list = array();
        foreach($Bonds['data'] as $k=>$value){
            $_list[$k]['id'] = $value['invest_id']; //项目id
            $_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
            $_list[$k]['interest_rate'] = $value['debt_interest_rate'] > 0? $value['debt_interest_rate']:$value['borrow_interest_rate']; //利率
            $_list[$k]['investor_capital'] = bcsub($value['total'],$value['has_pay']).'期/'.$value['total'].'期'; //未还/总期数
            $_list[$k]['money'] = $value['money']; //转让本金
            $_list[$k]['addtime'] = date('Y-m-d',$value['addtime']); //转让时间
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
        }
       //dump($data);
        if($_list){
            $this->assign("datas",$data['list']);
//            foreach($data['list'] as $v){
//                $this->assign("id",$v['id']);
//            }
            $this->assign("id",$data['list']);
        }else{
            $this->assign("no",1);
        }
        $this->assign("page",$show);
      $this->display();
    }


    //已转让债券列表
    public function debt_4()
    {
        $uid = $this->uid;
        $pre = C("DB_PREFIX");
        if($uid != $this->uid){
           $this->error('请登陆后查看!');
        }
        import("ORG.Util.Page");
        $count = M('debt')->where("sell_uid = ".$this->uid."  and status = 4")->count();
        $p = new Page($count, 10);
        $show = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $lists['data'] = M('debt d')
            ->join("{$pre}borrow_investor i ON d.invest_id = i.id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->field("d.id,d.invest_id, i.borrow_id, d.money,  d.status,d.addtime,d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
            ->where("d.sell_uid = ".$this->uid."  and d.status =4 ")
            ->limit($Lsql)
            ->order('d.id')
            ->select();
        $_list = array();
        foreach($lists['data'] as $k=>$value){
            $_list[$k]['id'] = $value['id']; //项目id
            $_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
            $_list[$k]['interest_rate'] = $value['interest_rate']; //收益率
            $_list[$k]['investor_capital'] = $value['period'].'期/'.$value['total_period'].'期'; //购买期数/总期数
            $_list[$k]['money'] = $value['money']; //债权本金
            $_list[$k]['addtime'] = date('Y-m-d',$value['addtime']); //转让时间
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
        }

        if($_list){
            $this->assign("datas",$data['list']);
        }else{
            $this->assign("no",1);
        }
        $this->assign("page",$show);
        $this->display();
    }

    /**
     * 已购买的债权
     *
     */
    public function debt_5()
    {
        $uid = $this->uid;
        $pre = C("DB_PREFIX");
        if($uid != $this->uid){
           $this->error('请登陆后查看!');
        }
        import("ORG.Util.Page");
        $where = "investor_uid=".$this->uid." and parent_invest_id > 0 and d.sell_uid !=".$this->uid;
        $count = M('borrow_investor i')
            ->join("{$pre}debt d ON d.invest_id = i.parent_invest_id")
            ->where($where)->count();
        $p = new Page($count, 10);
        $show = $p->show();
        $Lsql = "{$p->firstRow},{$p->listRows}";
        $lists['data'] = M('borrow_investor i')
            ->join("{$pre}debt d ON d.invest_id = i.parent_invest_id")
            ->join("{$pre}borrow_info b ON i.borrow_id = b.id")
            ->join("{$pre}members m ON d.sell_uid=m.id")
            ->field("d.id,i.id as invest_id, i.borrow_id, i.investor_capital, i.add_time, i.status, d.serialid, d.discount_gold, d.interest_rate, m.user_name,d.period, d.total_period, b.borrow_name,b.borrow_type, d.interest_rate, b.total, b.has_pay")
            ->where("i.investor_uid=".$this->uid." and d.status in(2,4) and d.sell_uid != ".$this->uid)
            ->limit($Lsql)
            ->order('d.status')
            ->select();
        if( !empty($lists['data']) ) {
            for( $i=0;$count=count($lists['data']),$i<$count; $i++ ) {
                $lists['data'][$i]['buy_money'] = $lists['data'][$i]['investor_capital'] * (1-$lists['data'][$i]['discount_gold']/100);
            }
        }
        $_list = array();
        foreach($lists['data'] as $k=>$value){
            $_list[$k]['id'] = $value['id']; //项目id
            $_list[$k]['invest_id'] = $value['invest_id']; //下载id
            $_list[$k]['borrow_name'] = $value['borrow_name']; //项目名称
            $_list[$k]['interest_rate'] = $value['interest_rate']; //利率
            $_list[$k]['total_periods'] = $value['period'].'期/'.$value['total_period'].'期'; //转让期数/总期数
            $_list[$k]['investor_capital'] = $value['investor_capital']; //债权总值
            $_list[$k]['buy_money'] = $value['buy_money']; //购买价格
            $_list[$k]['addtime'] = date('Y-m-d',$value['add_time']); //购买时间
            $_list[$k]['status_type'] = $value['status']; //状态是否可以下载
        }

        if(is_array($_list)){
            $data['list'] = $_list;
            $data['totalPage'] = $totalPage;
            $data['nowPage'] =  $page;
        }else{
            $data = '暂无相关数据！';
        }

        if($_list){
            $this->assign("datas",$data['list']);
        }else{
            $this->assign("no",1);
        }
        $this->assign("page",$show);
        $this->display();
    }


    /**
     *  撤销债权转让
     *
     */
    public function cancel_debt()
    {
        $invest_id = $_POST['id'];
        $paypsss = strval($_POST['paypass']);
        if(!$invest_id){
            ajaxmsg("非法参数");
        }
        if($this->cancel($invest_id, $paypsss)) {
            ajaxmsg("撤销成功",1);
        }else{
            ajaxmsg("密码错误撤销失败",0);
        }

    }

    /**
     * 取消转让
     *
     * @param intval $invest_id   //债权id
     * @param strval $paypass // 支付密码
     */
    public function cancel($invest_id, $paypass)
    {
        $invest_id = intval($invest_id);
        $paypass = md5($paypass);
        $vm = getMinfo($this->uid,'m.pin_pass');
        if( empty($paypass) ) {
            return false;
        }
        if($paypass != $vm['pin_pass']){
            return false;
        }
        if($this->cancelDebt($invest_id, 1)){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 撤销转让,债权没有任何人购买过的情况下才可以撤消
     *
     * @param mixed $invest_id  // 债权id
     * @param mixed $type     状态 1 债权人撤销，2债权还款撤销  3转让超时   4还款操作，用户可能提交还款
     */
    public function cancelDebt($invest_id, $type)
    {
        if(!$this->Model->where("invest_id={$invest_id}")->count('id')){
            return false;
        }
        //查询是否有人购买过
        $assigned = $this->Model->where("invest_id={$invest_id}")->getField('assigned');
        if( $assigned > 0 ) {
            return false;
        }
        $status = $this->Model->where("invest_id={$invest_id}")->getField('status');
        $sell_uid = $this->Model->where("invest_id={$invest_id}")->getField('sell_uid');
        $remark = array(
            '1'=>'债权人撤销',
            '2'=>'债权还款撤销',
            '3'=>'转让超时',
        );
        $update = array(
            'status'=>3,
            'cancel_time'=>time(),
            'remark' =>$remark[$type],
        );

        $condition1 = " id={$invest_id} and status=14";
        $condition2 =  " invest_id={$invest_id} and status=14";
        $this->Model->startTrans();
        $borrow_investor = M("borrow_investor")->where($condition1)->save(array("status"=>4));
        $investor_detail = M("investor_detail")->where($condition2)->save(array("status"=>7));
        $invest_detb = $this->Model->where(" invest_id={$invest_id} and status=2")->save($update);
        if($status==2){
            $detail_info = M('investor_detail')->field(" sum(capital) as capital, sum(interest) as interest")->where("invest_id={$invest_id}")->find();
            $money = $detail_info['capital'] + $detail_info['interest'];
            $this->moneyLog2($sell_uid, 49, 0, $money, "取消{$invest_id}号债权", 0);

            $money_collect = M('member_money')->where("uid={$sell_uid}")->getField('money_collect');
            $money_collect = bcadd($money_collect, $money, 2);
            M('member_money')->where("uid={$sell_uid}")->save(array('money_collect'=>$money_collect));
        }

        if($borrow_investor && $invest_detb && $investor_detail){
            $this->Model->commit();
            return true;
        }else{
            $this->Model->rollback();
            return false;
        }
    }


    //转让债券
    public function sell_debt()
    {

        if(!$this->uid)ajaxmsg("请先登陆");
        $discount_gold = floatval($_POST['discount_gold']);
        $money = floatval($_POST['money']);
        $paypass = $_POST['paypass'];
        $invest_id = intval($_POST['id']);
        //ajaxmsg($paypass);
        if($discount_gold<0 || $discount_gold > 7.5){
            ajaxmsg('折让率超过0.0%-7.5%的范围',0);
        }
        $deadline = M('investor_detail')->where("invest_id={$invest_id} and repayment_time=0")->getField('deadline');
        $day =   intval(($deadline - time())/ 3600/ 24);
        if($day < 5){
            ajaxmsg('剩余还款时间不得小于5天',0);
        }

        if($money && $paypass && $invest_id){
            //ajaxmsg(111);
            $result = $this->sell($invest_id, $money, $paypass, $discount_gold);
            //ajaxmsg($result);
            if($result ==='TRUE')
            {
                ajaxmsg('债权转让成功');
            }else{
                ajaxmsg($result,0);
            }
        }else{
            ajaxmsg('债权转让失败',0);
        }
    }

    /**
     * 统计债权回购情况
     * @param intval $invest_id  // 投资id
     */
    public function countDebt($invest_id)
    {
        $debt = array();
        $invest_id = intval($invest_id);
        if(!$invest_id){
            return $debt;
        }
        $condition = "invest_id= '".$invest_id."' and status =7";  // 还款中状态且未逾期
        //可转让期数、统计待收本金和利息
        $debt = M("investor_detail")->field("count(id) as re_num, sum(capital) as capital")->where($condition)->find();
        $debt['total'] = M("investor_detail")->where("invest_id=".$invest_id)->getField('total'); //总共多少期
        $benefit = M("investor_detail")->field("sum(interest) as interest ")->where("invest_id= {$invest_id} and  status in (1,2,3,4,5)")->find();
        $debt['uncollect'] = $this->getUncollect($invest_id); // 本期未收利息
        $debt['benefit']  = $benefit['interest']; // 已经回款的利息收益
        $debt['invest_id'] = $invest_id;
        return $debt;

    }

    /**
     * 获取本期未收利息，不到期按天计算
     *
     * @param intval $invest_id // 投资债权id
     */
    private function getUncollect($invest_id)
    {
        $time = time();

        $uncollect = 0.00;
        $invest_info = M('borrow_investor')->field("borrow_id, debt_time, add_time")->where("id={$invest_id}")->find();
        $borrow_info = M('borrow_info')->field("borrow_interest_rate, rate_type, full_time")->where("id={$invest_info['borrow_id']}")->find();

        $interest = M("investor_detail")->field("deadline, sort_order , sum(capital) as capital")->where("invest_id= {$invest_id} and status=7")->order(" sort_order asc")->find(); // 待收利息
        if( $borrow_info['rate_type'] == BorrowModel::BID_CONFIG_RATE_TYPE_IMMEDIATE ) {
            $deadline = $invest_info['add_time'];  // 以投资添加时间为准
        } else {
            $deadline = $borrow_info['full_time']; // 即投计息没有满标时间
        }

        // 如果已经还过款，查询上一次应该还款日。如果未还过款，那么利息从满标之后开始计算。//TODO: 企业直投即投计息
        if($interest['sort_order']>1){ // 存在还款的情况
            $sort_order = $interest['sort_order'] - 1;
            $detail_info = M("investor_detail")->field("deadline, sort_order")->where("invest_id= {$invest_id} and sort_order={$sort_order}")->find();

            $deadline = $detail_info['deadline'];

        }
        if($invest_info['debt_time']){
            $deadline = $invest_info['debt_time'];
            if($detail_info['deadline'] > $deadline){
                $deadline =  $detail_info['deadline'];
            }
        }

        $day = ($time - $deadline)/3600/24;

        $day = intval($day);
        $uncollect = bcDiv(bcMul(bcMul($borrow_info['borrow_interest_rate'], $day, 6), $interest['capital'], 6), 100, 6);
        $uncollect  = bcDiv($uncollect, 365, 2);
        return $uncollect;
    }

    /**
     * 债权转让操作
     *
     * @param int $invest_id   // 债权id
     * @param float $price    // 出售价格
     * @param string $paypass // 支付密码
     * @return mixed        // 成功返回TRUE 失败返回失败状态
     */
    public function sell($invest_id, $price, $paypass, $discount_gold)
    {
        $invest_id = intval($invest_id);
        $price = floatval($price);
        $paypass = md5($paypass);
        $db = new Model();
        $db->startTrans();

        $check = $this->checkSell($invest_id, $price, $paypass);
        file_put_contents("777.txt",$debt);
        if($check==='TRUE'){ // 检测通过
            $count_invest = $this->countDebt($invest_id);
            $info['invest_id'] = $invest_id;
            $info['sell_uid'] = $this->uid;
            $info['money'] =  $count_invest['capital'];
            $info['period'] = $count_invest['re_num'];
            $info['total_period'] = $count_invest['total'];
            $info['addtime'] = time();
            $info['discount_gold'] = $discount_gold;
            $info['ip'] = get_client_ip();

            $datag = get_global_setting();
            $debt_audit = $datag['debt_audit']; // 债权转让是否审核
            if($debt_audit){
                $info['status'] = 99; //审核
            }else{
                $info['status'] = 2;
                $info['valid'] = time()+$this->time ;

            }
            //file_put_contents("aaa.txt",22);
            //如果存在转让记录 则直接更新
            $record = $this->Model->where("invest_id=".$invest_id)->getField('id');
            //file_put_contents("ccc.txt",M()->getLastSql());
            if($record){
                $this->Model->where("id=".$record)->delete();
            }
            $this->Model->startTrans();
            $debt = $this->Model->add($info);
            //file_put_contents("sss.txt",M()->getLastSql());
            if( !empty($debt) ) {

                $debt_rate = $this->getInterestRate($invest_id);
                //file_put_contents("777.txt",$debt_rate);
                $update_res = M("debt")->where("id=".$debt)->save(array('interest_rate'=>$debt_rate));
                //file_put_contents("999.txt",M()->getLastSql());
                if(!$update_res) {
                    $this->Model->rollback();
                    return '债权转让失败';
                }
            }

            $investor = M("borrow_investor")->where("id=".$invest_id)->save(array('status'=>14));
            $detail = M("investor_detail")->where("invest_id=".$invest_id." and status=7")->save(array('status'=>14));
            //file_put_contents("888.txt",$debt);
            if(!$debt_audit){
                //file_put_contents("777.txt",$debt);
                $detail_info = M('investor_detail')->field(" sum(capital) as capital, sum(interest) as interest")->where("invest_id={$invest_id} and status=14")->find();
                $money = bcadd($detail_info['capital'] , $detail_info['interest'], 2);
                $this->moneyLog2($this->uid, 47, 0, $money, "转让{$invest_id}号债权", 0);
                $money_collect = M('member_money')->where("uid={$this->uid}")->getField('money_collect');
                $money_collect = bcsub($money_collect, $money, 2);
                M('member_money')->where("uid={$this->uid}")->save(array('money_collect'=>$money_collect));
//                file_put_contents("777.txt",$debt);
//                file_put_contents("888.txt",$investor);
//                file_put_contents("999.txt",$detail);

            }
//            file_put_contents("777.txt",$debt);
//            file_put_contents("888.txt",$investor);
//            file_put_contents("999.txt",$detail);
            if($debt && !empty($investor) && !empty($detail) ){
                $this->Model->commit();
                return 'TRUE';
            }else{
                $this->Model->rollback();
                return '债权转让失败';
            }

        }else{
            return $check;
        }
    }


    /**
     * 支付密码是否正确
     * @param intval $invest_id  // 投资id
     * @param float $price // 转让价格
     * @param password $paypass // 支付密码
     */
    private function checkSell($invest_id, $price, $paypass)
    {

        $vm = getMinfo($this->uid,'m.pin_pass,mm.account_money,mm.back_money');
        if($paypass != $vm['pin_pass']){
            return '支付密码错误';
            exit;
        }
        //如果有散标，并且正在借款或还款中的净值标，则不让其转让
        $version = FS("Webconfig/version");
        if( $version['single'] == 1 ) {
            $net_where = array(
                'borrow_uid' => $this->uid,
                'borrow_type' => BorrowModel::BID_CONFIG_TYPE_NET_ASSETS,
                'borrow_status' => array('in', '-1,0,2,6,8,9')
            );

            $net_borrow = M('borrow_info')->field('id')->where($net_where)->find();

            if( !empty($net_borrow) ) {
                return '您有借款中的净值标，不能进行债权转让';
            }

        }

        return 'TRUE';
    }

    /**
     * 获取购买债权利率
     * TODO: 等额本息和按天计息的推导利率的方式不同
     * @param mixed $invest_id
     */
    public function getInterestRate($invest_id)
    {
        $invest_id = intval($invest_id);
        $uncollect = $this->getUncollect($invest_id); // 本息未收利息
        $debt_info = $this->Model->field("money, assigned, discount_gold")->where("invest_id={$invest_id}")->find();
        $money =  bcsub($debt_info['money'], $debt_info['assigned'], 2);   // 本金

        if($debt_info['assigned'] > 0.00){
            $uncollect = bcSub($uncollect , ($uncollect/$debt_info['money']*$debt_info['assigned']), 2);
        }
        $d_gold = bcDiv(bcMul($money , $debt_info['discount_gold'], 5), 100, 2);// z折让金

        $interest = M('investor_detail')->where("invest_id={$invest_id} and repayment_time=0")->sum('interest'); // 待收利息

        if($debt_info['assigned'] > 0.00){
            $interest = bcSub($interest , ($interest/$debt_info['money']*$debt_info['assigned']), 2);
        }
        $invest_deadline = M('borrow_investor')->field('deadline')->where("id={$invest_id}")->find();
        $surplus_day =  intval(($invest_deadline['deadline'] - time())/3600/24); // 剩余天数

        $InterestRate = $this->subscriberRates($interest, $d_gold,  $money, $surplus_day);
        //file_put_contents("222.txt",$InterestRate);
        return $InterestRate;

    }

    /**
     * 购买方年化利率
     * (原债权人本债权未收利息+折让金)/(认购债权本金-折让金)/此债权到期剩余天数*365*100%=实际年化收益
     *
     * @param mixed $interest_closed  // 待收利息
     * @param mixed $d_gold   //  折让金
     * @param mixed $principal      //转让本金
     * @param mixed $surplus_day      // 剩余天数
     */
    private function subscriberRates($interest_closed, $d_gold,  $principal, $surplus_day)
    {
        $add1 = bcadd($interest_closed, $d_gold, 5);
        $sub = bcsub($principal, $d_gold, 5);
        $mul = bcmul($add1, 365, 5);
        $div1 = bcdiv($mul, $sub, 10);
        $div2 = bcdiv($div1, $surplus_day, 10);
        $rates = bcmul($div2, 100, 2);
        return $rates;
    }

    /**
     * 债权转让资金操作记录日志
     * @param int  $uid  // 用户id
     * @param int  $type // 操作类型
     * @param float $money  //操作资金
     * @param float $debt_money // 债权金额，实际影响转让人金额
     * @param string $info //日志说明
     * @param int  $target_uid // 交易对方uid
     */
    public function moneyLog2($uid, $type, $money, $debt_money, $info, $target_uid)
    {
        if(!$target_uid){
            $user['user_name'] = '@网站平台@';
        }else{
            $user = M("members")->field("user_name")->where("id={$target_uid}")->find();
        }
        //file_put_contents("111",111);
        $money_log = M("member_moneylog")
            ->field("account_money, back_money, collect_money, freeze_money")
            ->where("uid={$uid}")
            ->order("id desc")
            ->find();

        $money_log['affect_money'] = $money;
        $money_log['uid'] = $uid;
        $money_log['type'] = $type;
        $money_log['info'] = $info;
        $money_log['add_time'] = time();
        $money_log['add_ip'] = get_client_ip();
        $money_log['target_uid'] = $target_uid;
        $money_log['target_uname'] = $user['user_name'];
        //--------------------------------------------

        if($type==47){ //转让债权 在转让时一次性去掉待收
            if($money <= 0){
                $money_log['collect_money'] = bcsub($money_log['collect_money'], $debt_money, 2);//待收资金减少债权金额
                $money_log['affect_money'] = -$debt_money;
                $money_log['info'] = $info.",减少待收资金";
            }else{
                $money_log['account_money'] +=  $money;
                $money_log['info'] = $info."{$debt_money}元份额";
            }


        }elseif($type==49){ // 取消债权  一次性增加待收
            $money_log['collect_money'] = bcadd($money_log['collect_money'], $debt_money, 2) ;
            $money_log['affect_money'] = $debt_money;
            $money_log['info'] = $info.",增加待收资金";
        }

        //--------------------------------------------

        $id = M("member_moneylog")->add($money_log);
        return $id;
    }


    /**
     **
     **自动投标开始*
     **
     **/
    //自动投标显示页
    public function autolong(){
//        $jsoncode = file_get_contents("php://input");
//        $arr = array();
//        $arr = json_decode($jsoncode, true);
//        $arr = AppCommonAction::get_decrypt_json($arr);
        if(!$this->uid){
           ajaxmsg('请您登陆！');
        }


        $map['uid'] = $this->uid;
        $type = intval($_POST['type']);  //  1、散标  6、企业直投  7、 定投宝
        $map['borrow_type'] = $type;

        $vo = M('auto_borrow')->where($map)->find();
        //ajaxmsg($vo);
        $list = array();
        if (is_array($vo)){
            //`mxl:autoday`
            $MAXMOONS = 180;
            $vo['is_auto_day'] = ($vo['duration_to'] >= $MAXMOONS) ? 1 : 0; //1：月标，0天标
            $vo['duration_to'] = $vo['duration_to'] % $MAXMOONS;
            //`mxl:autoday`
            $list = array($vo);
        }
        //$this->assign('list',$list);
        $data['invest_money'] = (empty($list[0]['invest_money'])===true)? 200:$list[0]['invest_money'];
        $data['min_invest'] = (empty($list[0]['min_invest'])===true)? 50:$list[0]['min_invest'];
        if($list[0]['interest_rate'] <> 0){
            $data['interest_rate'] = $list[0]['interest_rate'];
            $data['is_interest_rate'] = 1;
        }else{
            $data['interest_rate'] = '';
            $data['is_interest_rate'] = 0;
        }
        if($list[0]['duration_from'] <> 0){
            $data['duration_from'] = $list[0]['duration_from'];
            $data['duration_to'] = $list[0]['duration_to'];
            $data['is_duration_from'] = 1;
        }else{
            $data['duration_from'] = '';
            $data['duration_to'] = '';
            $data['is_duration_from'] = 0;
        }
        if($list[0]['is_auto_day'] == 1){
            $data['is_auto_day'] = 1;
        }else{
            $data['is_auto_day'] = 0;
        }
        if($list[0]['account_money'] <> 0){
            $data['account_money'] = $list[0]['account_money'];
            $data['is_account_money'] = 1;
        }else{
            $data['account_money'] = '';
            $data['is_account_money'] = 0;
        }
        if($list[0]['end_time'] <>0){
            $data['end_time'] = $list[0]['end_time']==0? '':date('Y-m-d',$list[0]['end_time']);
            $data['is_end_time'] = 1;
        }else{
            $data['end_time'] = '';
            $data['is_end_time'] = 0;
        }
        $data['is_use'] = $list[0]['is_use'];
		//dump('$data['is_use']');
        ajaxmsg($data, 1);
    }

    //自动投标结束页
    public function savelong(){
//        $jsoncode = file_get_contents("php://input");
//        $arr = array();
//        $arr = json_decode($jsoncode, true);
//        $arr = AppCommonAction::get_decrypt_json($arr);
        if(!$this->uid){
            ajaxmsg('请您登陆！', 0);
        }
        $map['uid'] = $this->uid;
        $x = M('members')->field("time_limit,user_leve")->find($this->uid);
        (intval($_POST['invest_money'])==0)?$is_full=1:$is_full=0;


        $data['uid'] = $this->uid;
        $data['account_money'] = $_POST['is_account_money']==1? floatval($_POST['account_money']):0;
        $data['borrow_type'] = intval($_POST['type']);
        $data['interest_rate'] = $_POST['is_interest_rate']==1? floatval($_POST['interest_rate']):0;
        $data['duration_from'] = intval($_POST['is_duration_from'])==1? intval($_POST['duration_from']):0;
        $data['end_time'] = intval($_POST['is_end_time'])==1? strtotime($_POST['end_time']):0;

        $data['duration_to'] = intval($_POST['is_duration_from'])==1? intval($_POST['duration_to']):0;
        //`mxl:autoday`
        $MAXMOONS = 180;
        if (intval($_POST['is_auto_day'])==1){
            //此处隐含限制条件是duration_to最大不能超过75个月

            $data['duration_to'] += $MAXMOONS;

        }
        //`mxl:autoday`
        $data['is_auto_full'] = $is_full;
        $data['invest_money'] = floatval($_POST['invest_money']);
        $data['min_invest'] = floatval($_POST['min_invest']);
        $data['add_ip'] = get_client_ip();
        $data['add_time'] = time();
        $data['is_use'] = intval($_POST['is_use']);

        $c = M('auto_borrow')->field('id')->where("uid={$this->uid} AND borrow_type={$data['borrow_type']}")->find();
        if(is_array($c)){
            $data['id'] = $c['id'];
            $newid = M('auto_borrow')->save($data);
            if($newid) ajaxmsg("修改成功",1);
            else ajaxmsg("修改失败，请重试",0);
        }
        else{
            $data['invest_time'] = time();
            $newid = M('auto_borrow')->add($data);
            if($newid) ajaxmsg("添加成功",1);
            else ajaxmsg("添加失败，请重试",0);
        }
    }

    /**
     **
     **自动投标结束*
     **
     **/
}