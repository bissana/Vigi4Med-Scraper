**********************************************************************
* Ce framework est sous GNU License.
*
* All rights reserved 
*
*Ce fichier est écrit par Bissan AUDEH
*Pour plus d'informations, feedback, questions, merci de contacter bissana@gmail.com
*
*
*toute
***********************************************************************

A propos de ce document :
---------------
Ceci est une description détaillée d'un outil de moissonnage et d'anonymisation des web forums. Cet outil est composé de plusieurs scripts principales et auxiliaires, il a été utilisé pour moissonner des sites pour le projet Vigi4Med
Cet outil permet à :
1- moissonner des messages à partir de web forums, et les transformer à un graph RDF (format n-triples).
2- préciser les informations qu'on souhaite moissonnée à travers des Xpathes renseigner dans un fichier de configuration unique pour chaque site
3- garder une copie de chaque page moissonnée dans une base de données (cache) grâce à un proxy. Ce proxy interroge le cache avant d'aller chercher une page sur internet, ce qui économise les connexions vers le site moissonné au cas au le moissonnage est relancé (optionnelle).
4- Anonymiser les informations moissonnées(optionnelle).

Informations générales :
--------------------------
1- Les scripts de collecte sont faits en PHP. Il est recommandé de les lancer de la ligne de commande
2- Les scripts de proxies sont en PERL, et génères des bases de données Berckeley.


Etapes principaux
---------------------
1- générer un fichier qui contient les forums du site qu'on souhaite moissonné. J'ai séparée cette étape pour éliminer manuellement certains forums non pertinents (mode, voiture, ...). 
2- bien configurer les paramètres du site qu'on souhaite moissonné
3- lancer le proxy (optionnelle mais recommandée)
4- lancer les scripts de moissonnage
5- anonymiser une fois le moissonnage terminé (optionnelle mais recommandée pour protéger la confidentialité).
P.S. Cette application a été fait pour être le plus précis possible, pour cela, les étapes manuels été choisis volontairement pour garantir la qualité des données.

Description détaillée 
----------------------
1- Générer une liste (url par ligne) que vous mettez  dans un fichier (je nomme ce fichier Site-Forums). Cette étape peut être faite à l'aide du script Iscod_ForumsGénérator.
2- Préparation : préparez le fichier de configuration en suivant les instructions dans le manuel "Readme_ConfigurationFR.txt"
3- Proxy : le proxy organise un délai entre les requêtes consécutives, et garde une copie de chaque page moissonnées. Il faut lancer la commande suivante :
  -----------------------------------------------------------
 |	
 |	perl proxy.pl     
 |							   
 -----------------------------------------------------------
Entrée optionnelle : Le numéro du port est une entrée optionnelle, par défaut c'est 3129.
Sorti : une description pour chaque page demandé. 
Je recommande le lancement par "nohup" comme suivant: 
 -----------------------------------------------------------
 |	  
 |	nohup perl proxy.pl > nohup/2015_6_24 2>&1&    
 |							   
 -----------------------------------------------------------
4- Moissonnage : Une fois le proxy lancer sans souci, c'est le tour du moissonnage. Bien vérifier que le proxy que vous venez de lancer est le même mentionné dans le fichier de configuration. Pour lancer le moissonnage j'utilise toujours le mode nohup également. 
Entrée obligatoire: le fichier de configuration du site concerné. 
Sorti : information sur les pages moissonnée (nombre, temps), le fichier principale généré par ce script (graph.n3) est enregistré aux endroits précisés dans le fichier de configuration. 
Voici comment je lance le moissonnage :

 ---------------------------------------------------------------------------------------------------------
 |								  
 |	nohup php Iscod_MainScraper.php conigFiles/LeFichierDUSite.ini > nohup/nomduSIte_date 2>&1&	  
 |													  
 --------------------------------------------------------------------------------------------------------

5- Anonimisation, pour des détails sur les paramètres et le fonctionnement de ce script, merci de consulter le manuel Readme_AnonymisationFR.txt. Le script d'anonymisation prend 3 paramètres dont 2 optionnelles.
Entrée obligatoire: le fichier à anonymiser (le graphe RDF moissonée par l'étap 3) 
Entrée optionnelle : Le texte désignant l'utilisateur à ignorer (ex. profil supprim\U pour doctissimo)

 --------------------------------------------------------------------------------------------------------------------						 
 |	Iscod_Anonymization.php Graph.n3								    
 |													  	    
 --------------------------------------------------------------------------------------------------------------------
