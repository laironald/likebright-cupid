<?php
require('/home/ron/project-x/www/smarty/libs/Smarty.class.php');
$smarty = new Smarty();

//$smarty->force_compile = true;

$smarty->setTemplateDir('/home/ron/project-x/www/smarty/templates');
$smarty->setCompileDir( '/home/ron/project-x/www/smarty/templates_c');
$smarty->setCacheDir(   '/home/ron/project-x/www/smarty/cache');
$smarty->setConfigDir(  '/home/ron/project-x/www/smarty/configs');

$smarty->testInstall();

$smarty->debugging = true;
$smarty->caching = true;
$smarty->cache_lifetime = 120;

$smarty->assign("Name","Fred Irving Johnathan Bradley Peppergill",true);
$smarty->assign("FirstName",array("John","Mary","James","Henry"));
$smarty->assign("LastName",array("Doe","Smith","Johnson","Case"));
$smarty->assign("Class",array(array("A","B","C","D"), array("E", "F", "G", "H"),
	  array("I", "J", "K", "L"), array("M", "N", "O", "P")));

$smarty->assign("contacts", array(array("phone" => "1", "fax" => "2", "cell" => "3"),
	  array("phone" => "555-4444", "fax" => "555-3333", "cell" => "760-1234")));

$smarty->assign("option_values", array("NY","NE","KS","IA","OK","TX"));
$smarty->assign("option_output", array("New York","Nebraska","Kansas","Iowa","Oklahoma","Texas"));
$smarty->assign("option_selected", "NE");

$smarty->display('index.tpl');
?>
