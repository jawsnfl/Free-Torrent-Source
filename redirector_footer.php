<?php
require_once("include/bittorrent.php");

loggedinorreturn();



$url = fix_url($_GET['url']);
$url = clean($url);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<HEAD>
<META http-equiv="Content-Type" CONTENT="text/html; charset=<?=$charset;?>" />
<TITLE><?=$SITENAME;?></TITLE>
<style>
body {
	font-family: Verdana, Tahoma, Georgia;
	font-size: 10px;
}

.link a{
	color: #FFFFFF;
	text-decoration: none;
	font-weight:normal;
}

.link a:hover {
	text-decoration: underline;
}

.linkOrange a{
	color: #EC8749;
	text-decoration: none;
}

.linkOrange a:hover {
	text-decoration: underline;
}

.SmallText, .Link, .OrangeSmallText, .GraySmallText, .BoldSmallText{
	font-family: Verdana;
	font-size: 10; 
}

.BoldSmallText {
	font-weight: bold;
}

.OrangeSmallText {
	color: #EC8749;
}

.GraySmallText {
	color: #999999;
}

.WhiteSmallText {
	color: #FFFFFF;
}
</style>
</HEAD>

<body bgcolor="#444444">
<!--javascript:top.location = parent.document.referrer;"--> 
<div align="center">
<table cellpadding="0" cellspacing="0" border="0" height="20" width="600">
	<tr>
		<td align="left" class="link"><a href="<?=$BASEURL;?>" target="_top"><span style="font-weight:bold;font-size:10px;"><?=$BASEURL;?></span></a></td>
		
		<td align="left" class="link"><b class="OrangeSmallText">Invalid link?</b> <a href="<?=$BASEURL;?>/contactstaff.php?subject=invalid_link&link=<?=$url;?>" target="_top">Click here to report it</a></td>
		
		<td align="right" class="link"><a href="<?=$url;?>" target="_top"><img src="<?=$BASEURL;?>/<?=$pic_base_url;?>/close.gif" title="Remove Frame" border="0" /></a></td>
	</tr>
</table>
</div>
</body>
</HTML>