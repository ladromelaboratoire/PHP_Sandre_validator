# Sandre_validate

This PHP class is used for EDILabo context to validate the generated files against [Sandre's](http://www.sandre.eaufrance.fr/) webservice.
It allows users to send their XML files and get a token as answer. The token will be used for further validation checks. Validation process is done asynchronously. You need to check out the result afterwards using that token.

It supports versions 1.0 & 1.1 for COM_LABO & LABO_DEST scenario. This is automatically determined by the library.

# Library content

## Revision history
	v1.0	2018-11-15	first release of the library
	
## Features
The following features are included
 - Requester's entity setting
 - Requester's contact detail setting
 - Zip compression of the zip file
 - Parse XML & HTML Sandre's webservice response
 - Get token
 - Get the URIs
 - Check validation result

## Class content

```php
Class
	void	Sandre_validate(array)	//Constructor
	
Public
	string	Sandre_validate->getVersion(void)		//library version
	bool	Sandre_validate->setEntity(array)		//set requester entity
	bool	Sandre_validate->setRequester(array)		//set requester contact detail
	bool	Sandre_validate->setFileXml(path)		//set XML file path
	bool	Sandre_validate->setToken(string)		//set token to check validation
	void	Sandre_validate->setDebug(bool)		//set debug mode
	string	Sandre_validate->getToken(void)		//get token after submission
	string	Sandre_validate->getAckUri(void)		//get ACK URI
	string	Sandre_validate->getCertUri(void)		//get certificate URI
	string	Sandre_validate->getCertDetailedUri(void)		//get detailed certificate URI
	mixed	Sandre_validate->getErrors(void)		//get errors array
	mixed	Sandre_validate->getLogs(void)		//get logs array
	bool	Sandre_validate->send(bool)		//send post request - input boolean to zip XML before sending it - activated as default
	mixed	Sandre_validate->checkValidation(void)		//get validation status
```

## Files of the library

	./lib/class.sandre.validate.php	- library file
	./exple_sandre_post_edilabo.php	- example script to post xml
	./exple_sandre_getstatus.php - example script to check a validation status
	./readme.md - this file
	./readme.fr.md - Readme in French
	./license
	
## To do

 - Add support for other "Thematique" than EDILABO
 - Improve connection robustness to webservice
 - Add transformation requests
 - Test against V5 Sandre's webservice


# External documentation

 - Sandre [Reference Api](http://www.sandre.eaufrance.fr/api-referentiel)
 - Sandre [webservice doc V4](http://www.sandre.eaufrance.fr/sites/default/files/IMG/pdf/sandre_procedure_webservice_parseur_V4.pdf)
 - Sandre [webservice doc V5](www.sandre.eaufrance.fr/sites/default/files/IMG/pdf/sandre_procedure_webservice_parseur_V5.pdf)

# Authors
La Drôme Laboratoire - 26 - France

