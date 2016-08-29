**********************************************************************
* This framework is under GNU License.
*
* All rights reserved 
*
*This file is written by Bissan AUDEH
*For information, feedback, questions, please contact bissana@gmail.com
*
*
*
***********************************************************************

* The configuration file should have the extention ".ini" in order to get correctly interpreted by php

The file is composed of 3 parts: [Files_Info], [Threads_Info] and [Messages_Info]. Before these parts, two optional attributes can be added: the adresse of the proxy and the format of the dates.

*******************************************************************
Proxy and date format (optional)
*******************************************************************
Here is an example of the proxy attribute
proxy="localhost:3129"
--The proxy that will be used to access destination pages

dateFormat = "* d/m/Y H:i:s" 
-- This is the format of the date used in the web site. We use this attribute to transform the dates into the format aaaa-MM-jjThh:mm:ss in the rdf graph. This will be used as input to the function "CreateFromFormat" in php. For more information about this function please see http://php.net/manual/fr/datetime.createfromformat.php
If this attribute is not defined, the script will not try to transform the date, it will appear as a string in the RDF graph (ex. "le 31/5/2014  4:39:12").
For some sites, several data format are used, in this case, we can put as much date format as we want, separated by "|". In this case, the script will try to use the defined format (from left to right) to transform the date string. It stops if it success, or it keeps the original string if non of the provided formats works.
Another possible case, is when the months names are not in english. In this case, the php function will no be able to recoginze months. To resolve this problem, the configuration file accepts that you enter the names of the 12 months of the year as they appear in the site preceded by "$" in the date attribute. Exemple:

dateFormat = "$janv.$févr.$mars$avril$mai$juin$juil.$août$sept.$oct.$nov.$déc.|d m Y * H:i"
In this example, we tell the script to consider "janv" as the first month of the year, "fév" as the second and so on.

*******************************************************************
[Files_Info] section
*******************************************************************
This section contains information about input and output files. These are all mandatory. Here is examples and explainations about these attributes:

forumsInputList = "../vigi4medResults/Doctissimo/Doctissimo_Forums.txt"
-- The name of the file that contains the URLS of the forums we want to scrap

logFileName = "../vigi4medLogs/Doctissimo_"
-- The prefix we want to give to the log file

rdfFileName = "../vigi4medResults/site/site_Graph.n3"
-- The name we want to give to the output RDF graph.

*******************************************************************
[Threads_Info] and [Messages_Info] sections
*******************************************************************
This part of the file contains the information needed to scrap threads ([Threads_Info] section) and posts ([Messages_Info] section) from a web forum (for threads) page or thread page (for posts). 
What is mandatory here is only the Xpath of the identifier of threads and the Xpath of the nextPage link: 

sioc:Thread = "//a[starts-with(@id,'thread_title')]/@href""::id
-- Here you should choose the wpath that identifies the url of a thread. It will be consider as the identified of the corresponding element in the result RDF graph. the part "::id" will tell the script that this ID is an identifier, the scrip will then create (in the RDF file) the predicate "type" for the extracted URL and use "thread" as value (because we are in the thread section)

nextPage = "//*[@id='inlinemodform']/table[1]/tr/td[2]/div/table/tr/td/a"::X
--This attribute contains the XPATH to the URL of navigation through pages. Nothing will be generated in the RDF graph for this attribute. It is only used for navigation. The symbole "::X" indicates the nature of navigation used for the threads pages in this web site. X can have the following values :
*X=s : The navigation to the next page happens by clicking on a symbole (like '>'). Example :
ex. nextPage = "//span[starts-with(@class,'prev_next')]/a[@rel='next']/@href"::s

*X=n : There is no symbol for next page, but to navigate consecutively, we have to click (in ascending order) on the number of pages. In this case, the script will use an internal counter that considers it as the text of the link.
 ex.nextPage = "//*[@id='inlinemodform']/table[1]/tr/td[2]/div/table/tr/td/a"::n
 
 Other than these special attributes, the user can set any other attribute of a thread or a message he wants to scrap. The names of these user defined attributes is transformed to their corresponding semantic definition in the RDF graph. Ex. dc:creator corresponds to the semantic relation <http://purl.org/dc/terms/creator>..

The general pattern of attributes is :
-----------------------------
|  name = XPAthQuery::type 
-----------------------------
Where "name" is the name of the attribute, XPathQuery is the xpath; and "type" is optional, if not specified, the value retrieved by the xpath will be considered as text.
Example : The following attribute definition in the section [Threads_Info] 
sioc:num_views = "//*[@id='threadslist']/tr/td[6]"::xsd:integer
will generate in the RDF graph the following triple:
<http://theIDOfThread><http://rdfs.org/sioc/ns#num_views> "433"@xsd:integer

Here are other attributes examples :
dc:creator = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[1]/div[1]/div[1]/span"
dc:date = "//*[starts-with(@class,'postbitlegacy')]/div[1]/span[1]/span/text()"::xsd:dateTime
sioc:content = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[2]"::@fr
nie:htmlContent= "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[2]"::rdf:HTML
v4m:authorMetaData = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[1]/dl"::rdf:HTML
