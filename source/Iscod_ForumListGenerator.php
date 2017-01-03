<?php
#**********************************************************************
#* This framework is  under the GNU License.
#*
#* All rights reserved
#*
#*This file is written by Bissan AUDEH
#*For information, feedback, questions, please contact bissana@gmail.com
#*
#*
#* version 23/1/2014
#* copyright Bissan AUDEH (bissana@gmail.com)
#***********************************************************************

require "Iscod_Library.php";
error_reporting(E_ERROR | E_PARSE);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Paris');
$outFile = "";
$siteName;
if (count($argv) >3)
{
	$proxy = $argv[1];
    $webPage = $argv[2];
    $forumsXpath = $argv[3];
    initialCurl($proxy);
	$xpath = getXpathHandler($webPage,0);
	$idsXpath =   $xpath->query($forumsXpath);
	$nbforums = $idsXpath->length;
	for ($i=0; $i<$nbforums ; $i++)
	{
			echo getValidAbsolutLink($idsXpath->item($i)->nodeValue,$webPage)."\n";
	}
}
else
{
	echo "Hello, This script needs the following input : ".PHP_EOL;
	echo "	-proxy".PHP_EOL."	- url of the page from which I will extract the list".PHP_EOL."	-Xpath of these URLS." .PHP_EOL;
	echo "----------------------------------------------".PHP_EOL;
	echo "Ex: php Iscod_ForumListGenerator.php \"proxy.xyz.fr:1111\" \"http://www.xyzxyz/forumdisplay.php\"  \"//*[starts-with(@id,'cat')]/td[2]/b/a\" " ;
	echo PHP_EOL."----------------------------------------------".PHP_EOL;
}


?>
