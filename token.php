<?php

/* 設定もろもろ */
/* ------------------------------------------------------------------------------------------------------------------*/

/* データベース接続設定 */
$table_name = ''; /* テーブル名 */
function getDb(){
  $db_name = ''; /* データベース名 */
  $host = ''; /* ホスト名 */
  $usr = ''; /* ユーザー名 */
  $passwd = ''; /* パスワード */
  try{
    $db = new PDO("mysql:dbname={$db_name}; host={$host}; charset=utf8;", $usr, $passwd);
  }catch(PDOException $e){
    die("にゃーん:{$e->getMessage()}");
  }
  return $db;
}

/* プログラムの設定*/
$token_char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; /* トークンに使用する文字 */
$token_n = ''; /* 生成するトークンの数 */
$token_len = ''; /* 生成するトークンの長さ */
$token_ResetFlg = true; /* true: DBの内容をリセットして追加 false:DBの内容をチェックして継ぎ足し */

/* -----------------------------------------------------------------------------------------------------------------=*/

/* 関数定義 */
function genRandStr($length, $charSet){
  $retStr = '';
  $randMax =  strlen($charSet) - 1;
  for ($i = 0; $i < $length; ++$i) {
    $retStr .= $charSet[rand(0, $randMax)];
  }
  return $retStr;
}

/* テーブルの存在確認 */
$db = getDb();
$stt = $db->prepare("show tables LIKE '{$table_name}'");
$stt->execute();
$tables = $stt->fetch();
if(empty($tables)){
  echo 'テーブルが存在しないので作成：テーブル名'.$table_name."\n";
  $stt = $db->prepare("
  CREATE TABLE `{$table_name}` (
    `id` int(11)  NOT NULL AUTO_INCREMENT,
    `token` varchar(255) NOT NULL,
    `del_flg` varchar(1) DEFAULT '0',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
    ");
    $stt->execute();
  }

  /* ここからプログラム本体 */
  $db_tokenArr = array();
  $db = getDb();
  if($token_ResetFlg){
    /* リセット */
    $stt = $db->prepare("TRUNCATE TABLE `{$table_name}`");
    $stt->execute();
  }else{
    /* DB内のトークンの衝突チェック */
    $stt = $db->prepare("select token from `{$table_name}`");
    $stt->execute();
    $db_token = $stt->fetchAll();
    foreach($db_token as $t ){
      $token = $t['token'];
      if(isset($db_tokenArr[$token])){
        echo 'ERROR:DB内のトークンが衝突しています。'."\n";
        exit;
      }
      $db_tokenArr[$token] = 1;
    }
  }

  /* トークンの生成とトークンの衝突チェック*/
  $tokenArr = array();
  for ($i=0; $i < $token_n  ; $i++) {
    $token = '';
    $loop = true;
    $retry = 30; /* リトライ回数 */
    $conflict = 0; /* 衝突回数 */
    while ($loop && $retry > $conflict) {
      $token = genRandStr($token_len, $token_char);
      if(isset($tokenArr[$token]) || isset($db_tokenArr[$token])){
        $conflict ++;
      }else{
        $loop = false;
        $tokenArr[$token] = 1;
        $db_tokenArr = 1;
      }
      if($retry == $conflict){
        echo '衝突回数が'.$retry.'に達したので強制終了 '."\n";
        goto end;
      }
    }
  }
  end:;
  echo 'トークン数'.count($tokenArr)."\n";

  /* トークンをDBへ挿入 */
  $sql = '';
  $sql .=  "INSERT INTO `{$table_name}` (token, del_flg) VALUES";
  $block = 10000;
  $count = 0;
  $arr = array();
  foreach($tokenArr as $token => $num){
      $arr[] = "('{$token}', 0)";
      $count++;
      if($count == $block){
          $sql .= implode(',', $arr);
          $sql .= ';';
          $stt = $db->prepare($sql);
          $stt -> execute();
          $arr = array();
          $sql = '';
          $sql .= "INSERT INTO `{$table_name}` (token, del_flg) VALUES";
          $count = 0;
      }
  }
  if($count){
      $sql .= implode(',', $arr);
      $sql .= ';';
      $stt = $db->prepare($sql);
      $stt -> execute();
  }

  $db = null;
