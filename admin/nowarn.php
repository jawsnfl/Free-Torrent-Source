<?php
$rootpath = '../';
require_once ( $rootpath."include/bittorrent.php" ) ;


if(isset($_POST["nowarned"])&&($_POST["nowarned"]=="nowarned")){
//if (get_user_class() >= UC_SYSOP) {
ADMIN::check();

if (empty($_POST["usernw"]) && empty($_POST["desact"]) && empty($_POST["delete"]))
   bark("You Must Select A User To Edit.");

if (!empty($_POST["usernw"]))
{
$msg = sqlesc("Your Warning Has Been Removed By: " . $CURUSER['username'] . ".");
$added = sqlesc(get_date_time());
$userid = implode(", ", $_POST[usernw]);
//sql_query("INSERT INTO messages (sender, receiver, msg, added) VALUES (0, $userid, $msg, $added)") or sqlerr(__FILE__, __LINE__);

$r = sql_query("SELECT modcomment FROM users WHERE id IN (" . implode(", ", $_POST[usernw]) . ")")or sqlerr(__FILE__, __LINE__);
$user = mysql_fetch_array($r);
$exmodcomment = $user["modcomment"];
$modcomment = gmdate("Y-m-d") . " - Warning Removed By " . $CURUSER['username'] . ".\n". $modcomment . $exmodcomment;
sql_query("UPDATE users SET modcomment=" . sqlesc($modcomment) . " WHERE id IN (" . implode(", ", $_POST[usernw]) . ")") or sqlerr(__FILE__, __LINE__);

$do="UPDATE users SET warned='no', warneduntil='0000-00-00 00:00:00' WHERE id IN (" . implode(", ", $_POST[usernw]) . ")";
$res=sql_query($do);}

if (!empty($_POST["desact"])){
$do="UPDATE users SET enabled='no' WHERE id IN (" . implode(", ", $_POST['desact']) . ")";
$res=sql_query($do);}
}
header("Refresh: 0; url=warned.php");
?>