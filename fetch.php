<?php
require_once 'libs/facebook/facebook.php';
require_once 'libs/php/curl.php';
require_once 'libs/smarty/libs/Smarty.class.php';

/***************************************************************/

//FACEBOOK
$facebook = new Facebook(array(
  'appId' => '189260257772056',
  'secret' => '0083ef157e0c3133adbc8aef4f12551a',
  'cookie' => true,
));
$session = $facebook->getSession();
$me = null;
if ($session) {
	try { 
		$uid = $facebook->getUser();
	} catch (FacebookApiException $e) { 
		error_log($e); 
	}
}
$conn = get_db_conn();

$smarty = new Smarty();
$smarty->force_compile = true;
$smarty->setTemplateDir( 'libs/smarty/templates');
$smarty->setCompileDir(  'libs/smarty/templates_c');
$smarty->setCacheDir(    'libs/smarty/cache');
$smarty->setConfigDir(   'libs/smarty/configs');
$smarty->plugins_dir[] = 'libs/php';
$smarty->caching = false;
$smarty->cache_lifetime = 0;
$smarty->assign("ie", using_ie());

$url["degree"] = (in_array($get["degree"], array("0", "1", "2")))?"&degree={$get["degree"]}":"";
$url["status"] = (in_array($get["status"], array("s", "x", "a")))?"&status={$get["status"]}":"";
$url["gender"] = (in_array($get["gender"], array("m", "f")))?"&gender={$get["gender"]}":"";
$url["all"] = "{$url["degree"]}{$url["status"]}{$url["gender"]}";
$smarty->assign("url", $url);

/***************************************************************/

$fbook = array();
if ($session) {
	$fbook = array(	"session"	=> 	json_encode($session),
					"me"		=>  $me,
					"uid"		=>  $uid);
	$smarty->assign("fbook", $fbook);

	$meCupid = new meCupid($uid);
	$fbook["me"] = $meCupid->user;
	$get = getIt($_GET, $fbook["me"]["profile"]["status"]);
	$smarty->assign("get", $get);
	
	$oauth = $session['access_token'];
	$display = true;
	if ($_GET['q']=="match") {
	
		echo json_encode(match_api(25), JSON_HEX_APOS);
		//ADD SOME SORT OF SECURITY HERE PROBABLY...WILL PEOPLE WANT TO MANIPULATE VOTES?
	} elseif ($_GET["q"]=="vote" && isset($_POST["c"]) && isset($_POST["m1"]) && isset($_POST["m2"]) && isset($_POST["vote"])) {
	
		$cid = $_POST["c"];
		if ($_POST["m1"]<$_POST["m2"]) {
			$m1 = $_POST["m1"];
			$m2 = $_POST["m2"];
			$reg = array("m1", "m2");
		} else {
			$m1 = $_POST["m2"];
			$m2 = $_POST["m1"];
			$reg = array("m2", "m1");
		}
		$rank = new elo($uid, $cid, $m1, $m2);
		$meCupid = new meCupid($uid, $_GET["scr"]);
		
		/* ------------------- */
		
		if ($_POST["vote"] != "0") {
			$goRank = true;
			$wid = $_POST["m{$_POST["vote"]}"];
			$meCupid->addCnt();
			
			mysql_query("INSERT IGNORE INTO cupidVote (uid, cid, fid1, fid2, wid) VALUES ('{$uid}', '{$cid}', '{$m1}', '{$m2}', '{$wid}')", $conn);
			if (mysql_insert_id()==0) {
				$datum = mysql_fetch_assoc(mysql_query("SELECT * FROM cupidVote WHERE uid='{$uid}' AND cid='{$cid}' AND fid1='{$m1}' AND fid2='{$m2}'", $conn));
				if (strtotime("now")-strtotime($datum["updated"])>600) //cache vote results every 10 minutes... for fraud reasons, I guess
					mysql_query("REPLACE INTO cupidVote (uid, cid, fid1, fid2, wid) VALUES ('{$uid}', '{$cid}', '{$m1}', '{$m2}', '{$wid}')", $conn);
				else
					$goRank = false;
			}
			if ($goRank) {
				$rank->rating($wid);
			}
		} else {
			$rank->skip();
		}
		$rank->pct();
		echo json_encode(array($reg[0]=>0.75*$rank->uids[$m1]["C"]["E"]+0.25*$rank->uids[$m1]["A"]["E"],
							   $reg[1]=>0.75*$rank->uids[$m2]["C"]["E"]+0.25*$rank->uids[$m2]["A"]["E"],
							   "v"=>$rank->uids[$m1]["C"]["T"]+$rank->uids[$m2]["C"]["T"]+
									$rank->uids[$m1]["A"]["T"]+$rank->uids[$m2]["A"]["T"]));
	}
	elseif ($_GET["q"]=="matchlist") {
		$meCupid = new meCupid($uid);
		$fbook["me"] = $meCupid->user;
		$friends = getFriends($uid);
		
		if ($get["status"]=="x")
			$status = "status in ('Single', '')";
		else
			$status = "status not in ('Single', '')";
	
		$flist = getFriends($uid, 2);
		$in = "'".implode("','", $flist)."'";
		$res = mysql_query("SELECT fid as uid, pic, name FROM cupidRank WHERE uid='{$uid}' AND fid in ({$in}) AND {$status} AND P>50 ORDER BY R2 DESC LIMIT 50", $conn);
		
		$match_tops = array();
		while ($data = mysql_fetch_assoc($res)) {
			$data["name"] = json_decode($data["name"], true);
			$match_tops["item"][] = array("uid"=>$data["uid"], "pic"=>$data["pic"], "name"=>$data["name"]["name"], "matchlist"=>in_array($data["uid"], $friends));
		}
		if (count($match_tops["item"])==0) {
			echo "<font style='font-size: 9pt; color: #999; font-family: Arial;'>No matches have been made!</font>";
		} else {
			$smarty->assign("matchlistOK", $matchlistOK);		
			$smarty->assign("match_tops", $match_tops);		
			$smarty->display('matchtops.tpl');
		}
	}
	elseif ($_GET["q"]=="matchtops") {
		$meCupid = new meCupid($uid);
		$fbook["me"] = $meCupid->user;
		$matchlistOK = ($fbook["me"]["profile"]["matches"] >= 150 or $_GET["secret"]!="");
		
		if ($_GET["sex"]=="")
			$smarty->assign("fbook", $fbook);

		if ($_GET["sex"]=="")
			$match_tops = array("male"=>match_tops($friends, "male", 5), "female"=>match_tops($friends, "female", 5));	
		elseif ($_GET["sex"]=="male")
			$match_tops = array("item"=>match_tops($friends, "male", 50));	
		elseif ($_GET["sex"]=="female")
			$match_tops = array("item"=>match_tops($friends, "female", 50));
		$smarty->assign("match_tops", $match_tops);

		/* GET USER's MATCHES */
		$smarty->assign("match_your", $meCupid->top_matches($get));
		$smarty->assign("matchlistOK", $matchlistOK);		
		$smarty->assign("friendCnt", $meCupid->cntFriends());
		$smarty->display('matchtops.tpl');
	}
	elseif ($_GET["q"]=="friendlist") {
		$match_tops = array();
		$friends = getFriends($uid);
		$in = "'".implode("','", $friends)."'";	
		$res = mysql_query("SELECT uid, pic, name FROM cupidUser WHERE uid in ({$in}) ORDER BY matches DESC LIMIT 50", $conn);
		while ($data = mysql_fetch_assoc($res)) {
			$data["name"] = json_decode($data["name"], true);
			$data["name"] = $data["name"]["name"];
			$match_tops["item"][] = $data;
		}		
		$smarty->assign("match_tops", $match_tops);		
		$smarty->display('matchtops.tpl');
	}
	elseif ($_GET["q"]=="matchbutton") {
		$matchers = array();
		$friends = getFriends($uid);
		$res = mysql_query("SELECT fid, name, sex FROM cupidFriends WHERE fid in ('{$_GET["c"]}', '{$_GET["m"]}') GROUP BY fid", $conn);
		while ($data=mysql_fetch_assoc($res)) {
			if (in_array($data["fid"], $friends)) {
				$name = json_decode($data["name"], true);
				$matchers[$data["fid"]]["name"] = $name["first_name"];
				$matchers[$data["fid"]]["sex"] = $data["sex"];
			}
		}		
		$smarty->assign("oauth", $oauth);
		$smarty->assign("matchers", $matchers);
		$smarty->display('matchbutton.tpl');
	}
	
	if ($display)
		echo " ";
} 












else {
	if ($_GET["q"]=="vote" && isset($_POST["c"]) && isset($_POST["m1"]) && isset($_POST["m2"]) && isset($_POST["vote"])) {	
		$uid = 0;
		$cid = $_POST["c"];
		if ($_POST["m1"]<$_POST["m2"]) {
			$m1 = $_POST["m1"];
			$m2 = $_POST["m2"];
			$reg = array("m1", "m2");
		} else {
			$m1 = $_POST["m2"];
			$m2 = $_POST["m1"];
			$reg = array("m2", "m1");
		}
		$rank = new elo($uid, $cid, $m1, $m2);
		$rank->pct();
		echo json_encode(array($reg[0]=>0.50*$rank->uids[$m1]["C"]["E"]+0.50*$rank->uids[$m1]["A"]["E"],
							   $reg[1]=>0.50*$rank->uids[$m2]["C"]["E"]+0.50*$rank->uids[$m2]["A"]["E"],
							   "v"=>$rank->uids[$m1]["C"]["T"]+$rank->uids[$m2]["C"]["T"]+
									$rank->uids[$m1]["A"]["T"]+$rank->uids[$m2]["A"]["T"]));
	}
}
