<?php
$rootpath = '../';
require_once ( $rootpath."include/bittorrent.php" ) ;

ADMIN::check();

$id = 0 + $_GET['id'];
// DELETE FORUM ACTION
if ($_GET['action'] == "del") {


if (!$id) { header("Location: $BASEURL/admin/forummanage.php"); die();}

$result = mysql_query ("SELECT * FROM topics where forumid = '".$_GET['id']."'");
if ($row = mysql_fetch_array($result)) {
do {
mysql_query ("DELETE FROM posts where topicid = '".$row["id"]."'") or sqlerr(__FILE__, __LINE__);
} while($row = mysql_fetch_array($result));
}
mysql_query ("DELETE FROM topics where forumid = '".$_GET['id']."'") or sqlerr(__FILE__, __LINE__);
mysql_query ("DELETE FROM forums where id = '".$_GET['id']."'") or sqlerr(__FILE__, __LINE__);

header("Location: $BASEURL/admin/forummanage.php");
die();
}

//EDIT FORUM ACTION
if ($_POST['action'] == "editforum") {

$name = ($_POST['name']);
$desc = ($_POST['desc']);

if (!$name && !$desc && !$id) { header("Location: $BASEURL/admin/forummanage.php"); die();}

mysql_query("UPDATE forums SET sort = '" . $_POST['sort'] . "', name = " . sqlesc($_POST['name']). ", description = " . sqlesc($_POST['desc']). ", forid = ".sqlesc(($_POST['overforums'])).", minclassread = '" . $_POST['readclass'] . "', minclasswrite = '" . $_POST['writeclass'] . "', minclasscreate = '" . $_POST['createclass'] . "' where id = '".$_POST['id']."'") or sqlerr(__FILE__, __LINE__);
header("Location: $BASEURL/admin/forummanage.php");
die();
}

//ADD FORUM ACTION
if ($_POST['action'] == "addforum") {

$name = ($_POST['name']);
$desc = ($_POST['desc']);

if (!$name && !$desc) { header("Location: $BASEURL/admin/forummanage.php"); die();}

mysql_query("INSERT INTO forums (sort, name,  description,  minclassread,  minclasswrite, minclasscreate, forid) VALUES(" . $_POST['sort'] . ", " . sqlesc($_POST['name']). ", " . sqlesc($_POST['desc']). ", " . $_POST['readclass'] . ", " . $_POST['writeclass'] . ", " . $_POST['createclass'] . ", ".sqlesc(($_POST['overforums'])).")") or sqlerr(__FILE__, __LINE__);

header("Location: $BASEURL/admin/forummanage.php");
die();
}

// SHOW FORUMS WITH FORUM MANAGMENT TOOLS
stdhead("Forum Management Tools");
begin_main_frame();
 begin_frame("Forums");
?>
<script language="JavaScript">
<!--
function confirm_delete(id)
{
   if(confirm('Are you sure you want to delete this forum?'))
   {
      self.location.href='<?php $_SERVER["PHP_SELF"]; ?>?action=del&id='+id;
   }
}
//-->
</script>
<?php
echo '<table width="100%"  border="0" align="center" cellpadding="2" cellspacing="0">';
echo "<tr><td class=colhead align=left>Name</td><td class=colhead>OverForum</td><td class=colhead>Read</td><td class=colhead>Write</td><td class=colhead>Create topic</td><td class=colhead>Modify</td></tr>";
$result = mysql_query ("SELECT  * FROM forums ORDER BY sort ASC");
if ($row = mysql_fetch_array($result)) {
do {
$forid = $row['forid'];
$res2 = mysql_query("SELECT name FROM overforums WHERE id=$forid");
$arr2 = mysql_fetch_array($res2);
$name = $arr2['name'];


echo "<tr><td><a href=$BASEURL/forums/viewforum.php?action=viewforum&forumid=".$row["id"]."><b>".$row["name"]."</b></a><br>".$row["description"]."</td>";
echo "<td>".$name."</td><td>" . get_user_class_name($row["minclassread"]) . "</td><td>" . get_user_class_name($row["minclasswrite"]) . "</td><td>" . get_user_class_name($row["minclasscreate"]) . "</td><td align=center nowrap><b><a href=\"".$PHP_SELF."?action=editforum&id=".$row["id"]."\">Edit</a>&nbsp;|&nbsp;<a href=\"javascript:confirm_delete('".$row["id"]."');\"><font color=red>Delete</font></a></b></td></tr>";


} while($row = mysql_fetch_array($result));
} else {print "<tr><td>Sorry, no records were found!</td></tr>";}
echo "</table>";
?>
<br><br>
<form method=post action="<?=$BASEURL;?>/admin/forummanage.php">
<table width="100%"  border="0" cellspacing="0" cellpadding="3" align="center">
<tr align="center">
    <td colspan="2" class=colhead>Make new forum</td>
  </tr>
  <tr>
    <td><b>Forum name</td>
    <td><input name="name" type="text" size="20" maxlength="60"></td>
  </tr>
  <tr>
    <td><b>Forum description  </td>
    <td><input name="desc" type="text" size="30" maxlength="200"></td>
  </tr>
  <tr>
    <td><b>OverForum </td>
    <td>
    <select name=overforums>\n
    <?php
            /* $res = mysql_query("SELECT * FROM overforums");
             $maxrow = mysql_num_rows($res);
             for ($i = 1; $i <= $maxrow; ++$i) {
             $arr = mysql_fetch_array($res);
             $name = $arr["name"];
             $forid = $arr["id"];
            print("<option value=$i" . ($forid == $i ? " selected" : "") . ">$prefix" . $name . "\n");
            }*/
            $forid = $row["forid"];
            $res = mysql_query("SELECT * FROM overforums");
             while ($arr = mysql_fetch_array($res)) {

             $name = $arr["name"];
             $i = $arr["id"];

            print("<option value=$i" . ($forid == $i ? " selected" : "") . ">$prefix" . $name . "\n");
            }
?>
        </select>
    </td>
  </tr>

    <tr>
    <td><b>Minimun read permission </td>
    <td>
    <select name=readclass>\n
    <?php
             $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($user["class"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select>
    </td>
  </tr>
  <tr>
    <td><b>Minimun write permission </td>
    <td><select name=writeclass>\n
    <?php
              $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($user["class"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select></td>
  </tr>
  <tr>
    <td><b>Minimun create topic permission </td>
    <td><select name=createclass>\n
    <?php
            $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($user["class"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select></td>
  </tr>
    <tr>
    <td><b>Forum rank </td>
    <td>
    <select name=sort>\n
    <?php
$res = mysql_query ("SELECT sort FROM forums");
$nr = mysql_num_rows($res);
            $maxclass = $nr + 1;
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i>$i \n");
?>
        </select>


    </td>
  </tr>

  <tr align="center">
    <td colspan="2"><input type="hidden" name="action" value="addforum"><input type="submit" name="Submit" value="Make forum" class=btn></td>
  </tr>
</table>

<?php

print("<tr><td align=center colspan=1><form method=\"get\" action=\"moforums.php#add\"></form><form method=\"get\" action=\"moforums.php#add\"><input type=\"submit\" value=\"SubForum Manager\" class=\"btn\" /></form></td></tr>\n");
end_frame(); ?>

<?php if ($_GET['action'] == "editforum") {

//EDIT PAGE FOR THE FORUMS

$id = 0 + ($_GET["id"]);
begin_frame("Edit Forum");
$result = mysql_query ("SELECT * FROM forums where id = ".sqlesc($id));
if ($row = mysql_fetch_array($result)) {

// Get OverForum Name - To Be Written

do {
?>

<form method=post action="<?=$_SERVER["PHP_SELF"];?>">
<table width="100%"  border="0" cellspacing="0" cellpadding="3" align="center">
<tr align="center">
    <td colspan="2" class=colhead>edit forum: <?=$row["name"];?></td>
  </tr>

    <td><b>Forum name</td>
    <td><input name="name" type="text" size="20" maxlength="60" value="<?=$row["name"];?>"></td>
  </tr>
  <tr>
    <td><b>Forum description  </td>
    <td><input name="desc" type="text" size="30" maxlength="200" value="<?=$row["description"];?>"></td>
  </tr>


    <tr>
    <td><b>OverForum </td>
    <td>
    <select name=overforums>\n
    <?php
             //$maxclass = get_user_class();
          //for ($i = 0; $i <= $maxclass; ++$i)
            //print("<option value=$i" . ($row["minclassread"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");

            /*$forid = $row["forid"];
            $res = mysql_query("SELECT * FROM overforums");
             $maxrow = mysql_num_rows($res);
             for ($i = 1; $i <= $maxrow; ++$i) {
             $arr = mysql_fetch_array($res);
             $name = $arr["name"];

            print("<option value=$i" . ($forid == $i ? " selected" : "") . ">$prefix" . $name . "\n");
            }*/

            $forid = $row["forid"];
            $res = mysql_query("SELECT * FROM overforums");
             while ($arr = mysql_fetch_array($res)) {

             $name = $arr["name"];
             $i = $arr["id"];

            print("<option value=$i" . ($forid == $i ? " selected" : "") . ">$prefix" . $name . "\n");
            }


?>
        </select>
    </td>
  </tr>

    <tr>
    <td><b>Minimun read permission </td>
    <td>
    <select name=readclass>\n
    <?php
             $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($row["minclassread"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select>
    </td>
  </tr>
  <tr>
    <td><b>Minimun write permission </td>
    <td><select name=writeclass>\n
    <?php
              $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($row["minclasswrite"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select></td>
  </tr>
  <tr>
    <td><b>Minimun create topic permission </td>
    <td><select name=createclass>\n
    <?php
            $maxclass = get_user_class();
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($row["minclasscreate"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
?>
        </select></td>
  </tr>
    <tr>
    <td><b>Forum rank </td>
    <td>
    <select name=sort>\n
    <?php
$res = mysql_query ("SELECT sort FROM forums");
$nr = mysql_num_rows($res);
            $maxclass = $nr + 1;
          for ($i = 0; $i <= $maxclass; ++$i)
            print("<option value=$i" . ($row["sort"] == $i ? " selected" : "") . ">$i \n");
?>
        </select>


    </td>
  </tr>

  <tr align="center">
    <td colspan="2"><input type="hidden" name="action" value="editforum"><input type="hidden" name="id" value="<?=$id;?>"><input type="submit" name="Submit" value="Edit forum" class="btn"></td>
  </tr>
</table>

<?php
} while($row = mysql_fetch_array($result));
} else {print "Sorry, no records were found!";}

print("<tr><td align=center colspan=1><form method=\"get\" action=\"forummanage.php#add\"><input type=\"submit\" value=\"Return\" class=\"btn\" /></form></td></tr>\n");
end_frame();
end_main_frame();
}
print("</table>");
stdfoot();
?>