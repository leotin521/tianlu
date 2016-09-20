<?php

// 本类由系统自动生成，仅供测试用途
class HelpAction extends HCommonAction
{

    public function index()
    {
        $is_subsite = false;
        $typeinfo = get_type_infos();
        if (intval($typeinfo['typeid']) < 1) {
            $typeinfo = get_area_type_infos($this->siteInfo['id']);
            $is_subsite = true;
        }

        $typeid = $typeinfo['typeid'];
        $typeset = $typeinfo['typeset'];
        //left
        $listparm['type_id'] = $typeid;
        $listparm['limit'] = 20;
        if ($is_subsite === false)
            $leftlist = getTypeListActa($listparm); //getTypeList($listparm);
        else {
            $listparm['area_id'] = $this->siteInfo['id'];
            $leftlist = getAreaTypeList($listparm);
        }
        $this->assign("leftlist", $leftlist);
        $this->assign("cid", $typeid);

        if ($typeset == 1) {
            $parm['pagesize'] = 15;
            $parm['type_id'] = $typeid;
            if ($is_subsite === false) {
                $list = getArticleList($parm);
                $vo = D('Acategory')->find($typeid);
                if ($vo['parent_id'] <> 0)
                    $this->assign('cname', D('Acategory')->getFieldById($vo['parent_id'], 'type_name'));
                else
                    $this->assign('cname', $vo['type_name']);
            } else {
                $vo = D('Aacategory')->find($typeid);
                if ($vo['parent_id'] <> 0)
                    $this->assign('cname', D('Aacategory')->getFieldById($vo['parent_id'], 'type_name'));
                else
                    $this->assign('cname', $vo['type_name']);
                $parm['area_id'] = $this->siteInfo['id'];
                $list = getAreaArticleList($parm);
            }
            $this->assign("vo", $vo);
            $this->assign("list", $list['list']);
            $this->assign("pagebar", $list['page']);
        } else {
            if ($is_subsite === false) {
                $vo = D('Acategory')->find($typeid);
                if ($vo['parent_id'] <> 0)
                    $this->assign('cname', D('Acategory')->getFieldById($vo['parent_id'], 'type_name'));
                else
                    $this->assign('cname', $vo['type_name']);
            } else {
                $vo = D('Aacategory')->find($typeid);
                if ($vo['parent_id'] <> 0)
                    $this->assign('cname', D('Aacategory')->getFieldById($vo['parent_id'], 'type_name'));
                else
                    $this->assign('cname', $vo['type_name']);
            }
            $this->assign("vo", $vo);
        }

        $this->display($typeinfo['templet']);
    }

    //Update::2014/12/11
    public function fram()
    {
        $glo = $this->glo;
        if (intval($_GET['id'])) {
            $id = intval($_GET['id']);
            $request_uri = $_SERVER['REQUEST_URI'];
            if(strpos($request_uri, '.html') !== false){
                $typeid = M('article')->field('type_id')->where("id=".$id)->find();
                $ac_type = M('article_category')->field('type_nid')->where("id=".$typeid['type_id'])->find();
                $url = $ac_type['type_nid'].'/'.$id;
                $this->redirect($url);
            }
            //读取普通文章内容
            $id = intval($_GET['id']);
            $hc = 'data_article' . $id;
            if (S($hc)) {
                $vo = S($hc);
            } else {
                $vo = M('article')->field('art_content')->find($id);
                S($hc, $vo, 86400);
            }
        } else {
            //读取单页内容
            $type_nid = $_GET['type_nid'];
            $tc = 'data_article_category' . $type_nid;
            if (S($tc)) {
                $vo = S($tc);
            } else {
                $vo = M('article_category')->field('type_content')->where("type_nid='" . $type_nid . "'")->find();
                $jujianfang = M('hetong')->find();
                switch ($type_nid) {
                    case 'sbht':
                        $article_html = $vo['type_content'];
                        $hetong_num = "bytp2pD" . date('Ymd', $iinfo['add_time']);
                        $duration_unit = BorrowModel::get_unit_format($binfo['duration_unit']);
                        $healthy = array(
                            "[web_name]",
                            "[borrow_id]",
                            "[company_name]",
                            "[company_address]",
                            "[hetong_img]",
                            "[company_tel]",
                            "[invest_real_name]",
                            "[borrow_real_name]",
                            "[domain]",
                            "[capital_interest]",
                            "[invest_duration]]",
                            "[interest_rate]",
                            "[invest_user_name]",
                            "[invest_idcard]",
                            "[invest_capital]",
                            "[repayment_name]",
                            "[second_verify_time]",
                            "[hetong_num]",
                            "[deadline]",
                            "[repayment_list]"
                        );
                        $yummy = array(
                            $glo['web_name'],
                            $binfo['borrow_id'],
                            '居间方',
                            '居间方地址',
                            '<div class="htzhang"><img src="/' . $jujianfang['hetong_img'] . '" height="150px"/></div>',
                            '居间方电话',
                            '张三',
                            '李四',
                            DOMAIN,
                            '101',
                            '10天',
                            '10',
                            '张三',
                            '身份证号',
                            '100元',
                            '一次性还款',
                            'XXXX年XX月XX日',
                            $hetong_num,
                            'XXXX年XX月XX日',
                            $repayment_list_str
                        );

                        $vo['type_content'] = str_replace($healthy, $yummy, $article_html);

                        break;
                    case 'ztht':
                        $article_html = $vo['type_content'];
                        $healthy = array(
                            "[web_name]",
                            "[borrow_id]",
                            "[company_name]",
                            "[company_address]",
                            "[hetong_img]",
                            "[company_tel]",
                            "[invest_real_name]",
                            "[domain]",
                            "[capital_interest]",
                            "[invest_duration]",
                            "[invest_user_name]",
                            "[invest_idcard]",
                            "[invest_capital]",
                            "[repayment_type]",
                            "[second_verify_time]",
                            "[business_name]",
                            "[deadline]"
                        );
                        $yummy = array(
                            $glo['web_name'],
                            1,
                            '居间方',
                            '居间方地址',
                            '<div class="Seal"><img src="/' . $jujianfang['hetong_img'] . '" height="150px"/></div>',
                            '居间方电话',
                            '李四',
                            DOMAIN,
                            '101',
                            '10天',
                            'lisi',
                            '身份证号',
                            '100元',
                            '一次还款',
                            'XXXX年XX月XX日',
                            '张三',
                            'XXXX年XX月XX日'
                        );

                        $vo['type_content'] = str_replace($healthy, $yummy, $article_html);
                        break;
                    case 'dtbht':
                        $article_html = $vo['type_content'];
                        $healthy = array(
                            "[web_name]",
                            "[batch_no]",
                            "[borrow_name]",
                            "[company_name]",
                            "[company_address]",
                            "[company_phone]",
                            "[hetong_img]",
                            "[invest_real_name]",
                            "[domain]",
                            "[invest_user_name]",
                            "[invest_phone]",
                            "[invest_idcard]",
                            "[per_transfer]",
                            "[join_time]",
                            "[join_money]",
                            "[end_time]",
                            "[fee_rate]", //利息管理费
                            "[add_time]", //投资时间
                        );
                        $yummy = array(
                            $glo['web_name'],
                            $batch_no,
                            'DTB-000001',
                            '居间方',
                            '居间方地址',
                            '居间方电话',
                            '<div class="htzhang"><img src="/' . $jujianfang['hetong_img'] . '" height="150px"/></div>',
                            '张三',
                            DOMAIN,
                            '张三',
                            '手机号',
                            '身份证号',
                            '50',
                            'XXXX年XX月XX日',
                            '100',
                            'XXXX年XX月XX日',
                            '0.1',
                            'XXXX年XX月XX日',
                        );
                        $vo['type_content'] = str_replace($healthy, $yummy, $article_html);
                        break;
                    case 'zqht':
                        $article_html = $vo['type_content'];
                        $healthy = array(
                            "[web_name]",
                            "[serialid]",
                            "[add_time]",
                            "[transfer_real_name]",
                            "[transfer_idcard]",
                            "[invest_real_name]",
                            "[invest_idcard]",
                            "[company_name]",
                            "[domain]",
                            "[hetongzhang]",
                            "[transfer_capital]",
                            "[transfer_price]",
                            "[transfer_fee]",
                            "[remain_days]",
                            "[repayment_list]",
                        );
                        $yummy = array(
                            $glo['web_name'],
                            $debt['serialid'],
                            'XXXX年XX月XX日',
                            '王五',
                            '身份证号',
                            '李四',
                            '身份证号',
                            $jujianfang['name'],
                            DOMAIN,
                            '<div class="htzhang"><img src="/' . $jujianfang['hetong_img'] . '" height="150px"/></div>',
                            100,
                            90,
                            1,

                        );

                        $vo['type_content'] = str_replace($healthy, $yummy, $article_html);
                        break;
                    default:
                        $article_html = $vo['type_content'];
                        $healthy = array(
                            "[web_name]",
                            "[batch_no]",
                            "[company_name]",
                            "[company_address]",
                            "[jujianfang.hetong_img]",
                            "[invest_real_name]",
                            "[domain]",
                            "[invest_user_name]",
                            "[invest_idcard]",
                            "[bao_item.money]",
                            "[bao_item.add_time]",
                            "[bao_item.term_time_et]",
                            "[bao_item.time_et]",
                            "[bao_item.interest_rate]",
                            "[bao_item.start_funds]"
                        );
                        $yummy = array(
                            $glo['web_name'],
                            $bao_item['batch_no'],
                            '居间方',
                            '居间方地址',
                            '<div style="position: absolute; bottom:30px;right:30px;"><img src="/' . $jujianfang['hetong_img'] . '" height="150px"/></div>',
                            '张三',
                            DOMAIN,
                            '张三',
                            '身份证号',
                            '100',
                            'XXXX年XX月XX日',
                            'XXXX年XX月XX日',
                            'XXXX年XX月XX日',
                            '10%',
                            '50'
                        );
                        $vo['type_content'] = str_replace($healthy, $yummy, $article_html);
                        break;
                }
                S($tc, $vo, 86400);
            }
        }
        $this->assign('vo', $vo);
        $this->display();
        //file_put_contents("./App/Tpl/Home/default/content/".$id.".html",$vo['art_content']);
    }

    public function view()
    {
        $id = intval($_GET['id']);
        $vo = M('article')->field('title,art_keyword,art_info,art_time,type_id')->find($id);
        $tid = $vo['type_id'];
        $wo = M('article_category')->find($tid);
        $this->assign("wo", $wo);
        $this->assign("id", $id);
        $this->assign("vo", $vo);

        //left
        $typeid = $vo['type_id'];
        $listparm['type_id'] = $typeid;
        $listparm['limit'] = 15;
        if ($_GET['type'] == "subsite") {
            $listparm['area_id'] = $this->siteInfo['id'];
            $leftlist = getAreaTypeList($listparm);
        } else {
            $leftlist = getTypeListActa($listparm);
        }

        $this->assign("leftlist", $leftlist);
        $this->assign("cid", $typeid);

        if ($_GET['type'] == "subsite") {
            $vop = D('Aacategory')->field('type_name,parent_id')->find($typeid);
            if ($vop['parent_id'] <> 0)
                $this->assign('cname', D('Aacategory')->getFieldById($vop['parent_id'], 'type_name'));
            else
                $this->assign('cname', $vop['type_name']);
        } else {
            $vop = D('Acategory')->field('type_name,parent_id')->find($typeid);
            if ($vop['parent_id'] <> 0)
                $this->assign('cname', D('Acategory')->getFieldById($vop['parent_id'], 'type_name'));
            else
                $this->assign('cname', $vop['type_name']);
        }

        /*                     * ******实现上下篇开始********* */
        $str = "";
        $downarticle = M("article")->where("id>{$id} and type_id={$typeid} ")->limit(1)->order('id asc')->find();
        if (empty($downarticle)) {
            $str .= "上一篇:没有文章了<br>";
        } else {
            $str .= "上一篇：<a href='__URL__/view?id=" . $downarticle['id'] . "'>" . $downarticle['title'] . "</a><br>";
        }
        $uparticle = M("article")->where("id<{$id} and type_id={$typeid}")->limit(1)->order('id desc')->find();
        if (empty($uparticle)) {
            $str .= "下一篇:最后一篇文章<br><br>";
        } else {

            $str .= "下一篇：<a href='__URL__/view?id=" . $uparticle['id'] . "'>" . $uparticle['title'] . "</a>";
        }

        $this->assign('updownarticle', $str);

        /*                     * **实现上下篇结束**** */
        $this->display();
    }

    public function kf()
    {
        $kflist = M("ausers")->where("is_kf=1")->select();
        $this->assign("kflist", $kflist);
        //left
        $listparm['type_id'] = 0;
        $listparm['limit'] = 20;
        if ($_GET['type'] == "subsite") {
            $listparm['area_id'] = $this->siteInfo['id'];
            $leftlist = getAreaTypeList($listparm);
        } else
            $leftlist = getTypeList($listparm);

        $this->assign("leftlist", $leftlist);
        $this->assign("cid", $typeid);

        if ($_GET['type'] == "subsite") {
            $vop = D('Aacategory')->field('type_name,parent_id')->find($typeid);
            if ($vop['parent_id'] <> 0)
                $this->assign('cname', D('Aacategory')->getFieldById($vop['parent_id'], 'type_name'));
            else
                $this->assign('cname', $vop['type_name']);
        } else {
            $vop = D('Acategory')->field('type_name,parent_id')->find($typeid);
            if ($vop['parent_id'] <> 0)
                $this->assign('cname', D('Acategory')->getFieldById($vop['parent_id'], 'type_name'));
            else
                $this->assign('cname', $vop['type_name']);
        }

        $this->display();
    }

    public function tuiguang()
    {
        $uid = MembersModel::get_user_Encryption($this->uid);
        $url = "http://". $_SERVER['HTTP_HOST'].'/i/'. $uid;
        $this->assign('url', $url);
        $_P_fee = get_global_setting();
        $this->assign("reward", $_P_fee);
        $field = " m.id,m.user_name,sum(ml.affect_money) jiangli ";
        $list = M("members m")->field($field)->join(" lzh_member_moneylog ml ON m.id = ml.target_uid ")->where("ml.type=13")->group("ml.uid")->order('jiangli desc')->limit(10)->select();
        $this->assign("list", $list);

        $this->display();
    }

    //秒标未能自动复审时，管理员手动处理方法之应急处理方案  fan  2013-10-22
    //使用方法：直接在浏览器访问该方法。例如：http://www.lvmaquebeat.cn/help/domiao?borrow_id=15
    public function domiao()
    {
        $borrow_id = intval($_REQUEST['borrow_id']);
        $vm = M('borrow_info')->field('borrow_uid,borrow_money,has_borrow,borrow_type,borrow_status')->find($borrow_id);
        if (($vm['borrow_status'] == 7) || ($vm['borrow_status'] == 9) || ($vm['borrow_status'] == 10)) {
            $this->error('该标已还款完成，请不要重复还款！');
            exit;
        }

        //复审投标检测
        $capital_sum1 = M('investor_detail')->where("borrow_id={$borrow_id}")->sum('capital');
        $capital_sum2 = M('borrow_investor')->where("borrow_id={$borrow_id}")->sum('investor_capital');
        if (($vm['borrow_money'] != $capital_sum2) || ($capital_sum1 != $capital_sum2) || ($vm['borrow_money'] != $vm['has_borrow'])) {
            $this->error('投标金额不统一，请确认！');
            exit;
        } else {
            //dump($borrow_id);exit;
            if ($vm['borrow_type'] == 3) {
                borrowApproved($borrow_id);
                $done = borrowRepayment($borrow_id, 1);
                if (!$done) {
                    $this->error('还款失败，请确认！');
                    exit;
                } else {
                    $this->success('还款成功，请确认！');
                    exit;
                }
            } else {
                $this->error('非秒标类型，不能执行此操作，请确认！');
                exit;
            }
        }
    }

    //秒标未能自动复审时，管理员手动处理方法之应急处理方案  fan  2013-10-22
    //绿麻雀授权信息查询
    public function lvmaque()
    {
        lvmaqueinfo();
    }

}
