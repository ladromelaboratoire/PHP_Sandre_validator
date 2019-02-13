# Sandre_validator

Cette classe PHP permet de vérifier la validité des fichiers EDILabo avec le webservice du [Sandre](http://www.sandre.eaufrance.fr/).
Les fichiers XML sont envoyés au webservice, le jeton de soumission est récupéré pour vérifier le résultat plus tard. La librairie s'adapte fonctionnement assynchrone du webservice.

Les scénarii COM_LABO & LABO_DEST en version v1.0 et v1.1 sont supportés. Il sont automatiquement détectés dans le fichier XML.

# Contenu de la librairie

## Historique des versions
	v1.0	2018-11-15	1ère publication de la classe PHP
	
## Fonctionnalités
Opérations supportées par la classe
 - Définition de l'entité du demandeur
 - Définition des éléments de contact du demandeur
 - Compression zip du fichier XML avant envoi
 - Envoi du fichier
 - Traitement des réponses XML et HTML du webservice Sandre
 - Récupération du jeton
 - Récupération des URLs de l'acquittement, du certificat et du certificat détaillé
 - Vérification du statut de validation de manière assynchrone


## Contenu de la classe

```php
Class
	void	Sandre_validate(array)	//Constructeur
	
Public
	string	Sandre_validate->getVersion(void)		//Version de la classe
	bool	Sandre_validate->setEntity(array)		//Définition de l'entité du demandeur
	bool	Sandre_validate->setRequester(array)		//Définition du contact
	bool	Sandre_validate->setFileXml(path)		//Définition du chemin du fichier XML
	bool	Sandre_validate->setToken(string)		//Définition du jeton à vérifier
	void	Sandre_validate->setDebug(bool)		//Activation du mode debug
	string	Sandre_validate->getToken(void)		//Récupération du jeton juste après la soumission
	string	Sandre_validate->getAckUri(void)		//Récupération URL de l'aquittement
	string	Sandre_validate->getCertUri(void)		//Récupération URL du certificat
	string	Sandre_validate->getCertDetailedUri(void)		//Récupération URL du certificat détaillé
	mixed	Sandre_validate->getErrors(void)		//Récupération du tableau des erreurs
	mixed	Sandre_validate->getLogs(void)		//Récupération du tableau des logs
	bool	Sandre_validate->send(bool)		//Envoi du fichier au sandre - le paramètre d'entrée active la compression zip - activé par défaut
	mixed	Sandre_validate->checkValidation(void)		//Récupère le statut de validation
```

## Fichiers de la librairie

	./lib/class.sandre.validate.php	- Fichier de la classe
	./exple_sandre_post_edilabo.php	- Exemple d'envoi d'un fichier à valider
	./exple_sandre_getstatus.php - Exemple de vérification du statut de validation
	./readme.md - Readme en anglais
	./readme.fr.md - Ce fichier
	./license
	
## Reste à faire

 - Ajouter d'autres thématique qu'EDILabo
 - Vérifier la robustesse de la connexion au webservice Sandre
 - Ajouter le support des demandes de transformation


# Documentation externe

 - Sandre [Reference Api](http://www.sandre.eaufrance.fr/api-referentiel)
 - Sandre [webservice doc](http://www.sandre.eaufrance.fr/sites/default/files/IMG/pdf/sandre_procedure_webservice_parseur_V4.pdf)

# Auteurs
La Drôme Laboratoire - 26 - France