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
$smarty->debugging = false;
$smarty->setTemplateDir( 'libs/smarty/templates');
$smarty->setCompileDir(  'libs/smarty/templates_c');
$smarty->setCacheDir(    'libs/smarty/cache');
$smarty->setConfigDir(   'libs/smarty/configs');
$smarty->plugins_dir[] = 'libs/php';
$smarty->caching = false;
$smarty->cache_lifetime = 120;
$smarty->assign("facebook", $facebook);
$smarty->assign("ie", using_ie());

$fbook = array("session"=> 	json_encode($session),
			   "me"		=>  $me,
			   "uid"	=>  $uid,
			   "login"	=>	$facebook->getLoginUrl(array('req_perms' => 'email, user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday')),
			   "loginB"	=>	$facebook->getLoginUrl(array('req_perms' => 'user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday, user_photos, friends_photos')),
			   "logout"	=>	$facebook->getLogoutUrl(array('next' => 'http://likebright.com/cupid/?logout=true')));

if (isset($session)) {
	$meCupid = new meCupid($uid, null, $fbook["me"]);
	$fbook["me"] = $meCupid->user;
	$get = getIt($_GET, $fbook["me"]["profile"]["status"]);
	$smarty->assign("get", $get);

	$oauth = $session['access_token'];

	/*
		if ($uid == "2203233") {
			echo $oauth;
			echo $session['access_token'];
		}
	*/
	$fbook = array_merge($fbook, array("oauth" => $oauth));
	$fbook["me"]["user"] = get_user($uid, array("email"=>$fbook["me"]["profile"]["email"], "access_key"=>$oauth, "name"=>$fbook["me"]["profile"]["name"]));
	
	$match = match_api(25);
	$smarty->assign("matchJSON", json_encode($match, JSON_HEX_APOS));
	$smarty->assign("match", $match);

	$match_tops = array("male"=>match_tops($friends, "male", 5), "female"=>match_tops($friends, "female", 5));	
	$smarty->assign("match_tops", $match_tops);
	$smarty->assign("match_your", $meCupid->top_matches($get));
	$smarty->assign("friendCnt", $meCupid->cntFriends());

	//friends!  friends!
	/*
	$friends = getFriends($uid);
	$in = "'".implode("','", $friends)."'";	
	
	if ($_GET["secret"]!="" and ($uid=="211897" or $uid=="2203233"))
		$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser", $conn);
	else
		$res = mysql_query("SELECT name, uid, pic, status FROM cupidUser WHERE uid in ({$in})", $conn);
	*/
	

	/* //HIDE WINGS 
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
			$frd["faces"] = array_merge($frd["faces"], $frd["extra"]);
		} 
		$frd["faces"] = array_slice($frd["faces"], 0, 9);
		$smarty->assign("wings", $frd);
	}
	*/
	
	
	$url["degree"] = (in_array($get["degree"], array("0", "1", "2")))?"&degree={$get["degree"]}":"";
	$url["status"] = (in_array($get["status"], array("s", "x", "a")))?"&status={$get["status"]}":"";
	$url["gender"] = (in_array($get["gender"], array("m", "f")))?"&gender={$get["gender"]}":"";
	$url["all"] = "{$url["degree"]}{$url["status"]}{$url["gender"]}";
	$smarty->assign("url", $url);
	
} else {	
	$match = match_api(25, false);
	$smarty->assign("matchJSON", json_encode($match, JSON_HEX_APOS));
	$smarty->assign("match", $match);
}


$smarty->assign("fbook", $fbook);
$smarty->display('match.tpl');
