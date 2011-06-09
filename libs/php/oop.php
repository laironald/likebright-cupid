<?php

class Memcache2 extends Memcache {
	public function toggle($key, $value=false, $time=60) {
		$get = $this->get($key);
		if (!$get && $value !== false) {
			$compress = is_bool($value) || is_int($value) || is_float($value) ? false : MEMCACHE_COMPRESSED;
			$this->add($key, $value, $compress, $time);
		}
		return $this->get($key);		
	}
}

class elo {
	public $adj=1.0;
	public $cid;
	public $fid1;
	public $fid2;
	public $uids = array();
	public $table = "cupidRank";
	
	public function elo($uid, $cid, $fid1, $fid2) {
		$conn = get_db_conn();
		
		$this->uids[$fid1]["A"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);
		$this->uids[$fid1]["R"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);
		$this->uids[$fid1]["C"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);
		$this->uids[$fid2]["A"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);
		$this->uids[$fid2]["R"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);
		$this->uids[$fid2]["C"] = array("R"=>1500, "W"=>0, "L"=>0, "T"=>0, "P"=>0);

		//do we know this person?  give 50% credibility for each person we do NOT know
		if ($uid != 0) {
			$friends = getFriends($uid);
			$this->adj = (in_array($fid1, $friends)?1 :0.5) * (in_array($fid2, $friends)?1 :0.5);
		}
		if ($uid == $cid) {
			$this->table = "cupidRankM";
		}
		$table = $this->table;
		
		$query = "SELECT * FROM {$table} WHERE uid='{$cid}' and fid IN ('{$fid1}', '{$fid2}')";
		$res = mysql_query($query, $conn);
		while ($datum = mysql_fetch_assoc($res))
			$this->uids[$datum["fid"]]["C"] = $datum;		
		mysql_query("INSERT IGNORE INTO {$table} (uid, fid) VALUES ('{$cid}', '{$fid1}'), ('{$cid}', '{$fid2}')", $conn);

		$query = "SELECT * FROM {$table} WHERE fid='{$cid}' and uid IN ('{$fid1}', '{$fid2}')";
		$res = mysql_query($query, $conn);
		while ($datum = mysql_fetch_assoc($res))
			$this->uids[$datum["cid"]]["R"] = $datum;
		mysql_query("INSERT IGNORE INTO {$table} (uid, fid) VALUES ('{$fid1}', '{$cid}'), ('{$fid2}', '{$cid}')", $conn);
		
		$query = "SELECT * FROM cupidRankAll WHERE uid IN ('{$fid1}', '{$fid2}')";
		$res = mysql_query($query, $conn);
		while ($datum = mysql_fetch_assoc($res))
			$this->uids[$datum["uid"]]["A"] = $datum;
		mysql_query("INSERT IGNORE INTO cupidRankAll (uid) VALUES ('{$fid1}'), ('{$fid2}')", $conn);

		mysql_query("INSERT IGNORE INTO cupidRankAll (uid) VALUES ('{$uid}')", $conn);
		
		$this->uid = $uid;
		$this->cid = $cid;
		$this->fid1 = $fid1;
		$this->fid2 = $fid2;
	}
	public function rating($win) {
		$conn = get_db_conn();
		$cid = $this->cid;
		$fid1 = $this->fid1;
		$fid2 = $this->fid2;
		$uids = $this->uids;
		$table = $this->table;
		$uids[$fid1]["S"] = ($win == $fid1)?1 :0;
		$uids[$fid2]["S"] = ($win == $fid2)?1 :0;
		
		$this->calc("C", $uids);
		$this->calc("R", $uids);
		$this->calc("A", $uids);

		$lambda = function($id, $cid, $uids, $table) {
			global $conn;
			mysql_query("UPDATE  {$table} a, cupidFriends b
							SET  a.R={$uids[$id]["C"]["R"]}, a.W={$uids[$id]["C"]["W"]}, a.L={$uids[$id]["C"]["L"]}, a.T={$uids[$id]["C"]["T"]}, a.P={$uids[$id]["C"]["P"]}, a.sex=b.sex, a.name=b.name, a.status=b.status, a.pic=b.pic
						  WHERE  a.uid='{$cid}' AND a.fid='{$id}' AND a.fid=b.fid", $conn);
			mysql_query("UPDATE  {$table} a, cupidFriends b
							SET  a.R={$uids[$id]["R"]["R"]}, a.W={$uids[$id]["R"]["W"]}, a.L={$uids[$id]["R"]["L"]}, a.T={$uids[$id]["R"]["T"]}, a.P={$uids[$id]["R"]["P"]}, a.sex=b.sex, a.name=b.name, a.status=b.status, a.pic=b.pic
						  WHERE  a.uid='{$id}' AND a.fid='{$cid}' AND a.fid=b.fid", $conn);
			mysql_query("UPDATE  cupidRankAll a, cupidFriends b
							SET  a.R={$uids[$id]["A"]["R"]}, a.W={$uids[$id]["A"]["W"]}, a.L={$uids[$id]["A"]["L"]}, a.T={$uids[$id]["A"]["T"]}, a.P={$uids[$id]["A"]["P"]}, a.sex=b.sex, a.name=b.name, a.status=b.status, a.pic=b.pic
						  WHERE  a.uid='{$id}' AND a.uid=b.fid", $conn);
			mysql_query("UPDATE  {$table} SET R2=(3*R+{$uids[$id]["A"]["R"]})/4 WHERE fid in ('{$id}', '{$cid}')", $conn);
			
			//UPDATE VOTED COUNT
			$res = mysql_query("SELECT uid FROM cupidFriends WHERE fid='{$id}'", $conn);
			$uids = array();
			while($data = mysql_fetch_assoc($res))
				$uids[] = $data["uid"];
			$uids = "'".implode($uids, "', '")."'";
			mysql_query("UPDATE cupidUser SET voted=voted+1 WHERE uid in ({$uids})", $conn);
		};
		$lambda($fid1, $cid, $uids, $table);
		$lambda($fid2, $cid, $uids, $table);		

		//UPDATE MATCHED COUNT
		if ($table == "cupidRank") //if table is not self!
			mysql_query("UPDATE cupidUser SET matched=matched+1 WHERE uid='{$cid}'", $conn);		
		
		$this->uids = $uids;
	}
	public function skip() {
		$conn = get_db_conn();
		$uid = $this->uid;
		mysql_query("UPDATE  cupidUser
						SET  skipped = skipped + 1
					  WHERE  uid='{$uid}'", $conn);
	}
	public function pct() {
		$fid1 = $this->fid1;
		$fid2 = $this->fid2;		
		$uids = $this->uids;
		foreach(array("C", "R", "A") as $C) {
			$QA = pow(10, $uids[$fid1][$C]["R"]/400);
			$QB = pow(10, $uids[$fid2][$C]["R"]/400);
			$uids[$fid1][$C]["E"] = $QA / ($QA + $QB);
			$uids[$fid2][$C]["E"] = $QB / ($QA + $QB);
		}
		$this->uids = $uids;
	}
	
	private function calc($C="C", &$uids) {
		$fid1 = $this->fid1;
		$fid2 = $this->fid2;		

		$uids[$fid1][$C][$uids[$fid1]["S"]==1?"W":"L"]++;
		$uids[$fid2][$C][$uids[$fid2]["S"]==1?"W":"L"]++;
		$uids[$fid1][$C]["T"]=$uids[$fid1][$C]["W"]+$uids[$fid1][$C]["L"];
		$uids[$fid2][$C]["T"]=$uids[$fid2][$C]["W"]+$uids[$fid2][$C]["L"];
		$uids[$fid1][$C]["P"]=round(100*$uids[$fid1][$C]["W"]/$uids[$fid1][$C]["T"], 0);
		$uids[$fid2][$C]["P"]=round(100*$uids[$fid2][$C]["W"]/$uids[$fid2][$C]["T"], 0);
		
		$QA = pow(10, $uids[$fid1][$C]["R"]/400);
		$QB = pow(10, $uids[$fid2][$C]["R"]/400);
		$EA = $QA / ($QA + $QB);
		$EB = $QB / ($QA + $QB);
		
		//implement adjustable Rating floors?
		//Adjustment factor... do you actually know this person?  If not friend NO -- 25% credible?		
		if ($C=="C" && $this->uid==$this->cid)
			$adj = 1;
		else
			$adj = $this->adj;
		$uids[$fid1][$C]["R"] = round(max(100, $uids[$fid1][$C]["R"] + $adj * $this->KFactor($uids[$fid1][$C]["R"]) * ($uids[$fid1]["S"] - $EA)), 0);
		$uids[$fid2][$C]["R"] = round(max(100, $uids[$fid2][$C]["R"] + $adj * $this->KFactor($uids[$fid2][$C]["R"]) * ($uids[$fid2]["S"] - $EB)), 0);
	}
	//Internet KFactor
	private function KFactor($fid) {
		
		if ($rank < 2100)
			$K = 32;
		else if ($rank <= 2400)
			$K = 24;
		else
			$K = 16;
		return $K;
	}
}

class meCupid {
	public $scr;
	public $uid;
	public $user = array();
	public $friends;
	public $cntFriends;
	
	public function meCupid($uid, $scr=null, $data=null) {
		if ($data==null)
			$data = array();
		$conn = get_db_conn();
		$data["profile"] = mysql_fetch_assoc(mysql_query("SELECT name, sex, config, status, pic, matches FROM cupidUser WHERE uid='{$uid}'", $conn));
		$data["profile"]["name"] = json_decode($data["profile"]["name"], true);
		$data["profile"]["config"] = json_decode($data["profile"]["config"], true);
		if ($data["profile"]["config"]==null)
			$data["profile"]["config"] = array("0s"=>0, "0x"=>0, "1s"=>0, "1x"=>0, "2s"=>0, "2x"=>0);
		$get = getIt($_GET);
		if ($scr==null)
			$data["screen"] = $get["degree"].$get["status"];
		else
			$data["screen"] = $scr;
			
		if ($data["profile"]["config"][$data["screen"]]=="")
			$data["profile"]["config"][$data["screen"]]=0;
			
		$this->user = $data;
		$this->scr = $scr;
		$this->uid = $uid;
		$this->friends = getFriends($uid);
	}
	public function addCnt() {
		$conn = get_db_conn();
		$this->user["profile"]["config"][$this->scr]++;
		$this->user["profile"]["matches"]++;
		mysql_query(sprintf("UPDATE cupidUser SET matches=matches+1, config='%s' WHERE uid={$this->uid}", 
			json_encode($this->user["profile"]["config"])), $conn);		
	}
	public function cntFriends() {
		$friends = $this->friends;
		$in = "'".implode("','", $friends)."'";	
	
		$conn = get_db_conn();
		if ($_GET["secret"]!="" and ($uid=="211897" or $uid=="2203233"))
			$res = mysql_query("SELECT count(*) AS cnt FROM cupidUser", $conn);
		else
			$res = mysql_query("SELECT count(*) AS cnt FROM cupidUser WHERE uid in ({$in})", $conn);
		$cntFriends = mysql_fetch_assoc($res);
		$cntFriends = $cntFriends["cnt"];
		
		$this->cntFriends = $cntFriends;
		return $cntFriends;
	}
	public function top_matches($get=NULL, $uid=NULL, $LIMIT=6) {
		if ($uid==NULL)
			$uid = $this->uid;
			
		$mc = new Memcache2;
		$mc->connect('localhost', 11211);
		$mckey = "{$uid}|top_matches|{$get["status"]}|{$get["degree"]}|{$LIMIT}";
		$mcval = $mc->toggle($mckey);
		//$mc->flush();
		if ($mcval != false) {
			$match_your = json_decode($mcval, true);
		} else {
			$conn = get_db_conn();
			
			if ($get["status"]=="x")
				//$status = "status in ('Single', '')";
				$status = "status in ('')";
			elseif ($get["status"]=="s")
				$status = "status='Single'";
			else
				$status = "status not in ('Single', '')";

						
			if ($get["degree"]==0)
				$res = mysql_query("SELECT fid as uid, pic, name FROM cupidRank WHERE uid='{$uid}' AND P>50 AND {$status} ORDER BY R2 DESC LIMIT {$LIMIT}", $conn);
			else {
				$friends = getFriends($uid, $get["degree"], false);
				$in = "'".implode("','", $friends)."'";	
				$res = mysql_query("SELECT fid as uid, pic, name FROM cupidRank WHERE uid='{$uid}' AND fid in ({$in}) AND P>50 AND {$status} ORDER BY R2 DESC LIMIT {$LIMIT}", $conn);
			}
			$match_your = array();	
			resUser($match_your, $res);		
			$mc->toggle($mckey, json_encode($match_your), 15*60);
		}			
		return $match_your;
	}
}





class Curler {
	public function curling($urls, $force=false, $return=true, $maxconn=50, $usleep=100000)
	{
		if ($force) {
			$html = array();
		}
		else {
			$retCheck = checkDB($urls, $return);
			$urls = $retCheck["urls"];
			$html = $retCheck["html"];
		}
		$total = count($urls);
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
				curl_setopt_array($ch,
					array(CURLOPT_URL => $urls[$keys[$y+$x*$maxconn]],
						  CURLOPT_HEADER => 0,
						  CURLOPT_CONNECTTIMEOUT => 20,
						  CURLOPT_TIMEOUT => 20,
						  CURLOPT_RETURNTRANSFER => 1,
						  CURLOPT_POST => 1,
						  CURLOPT_POSTFIELDS => array()));
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
				$html[$keyv] = json_decode($rest, true);

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
}



