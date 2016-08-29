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

* Le fichier de configuration doit avoir une extension .ini pour qu'il soit interprété correctement par php.

Ce fichier est composé de 3 sous parties : [Files_Info], [Threads_Info] et [Messages_Info]. Avant ces trois partie, deux attributs (optionelles) peuvent être ajouté : une pour le proxy et l'autre pour le format des dates des messages.

---------------------------------------------------------------
Les deux attributs génériques :
*******************************************************************
Proxy et le format de la date  (optionelles)
*******************************************************************
proxy="localhost:3129"
--C'est le proxy auquel s'adressé pour le moissonnage

dateFormat = "* d/m/Y H:i:s" 
--C'est le format des dates des messages sur le site. On utilise cette attribut pour transformer les dates de messages au format aaaa-MM-jjThh:mm:ss dans le graphe RDF.
Par exemple, l'attribut ce dessus correspond à "le 31/5/2014  4:39:12" dans le site web, ce qui deviendra "2014-05-31T04:39:12" dans le RDF.

*Le dateFormat est utiliser à travers la fonction "CreateFromFormat" de php pour plus d'information sur cette fonction et la signification des symboles, vous pouvez voir la page http://php.net/manual/fr/datetime.createfromformat.php
Si cet attribut n'est pas défini, les script ne vont pas essayé de transformer le texte contenant la date d'un message, il va donc apparaitre tel quel dans le fichier rdf (ex. "le 31/5/2014  4:39:12")

*Pour certain forum, plusieurs formats de dates sont utilisé, on peut ajouter autant de format qu'on veut en les séparant par | 
Le script va essayer tous les formats dans l'ordre (de gauche à droite) sinon il garde le texte du date tel quel.

*Pour certain site, les nom de mois ne sont pas en anglais, ex. Janv. mars sépt. 
La fonction php ne reconnait pas ces noms de mois (surtout que l'abréviation des mois français sont différrents d'un site à un autre (janv, Jan.) Pour résoudre ce problème, on peut donner les noms de mois au script qui va les transformer en chiffres. Cela se fait en ajoutant les noms des mois séparés par '$'.
 Voici un exemple:
dateFormat = "$janv.$févr.$mars$avril$mai$juin$juil.$août$sept.$oct.$nov.$déc.|d m Y * H:i"
Dans cet exemple, on indique au script de considérer "janv" comme le premier mois de l'année, "fév" comme le deuxième, et ainsi de suite.

*******************************************************************
La section [Files_Info] 
*******************************************************************

forumsInputList = "../vigi4medResults/Doctissimo/Doctissimo_Forums.txt"
--le nom du fichier contenant les urls des forums qu'on souhaite moissonner 

logFileName = "../vigi4medLogs/Doctissimo_"
--le préfix qu'on souhaite donner au fichier de log

rdfFileName = "../vigi4medResults/Doctissimo/Doctissimo_Graph.n3"
--le nom qu'on souhaite donner au fichier contenant le graph RDF final 

*******************************************************************
Les sections [Threads_Info] et [Messages_Info] 
*******************************************************************

--Cette partie contient les informations nécessaires pour moissonner les données de threads dans la page d'un forum.
--Ce qui est obligatoire dans cette partie est d'avoir un attribut de type 'id' et l'attribut nextPage qui va définir la navigation

sioc:Thread = "//a[starts-with(@id,'thread_title')]/@href""::id
--Il faut choisir pour cet attribut l'XPath de l'élément qui identifie le thread d'une manière unique. Typiquement c'est son URL. La partie "::id" indique au script que ce URL est un identifiant, il va donc générer, dans le RDF, une relation "type" et une valeur "thread" (car on est dans la section "thread").

nextPage = "//*[@id='inlinemodform']/table[1]/tr/td[2]/div/table/tr/td/a"::s
--L'Xpath vers le lien de navigation entre les pages contenants les urls de threads. 
* Cet attribut est exceptionnelle : elle n'a pas de correspondance dans le graph RDF, il sert uniquement à naviguer entre les pages.
* Le symbole qui suit les deux points :: indique la nature de navigation. Il peut avoir 3 valuers : s, n ou a.

1)s : la navigation vers la page suivant se passe par un url d'un symbole ou une image (ex.'>')
ex. nextPage = "//span[starts-with(@class,'prev_next')]/a[@rel='next']/@href"::s

2)n : la navigation vers la page suivant se passe par l'url des nombre de pages, le script dans ce cas va utiliser un compteur interne qui le considère comme le texte chercher par l'Xpath.
ex.nextPage = "//*[@id='inlinemodform']/table[1]/tr/td[2]/div/table/tr/td/a"::n

à l'exception ces deux attributs, l'utilisateur peut utiliser n'importe quel attribut pour décrire une partie qu'il souhaite moissonner.  Les noms des attributs qu'on met dans ce fichier vont être transformé au nom correspondant dans le graph RDF. Ex. dc:creator correspond à la relation <http://purl.org/dc/terms/creator>
*Le format général des attribues est 
-----------------------------
|  nom = chemainXPAth::type 
-----------------------------
nom : est le nom de l'attribut, chemainXPAth est le XPath vers la valeur correspondante qu'on souhaite moissonner, "type" n'est pas obligatoire, s'il n'est pas précis, le script considère la valeur moissonée en tant que texte. 

Par example, voici la définition d'un attribut dans la section [Threads_Info] 
sioc:num_views = "//*[@id='threadslist']/tr/td[6]"::xsd:integer
Cela va générer le triplet suivant dans le graphe RDF:
<http://theIDOfThread><http://rdfs.org/sioc/ns#num_views> "433"@xsd:integer

Autres examples d'attributs :
dc:creator = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[1]/div[1]/div[1]/span"
dc:date = "//*[starts-with(@class,'postbitlegacy')]/div[1]/span[1]/span/text()"::xsd:dateTime
sioc:content = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[2]"::@fr
nie:htmlContent= "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[2]"::rdf:HTML
v4m:authorMetaData = "//*[starts-with(@class,'postbitlegacy')]/div[2]/div[1]/dl"::rdf:HTML
I
