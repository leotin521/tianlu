<?php
  //在线客服
function get_qq($type){
    $list = M('qq')->where("type = $type and is_show = 1")->order("qq_order DESC")->select();
    return $list;
}
?>
