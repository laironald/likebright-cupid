<?php

require_once("curl.php");
header("Content-type: image/png");

$q = (($_GET["a"]=="")?"0":$_GET["a"]).",".(($_GET["b"]=="")?"0":$_GET["b"]).",".(($_GET["c"]=="")?"0":$_GET["c"]).",".(($_GET["d"]=="")?"0":$_GET["d"]);

$conn = get_db_conn();
$res = mysql_query("SELECT image FROM cupidImage WHERE uid='{$q}'", $conn);

if (mysql_num_rows($res) == 0) {
	exec("python ../py/image.py {$q}", $img);
	$res = mysql_query("SELECT image FROM cupidImage WHERE uid='{$q}'", $conn);
}
$data = mysql_fetch_assoc($res);
echo $data["image"];
