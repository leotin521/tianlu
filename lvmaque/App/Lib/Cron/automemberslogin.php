<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$link = mysql_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'));
// file_put_contents("D:/a.txt", C('DB_HOST'));
mysql_set_charset("utf8");
$mysql_database =C('DB_NAME');
if($link){
    $prefix=C('DB_PREFIX');
    $where = null;
    $where = 'is_ban=2';
    $sql = "update {$prefix}members set is_ban=0 where ".$where;
// file_put_contents("D:/a.txt", $sql);
    $result=mysql_db_query($mysql_database, $sql,$link );
    
    
}