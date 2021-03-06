<?php
$rootpath = '../';
require_once($rootpath."forums/functions/fts.php");
require_once($rootpath."include/bittorrent.php");
  

  loggedinorreturn();
  $wherethisuser = where ($_SERVER["SCRIPT_FILENAME"],$CURUSER["id"]);

  iplogger();
	parked();
    $forumid = $_POST["forumid"];
    	if (isset($forumid))
    		int_check($forumid,true);
    	
    $topicid = $_POST["topicid"];
    	if (isset($topicid))
    		int_check($topicid,true);

    $newtopic = $forumid > 0;

    $subject = $_POST["subject"];

    if ($newtopic)
    {
      $subject = trim($subject);

      if (!$subject) {
        JsB::showgrowl("Error: You must enter a subject.");
        die;
		}
      if (strlen($subject) > $maxsubjectlength) {
        JsB::showgrowl("Error: Subject is limited..");
        die;
        }
    }
    else
      $forumid = get_topic_forum($topicid) or die("Bad topic ID").JsB::showgrowl('Bad topic ID');
      if ($CURUSER["forumpost"] == 'no')
{
JsB::showgrowl('You are not authorized to Post. (<a href=\"inbox.php#up\">Read Inbox</a>)');
exit;
}

    //------ Make sure sure user has write access in forum

    $arr = get_forum_access_levels($forumid) or die("Bad forum ID");

    if (get_user_class() < $arr["write"] || ($newtopic && get_user_class() < $arr["create"])) {
      JsB::showgrowl('Error: Permission denied.');
      die;
      }

    $body = trim($_POST["body"]);

    if ($body == "") {
      JsB::showgrowl('Error: No body text.');
      die;
      }

    $userid = 0+$CURUSER["id"];
    
    // Anti Flood Code
   // To ensure that posts are not entered within 60 seconds limiting posts
   // to a maximum of 60 per hour.
global $___flood___,$usergroups;
	$___flood___->protect('last_post','post',$usergroups['antifloodtime'],false,true);
   /////////////////////////////////////////////////////////////////////////

    if ($newtopic)
    {
      //---- Create topic
      
		//===add karma
		UserHandle::KPS("+","2.0",$userid);
		//===end

      $subject = sqlesc($subject);
      $iconid = sqlesc($_POST['iconid']);

      sql_query("INSERT INTO topics (userid, forumid, subject, iconid) VALUES($userid, $forumid, $subject, $iconid)") or sqlerr(__FILE__, __LINE__);

      $topicid = mysql_insert_id() or JsB::showgrowl('Error: No topic ID returned',true);
    }
    else
    {
      //---- Make sure topic exists and is unlocked

      $res = sql_query("SELECT * FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

      $arr = mysql_fetch_assoc($res) or JsB::showgrowl('Error: Topic ID n/a',true);

      if ($arr["locked"] == 'yes' && get_user_class() < UC_MODERATOR)
        JsB::showgrowl('Error: The topic is locked',true);
//=== PM subscribed peeps
$res_sub = mysql_query("SELECT userid FROM subscriptions  WHERE topicid = $topicid") or sqlerr(__FILE__, __LINE__);
while($row = mysql_fetch_assoc($res_sub)) {
$res_yes = mysql_query("SELECT subscription_pm, username FROM users WHERE id = $row[userid]") or sqlerr(__FILE__, __LINE__);
$arr_yes = mysql_fetch_array($res_yes);
$msg = "Hey there!!! \n a thread you subscribed to: [b]".$arr["subject"]."[/b] has had a new post!\n click [url=".$BASEURL."/forums/viewtopic.php?topicid=".$topicid."&page=last][b]HERE[/b][/url] to read it!\n\nTo view your subscriptions, or un-subscribe, click [url=".$BASEURL."/forums/subscriptions.php][b]HERE[/b][/url].\n\ncheers.";
if ($arr_yes["subscription_pm"] == 'yes' && $row["userid"] != $CURUSER["id"])
mysql_query("INSERT INTO messages (sender, subject, receiver, added, msg) VALUES(0, 'New post in subscribed thread!', $row[userid], '" . get_date_time() . "', " . sqlesc($msg) . ")") or sqlerr(__FILE__, __LINE__);
}
//===end
      //---- Get forum ID

      $forumid = $arr["forumid"];
    }

    //------ Insert post

    $added = "'" . get_date_time() . "'";

    $body = sqlesc($body);
    
    $subject = sqlesc($subject);

    sql_query("INSERT INTO posts (topicid, userid, added, body, subject) " .
    "VALUES($topicid, $userid, $added, $body, $subject)") or sqlerr(__FILE__, __LINE__);

    $postid = mysql_insert_id() or die("Post id n/a");

    //------ Update topic last post

    update_topic_last_post($topicid);
    
    //===add karma
    UserHandle::KPS("+","1.0",$userid);
	//===end
    
    // Update last post sent
	$___flood___->update("last_post");

    //------ All done, redirect user to the post

    $headerstr = "Location: $BASEURL/forums/viewtopic.php?topicid=$topicid&page=last";
    JsB::showgrowl("Post has been added!");
    echo <<<s
<script>
function cleanit() {
	$('textarea[name="body"]').attr('value',"");
}
cleanit();
</script>
s;
  ?>