<?php

$host = '';
$usr = '';
$passwd = '';
$dbName = '';
$tableName = '';
$charSet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$tokenNum = 1000000;
$tokenLen = 128;
$ResetFlg = true;

function getDb($dbName, $host, $usr, $passwd){

    try{
        $db = new PDO("mysql:dbname={$dbName}; host={$host}; charset=utf8;", $usr, $passwd);
    }catch(PDOException $e){
        die("データベースの接続に失敗しました。:{$e->getMessage()}");
    }
    return $db;

}

function createRandStr($length, $charSet) {

  $retStr = '';
  $randMax =  strlen($charSet) - 1;
  for ($i = 0; $i < $length; ++$i) {
    $retStr .= $charSet[rand(0, $randMax)];
  }
  return $retStr;

}

$db = getDb($dbName, $host, $usr, $passwd);
$stt = $db->prepare("show tables LIKE '{$tableName}'");
$stt->execute();
$tables = $stt->fetch();

if ( empty($tables) ) {
    echo $tableName . 'テーブルが存在しないので作成：テーブル名' ."\n";
    $stt = $db->prepare("
        CREATE TABLE `{$tableName}` (
          `id` int(11)  NOT NULL AUTO_INCREMENT,
          `token` varchar(255) NOT NULL,
          `del_flg` varchar(1) DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
    ");
    $stt->execute();
}

$dbTokenArr = [];
$db = getDb($dbName, $host, $usr, $passwd);

if ( $ResetFlg ) {
    $stt = $db->prepare("TRUNCATE TABLE `{$tableName}`");
    $stt->execute();
} else {
    /* DB内のトークンの衝突チェック */
    $stt = $db->prepare("select token from `{$tableName}`");
    $stt->execute();
    $db_token = $stt->fetchAll();

    foreach ( $db_token as $t ) {
        $token = $t['token'];
        if ( isset($db_tokenArr[$token]) ) {
             echo 'ERROR:DB内のトークンが衝突しています。'."\n";
             return 0;
        }
        $db_tokenArr[$token] = 1;
        }
    }

$tokenArr = [];
$token = '';
$loop = true;
$retry = 20;
$conflict = 0;

for ($i=0; $i<$tokenNum; $i++ ) {

    $token = createRandStr($tokenLen, $charSet);

    if (isset($tokenArr[$token]) || isset($dbTokenArr[$token])) {
        $conflict++;
    } elseif ($retry === $conflict) {

        echo '衝突回数が' . $retry . 'に達したので強制終了';
        return 0;

    } else {
        $tokenArr[$token] = 1;
    }

}
echo 'トークン数'.count($tokenArr)."\n";

/* トークンをDBへ挿入 */
$sql = '';
$sql .=  "INSERT INTO `{$tableName}` (token, del_flg) VALUES";
$block = 1000;
$count = 0;
$arr = [];

try {

    foreach ($tokenArr as $token => $value) {

        $arr[] = "('{$token}', 0)";
        $count++;

        if($count == $block){

            $sql .= implode(',', $arr);
            $sql .= ';';
            $stt = $db->prepare($sql);
            $stt->execute();
            $arr = array();
            $sql = '';
            $sql .= "INSERT INTO `{$tableName}` (token, del_flg) VALUES";
            $count = 0;

        }

    }
    if ($count) {
        $sql .= implode(',', $arr);
        $sql .= ';';
        $stt = $db->prepare($sql);
        $stt->execute();
    }

} catch (Exception $e) {

    die($e->getMessage());

}

$db = null;
