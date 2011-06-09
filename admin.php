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

$fbook = array("session"=> 	json_encode($session),
			   "me"		=>  $me,
			   "uid"	=>  $uid,
			   "login"	=>	$facebook->getLoginUrl(array('req_perms' => 'email, user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday')),
			   "loginB"	=>	$facebook->getLoginUrl(array('req_perms' => 'user_relationship_details, friends_relationship_details, user_relationships, friends_relationships, user_likes, friends_likes, offline_access, user_location, friends_location, user_birthday, friends_birthday, user_photos, friends_photos')),
			   "logout"	=>	$facebook->getLogoutUrl(array('next' => 'http://likebright.com/cupid/?logout=true')));

if (isset($session) && ($uid=="2203233" || $uid=="211897")) {

	$page = 25*$_GET["page"];
	$users = array();
	$res = mysql_query("SELECT count(*) AS cnt FROM cupidUser", $conn);
	$data = mysql_fetch_assoc($res);	
	$users["cnt"] = $data["cnt"];

	if ($_GET["section"]=="email") {
		$email = array();
		$res = mysql_query("SELECT email FROM cupidUser ORDER BY id DESC", $conn);
		while($data = mysql_fetch_assoc($res))
			if ($data["email"]!="")
				$email[] = $data["email"];
	} else {
		$res = mysql_query("SELECT json, config, matches, skipped, matched, voted FROM cupidUser ORDER BY id DESC LIMIT 25 OFFSET {$page}", $conn);
		while($data = mysql_fetch_assoc($res)) {
			$data["json"] = json_decode($data["json"], true, 512);
			$data["config"] = json_decode($data["config"], true, 512);
			$users["uid"][] = $data;
		}
	}
	$smarty->assign("users", $users);
	$smarty->assign("email", $email);
	$smarty->caching = false;
	$smarty->cache_lifetime = 0;
	$smarty->display('adminPage.tpl');	
} else {	
}