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

$ignoredCreator;
$creatorProfileInfo = "";
$currSubject;
$currBlock;
$autorMetaDataLine="";
$autorMetaDataCLEARLine = "";
$containerProperty = "";
$isThread=1;
function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
    
function anonymeLine($line, $resFile, $keysFile)
{
	$triple = explode('>', $line);
	$value1 = substr($triple[0], 1);
	$key1 = sha1($value1);
	$triple[0] = '<urn:vigi4med:'.$key1;
	if (strcmp($triple[1] ," <http://purl.org/v4m/order") == 0)
		return;
	if (strcmp($triple[2] ," <http://rdfs.org/sioc/ns#Post") == 0)
        {
		$GLOBALS['isThread'] = 0;
	}
	if (strcmp($triple[1] ," <http://purl.org/v4m/authorMetaData") == 0)
	{
		
		$GLOBALS['autorMetaDataLine']  = implode('>', $triple)."\n";
		#echo $GLOBALS['autorMetaDataLine'];
		$GLOBALS['autorMetaDataCLEARLine']  = $line;
		return;
	}
	if (strcmp($triple[1] ," <http://rdfs.org/sioc/ns#container_of") == 0)
	{
		$value2 = substr($triple[2], 2);
		$key2 = sha1($value2);
		$triple[2] = ' <urn:vigi4med:'.$key2;
		$GLOBALS['containerProperty'] = implode('>', $triple);
		fwrite($keysFile, '<urn:vigi4med:'.$key1.'> <http://www.w3.org/2002/07/owl#sameAs> <'.$value1."> .\n");
		return;
		
	}
	if (strcmp($triple[1] ," <http://purl.org/dc/terms/creator") == 0)
	{
		return implode('>', $triple);

	}
	$newLine = implode('>', $triple);
	$newLine .= "\n";
	#echo $newLine;
	fwrite($resFile, $newLine);
}
function anonymeBlock ($block, $outputFile, $keysFile, $metaFile)
{
	$creatorLine = "";
	$GLOBALS['autorMetaDataLine'] = "";
        $GLOBALS['autorMetaDataCLEARLine'] = "";
	$GLOBALS['isThread'] = 1;
	foreach(preg_split("/((\r?\n)|(\r\n?))/", trim($block)) as $line)
	{
		$result =anonymeLine($line, $outputFile, $keysFile) ; 
		if ($result != "")
		{
			$creatorLine = $result;
		}
			
	}
	
	if ($GLOBALS['isThread'] ==0)
		anonymeCreatorNode($creatorLine, $outputFile, $keysFile, $metaFile);
	else
		fwrite($outputFile,$GLOBALS['containerProperty']."\n");	
}

function anonymeCreatorNode($creatorLine, $outputFile, $keysFile, $metaFile)
{ 
	$profile = "";
	$triple = explode('>', $creatorLine);
	$creatorName = "";
	if (isset($triple[2]))
		$creatorName = substr($triple[2], 2, strlen($triple[2])-5);
	//profile supprimé ou anonyme : ignore la ligne author, on consider un message sans auteur.
	if (($creatorName == "") or (strcmp($creatorName ,$GLOBALS['ignoredCreator'])==0)) // no author for this block, do nothing
	{
		return;
	}
	
	if ($GLOBALS['creatorProfileInfo'] == "") //blanck node
	{
		$profile = "_:id".sha1($creatorName);
	}
	else if (startsWith($GLOBALS['creatorProfileInfo'], "http")) //domain of all profiles
	{
		$profile = $GLOBALS['creatorProfileInfo'].$creatorName;
	}
	else //creatorProfileInfo is a regular expression, use it to extract profile from htmlMetaData
	{ 
		preg_match($GLOBALS['creatorProfileInfo'],$GLOBALS['autorMetaDataCLEARLine'],$matches);
		for ($i=1; $i<count($matches); $i++)
		{
			$profile = $profile.$matches[$i];
		}
		if ($profile == "") #only if this is a post
		{
			$profile = "_:id".sha1($creatorName);
		}
	}
	$anoProfile = sha1($profile);
	$creatorAttribute = str_replace( '"'.$creatorName.'"', "<urn:vigi4med:".$anoProfile.">",$creatorLine)."\n";
	$creatorAttribute .= $GLOBALS['containerProperty']."\n";
	$anonymisedCreatorAttribute = "<urn:vigi4med:".$anoProfile."> <http://xmlns.com/foaf/0.1/nick> \"".$creatorName."\" .\n" ;
	$anonymisedCreatorAttribute .= "<urn:vigi4med:".$anoProfile."> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .\n";
	$anonymisedCreatorAttribute .= '<urn:vigi4med:'.sha1($profile).'> <http://www.w3.org/2002/07/owl#sameAs> <'.$profile."> .\n";
	fwrite($outputFile, $creatorAttribute);
	fwrite($keysFile,$anonymisedCreatorAttribute);
	fwrite($metaFile, $GLOBALS['autorMetaDataLine']);
}
$GLOBALS['ignoredCreator'] = $argv[2];
if (count($argv) > 3)
	$GLOBALS['creatorProfileInfo'] = $argv[3]; 
$keysFileName = str_replace("n3", "AnoKeys.n3", $argv[1]);
$inputFile = @fopen($argv[1], "r") ;
$outputFile = @fopen(str_replace("n3", "Anonymised.n3", $argv[1]), "w") ;
$keysFile = @fopen($keysFileName, "w") ;
$metaFile = @fopen(str_replace("n3", "AnoMeta.n3", $argv[1]), "w");

// while there is another line to read in the file
$currentValue = "";
$block = fgets($inputFile);
while (!feof($inputFile))
{
	$currentLine = fgets($inputFile) ;
	$triple = explode('>', $currentLine);
	if (!isset($triple[1]))
		continue;
	$value1 = substr($triple[0], 1);
	$value2 = substr($triple[2], 2);
	if ((strcmp($currentValue, $value1) == 0) or (strcmp($currentValue, $value2) == 0)) //new collection	
	{
		$block.=$currentLine;
	}
	else
	{
		anonymeBlock ($block, $outputFile, $keysFile, $metaFile);
		$currentValue = $value1;
		$block = $currentLine;
	}
	// Get the current line that the file is reading
	
}

fclose($inputFile) ;
fclose($outputFile) ;
fclose($keysFile) ;
fclose($metaFile);
$command = 'sort -u '.$keysFileName. ' > '.$keysFileName.'.temp';
exec($command);
$command = 'mv '.$keysFileName.'.temp '.$keysFileName;
exec($command);

?>
