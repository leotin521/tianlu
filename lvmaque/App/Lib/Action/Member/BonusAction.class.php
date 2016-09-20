<?php
/**
* 红包 推广管理
* @author zhang ji li 2015.03.06
* @copyright lvmaque
* 
*/
class BonusAction extends MCommonAction {

    // 展示
    public function index(){
        $bonus_config = BonusModel::get_bonus_config();
        // 已发放未领取红包
        $status = isset($_GET['status']) ? intval($_GET['status']) : 1;
        $uid = $this->uid;
        switch($status) {
            case 1:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
                break;
            case 4:
                $where = array(
                    'receive_user_id' => $uid,
                );
                break;
            case 3:
                $where = array(
                    'uid' => $uid,
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('lt', date('Y-m-d H:i:s', time()))
                );
                break;
            default:
                $where = array(
                    'status' => BonusModel::BONUS_STATUS_UNRECEIVE,
                    'validate_et' => array('gt', date('Y-m-d H:i:s', time()))
                );
        }
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $bonus_items = BonusModel::get_bonus_byPage($where, '*', null, null, $p);
        if( !empty($bonus_items['data']) ) {
            $bonus_items['data'] = BonusModel::get_url_format($bonus_items['data']);
            $send_uids = only_array($bonus_items['data'], 'uid');
            $map['id'] = array('in', implode(',', $send_uids));
            $user_items = M('members')->field('user_name,id')->where($map)->select();
            if( !empty($user_items) ) {
                for($i=0; $i<count($bonus_items['data']); $i++) {
                    foreach( $user_items as $val ) {
                        if( $bonus_items['data'][$i]['uid'] == $val['id'] ) {
                            $bonus_items['data'][$i]['source_name'] = $val['user_name'];
                            break;
                        }
                    }
                }
            }
        }
        $this->assign('status', $status);
        $this->assign('bonus_items', $bonus_items);
        $this->assign('bonus_config_arr', $bonus_config);
        $this->assign('bonus_config', json_encode($bonus_config));
		$this->display();
    }

    // 生成
    public function send()
    {
        $bonus_money = floatval($_POST['bonus_money']);//金额
        if( BonusModel::validate_bonus_money($bonus_money) == false ) {
            $ret['msg'] = '红包金额填写有误';
            ajaxmsg($ret, 0);
        }
        $mm = getMinfo($this->uid);
        if( $mm['user_account'] < $bonus_money ) {
            $ret['msg'] = '账户余额不足';
            ajaxmsg($ret, 0);
        }else {
            $config_id = getMillisecond();
            //生成配置文件
            $data = array(
                'config_id' => $config_id,
                'uid' => $this->uid,
                'bonus_money' => $bonus_money,
                'source_type' => BonusModel::BONUS_SOURCE_TYPE_USER,
                'bonus_type' => 1,
                'send_way' => 2,
                'take_way' => 1,
                'validate_st' => date('Y-m-d', time()),
                'validate_et' => date('Y-m-d', strtotime("+30 days", time())) //默认有效期一个月
            );
            $db = M();
            $db->startTrans();
            if( BonusModel::create_bonus($data) ) {
                //冻结用户的金钱
                $result = memberMoneyLog($this->uid, 55, -$bonus_money, '生成红包链接成功,冻结金额'.$bonus_money, '', '', 0, $db);
                if( $result == true ) {
                    $db->commit();
                    $ret['msg'] = '操作成功';
                    ajaxmsg($ret, 1);
                }else{
                    $db->rollback();
                    $ret['msg'] = '生成失败';
                    ajaxmsg($ret, 1);
                }
            }else {
                $ret['msg'] = '生成失败';
                ajaxmsg($ret, 0);
            }
        }
    }

    //领取
    public function take()
    {
        if( empty($this->uid) ) MembersModel::unlogin_home();
        $code = htmlspecialchars($_GET['code'], ENT_QUOTES);
        $config_id = any2Dec($code, 62);
        $bonus = BonusModel::get_bonus($config_id);
        if( !empty($bonus) ) {
            $bonus = $bonus[0];
            $jump_url = DOMAIN.'/Member/bonus/index?status=4';
            if( $bonus['uid'] == $this->uid ) {
                $this->error('对不起，您不能领取自己的红包！', $jump_url);
            }
            elseif($bonus['status'] == BonusModel::BONUS_STATUS_TYPE_RECEIVE) {
                $this->error('对不起，红包被别人抢走了！', $jump_url);
            }
            elseif( $bonus['status'] == BonusModel::BONUS_STATUS_UNRECEIVE && strtotime($bonus['validate_et']) < time() ) {
                $this->error('对不起，此链接已失效！', $jump_url);
            }elseif($bonus['status'] == BonusModel::BONUS_STATUS_UNRECEIVE && strtotime($bonus['validate_et']) > time() && $bonus['receive_user_id'] == 0) {
                $db = M();
                $db->startTrans();
                $result = BonusModel::take_bonus($this->uid, $config_id, $db);
                if( $result ) {
                    $db->commit();
                    $this->success('恭喜，领取红包'.$bonus['bonus_money'].'元！', $jump_url);
                }else{
                    $db->rollback();
                    $this->error('服务器忙');
                }
            }
        }else {
            $this->error('非法请求');
        }
    }


}
