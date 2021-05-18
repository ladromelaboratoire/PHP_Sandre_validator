<?php
/**
 * @file exple_sandre_post_edilabo.php
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
 * This example uses the Sandre_validate class to post an EDIlabo file to Sandre's webservice
 * It provides the token and error. Logs can be activated
 */
require '../vendor/autoload.php';
use ladromelaboratoire\php_sandre_validator\Sandre_validator;

	//data to process
	$file_sandre = ('./sampledata/myxmlfile.xml');
	$person = array("LDA26-Name", "LDA26-Firstname", "email@domain.tld");
	$entity = array("Laboratoire Departemental de la Drome", "lims", "22260001700362");
	
	//////////////////////////////////////////////
	//data processing using Sandre_Validator class
	//////////////////////////////////////////////
	$sandre = New Sandre_validator($entity);
	$sandre->setDebug(false);
	$sandre->setRequester($person);
	$sandre->setFileXml($file_sandre);
	$sandre->send();
	
	/*
	 *	Other usage : using a loop to submit several files and storing the token for each one. $file_sandre is then an array
	 *
	 *  for ($i=0; $i < count($file_sandre); $i++) {
	 *		$sandre->setFileXml($file_sandre[$i]);
	 *		$sandre->send();
	 *		$tokens[] = $sandre->getToken();
	 *	}
	 *
	 */

	
	///////////////////////////////////////////////
	//display results as plain text for the example
	///////////////////////////////////////////////
	echo "<html><body><pre>";

	echo "Token: " . $sandre->getToken() . "\r\n";
	echo "ACK: " . $sandre->getAckUri() . "\r\n";
	echo "Cert: " . $sandre->getCertUri() . "\r\n";
	echo "Cert Detailed: " . $sandre->getCertDetailedUri() . "\r\n";
	echo "\r\n\r\n";
	echo "Errors\r\n";
	print_r ($sandre->getErrors());
	echo "\r\n\r\n";
	echo "Logs\r\n";
	print_r ($sandre->getLogs());
	echo "\r\n\r\n";

	echo "</pre></body></html>";
?>