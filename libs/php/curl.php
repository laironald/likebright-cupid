<?php

require_once("/var/www/cupid_config.php");
require_once("oop.php");

function using_ie() { 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
    $ub = False; 
    if(preg_match('/MSIE/i',$u_agent)) 
    { 
        $ub = True; 
    } 
    
    return $ub; 
} 
function get_db_conn() {
	$conn = @mysql_connect($GLOBALS['db_ip'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
	@mysql_select_db($GLOBALS['db_name'], $conn);
	return $conn;
}
function isAssoc ($arr) {
	return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
}
function resUser(&$array, $res) {
	while ($data = mysql_fetch_assoc($res)) {
		$data["name"] = json_decode($data["name"], true);
		$data["name"] = $data["name"]["name"];			
		$array[] = $data;
	}
}

function getFriends($uid, $degree="1", $inc=true) {
	global $session;
	$mc = new Memcache2;
	$mc->connect('localhost', 11211);
	
	if ($degree=="2")
		$mckey = "{$uid}|friends|2|{($inc)?1:0}_Array";
	else
		$mckey = "{$uid}|friends|1|_Array";
	$mcval = $mc->toggle($mckey);

	//$mc->flush();
	if ($mcval != false) {
		$friends = json_decode($mcval, true);
	} else {
		$conn = get_db_conn();
		$friends = array();
		$oauth = $session['access_token'];
		$fList = curling(array("{$uid}|friends" => "https://graph.facebook.com/me/friends?access_token={$oauth}"), $force=true);
		$fList = $fList["{$uid}|friends"];
		foreach($fList["data"] as $friend) $friends[] = $friend["id"];
		
		if ($degree=="2") {
			$in = "'".implode("','", $friends)."'";	
			$friend2 = array();
			if ($inc)
				$res = mysql_query("SELECT distinct(fid) as fid FROM cupidFriends WHERE uid in ({$in}) AND fid!='{$uid}'", $conn);
			else
				$res = mysql_query("SELECT distinct(fid) as fid FROM cupidFriends WHERE uid in ({$in}, '{$uid}') AND fid!='{$uid}'", $conn);
			while ($datum = mysql_fetch_assoc($res)) {
				if ($inc or (!$inc and !in_array($datum["fid"], $friends)))
					$friend2[] = $datum["fid"];
			}
			$friends = $friend2;
		}		
		$mc->toggle($mckey, json_encode($friends), 12*60*60);
	}
	return $friends;
}

/***************************************************************/

function checkDB($urls, $return) {
	$retUrl = array();
	$html = array();
	$total = count($urls);
	$keys = array_keys($urls);
	$maxconn = 250;
	$conn = get_db_conn();
	for($x=0; $x<ceil($total/$maxconn); $x++)
	{
		$num_conn = min($total-$x*$maxconn, $maxconn);
		$ins = array();
		$ret = array();
		for($y=0; $y<$num_conn; $y++) 
			$ins[] = $keys[$y+$x*$maxconn];

		$res = mysql_query(sprintf("select keycat, value, updated from json where keycat in('%s')", implode("', '", $ins)), $conn);
		while ($datum = mysql_fetch_assoc($res)) {
			$ret[$datum["keycat"]] = array("value" => $datum["value"], "updated" => $datum["updated"]);
		}
		
		foreach($ins as $keycat)
			//Was key found?
			if (array_key_exists($keycat, $ret))
			{
				if (time() > $ret[$keycat]["updated"])
					$retUrl[$keycat] = $urls[$keycat];
				elseif ($return)
					$html[$keycat] = json_decode($ret[$keycat]["value"], true, 512);
			}
			else
				$retUrl[$keycat] = $urls[$keycat];
	}
	return array("urls"=>$retUrl, "html"=>$html);
}

/*
	CURL FUNCTION
*/
function curling($urls, $force=false, $return=true, $maxconn=50, $usleep=100000) {
	if ($force) {
		$html = array();
	}
	else {
		$retCheck = checkDB($urls, $return);
		$urls = $retCheck["urls"];
		$html = $retCheck["html"];
	}
	$total = count($urls);
	//echo $total;
	$keys = array_keys($urls);
	$conn = get_db_conn();

	for($x=0; $x<ceil($total/$maxconn); $x++) 
	{
		$query = array();
		$curl = array();
		$mh = curl_multi_init();
		$num_conn = min($total-$x*$maxconn, $maxconn);
		for($y=0; $y<$num_conn; $y++) 
		{
			$ch = curl_init();
			
			if (isAssoc($urls[$keys[$y+$x*$maxconn]]))
				curl_setopt_array($ch,
					array(CURLOPT_URL => $urls[$keys[$y+$x*$maxconn]]["url"],
						  CURLOPT_HEADER => 0,
						  CURLOPT_CONNECTTIMEOUT => 60,
						  CURLOPT_TIMEOUT => 60,
						  CURLOPT_RETURNTRANSFER => 1,
						  CURLOPT_POST => 1, 
						  CURLOPT_POSTFIELDS => $urls[$keys[$y+$x*$maxconn]]["params"]));
			else
				curl_setopt_array($ch,
					array(CURLOPT_URL => $urls[$keys[$y+$x*$maxconn]],
						  CURLOPT_HEADER => 0,
						  CURLOPT_CONNECTTIMEOUT => 60,
						  CURLOPT_TIMEOUT => 60,
						  CURLOPT_RETURNTRANSFER => 1));
			
			curl_multi_add_handle($mh, $ch);
			$curl[] = $ch;
		}
		do
		{
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		while ($active && $mrc == CURLM_OK) 
			if (curl_multi_select($mh) != -1) 
				do 
				{
				    $mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		for($y=0; $y<$num_conn; $y++) 
		{
			curl_multi_remove_handle ($mh, $curl[$y]);
			//$html[$keys[$y+$x*$maxconn]] = curl_multi_getcontent ($curl[$y]);
			$keyv = $keys[$y+$x*$maxconn];
			$keyd = explode("|", $keyv);
			$rest = curl_multi_getcontent ($curl[$y]);
			$rest = preg_replace('/:(\d+)/', ':"${1}"', $rest);
			$html[$keyv] = json_decode($rest, true, 512);

			if ($html[$keyv]==false)
				return $html;
			if (array_key_exists("error", $html[$keyv]))
				return $html;

			$updateTxt = "";
			if (in_array($keyd[1], array("likes", "checkins"))) {
				if (isset($html[$keyv]["data"][0]["created_time"]))
					$updateTxt = $html[$keyv]["data"][0]["created_time"];
			} else {
				if (isset($html[$keyv]["updated_time"]))
					$updateTxt = $html[$keyv]["updated_time"];
			}
			
			$query[] = sprintf("('%s', '%s', '%s', '%s', %s, %s, '%s')", 
				mysql_real_escape_string($keyd[0]), 
				mysql_real_escape_string($keyd[1]), 
				mysql_real_escape_string($keyv), 
				mysql_real_escape_string($rest), 
				time(),
				time()+cache_date($updateTxt),
				$updateTxt);
			/*
			$query[] = sprintf("('%s', '%s', '%s', '%s', '%s')", 
				mysql_real_escape_string($keyd[0]), 
				mysql_real_escape_string($keyd[1]), 
				mysql_real_escape_string($keyv), 
				mysql_real_escape_string($rest), 
				$updateTxt);
			*/
			
			parseJSON($keyv, $html[$keyv]);
		}	
		curl_multi_close($mh);
		mysql_query(sprintf("REPLACE INTO json (uid, category, keycat, value, created, updated, updateTxt) VALUES %s", implode(", ", $query)), $conn);
		//mysql_query(sprintf("REPLACE INTO json (uid, category, keycat, value, updateTxt) VALUES %s", implode(", ", $query)), $conn);
		if ($usleep > 0)
			usleep($usleep);
	}		
	if ($return)
		return $html;
}
function parseJSON($k, $v) {
	$conn = get_db_conn();
	$keyd = explode("|", $k);
	
	if (!is_array($v))
		$v = json_decode($v, true, 512);

	$friends = array();
	$likes = array();
	$lids = array();
	$profiles = array();

	$insert = array();
	if ($keyd[1]=="friends")
	{
		mysql_query("DELETE FROM friends WHERE uid='{$keyd[0]}'", $conn);
		foreach($v["data"] as $friend)
			$friends[] = sprintf("('{$keyd[0]}', '{$friend["id"]}', '%s')", mysql_real_escape_string($friend["name"]));
	} 
	else if ($keyd[1]=="likes")
	{
		foreach($v["data"] as $like) 
		{
			//$d1 = new DateTime($like["created_time"]);
			$d1 = strtotime($like["created_time"]);
			$likes[] = sprintf("('{$keyd[0]}', '{$like["id"]}', '{$keyd[1]}', '%s', '%s', {$d1})", mysql_real_escape_string($like["name"]), mysql_real_escape_string($like["category"]));
			$lids[] = $like["id"];
		}
	}
	else if ($keyd[1]=="profile") 
	{
		//$d1 = strtotime($v["updated_time"]);
		$d1 = strtotime($v["updated_time"]);

		$loc = explode(", ", $v["location"]["name"]);
		$profiles[] = sprintf("('{$v["id"]}', '%s', '%s', '%s', '%s', '%s', '%s', {$d1}, '{$v["location"]["id"]}', '%s', '%s')",
			mysql_real_escape_string($v["name"]),
			mysql_real_escape_string($v["first_name"]), 
			mysql_real_escape_string($v["last_name"]),
			mysql_real_escape_string($v["middle_name"]),
			mysql_real_escape_string($v["gender"]),
			mysql_real_escape_string($v["relationship_status"]),
			mysql_real_escape_string($v["location"]["name"]),
			mysql_real_escape_string($loc[1]));

		foreach(array('sports'=>'Sport', 'favorite_athletes'=>'Athlete', 'favorite_teams'=>'Sports Team', 'inspirational_people'=>'Person') as $kp=>$vp)
			if (isset($v[$kp])) {
				foreach($v[$kp] as $interest) {
					$likes[] = sprintf("('{$keyd[0]}', '{$interest["id"]}', '{$keyd[1]}', '%s', '%s', {$d1})", mysql_real_escape_string($interest["name"]), mysql_real_escape_string($vp));
					$lids[] = $interest["id"];
				}
			}
	}
	else if ($keyd[1]=="checkins")
		foreach($v["data"] as $checkin)
		{		
			//$d1 = new DateTime($checkin["created_time"]);
			$d1 = strtotime($checkin["created_time"]);
			$likes[] = sprintf("('{$keyd[0]}', '{$checkin["place"]["id"]}', '{$keyd[1]}', '%s', 'Checkin', {$d1})", mysql_real_escape_string($checkin["place"]["name"]));
			$lids[] = $checkin["place"]["id"];
		}
	else if ($keyd[1]=="fql_profile") 
	{
		$fqli = array();
		mysql_query("DELETE FROM cupidFriends WHERE uid='{$keyd[0]}'", $conn);
		foreach($v as $fql)
			if ($keyd[0]!=$fql["uid"])
				$fqli[] = sprintf("('{$keyd[0]}', '{$fql["uid"]}', '{$fql["sex"]}', '{$fql["pic_square"]}', '%s', '%s', '%s')", 
					mysql_real_escape_string($fql["relationship_status"]),
					mysql_real_escape_string(json_encode(array("first_name"=>$fql["first_name"], "middle_name"=>$fql["middle_name"], "last_name"=>$fql["last_name"],	"name"=>$fql["name"]))),
					mysql_real_escape_string(json_encode($fql)));
			else {
				mysql_query("INSERT IGNORE INTO cupidUser (uid) VALUES ('{$keyd[0]}')", $conn);
				mysql_query(sprintf("UPDATE cupidUser 
										SET email='{$fql["email"]}',
											sex='{$fql["sex"]}', 
											pic='{$fql["pic_square"]}', 
											status='%s', name='%s', json='%s' 
									  WHERE uid='{$keyd[0]}'",
					mysql_real_escape_string($fql["relationship_status"]),
					mysql_real_escape_string(json_encode(array("first_name"=>$fql["first_name"], "middle_name"=>$fql["middle_name"], "last_name"=>$fql["last_name"],	"name"=>$fql["name"]))),
					mysql_real_escape_string(json_encode($fql))), $conn);
			}

		mysql_query(sprintf("INSERT IGNORE INTO cupidFriends (uid, fid, sex, pic, status, name, json) VALUES %s", implode(", ", $fqli)), $conn);
	}
	else
		return;

	if (count($friends) > 0)
		mysql_query(sprintf("INSERT IGNORE INTO friends (uid, fid, name) VALUES %s", implode(", ", $friends)), $conn);
	if (count($likes) > 0 and count($lids)>0)
	{
		mysql_query(sprintf("INSERT IGNORE INTO liketory (uid, lid, field, name, category, created) VALUES %s", implode(", ", $likes)), $conn);
		mysql_query("DELETE FROM likes WHERE uid='{$keyd[0]}' and field='{$keyd[1]}'", $conn);
		mysql_query(sprintf("INSERT IGNORE INTO likes (uid, lid, field, name, category, created) VALUES %s", implode(", ", $likes)), $conn);
		if (mysql_insert_id() > 0) 
		{
			mysql_query("UPDATE  likes a, friends b
							SET  a.friends=concat_ws(',', a.friends, b.uid) 
						  WHERE  a.uid=b.fid and (a.friends not like concat('%', b.uid, '%') or a.friends is null) and a.uid='{$keyd[0]}';", $conn);
			mysql_query("UPDATE  likes a, profile b 
							SET  a.gender=b.gender, a.status=b.status, a.uname=b.name, a.location=b.location, a.state=b.state,
							     a.uname2=concat_ws(' ', b.fname, concat(SUBSTR(b.lname, 1, 1), '.')) 
						  WHERE  a.uid=b.uid and a.uid='{$keyd[0]}';", $conn);
			mysql_query(sprintf("UPDATE  likes a, (select lid, count(*) as cnt from likes where lid in ('%s') group by lid) b 
									SET  a.lcount = 1 - ln(b.cnt)*0.01  WHERE  a.lid = b.lid;", implode("','", $lids)), $conn);
		}
	}
	if (count($profiles) > 0)
		mysql_query(sprintf("REPLACE INTO profile (uid, name, fname, lname, mname, gender, status, updated, locid, location, state) VALUES %s", implode(", ", $profiles)), $conn);
}
//THIS IS A LOAD ALL DATA FUNCTION
function processAll() {
	$conn = get_db_conn();
	$res = mysql_query("SELECT keycat, value FROM json", $conn);
	$keys = array();
	$types = array();
	$checkins = array();
	$likes = array();
		
	while ($datum = mysql_fetch_assoc($res))
	{
		$k = $datum['keycat'];
		$v = json_decode($datum['value'], true);
		parseJSON($k, $v, true);
		$keyd = explode("|", $k);
		if ($types[$keyd[1]]<0) /* modify this number */
		{
			$types[$keyd[1]]=$types[$keyd[1]]+1;
			print $keyd[1]."<br/>";
			var_dump($v);
			print "<hr/>";
		}		
		if ($keyd[1]=="profile") 
		{
			foreach(array_keys($v) as $ak)
				$keys[$ak]=$keys[$ak]+1;
			$pkey = $_GET['profile_key'];
			if (isset($v[$pkey]))
			{
				print_r($v[$pkey]);
				print " | ";
			}
			/*
				meh:
				  [locale] => 2042 [updated_time] => 2040 [quotes] => 580 [significant_other] => 374 [meeting_for] => 6 [timezone] => 2 [verified] => 2 
				  [languages] => 159 [hometown] => 2 [work] => 1 [education] => 1 [interested_in] => 6 
				  
				yeah yeah!
				  [id] => 2042 [name] => 2042 [first_name] => 2042 [last_name] => 2042 [link] => 2042 [gender] => 1984 
				  [middle_name] => 189 [relationship_status] => 697 [email] => 1

				  [location] => 1292 (id, name)

				likes:				  
				 [sports] => 30, [favorite_athletes] => 45, [favorite_teams] => 58, [inspirational_people] => 23 
				 sports => Sport -- id, name
				 favorite_athletes => Athlete
				 favorite_teams => Professional Sports Team
				 inspirational_people => Person
			*/
		}
		if ($keyd[1]=="checkins") 
			$checkins[count($v["data"])] = $checkins[count($v["data"])] + 1;
		if ($keyd[1]=="likes") {
			$likes[count($v["data"])] = $likes[count($v["data"])] + 1;
			if (count($v["data"]) > 500)
				print $k;
		}
	}
	print "<hr/>";
	print_r($keys);
	print "<hr/>";
	print_r($checkins);
	print "<hr/>";
	print_r($likes);
	print "<hr/>";
}

/*************************************/

function cache_date($time) {
	if ($time=="")
		return 15*24*60*60;
	/*
		$d1 = new DateTime($time);
		$d2 = new DateTime('now');
		$days = intval($d1->diff($d2)->format("%a"));
	*/
	$days = strtotime($time)-time();
	
	if ($days<=2*24*60*60) 
		return 24*60*60;
	elseif ($days<=7*24*60*60)
		return 4*24*60*60;
	elseif ($days<=30*24*60*60)
		return 10*24*60*60;
	else
		return 15*24*60*60;
}
function friend_fetch($var, $friends=null, $num=null) {
	global $uid;
	global $oauth;
	if ($friends==null)
	{
		$friends = curling(array("{$uid}|friends" => "https://graph.facebook.com/me/friends?access_token={$oauth}"));
		$friends = $friends["{$uid}|friends"];
	}
	$url = array();
	$var_full = ($var=="profile")?"" :$var;
	
	if ($num != null)
		shuffle($friends["data"]);
	
	$cnt = 1;
	foreach($friends["data"] as $friend) 
	{
		if ($var == "load") {
			$url["{$friend['id']}|profile"] = "https://graph.facebook.com/{$friend['id']}/?access_token={$oauth}";
			$url["{$friend['id']}|likes"] = "https://graph.facebook.com/{$friend['id']}/likes?access_token={$oauth}";
		} else
			$url["{$friend['id']}|{$var}"] = "https://graph.facebook.com/{$friend['id']}/{$var_full}?access_token={$oauth}";
		$cnt++;
		if ($num != null && $num < $cnt)
			break;
	}
	return curling($url);
}
function get_user($uid, $params) {
	$assoc = isAssoc($params);
	$keys = ($assoc)?array_keys($params) :$params;
	$fields = implode(", ", $keys);
	$conn = get_db_conn();
	$user = @mysql_fetch_assoc(mysql_query("SELECT {$fields}, p_checkin, p_email, p_relationship, updated FROM user WHERE uid='{$uid}'", $conn));
	$first = false;
	
	if ($assoc)
	{
		$values = array_values($params);
		$values = "'".implode("', '", $values)."'";
		if (!is_array($user))
		{
			mysql_query("INSERT IGNORE INTO user (uid, {$fields}) VALUES ('{$uid}', {$values})", $conn);
			$first = true;
		}
		else
		{
			$update = array();
			foreach($params as $k=>$v)
			{
				if ($user[$k]!=$v)
				{
					$update[] = "{$k}='{$v}'";
				}
			}
			if (count($update)>0)
			{
				$update = implode(", ", $update);
				mysql_query("UPDATE user SET {$update}, updated={time()} WHERE uid='{$uid}'", $conn);
			}
		}
	}
	$user = @mysql_fetch_assoc(mysql_query("SELECT {$fields}, updated FROM user WHERE uid='{$uid}'", $conn));
	$user["first"] = $first;
	return $user;
}

/************************/
function mysql_assoc($query, $filter=null, $array=null) {
	$conn = get_db_conn();
	$return = array();
	$res = mysql_query($query, $conn);
	while ($datum = mysql_fetch_assoc($res))
	{
		if ($array != null)
			$datum[$array] = explode(",", $datum[$array]);
		$return[] = ($filter == null)?$datum: $datum[$filter];
	}
	return $return;
}
/***********************/
function usrLoaded($uid) {
	/* Return the status! */
	$cTime = time();
	$conn = get_db_conn();
	$n = mysql_fetch_array(mysql_query("select count(*) from friends as a inner join json as b on a.fid=b.uid and a.uid={$uid} and b.category in ('likes', 'profile') where b.updated>={$cTime};", $conn));
	$d = mysql_fetch_array(mysql_query("select count(*) from friends as a inner join json as b on a.fid=b.uid and a.uid={$uid} and b.category in ('likes', 'profile');", $conn));
	return $n[0]/$d[0];
}

function match_tops($friends=NULL, $sex="male", $limit=5, $status=NULL) {
	global $uid;
	$conn = get_db_conn();
	$myFriends = getFriends($uid);
	if ($friends == NULL)
		$friends = getFriends($uid, $_GET["degree"], false);
	$in = "'".implode("','", $friends)."'";	
	
	if ($status==NULL)
		$status = $_GET["status"];
	
	if ($status=="x")
		//$status = "status in ('Single', '')";
		$status = "status in ('')";
	else
		$status = "status='Single'";
	
	//$res = mysql_query("SELECT uid, name, pic FROM cupidRankAll WHERE sex='{$sex}' AND uid in ({$in}) AND P>=50 AND {$status} ORDER BY R DESC LIMIT {$limit}", $conn);
	$res = mysql_query("SELECT uid, name, pic FROM cupidRankAll WHERE sex='{$sex}' AND uid in ({$in}) AND {$status} ORDER BY R DESC LIMIT {$limit}", $conn);
	$ui = array();
	while ($datum = mysql_fetch_assoc($res)) {
		$n = json_decode($datum["name"], true);
		$ui[] = array("name"=>$n["name"], "uid"=>$datum["uid"], "pic"=>$datum["pic"], "matchlist"=>in_array($datum["uid"], $myFriends));
	}
	return $ui;	
}
function match_api($num=50, $login=true) {
	global $uid;
	global $conn;
	global $oauth;
	
	$frd = array();
	$friends = array();
	$ageIt = function($birthday) {
		return floor((time() - strtotime($birthday))/31556926);
	};

	/* 
		//GRAB AND PARSE ALL FQL PROFILES
		$res = mysql_query("SELECT keycat, value FROM json WHERE category='fql_profile'", $conn);
		while ($datum = mysql_fetch_array($res))
			parseJSON($datum["keycat"], $datum["value"]);
	*/
	
	if ($login) {
		$friends = getFriends($uid);
		$in = "'".implode("','", $friends)."'";	
		
		//friends!  friends!
		$frd = mysql_fetch_assoc(mysql_query("SELECT group_concat(uid) AS uid FROM json WHERE category='fql_profile' and uid in ({$in})", $conn));
		$frd = explode(",", $frd["uid"]);

		$val = curling(array(
			"{$uid}|fql_profile"=>  
				array("url"=>"https://api.facebook.com/method/fql.query", 
					"params"=>array("query"=>"select  uid,email,first_name,middle_name,last_name,name,pic_square,pic_big,affiliations,
													  birthday_date,sex,meeting_sex,relationship_status,current_location,family
												from  user 
											   where  uid in ({$in}, '{$uid}')", 
					"access_token"=>$oauth, "format"=>"json"))
			), $force=false);
		
		if ($uid=="2203233") {		
			/*
				$val2 = curling(array(
					"{$uid}|fql_minilike"=>  
						array("url"=>"https://api.facebook.com/method/fql.query", 
							"params"=>array("query"=>"select  uid,name,relationship_status
														from  user 
													   where  uid in ('405813')", 
							"access_token"=>$oauth, "format"=>"json"))
				), $force=true);
				var_dump($oauth);
				var_dump($val2["{$uid}|fql_minilike"]);
			*/
		}
		
		if ($_GET["degree"]==2) {
			$res = mysql_query("SELECT keycat, value FROM json WHERE category='fql_profile' AND uid in ({$in})", $conn);
			while ($datum = mysql_fetch_array($res))
				$val["{$uid}|fql_profile"] = array_merge($val["{$uid}|fql_profile"], json_decode($datum["value"], true, 512));					
		}
		$val["data"] = $val["{$uid}|fql_profile"];
	} else {		
		//adding MemCache here to speed up loads
		$val["data"] = array();
		$mc = new Memcache2;
		$mc->connect('localhost', 11211);
		$mckey = "match_api|splash";
		$mcval = $mc->toggle($mckey);
		if ($mcval != false) {
			$val["data"] = json_decode($mcval, true);
		} else {
			$res = mysql_query("SELECT keycat, value FROM json WHERE category='fql_profile' ORDER BY rand() LIMIT 2", $conn);
			while ($data=mysql_fetch_assoc($res))
				$val["data"] = array_merge($val["data"], json_decode($data["value"], true, 512));
			$mc->toggle($mckey, json_encode($val["data"]), 7200);
		}	
	}

	$lock = false;
	
	//IF USER "LOCKS" uid
	if ($_GET["uid"]!="") {
		$res = mysql_query("SELECT fid, sex FROM cupidFriends WHERE uid='{$uid}' AND fid='{$_GET["uid"]}'", $conn);
		if (mysql_num_rows($res) > 0)
			$lock = mysql_fetch_assoc($res);
	}
	
	$loc = array("male"=>array("male"=>0, "female"=>0), "female"=>array("male"=>0, "female"=>0));
	$gdr = array("male", "female");

	$meet = array();
	$yall = array();
	foreach($val["data"] as $v) {
		if (!array_key_exists($v["uid"], $yall)) {
			$yall[$v["uid"]] = $v;

			if (substr_count($v["birthday_date"], "/") == 2)
				$yall[$v["uid"]]["age"] = $ageIt($v["birthday_date"]);				

			if ($v["family"]!=null)
				foreach($v["family"] as $famid)
					$yall[$v["uid"]]["fam"][] = $famid["uid"];
			
			if (($v["relationship_status"]=="Single" || 
				($_GET['status']=="x" && $v["relationship_status"]==null)) && $v["sex"]!="") {
				$cR = array("uid"=>$v["uid"], "name"=>(($login)?$v["name"]:"{$v["first_name"]} {$v["last_name"][0]}."), "pic"=>$v["pic_square"], "picB"=>$v["pic_big"]);
				 				
				if ($lock) {
					if (($v["sex"]==$lock["sex"] && $v["sex"]=="male" && $v["uid"]==$lock["fid"]) || $v["sex"]=="female")
						$meet["male"][$v["sex"]][] = $cR;
					if (($v["sex"]==$lock["sex"] && $v["sex"]=="female" && $v["uid"]==$lock["fid"]) || $v["sex"]=="male")
						$meet["female"][$v["sex"]][] = $cR;
				} else {
					if ($login) {
						//This guarantees that the people that show up as base matches will be people you recognize..
						if (in_array($v["uid"], $frd)) { //Give friend a boost in occurence
							for($i=0; $i<50; $i++)
								$meet[$v["sex"]][$v["sex"]][] = $cR;
						} else {
							if (($v["sex"]=="male" && in_array($cR["uid"], $friends)) || $v["sex"]=="female")
								$meet["male"][$v["sex"]][] = $cR;
							if (($v["sex"]=="female" && in_array($cR["uid"], $friends)) || $v["sex"]=="male")
								$meet["female"][$v["sex"]][] = $cR;
						}
					} else {
						$meet["male"][$v["sex"]][] = $cR;
						$meet["female"][$v["sex"]][] = $cR;
					}
				}
			}		
		}
	}

	if (count($meet["male"]["female"])<2 or count($meet["female"]["male"])<2)
		return;
	if (!isset($meet["male"]["male"]) and !isset($meet["female"]["female"])) {
		return;
	} else {
		@shuffle($meet["male"]["male"]);
		@shuffle($meet["male"]["female"]);
		@shuffle($meet["female"]["male"]);
		@shuffle($meet["female"]["female"]);
	}


	//matching algorithm starts here
	
	$match = array();
	for($i=0; $i<$num; $i++) {
		$cMatch = array();
		
		if ($lock) {
			$cG = $lock["sex"];
			$nG = ($lock["sex"]=="male")?"female" :"male";
		} else {
			if (in_array($_GET["gender"], array("m", "f"))) {
				$cG = ($_GET["gender"]=="f")?"female":"male";
				$nG = ($_GET["gender"]=="m")?"female":"male";
			} else {	
				$rG = mt_rand(0, 1);
				$cG = $gdr[$rG];
				$nG = $gdr[1-$rG];
			}
		}
		if (count($meet[$cG][$cG])==0) {
			$i--;
			continue;
		}
		
		if ($loc[$cG][$cG]+1>=count($meet[$cG][$cG])) {
			$loc[$cG][$cG] = 0;
			shuffle($meet[$cG][$cG]);
		}
		$cMatch["c"] = $meet[$cG][$cG][$loc[$cG][$cG]];
		
		if ($loc[$cG][$nG]+2>=count($meet[$cG][$nG])) {
			$loc[$cG][$nG] = 0;
			shuffle($meet[$cG][$nG]);
		}
		$cMatch["m1"] = $meet[$cG][$nG][$loc[$cG][$nG]];
		$cMatch["m2"] = $meet[$cG][$nG][$loc[$cG][$nG]+1];

		$loc[$cG][$cG]++;
		$loc[$cG][$nG]+=2;
		
		$skip = false;
		
		// age check
		//if c<18 and other age unclear
		if ($yall[$cMatch["c"]["uid"]]["age"]>0 && $yall[$cMatch["c"]["uid"]]["age"]<18 && $yall[$cMatch["m1"]["uid"]]["age"]*$yall[$cMatch["m2"]["uid"]]["age"]==0)
			$skip = true;
		//if c unclear and other<18
		elseif ($yall[$cMatch["c"]["uid"]]["age"]==0 && 
				(($yall[$cMatch["m1"]["uid"]]["age"]<18 && $yall[$cMatch["m1"]["uid"]]["age"]>0) || 
				 ($yall[$cMatch["m2"]["uid"]]["age"]<18 && $yall[$cMatch["m2"]["uid"]]["age"]>0)))
			$skip = true;
		//if c<18 and other age/2+7>c
		elseif ($yall[$cMatch["c"]["uid"]]["age"]<18 && $yall[$cMatch["c"]["uid"]]["age"]>0 &&
			   (($yall[$cMatch["m1"]["uid"]]["age"]>0 && 
				 max($yall[$cMatch["c"]["uid"]]["age"], $yall[$cMatch["m1"]["uid"]]["age"])/2+7>min($yall[$cMatch["c"]["uid"]]["age"], $yall[$cMatch["m1"]["uid"]]["age"])) ||
			    ($yall[$cMatch["m2"]["uid"]]["age"]>0 && 
				 max($yall[$cMatch["c"]["uid"]]["age"], $yall[$cMatch["m2"]["uid"]]["age"])/2+7>min($yall[$cMatch["c"]["uid"]]["age"], $yall[$cMatch["m2"]["uid"]]["age"]))))
			$skip = true;
		//if c>18 and c/2+7>other age
		elseif ($yall[$cMatch["c"]["uid"]]["age"]>=18 && 
				(($yall[$cMatch["m1"]["uid"]]["age"]>0 && $yall[$cMatch["m1"]["uid"]]["age"]<18 && $yall[$cMatch["c"]["uid"]]["age"]/2-7>$yall[$cMatch["m1"]["uid"]]["age"]) || 
				 ($yall[$cMatch["m2"]["uid"]]["age"]>0 && $yall[$cMatch["m2"]["uid"]]["age"]<18 && $yall[$cMatch["c"]["uid"]]["age"]/2-7>$yall[$cMatch["m2"]["uid"]]["age"])))
			$skip = true;
		
		//get rid of "shady" matches
		// - same last name
		// - same family
		// - same person!?
		if ($yall[$cMatch["c"]["uid"]]["last_name"] == $yall[$cMatch["m1"]["uid"]]["last_name"] ||
			$yall[$cMatch["c"]["uid"]]["last_name"] == $yall[$cMatch["m2"]["uid"]]["last_name"] ||
			@in_array($cMatch["c"]["uid"], $yall[$cMatch["m1"]["uid"]]["fam"]) || 
			@in_array($cMatch["c"]["uid"], $yall[$cMatch["m2"]["uid"]]["fam"]) ||
			$cMatch["m1"]["uid"]==$cMatch["m2"]["uid"] ||
			$cMatch["c"]["uid"]==$cMatch["m1"]["uid"] ||
			$cMatch["c"]["uid"]==$cMatch["m2"]["uid"] ||
			$skip)
			$i--;
		else
			$match[] = $cMatch;
	}	
	
	return $match;
}
function api($uid=null, $params=null) {
	global $fbook;
	$conn = get_db_conn();
	$limit = 75;
	$table = "listed";
	$sList = array();
	
	$mc = new Memcache2;
	$mc->connect('localhost', 11211);
	$mckey = "{$uid}|{$params["status"]}|{$params["loc"]}|{$params["gender"]}|{$params["degree"]}";
	$mcval = $mc->toggle($mckey);

	if ($mcval != false) {
		$api = json_decode($mcval, true);
	} else {
		//default display

		function api_attributes($datum, $uid, $netw, $fList, $fbook) {
			$datum["lids"] = explode(",", $datum["lids"]);
			if ($datum["lcount"] > 6)
				$datum["lids"] = array_slice($datum["lids"], 0, 5);			
			
			$dFriends = explode(",", $datum["friends"]);
			shuffle($datum["lids"]);
			if ($uid!=null) {
				$netw = array_intersect($fList, $dFriends);
				$datum["degree"] = (count($netw)>0)?((in_array($uid, $netw))?1: 2):0;
				if ($datum["degree"]==0 and in_array($datum["uid"], $fList))
					$datum["degree"]=1;
			}
			$datum["name"] = ($datum["degree"]>0)?$datum["uname"]:$datum["uname2"];		

			if ($uid==null)
				$datum["url"] = $fbook["login"];
			else
				$datum["url"] = "profile.php?q={$datum["uid"]}";
			
			unset($datum["uname"]);
			unset($datum["uname2"]);
			return $datum;
		}

		if ($uid==null)
		{
			mysql_query("CREATE TEMPORARY TABLE friend0 AS SELECT distinct fid FROM friends ORDER BY rand() limit 40", $conn);
			mysql_query("CREATE INDEX fid ON friend0 (fid);", $conn);
			mysql_query("CREATE TEMPORARY TABLE listed AS SELECT b.* FROM friend0 AS a INNER JOIN likes AS b ON a.fid=b.uid;", $conn);
			mysql_query("CREATE INDEX uid ON listed (uid);", $conn);		
		}
		else
		{
			$fList = array($uid);
			foreach($fbook["me"]["friends"]["data"] as $friend) 
				$fList[] = $friend["id"];

			if ($params["status"]=="")
				$params["status"] = "a";

			$qStr = array("1=1");
			switch ($params["status"])
			{
				case "s":
					$qStr[] = "b.status in ('Single')";
					break;
				case "x":
					$qStr[] = "b.status in ('Single', '')";
					break;
			}
			switch ($params["loc"])
			{
				case "s":
					$qStr[] = "b.location in ('{$fbook["me"]["profile"]["location"]["name"]}')";
					break;
				case "x":
					$qStr[] = "b.state in ('{$fbook["me"]["profile"]["location"]["loc"][1]}')";
					break;
			}
			if (in_array($params["gender"], array("male", "female")))
				$qStr[] = "b.gender = '{$params["gender"]}'";

			#degree = 0, all
			#degree = 1, self
			#degree = 2, self + friends
			$nodeStr = "'".(($params["degree"]==1)?$uid :implode("', '", $fList))."'";
			$qStr = implode(" AND ", $qStr);

			$table = ($params["degree"]>0)?"list0":"listed";
			mysql_query("CREATE TEMPORARY TABLE listed AS SELECT b.* FROM likes AS a INNER JOIN likes AS b ON a.lid=b.lid AND a.uid in ('{$uid}') AND b.uid not in ('{$uid}') AND {$qStr}", $conn);
			mysql_query("CREATE INDEX uid ON listed (uid);", $conn);

			if ($params["degree"]>0) 
			{
				mysql_query("CREATE TEMPORARY TABLE friend0 AS SELECT distinct fid FROM friends WHERE uid in ({$nodeStr}) AND fid!='{$uid}'", $conn);
				mysql_query("CREATE INDEX fid ON friend0 (fid);", $conn);
				mysql_query("CREATE TEMPORARY TABLE list0 AS SELECT b.* FROM friend0 AS a INNER JOIN listed AS b ON a.fid=b.uid", $conn);
				mysql_query("CREATE INDEX uid ON list0 (uid);", $conn);
			}
			$api = array();
		}
		//$res = mysql_query("SELECT lid as uid, group_concat(uid) AS lids, gender, name as uname2, friends FROM list0 GROUP BY lid ORDER BY count(*) DESC LIMIT 100", $conn);
		
		$res = mysql_query("SELECT  uid, group_concat(lid) AS lids, count(*) AS lcount, gender, uname, uname2, friends 
							  FROM  {$table} 
						  GROUP BY  uid 
						  ORDER BY  sum(lcount) DESC 
						     LIMIT  {$limit}", $conn);
		while ($datum = mysql_fetch_assoc($res)) {
			$api[] = api_attributes($datum, $uid, $netw, $fList, $fbook);
			$sList[] = $datum["uid"];
		}

		//If less than limit and not default screen
		if ($uid != null)
		{
			$cnt = mysql_fetch_array(mysql_query("SELECT count(distinct uid) FROM {$table}", $conn));
			if ($cnt[0] < $limit)
			{
				$sListStr = implode("', '", $sList);
				if ($params["degree"]>0) {
					mysql_query("CREATE TEMPORARY TABLE extras AS 
									SELECT  b.*
									  FROM  friend0 AS a
								INNER JOIN  likes AS b
									    ON  a.fid=b.uid AND b.uid not in ('{$uid}', '{$sListStr}') AND {$qStr}", $conn);
				}
				else {
					mysql_query("CREATE TEMPORARY TABLE extras AS 
									SELECT  b.*
									  FROM  likes AS b
									 WHERE  b.uid not in ('{$uid}', '{$sListStr}') AND {$qStr}", $conn);
				}
				mysql_query("CREATE INDEX uid ON extras (uid);", $conn);
				$res = mysql_query("SELECT  uid, '' AS lids, 0 AS lcount, gender, uname, uname2, friends 
									  FROM  extras
								  GROUP BY  uid 
								  ORDER BY  rand() 
									 LIMIT  {$cnt[0]}", $conn);
				while ($datum = mysql_fetch_assoc($res))
					$api[] = api_attributes($datum, $uid, $netw, $fList, $fbook);			
			}
		}
		$mc->toggle($mckey, json_encode($api), ($uid==null)?18000:120);
	}
	//$mc->flush();
	return $api;
}