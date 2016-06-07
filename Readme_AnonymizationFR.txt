@**********************************************************************
* Ce framework est sous GNU License.
*
* All rights reserved 
*
*Ce fichier est écrit par Bissan AUDEH
*Pour plus d'informations, feedback, questions, merci de contacter bissana@gmail.com
*
*
*
***********************************************************************
Anonymiser quoi?
- Tous les URI dans le graph RDF; 

Comment?
- en utilisant sha1 on génère: sha1(uri_claire) = uri_anonyme. 
- dans le rdf on remplace uri_claire par uri_anonyme
- dans un autre fichier rdf (que j'appel le fichier des clés d'anonymisation), on stock le lien uri_claire <-> uri_anonyme comme ceci :
<uri_anonyme> <http://www.w3.org/2002/07/owl#sameAs> <uri_claire> .

*** IMPORTANT A savoir **** :
- Le RDF qu'on récupère par les scripts de moissonnage exprime les noeudd "créateur" de la manière suivante : 
-------------------------------------------------------------------------
|	<uri> <http://purl.org/dc/terms/creator> "toto" .		
-------------------------------------------------------------------------

Afin de correspondre au protocole standard de RDF, il faut que le noeud créateur point sur un uri exprimant le profil d'un utilisateur, et pas son pseudo. c'est à dire qu'un créateur doit être exprimé comme ceci :

--------------------------------------------------------------------------------------------------------
|	<uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .	
|	<uri> <http://www.w3.org/2002/07/owl#sameAs> <http://sante-yy/profile/user/alyo> .		
|	<uri> <http://xmlns.com/foaf/0.1/nick> "alyo" .							
--------------------------------------------------------------------------------------------------------

Lors du moissonnage, il n'est pas possible de récupérer le profile utilisateur d'une manière uniforme pour tous les sites. Certains site n'offre pas un profile utilisateurs, pour d'autre il est crypté, etc ... Pour simplifier les choses, je profite de l'étape d'anonymisation pour régler ce problème des profils des utilisateurs. 



* Je distingue 3 cas :
cas 1: Les utilisateurs dans le site n'ont pas de pages de profile (que de pseudos). 
cas 2: Les utilisateur ont des pages de profile mais ces pages n'apparaissent pas dans l'html que je moissonne (crypté ou en javascript)
cas 3: Les utilisateurs ont des pages de profile accessible. Dans ce cas, les métadonnées  des auteurs devraient être déjà moissonnées par le script de moissonnage. Cela signifie que dans le fichier de configuration on a la ligne suivante (où 'Xpath' doit être remplacé par la requête xpath qui amène à la cellul contenant le maximum de données sur l'auteur)
--------------------------------------------------------------------------------------------------------
| 	v4m:authorMetaData = "Xpath"::rdf:HTML 
--------------------------------------------------------------------------------------------------------

* Dans certains sites, il est possible de postuler un message sans s'identifié, cela génère le pseudo invité vers le message. Dans d'autres site, si un utilisateur supprime son profile, ces anciens messages auront un pseudo spécifique (ex. "profil supprimé" sur doctissimo). Dans ces deux cas (pseudo invité ou profil supprimé) il n'est pas possible de considérer ce pseudo comme auteur, car cela signifie que tous les messages écrits par le pseudo invité sont écrit par la même personne, ce qui n'est pas correcte. Pour évité tout confusion, mon script d'anonymisation va éliminer les créateurs non identifiable s'il reçoit le texte (le pseudo) de tels créateurs comme paramètre d'entré.

Pour cela, quand il est possible, je ramasse le code HTML autour du pseudo de l'utilisateur. Ce code pourrais nous servir à extraire l'uri du profil en utilisant regex.

Le script d'anonymisation prend comme entrée :
1- le nom du fichier RDF qu'on souhaite anonymiser
2- le nom du profile à ignoré (ou "" si rien)
3- Informations sur comment extraire l'URI du profile :
	3-1 le regx (entouré par '/') pour extraire le profile utilisateur si on est dans le cas 3
	3-2 le prefix des pages des profile (ex. http://www.doctissimo.fr/profile/) si on est dans le cas 2. Dans ce cas le script va ajouter le pseudo de chaque utilisateur à ce lien pour construire sa page de profile.
	3-3 Rien pour dire au script que les utilisateurs n'ont pas de profile (cas 1). Ce dernier cas va automatiquement générer un identifiant unique pour l'utilisateur à la place de sa page de profile. Cela va générer dans le fichier de clés les triplets suivants :
--------------------------------------------------------------------------------------------------------
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://www.w3.org/2002/07/owl#sameAs> <_:id878140c00e295edea3a81d00> .
<urn:vigi4med:0001021f68786f9e8863b75fce356d93ecea5955> <http://xmlns.com/foaf/0.1/nick> "XYZ" .
--------------------------------------------------------------------------------------------------------

Voici des exemples pour appeler le script d'anonymisation
ex.1
---------------------------------------------------------------------------------------------------------------
php Iscod_Anonymization.php Doctissimo_sante_Graph.n3  "Profil supprim\u00E9" 'http://club.doctissimo.fr/'
---------------------------------------------------------------------------------------------------------------
ex.2
---------------------------------------------------------------------------------------------------------------
php Iscod_Anonymization.php  BaclofeneFR_Graph.n3 "" '/href=\\"(http:[^"]*)/'
---------------------------------------------------------------------------------------------------------------
