<?php
/**
 * @file exple_sandre_getstatus_edilabo.php
 * @author  Laboratoire Départemental de La Drôme (26) - France
 * @version 1.0
 * @date 2018/11/15
 *
 * @section LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details at
 * https://www.gnu.org/copyleft/gpl.html
 *
 * @section DESCRIPTION
 *
 * This example uses the Sandre_validate class to request the validation status of an EDILabo file. It uses the token provided earlier by the webservice
 * It provides the results and error. Logs can be activated
 */
require_once('./lib/class.sandre.validator.php');

	//Data
	$token = "2018-11-16_14-11-51-528@myxmlfile.xml"; //warning, this one will generate HTTP 500 error Sandre side
	$person = array("LDA26-Name", "LDA26-Firstname", "email@domain.tld");
	$entity = array("Laboratoire Departemental de la Drome", "lims", "22260001700362");

	////////////////////////////////////////////////
	//Check validation using Sandre_Validator class
	////////////////////////////////////////////////
	$sandre = New Sandre_validator($entity);
	$sandre->setDebug(false);
	$sandre->setRequester($person);
	$sandre->setToken($token);
	$result = $sandre->checkValidation();


	
	///////////////////////////////////////////////
	//display results as plain text for the example
	///////////////////////////////////////////////
	echo "<html><body><pre>";
	
	echo "Token: " . $sandre->getToken() . "\r\n\r\n";
	echo "Result\r\n";
	print_r($result);
	echo "\r\n\r\n";
	echo "Errors\r\n";
	print_r ($sandre->getErrors());
	echo "\r\n\r\n";
	echo "Logs\r\n";
	print_r ($sandre->getLogs());
	echo "\r\n\r\n";

	echo "</pre></body></html>";
?>