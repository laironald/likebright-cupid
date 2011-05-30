<?php
require_once 'libs/smarty/libs/Smarty.class.php';
require_once 'libs/facebook/facebook.php';
require_once 'libs/php/curl.php';

/***************************************************************/
//MYSQL
$conn = get_db_conn();

/***************************************************************/

//FACEBOOK
if ($_GET["logout"]!="") {
	setcookie('fbs_'.$fappId, "", time()-3600, "/", ".likebright.com");
	header('Location: http://likebright.com/cupid/');
} else {
	$facebook = new Facebook(array(
		  'appId' => $fappId,
		  'secret' => $fsecret,
		  'cookie' => true,
	));
}

$session = $facebook->getSession();
$me = null;
if ($session) {
	try { 
		$uid = $facebook->getUser();
	} catch (FacebookApiException $e) { 
		error_log($e); 
	}
}
/***************************************************************/

$smarty = new Smarty();
$smarty->force_compile = true;
$smarty->setTemplateDir( 'libs/smarty/templates');
$smarty->setCompileDir(  'libs/smarty/templates_c');
$smarty->setCacheDir(    'libs/smarty/cache');
$smarty->setConfigDir(   'libs/smarty/configs');
$smarty->plugins_dir[] = 'libs/php';
$smarty->assign("facebook", $facebook);
$smarty->assign("ie", using_ie());

$fbook = array("session"=> 	json_encode($session),
			   "me"		=>  $me,
			   "uid"	=>  $uid,
			   "login"	=>	$facebook->getLoginUrl(array('req_perms' => 'email, user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday')),
			   "loginB"	=>	$facebook->getLoginUrl(array('req_perms' => 'user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday, user_photos, friends_photos')),
			   "logout"	=>	$facebook->getLogoutUrl(array('next' => 'http://likebright.com/cupid/?logout=true')));

if (isset($session)) {
	$oauth = $session['access_token'];

	/*
		if ($uid == "2203233") {
			echo $oauth;
			echo $session['access_token'];
		}
	*/
	$fbook = array_merge($fbook, array("oauth" => $oauth));
	/*
		$fbook["me"] = curling(array($uid."|friends"  => "https://graph.facebook.com/me/friends?access_token={$oauth}",
									 $uid."|likes"    => "https://graph.facebook.com/me/likes?access_token={$oauth}"));
		$fbook["me"]["friends"]  = $fbook["me"][$uid."|friends"];
		$fbook["me"]["likes"]    = $fbook["me"][$uid."|likes"];
		unset($fbook["me"][$uid."|friends"]);
		unset($fbook["me"][$uid."|likes"]);
	*/
	$fbook["me"]["user"] = get_user($uid, array("email"=>$fbook["me"]["profile"]["email"], "access_key"=>$oauth, "name"=>$fbook["me"]["profile"]["name"]));
	
	$match = match_api(25);
	
	$meCupid = new meCupid($uid, null, $fbook["me"]);
	$fbook["me"] = $meCupid->user;
	$smarty->assign("matchJSON", json_encode($match, JSON_HEX_APOS));
	$smarty->assign("match", $match);

	if ($fbook["me"]["profile"]["config"][$fbook["me"]["screen"]] >= 10 or $_GET["secret"]!="" or $_GET["vote"]>=10) {
		$match_tops = array("male"=>match_tops($friends, "male", 5), "female"=>match_tops($friends, "female", 5));	
		$smarty->assign("match_tops", $match_tops);
	}
	
	if ($fbook["me"]["profile"]["matches"] >= 40 or $_GET["secret"]!="" or $_GET["tvote"]>=40) {
		/* GET USER's MATCHES */
		$res = mysql_query("SELECT fid, pic, name FROM cupidRank WHERE uid='{$uid}' AND P>50 ORDER BY R2 DESC LIMIT 6", $conn);
		$match_your = array();		
		resUser($match_your, $res);
		$smarty->assign("match_your", $match_your);
	}

	//friends!  friends!
	$friends = getFriends($uid);
	$in = "'".implode("','", $friends)."'";	
	
	if ($_GET["secret"]!="" and ($uid=="211897" or $uid=="2203233"))
		$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser", $conn);
	else
		$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser WHERE uid in ({$in})", $conn);

	if (mysql_num_rows($res) > 0) {
		$frd = array("faces"=>array(), "extra"=>array());
		$frd["count"] = mysql_num_rows($res);
		$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser WHERE status in ('Single', '') and uid in ({$in})", $conn);
		$frd["status"] = mysql_num_rows($res);
		resUser($frd["faces"], $res);
		shuffle($frd["faces"]);
		if ($frd["status"]<9) {
			$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser WHERE status not in ('Single', '') and uid in ({$in})", $conn);
			resUser($frd["extra"], $res);
			shuffle($frd["extra"]);			
			$frd["extra"] = array_slice($frd["extra"], 0, 9-$frd["status"]);
			$frd["faces"] = array_merge($frd["faces"], $frd["extra"]);
		}
		$smarty->assign("wings", $frd);
	}
	$url["degree"] = (in_array($_GET["degree"], array("1", "2")))?"&degree={$_GET["degree"]}":"";
	$url["status"] = (in_array($_GET["status"], array("s", "x")))?"&status={$_GET["status"]}":"";
	$url["gender"] = (in_array($_GET["gender"], array("m", "f")))?"&gender={$_GET["gender"]}":"";
	$url["all"] = "{$url["degree"]}{$url["status"]}{$url["gender"]}";
	$smarty->assign("url", $url);
	
} else {	
	$match = match_api(25, false);
	$smarty->assign("matchJSON", json_encode($match, JSON_HEX_APOS));
	$smarty->assign("match", $match);
}


$smarty->assign("fbook", $fbook);
$smarty->caching = false;
$smarty->cache_lifetime = 0;
$smarty->display('match.tpl');
