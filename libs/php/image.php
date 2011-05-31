<?php

require_once("curl.php");
header("Content-type: image/png");

$conn = get_db_conn();
$res = mysql_query("SELECT image FROM cupidImage WHERE uid='{$_GET["q"]}'", $conn);

if (mysql_num_rows($res) == 0) {
	exec("python ../py/image.py {$_GET["q"]}", $img);
	$res = mysql_query("SELECT image FROM cupidImage WHERE uid='{$_GET["q"]}'", $conn);
}
$data = mysql_fetch_assoc($res);
echo $data["image"];
