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

ini_set('memory_limit', '256M');
require "Iscod_Library.php";

#------------------------------------------------------------------------
#
# GET INPUT DATA
#
#------------------------------------------------------------------------
# get configuration file from input :
if (!isset($argv[1])&(!isset($_GET["confFile"])))
{
    echo "This script needs as input the name of the configuration file".PHP_EOL;
    exit;
}
if (php_sapi_name() == "cli")
{
    $iniFile = $argv[1];
}
else
{
    $iniFile = $_GET["confFile"];
}

#parse parameters
$ini_array = parse_ini_file($iniFile, true);

# get the proxy adresse (if any)
$proxy = $ini_array[proxy];

#get the list of forums we want to scrape
$forumOriginalFile = $ini_array[Files_Info][forumsInputList];


#get the names of the output files
$logFileName = $ini_array[Files_Info][logFileName];
$allXmlFileName = $ini_array[Files_Info][rdfFileName].".Pages";
$rdfFileName = $ini_array[Files_Info][rdfFileName];

$forumTempFile = $rdfFileName.".forums.temp";
$threadsTempFile = $rdfFileName.".threads.temp";
$messagesTempFile = $rdfFileName.".messages.temp";

#get threads xpath queries
$thrdElements = $ini_array[Threads_Info];
    
#get messages xpath queries
$ourdateFormat = $ini_array[dateFormat];
$msgElements = $ini_array[Messages_Info];

#------------------------------------------------------------------------
#
# The following code is to refresh browser window to show results
#
#------------------------------------------------------------------------
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) {ob_end_flush(); }
//echo str_pad('',1024);
ob_implicit_flush(1);


#------------------------------------------------------------------------
#
# Starting information
#
#------------------------------------------------------------------------
$today = getdate();
$timeStart =$today[mday]."/".$today[mon]."/".$today[year]." ".$today[hours].":".$today[minutes].":".$today[seconds];
$logFileName = $logFileName.$today[year].$today[mon].$today[mday].$today[hours].$today[minutes];
echo "Start time : ".$timeStart.$GLOBALS['NL'].$GLOBALS['NL'];

    
#initialize files
echo "the proxy is : ".$proxy.PHP_EOL;
initial($logFileName,$rdfFileName, $proxy, $ourdateFormat);

# show input information

echo "Date format : ".$ourdateFormat.$GLOBALS['NL'];
echo "All xml file : ".$allXmlFileName .$GLOBALS['NL'];
echo "Forums csv file : ".$ini_array[Files_Info][forumsInputList].$GLOBALS['NL'];
echo "Rdf graph file : ".$rdfFileName .$GLOBALS['NL'];
echo "Log file : ".$logFileName .$GLOBALS['NL']."**************************************************".$GLOBALS['NL'];

#------------------------------------------------------------------------
#
#   Processing
#
#------------------------------------------------------------------------

if (!file_exists($forumTempFile))
{
    copy($forumOriginalFile, $forumTempFile);
}
$htmls = file($forumTempFile);
foreach($htmls as $html)
{
    #for each forum, a file containing threads links should be availabie
    if (!file_exists($threadsTempFile))
    {
        #if the file doesn't existe, scrap the forum to get threads links.
        scrapPages($threadsTempFile, $html, NULL,$thrdElements, "#thread");
    }
    $threadsUrlsArray = file($threadsTempFile);
	while ($oneThreadUrl = array_shift($threadsUrlsArray))
	{
            scrapPages($messagesTempFile,$oneThreadUrl,NULL, $msgElements, "#message");
            file_put_contents($threadsTempFile, implode('', $threadsUrlsArray));
            unlink($messagesTempFile);
	}
    unlink($threadsTempFile);
    array_shift($htmls);
    file_put_contents($forumTempFile, implode('', $htmls));
}
unlink($forumTempFile);
#------------------------------------------------------------------------
#
#   Finishing
#
#------------------------------------------------------------------------

# Resumate about the finished process
echo $GLOBALS['NL'];
$today = getdate();
$timeEnd =$today[mday]."/".$today[mon]."/".$today[year]." ".$today[hours].":".$today[minutes].":".$today[seconds];
$resum = "**************************************************".$GLOBALS['NL'].
        "Log file name: ".$logFileName.PHP_EOL.
        "Start time: ".$timeStart.PHP_EOL.
        "End time: ".$timeEnd.PHP_EOL.
        "Number of all pages downloaded:  ".$GLOBALS['OverAllPages'].PHP_EOL.
        "Number of all threads downloaded:  ".$GLOBALS['OverAllThreads'].PHP_EOL.
        "Number of all messages downloaded:  ".$GLOBALS['OverAllMessages'].PHP_EOL;
fillLog($resum);
if (php_sapi_name() == "cli")
{
    echo $resum;
}
    else
{
    echo nl2br($resum);
}
echo $GLOBALS['NL']."Closing files and logs ".$GLOBALS['NL'];
    
# Close files
fclose($messagesFile);
fclose($ThrdOutFile);
closeGlobalFiles();

?>
