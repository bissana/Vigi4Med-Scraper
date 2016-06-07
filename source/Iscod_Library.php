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

	set_include_path ( get_include_path () . PATH_SEPARATOR . 'lib/' );
	require_once "EasyRdf.php";
	error_reporting ( E_ERROR | E_PARSE );
	ini_set ( 'max_execution_time', 0 );
	setlocale ( LC_ALL, 'fr_FR' );
	date_default_timezone_set ( 'Europe/Paris' );
	
	// ------------------------------------------------------------------------
	//
	// The following code is to manage output according to the execution from browser or commandline
	//
	// ------------------------------------------------------------------------
	$NL = "<br>";
	if (php_sapi_name () == "cli") {
		$NL = PHP_EOL;
	}
	// ------------------------------------------------------------------------
	
	$lastScrapTime = microtime ( true );
	$pageLoadTime = 0.0;
	$pageTreatTime = 0.0;
	$allLoadTime = 0.0;
	$allTreatTime = 0.0;
	$OverAllPages = 0;
	$OverAllMessages = 0;
	$OverAllThreads = 0;
	$lastHTML = "";
	$AllScrapedLinksFile;
	$logFile;
	$rdfFile;
	$ch;
	$dateFormat;
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	// Auxeliary functions
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	// ***********************************************************************************
	//
	// File treatement functions
	//
	// ***********************************************************************************
	// We call this function to save a mini graph ($graph) that contains only one class and its attributes to the rdf file.
	function dumpGraph($graph, $ignorePrefixes) {
		$data = $graph->serialise ( 'ntriples' );
		if (! is_scalar ( $data )) {
			$data = var_export ( $data, true );
		}
		fwrite ( $GLOBALS ['rdfFile'], $data );
	}
	
	// initialize the properties of the curl instruction that we will use for scraping
	function initialCurl($proxy) {
		$GLOBALS ['ch'] = curl_init ();
		if (isset ( $proxy ))
			curl_setopt ( $GLOBALS ['ch'], CURLOPT_PROXY, $proxy );
		curl_setopt ( $GLOBALS ['ch'], CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $GLOBALS ['ch'], CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $GLOBALS ['ch'], CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt ( $GLOBALS ['ch'], CURLOPT_TIMEOUT, 30 );
	}
	
	// Main initialization file that prepares output files, global variables, rdf difinitions, and curl connexions and properties
	function initial($logFileName, $rdfFileName, $proxy, $dateFormat) {
		// Define rdf name spaces
		EasyRdf_Namespace::set ( 'v4m', 'http://purl.org/v4m/' );
		EasyRdf_Namespace::set ( 'nie', 'http://www.semanticdesktop.org/ontologies/2007/01/19/nie#' );
		// prepare files for writing
		$GLOBALS ['logFile'] = fopen ( $logFileName, "w" );
		$GLOBALS ['rdfFile'] = fopen ( $rdfFileName, 'a' );
		$GLOBALS ['AllScrapedLinksFile'] = $rdfFileName . ".links";
		file_put_contents ( $GLOBALS ['AllScrapedLinksFile'], "", FILE_APPEND );
		$GLOBALS ['dateFormat'] = explode ( "|", $dateFormat );
		// initial connexion parameters
		initialCurl ( $proxy );
		fillLog ( "PageNumber:PostsCounts(loading time | treatementTime)\n\n" );
		if (file_exists ( $rdfFileName . ".nbMessages" )) {
			$GLOBALS ['OverAllMessages'] = file_get_contents ( $rdfFileName . ".nbMessages" );
		}
		if (file_exists ( $rdfFileName . ".nbThreads" )) {
			$GLOBALS ['OverAllThreads'] = file_get_contents ( $rdfFileName . ".nbThreads" );
		}
	}
	
	// close opened global files
	function closeGlobalFiles() {
		fclose ( $GLOBALS ['logFile'] );
		fclose ( $GLOBALS ['AllScrapedLinksFile'] );
		fclose ( $GLOBALS ['rdfFile'] );
	}
	
	// Fills the log file with a string $str
	function fillLog($str) {
		fwrite ( $GLOBALS ['logFile'], $str );
	}
	function check404($url) {
		$handle = curl_init ( $url );
		curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, TRUE );
		
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec ( $handle );
		echo $reponse;
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
		echo strcmp($reponse,$GLOBALS ['lastHTML'])."\n";
		if (($httpCode == 404)|| (strcmp($reponse,$GLOBALS ['lastHTML']) == 0)) {
			
			$result = true;
		} else {
			
			$GLOBALS ['lastHTML'] = $reponse;
			$result = false;
		}
		curl_close ( $handle );
		
		return $result;
	}
	
	// ***********************************************************************************
	//
	// Xpath and DomNodes functions
	//
	// ***********************************************************************************
	// This function will scrape the $url, generate a DomDocument from this $url, and return a handler to this DomDocument.
	// The returned handler will be used to execute xpath queries on the dom document.
	function getXpathHandler($url, $keepCopy) {
		// the delay management is now handeled by the proxy
		// Minimum delai between two scraps is defined here. We differ according to the time of the day.
		// $minDelai;
		// at night, scrap maximum 3 pages per seconds
		// if ((date('H') > 20) or (date('H') < 7))
		// $minDelai = 0.33;
		// else
		// at working hours, maximum 1 pages each 2 seconds
		// $minDelai = 2;
		// $actualDelai = microtime(true) - $GLOBALS['lastScrapTime'];
		// if ($actualDelai < $minDelai)
		// usleep(($minDelai-$actualDelai)*1000000);
		$beforeLoad = microtime ( true );
		if (startsWith($url, "https://"))
			$url = str_replace("https://", "http://+", $url);
		curl_setopt ( $GLOBALS ['ch'], CURLOPT_URL, $url );
		$html = curl_exec ( $GLOBALS ['ch'] );
		if (strcmp($html,$GLOBALS ['lastHTML']) == 0)
			return NULL;
		else
		{
			file_put_contents ("1", $GLOBALS ['lastHTML']);
			file_put_contents ("2", $html);
			$GLOBALS ['lastHTML'] =$html;
		}
		if (curl_errno ( $GLOBALS ['ch'] )) {
			if (curl_errno ( $GLOBALS ['ch'] ) == 7) // connexion broken -> stop everything
{
				$today = getdate ();
				$time = $today [mday] . "/" . $today [mon] . "/" . $today [year] . " " . $today [hours] . ":" . $today [minutes] . ":" . $today [seconds];
				$err = "I lost the connexion at " . $time . " while trying to fetch the url : " . $url . "\n";
				echo $err;
				fillLog ( $err );
				exit ( - 1 );
			}
			fillLog ( "\n!!!!!!!!!!!!!!!! Blocked for some reason at the url : " . $url . ", curl error number : " . curl_errno ( $GLOBALS ['ch'] ) . "\n" );
			return NULL;
		}
		$GLOBALS ['lastScrapTime'] = microtime ( true );
		$GLOBALS ['pageLoadTime'] = $GLOBALS ['lastScrapTime'] - $beforeLoad;
		$GLOBALS ['pageTreatTime'] = microtime ( true );
		$doc = new DOMDocument ();
		$out;
		foreach ( preg_split ( "/((\r?\n)|(\r\n?))/", $html ) as $s ) {
			if (mb_detect_encoding ( $s, 'UTF-8', true ))
				$out = $out . $s; // echo 'UTF ';
			else if (mb_detect_encoding ( $s, 'ISO-8859-1', true ))
				
				$out = $out . iconv ( "ISO-8859-1", "UTF-8//TRANSLIT", $s ); // echo 'UTF ';
			$out = $out . "\n";
		}
		
		$doc->loadHTML ( mb_convert_encoding ( $out, 'HTML-ENTITIES', "UTF-8" ) );
		// remove script tag from the document, we don't want to see their values when querying
		while ( ($r = $doc->getElementsByTagName ( "script" )) && $r->length ) {
			$r->item ( 0 )->parentNode->removeChild ( $r->item ( 0 ) );
		}
		return new DOMXpath ( $doc );
	}
	
	// get pure text content of a node in a position i, this function also removes line break to prevent file importing issues
	function getTextValue($xpath, $i) {
		if ($xpath->length > 0) {
			$str = $xpath->item ( $i )->nodeValue;
			return trim ( $str );
		} else
			return "";
	}
	function convertFrenchDate($str, $months)
	{
		unset($months[0]);
		$numbers = array ("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12");
		return str_replace($months, $numbers, $str);
	}
	
	// Get date value of a node in a position i, and put it in the wanted form

	function getDateValue($xpath, $i) {
		if ($xpath->length > 0) {
			$strOrigin = $xpath->item ( $i )->nodeValue;
			$str = trim ( preg_replace ( '/\s+/', ' ', $strOrigin ) );
			$i = 0;
			if (startsWith ( $GLOBALS ['dateFormat'] [0], "$" )) //convert french month names to numbers
			{
				$str = convertFrenchDate($str, explode ( "$", $GLOBALS ['dateFormat'] [0] ));
				$i = 1;
			}
			for($i; $i < count ( $GLOBALS ['dateFormat'] ); $i ++) {
				$date = DateTime::createFromFormat ( $GLOBALS ['dateFormat'] [$i], $str );
				if ($date == FALSE) {
					continue;
				} else
					return $date->format ( 'Y-m-d\TH:i:s' );
			}
			return $strOrigin;
		} else
			return "";
	}
	
	// Get integer value of a node in a position i, and handle integers with thousands separators
	function getIntValue($xpath, $i) {
		if ($xpath->length > 0) {
			$str = $xpath->item ( $i )->nodeValue;
			return intval ( preg_replace ( "/[^0-9,.]/", "", $str ) );
		} else
			return "";
	}
	function getIdValue($xpath, $i, $prefix, $doGenerate) {
		if ($doGenerate != "") {
			return str_replace ( "\n", "", $prefix ) . $doGenerate;
		} else
			return getValidAbsolutLink ( $xpath->item ( $i )->nodeValue, $prefix );
	}
	// Get inner tags and note only text of a node in a position i. This function is used to get message contents and user meta data
	function getHtmlValue($xpath, $i) {
		if ($xpath->length > 0) {
			$node = $xpath->item ( $i );
			$doc = $node->ownerDocument;
			if ($doc == NULL)
				return "";
			$frag = $doc->createDocumentFragment ();
			foreach ( $node->childNodes as $child ) {
				$frag->appendChild ( $child->cloneNode ( TRUE ) );
			}
			return $doc->saveXML ( $frag );
		} else
			return "";
	}
	function getValidAbsolutLink($link, $prefix) {
		$prefix = str_replace ( "\n", "", $prefix );
		if (startsWith ( $link, "http" ))
			return preg_replace ( '#([\w\d]+=[\w\d]{32})#', '', $link );
		if (startsWith ( $link, "#" ))
			return preg_replace ( '#([\w\d]+=[\w\d]{32})#', '', $prefix . $link );
		if (startsWith ( $link, "/" )) {
			$url = parse_url ( $prefix );
			$prefixWithNoPage = $url ['host'];
			return preg_replace ( '#([\w\d]+=[\w\d]{32})#', '', "http://" . $prefixWithNoPage . $link );
		}
		$prefixWithNoPage = substr ( $prefix, 0, strrpos ( $prefix, '/' ) + 1 );		
		return preg_replace ( '#([\w\d]+=[\w\d]{32})#', '', $prefixWithNoPage . $link );
	}
	
	// # ***********************************************************************************
	//
	// rdf graph functions
	//
	// ***********************************************************************************
	// This function generates one triple : the name (ex.sioc:num_replies), the $value[i] (ex.235) and the $type(ex. xsd:integer).
	// the $value is a table because it is the result of passing the xpath on the domDoc.
	function generateAttributeRecord($name, $value, $type, $i) {
		if ($type == "xsd:integer")
			return array (
					$name,
					getIntValue ( $value, $i ),
					$type 
			);
		if ($type == "rdf:HTML")
			return array (
					$name,
					getHtmlValue ( $value, $i ),
					$type 
			);
		if ($type == "xsd:dateTime")
			return array (
					$name,
					getDateValue ( $value, $i ),
					$type 
			);
		return array (
				$name,
				getTextValue ( $value, $i ),
				$type 
		);
	}
	function handleSuffix($url, $suffix) {
		if (strpos ( $url, "#" ) == false)
			return $url . $suffix;
		else
			return $url;
	}
	// The goal of this function is the same as generateAttributeRecord, the only difference is that it is only used to generate id triples. it checks if the $url of the
	// id has a tag '#' otherwise it adds the suffix.
	function generateIdRecord($name, $url, $type, $suffix) {
		return array (
				$name,
				handleSuffix ( $url, $suffix ),
				$type 
		);
	}
	function createRdfRoot($parent) {
		$graph = new EasyRdf_Graph ();
		$graph->add ( str_replace ( "\n", "", handleSuffix ( $parent, "#forum" ) ), 'rdf:type', 'sioc:Forum' );
		dumpGraph ( $graph, false );
	}
	function addRdfRecord($parent, $record) {
		$graph = new EasyRdf_Graph ();
		$keys = array_keys ( $record );
		for($i = 0; $i < count ( $record ); $i ++) {
			$name = $record [$i] [0];
			$value = $record [$i] [1];
			$type = $record [$i] [2];
			if ($type == "") {
				$element->set ( $name, $value );
				continue;
			}
			if ($type == "id") {
				$element = $graph->resource ( $value, $name );
				continue;
			}
			$element->addLiteral ( $name, $value, str_replace ( '@', '', $type ) );
		}
		$graph->add ( str_replace ( "\n", "", $parent ), 'sioc:container_of', $element );
		dumpGraph ( $graph, true );
	}
	
	// # ***********************************************************************************
	//
	// String treatement functions
	//
	// ***********************************************************************************
	function startsWith($haystack, $needle) {
		$length = strlen ( $needle );
		return (substr ( $haystack, 0, $length ) === $needle);
	}
	function str_lreplace($search, $replace, $subject) {
		$pos = strrpos ( $subject, $search );
		
		if ($pos !== false) {
			$subject = substr_replace ( $subject, $replace, $pos, strlen ( $search ) );
		}
		
		return $subject;
	}
	
	function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	
	
	function scrapOnePage($parent, $scrapedLinksFileName, $xpath, $pageNumber, $elementAttributes, $typeOfContent) {
		// initialize output and types arrays
		$types = array ();
		$localCounter;
		if ($typeOfContent == "#thread")
			$localCounter = $GLOBALS ['OverAllThreads'];
		else
			$localCounter = $GLOBALS ['OverAllMessages'];
			// get attribute names which are the keys (indexes) of the array $elementAttributes)
		$keys = array_keys ( $elementAttributes );
		// this variable will hold the index of the id attribute
		$idIndex;
		$doGenerate = "";
		// loop over attributes array until befor last attribute, because the last one contains the query of the link to next page, we don't want to include this information in the rdf record.
		for($attribute = 0; $attribute < count ( $elementAttributes ) - 1; $attribute ++) {
			$query = $elementAttributes [$keys [$attribute]];
			// the value of the array could containt a type. put it in $type array where $type[0] is always the query and $type[1] is the type (if exists)
			$type = explode ( "::", $query );
			// replace the xpath query (and the eventuel ::type) with the result of executing the query. This way wa keep the index of the array as the attribute name corresponding to the result.
			$elementAttributes [$keys [$attribute]] = $xpath->query ( $type [0] );
			// fill the types array (even when there is no type to keep the same number of elements as $keys and $elementAttributes)
			if (count ( $type ) > 1) {
				$types [$attribute] = $type [1];
				// register the index of the id attribute
				if ($type [1] == "id") {
					if (count ( $type ) > 2) {
						$doGenerate = $typeOfContent . "_" . $localCounter;
					}
					$idIndex = $attribute;
				}
			} else
				$types [$attribute] = "";
		}
		// special atoute.org treatement (the case of replaces threads)
		// #########################################################################
		$status = $xpath->query ( "//*[starts-with(@id,'td_status_')]/img" );
		// this counter is used to escape replaed threads in atoute.org
		$j = 0;
		// #########################################################################
		
		$nbElements = $elementAttributes [$keys [$idIndex]]->length;
		echo $pageNumber . ":" . $nbElements;
		fillLog ( "page " . $pageNumber . ":" . $nbElements );
		
		for($i = 0; $i < $nbElements; $i ++) {
			// special atoute.org treatement (the case of replaces threads)
			// #########################################################################
			if (($typeOfContent == "#thread") && ($status != null) && ($status->length > 0) && ($status->item ( $i )->getAttribute ( "src" ) == "images/statusicon/thread_moved.gif")) {
				echo "(" . $typeOfContent . " " . $localCounter . " treated)" . $GLOBALS ['NL'];
				$i = $i + 1;
			}
			// #########################################################################
			if ($i < $nbElements) {
				if ($doGenerate != "") {
					$doGenerate = $typeOfContent . "_" . $localCounter;
				}
				$id = getIdValue ( $elementAttributes [$keys [$idIndex]], $i, $parent, $doGenerate );
				// process
				$scrapable = true;
				if (file_exists ( $scrapedLinksFileName )) {
					//echo ($id);
					$command = 'grep  ' . escapeshellarg ( $id ) . '$ ' . $scrapedLinksFileName;
					if (exec ( $command ) != "") {
						$scrapable = false;
					}
				}
				if ($scrapable) {
					$record = array ();
					for($attribute = 0; $attribute < count ( $elementAttributes ) - 1; $attribute ++) {
						if ($attribute == $idIndex)
							$record [$attribute] = generateIdRecord ( $keys [$attribute], $id, $types [$attribute], $typeOfContent );
						else
							$record [$attribute] = generateAttributeRecord ( $keys [$attribute], $elementAttributes [$keys [$attribute]], $types [$attribute], $i );
					}
					if ($typeOfContent == "#thread") {
						$GLOBALS ['OverAllThreads'] = $GLOBALS ['OverAllThreads'] + 1;
						$localCounter = $GLOBALS ['OverAllThreads'];
					} else {
						$GLOBALS ['OverAllMessages'] = $GLOBALS ['OverAllMessages'] + 1;
						$localCounter = $GLOBALS ['OverAllMessages'];
					}
					$record [count ( $elementAttributes ) - 1] = generateIdRecord ( "v4m:order", $localCounter, "iscodid", "" );
					file_put_contents ( $scrapedLinksFileName, $id . "\n", FILE_APPEND );
					if ($typeOfContent == "#thread")
						addRdfRecord ( handleSuffix ( $parent, "#forum" ), $record );
					else
						addRdfRecord ( handleSuffix ( $parent, "#thread" ), $record );
				} else {
					echo "Repeated(" . $id . ") ";
				}
				$j = $j + 1;
			}
		}
	}
	
	// Scrap all elements in the first x pages of the parent (x=$NbPagesToScrap) :
	// $scrapedLinksFileName : the file in which we'll keep the urls of the threads (in case we are scraping in forum level).
	// $urlInit : the URL of the first page to scrape
	// $NbPagesToScrap : the max number of pages to scrap or null if we want to scrap everything
	// $elementAttributes : an array containing the attributes we want to scrape and their xpath and eventual type. each member of this array looks like this : [RDFName] "XpathQuery"::Type, where RDFName is the index of the element in the array and ::Type is optional.
	// $typeOfContent : #thread or #message. This is used in naming temp files and as extention of the RDF id.
	// Output : an array containing the ids (urls) of scraped elements.
	function scrapPages($scrapedLinksFileName, $urlInit, $NbPagesToScrap, $elementAttributes, $typeOfContent) {
		// initialize
		$GLOBALS ['allLoadTime'] = 0.0;
		$GLOBALS ['allTreatTime'] = 0.0;
		$pageNumber = 1;
		$scrapNextPage = true;
		$url = $urlInit;

		// scraping messages case
		if ($typeOfContent == "#message") {
			echo "Scraping messages in thread : " . $url . $GLOBALS ['NL'];
			fillLog ( "Thread : " . $url );
		} else		// scraping threads case
		{
			echo "Scraping threads in forum : " . $url . $GLOBALS ['NL'];
			fillLog ( "Forum : " . $url );
			if ($scrapNextPage)
				createRdfRoot ( $url );
		}
		// anyway, handle next page problem
		$nextPointer = $elementAttributes ['nextPage'];
		$typeOfNext = explode ( "::", $nextPointer );
		$type = "number";	
		if (count ( $typeOfNext ) > 1)
		{
			if ($typeOfNext[1] == "s")
				$type = "symbol";
			if ($typeOfNext[1] == "a")
			{
				//if type = auto this means that we need to fetch the stop condition, which should come in the same line separated with ::
				//textForNextPage::a::xpath for last page link::the text
				$type = "auto";
				
			}
		}
		$currentURL = $urlInit;
		while ( $scrapNextPage ) {
			$url = str_replace ( "\n", "", $url );
			
			$command = 'grep  ' . escapeshellarg ( $url ) . '$ ' . $GLOBALS ['AllScrapedLinksFile'];
			if (exec ( $command ) == "") // link not already scraped
{
				$xpath = getXpathHandler ( $url, 1 );
				if ($xpath == NULL)
					return;
				$onePageLinks = scrapOnePage ( $urlInit, $scrapedLinksFileName, $xpath, $pageNumber, $elementAttributes, $typeOfContent );
				file_put_contents ( $GLOBALS ['AllScrapedLinksFile'], $url . "\n", FILE_APPEND );
				$GLOBALS ['pageTreatTime'] = microtime ( true ) - $GLOBALS ['pageTreatTime'];
				$GLOBALS ['OverAllPages'] = $GLOBALS ['OverAllPages'] + 1;
				$GLOBALS ['allLoadTime'] = $GLOBALS ['allLoadTime'] + $GLOBALS ['pageLoadTime'];
				$GLOBALS ['allTreatTime'] = $GLOBALS ['allTreatTime'] + $GLOBALS ['pageTreatTime'];
				$output = "(" . $GLOBALS ['pageLoadTime'] . "|" . $GLOBALS ['pageTreatTime'] . "), ";
				echo $output;
				fillLog ( $output );
			} else {
				$xpath = getXpathHandler ( $url, 0 );
				if ($xpath == NULL)
					return;
				$output = "ALREADY SCRAPED: " . $url . PHP_EOL;
				echo $output;
				fillLog ( $output );
			}
			
			// nextPage is the name of the attribute that contains the query which leads us to next pages url (the placement of the links of next pages (1 2 .. >dernier) in the url).
			$pageNumber = $pageNumber + 1;
			$nextPageResult;
			
			// fill the types array (even when there is no type to keep the same number of elements as $keys and $elementAttributes)
			if ($type == "auto")
			{
				$stopQuery = $xpath->query ( $typeOfNext [2] );
				if ($stopQuery->item (0)->nodeValue != $typeOfNext [3])
				{
					$scrapNextPage = false;
				}
				if (endsWith(trim($currentURL), ".htm"))
				{
					$url = str_lreplace ("_".($pageNumber-1).".htm", "_".$pageNumber.".htm", $currentURL);
				}
				else if (! strpos($currentURL, $typeOfNext [0]. ($pageNumber - 1)))
					$url = $url . $typeOfNext [0] . $pageNumber;
				else
					$url = str_lreplace ( $typeOfNext [0] . ($pageNumber - 1), $typeOfNext [0] . $pageNumber, $currentURL );
			}
			else
			{
				if ($type == "symbol") // next page symbol ">" for example
					$nextPageResult = $xpath->query ( $typeOfNext [0] );
				if ($type == "number")
					$nextPageResult = $xpath->query ( $nextPointer . "[text()=" . $pageNumber . "]/@href" );
				if ($nextPageResult->length != 0) 
					$url = getValidAbsolutLink ( $nextPageResult->item ( 0 )->nodeValue, $urlInit );
				else
					$scrapNextPage = false;
			}
	
			if ((($pageNumber - 1) == $NbPagesToScrap) or ($url == $currentURL)) {
				echo $nextPageResult->length . "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
				$scrapNextPage = false;
				echo $GLOBALS ['NL'];
			} else {
				$currentURL = $url;
			}
		}
		echo "Total time (" . $GLOBALS ['allLoadTime'] . "|" . $GLOBALS ['allTreatTime'] . PHP_EOL;
		fillLog ( "\nTotal time (" . $GLOBALS ['allLoadTime'] . "|" . $GLOBALS ['allTreatTime'] . ")\n" );
		fillLog ( ".....................................................\n" );
	}
	
	?>
