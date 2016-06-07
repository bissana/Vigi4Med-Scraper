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
What to anonymise?
- All URI in the RDF graph; 

How?
- We use SHA1 to generate  sha1(uri) = uri_anonyme. 
- In the RDF, we replace uri by uri_anonyme
- In a different rdf (what I call the file of anonymisation keys), we stock the link uri <-> uri_anonyme as following :
<uri_anonyme> <http://www.w3.org/2002/07/owl#sameAs> <uri> .

*** IMPORTANT **** :
- The output RDF Graph of the scrapping algorithm uses the following format for the creator node :
-------------------------------------------------------------------------
|	<uri> <http://purl.org/dc/terms/creator> "toto" .		
-------------------------------------------------------------------------
To correspond to the standard protocol of RDF, the creator node should only point to an URI that represents the profile of a user. This means that the previous triplet should be corrected to the following 3 lines:


--------------------------------------------------------------------------------------------------------
|	<uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .	
|	<uri> <http://www.w3.org/2002/07/owl#sameAs> <http://sante-yy/profile/user/alyo> .		
|	<uri> <http://xmlns.com/foaf/0.1/nick> "alyo" .							
--------------------------------------------------------------------------------------------------------
We can not create these three lines during the scraping script for all the forums uniformly, because some forums do not even offer a user profile, other forums encodes rhe profile, ... To simplify, we handle this user profile issue in post-treatment while anonymizing the graph.

*We distinguish 3 cases:
case 1: Users in the site do not have a profile (only a pseudonyme)
case 2: Users have a profile page but it is not accessible
case 3: Users have accessible profile pages. In this case, the metadata of the author should be extracted by the scraping step. This means to put the following line in the configuration file ('Xpath' should be replaced by the xpath query that leads to the cell that contains the maximum of authors information)
--------------------------------------------------------------------------------------------------------
| 	v4m:authorMetaData = "Xpath"::rdf:HTML 
--------------------------------------------------------------------------------------------------------

* In some sites, it is possible to submit a message without authentification, this generates an automatic "gest" pseudo
* In some sites, the user can delete his profile, in this case, his old messages shows an auto pseudo "deleted profile". This case is problematic. Because we should be able to know that "deleted profile" is not the pseudo of a real user, otherwise, all deleted profiles will be handled as the same user. 

The script has the following inputs:
1- the name of the RFD file to anonymize
2- the name of the profile we want to ignore, ex. "deleted profile" (optional)
3- Information about how to extract the profile URI
	3-1 the regular expression (surrounded by '/') needed to extract the URI of the profile if we are in the case 3
	3-2 le préfixe des pages de profiles (ex. http://www.doctissimo.fr/profile/) for the case 2. In this case, the script will automatically generate URIS composed of the prefixe and the pseudo of the user.
	3-3 Nothing. In this case, we tell the script that there is no URI for the profiles (case 1). In this case, the script will automatically generate a unique identifier for users instead of profiles URIs. This corresponds to the following generated lines:
--------------------------------------------------------------------------------------------------------
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://www.w3.org/2002/07/owl#sameAs> <_:id878140c00e295edea3a81d00> .
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://xmlns.com/foaf/0.1/nick> "XYZ" .
--------------------------------------------------------------------------------------------------------
Here are examples about calling the anonymization script
ex.1
-------------------------------------------------------------------------------------------------------------
php Iscod_Anonymization.php Doctissimo_sante_Graph.n3  "Profil supprim\u00E9" 'http://club.doctissimo.fr/'
-------------------------------------------------------------------------------------------------------------
ex.2
-------------------------------------------------------------------------------------------------------------
php Iscod_Anonymization.php  BaclofeneFR_Graph.n3 "" '/href=\\"(http:[^"]*)/'
-------------------------------------------------------------------------------------------------------------
