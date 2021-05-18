<?php
/**
 * @file class.sandre.validator.php
 * @author  Laboratoire Départemental de La Drome (26) - France
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
 * PHP class to submit EDILabo XML files & check validity against Sandre's webservice
 * Supports EDILabo V1.0 and V1.1
 */
namespace ladromelaboratoire\php_sandre_validator;
use ZipArchive;

define("___SANDREWSURI", "https://www.sandre.eaufrance.fr/PS5/api/upload");
define("___SANDREACKURI", "http://www.sandre.eaufrance.fr/PS5/api/acquittement/");
define("___SANDRECERTURI", "http://www.sandre.eaufrance.fr/PS5/api/certificat/");
define("___SANDRECERTFULLURI", "http://www.sandre.eaufrance.fr/parseur/getCertificatD.php?jeton=");
define("___UA", "LDA26-PHPbot");
define("___VERSION", "1.0");
define("___NOMSI", "LDA26-LIMS");
define("___VERSIONSI", "1.0");

define("___DEFAULT_INTERV","Laboratoire Departemental de la Drome");
define("___DEFAULT_DEPT","Nom du service");
define("___DEFAULT_SIRET","22260001700362");
define("___DEFAULT_NAME","Name requester");
define("___DEFAULT_FIRSTNAME","Firstname requester");
define("___DEFAULT_EMAIL","mail@domain.tld");
define("___DEFAULT_ERROR_CODE",'99');
define("___TOKEN_REGEXP",'/^[0-9-_]+\@[a-zA-Z0-9-_]+\.xml/');
define("___MIMETYPE_ZIP","application/x-zip-compressed");
define("___MIMETYPE_XML","text/xml");


Class Sandre_validator {
	
	private $_params = array();
	private $_eol = "\r\n";
	private $_body;
	private $_token;
	private $_scenario;
	private $_error = false;
	private $_errors = array();
	private $_sandre_valid_status = array();
	private $_debug = false;
	private $_log = array();
	
	
	function __construct($entity = array(___DEFAULT_INTERV, ___DEFAULT_DEPT, ___DEFAULT_SIRET)) {
		//init params array
		$this->_params['entity']['NomIntervenant'] = $entity[0];
		$this->_params['entity']['NomService'] = $entity[1];
		$this->_params['entity']['cdIntervenant'] = $entity[2];
		$this->_params['person'] = array("name"=>___DEFAULT_NAME, "firstname"=>___DEFAULT_FIRSTNAME, "email"=>___DEFAULT_EMAIL);
		$this->_params['file'] = array("xmlpath"=>"", "xmlname"=>"", "zippath"=>"", "zipname"=>"");
	}
	
	/////////////////////////////
	///////// Public methods
	/////////////////////////////
	
	public function getVersion() {
		return ___VERSION;
	}
	
	public function setEntity($entity) {
		if (count($entity) == 3) {
			$this->_params['entity']['NomIntervenant'] = $entity[0];
			$this->_params['entity']['NomService'] = $entity[1];
			$this->_params['entity']['cdIntervenant'] = $entity[2];
			return true;
		}
		else {
			$this->setError(98);
			return false;
		}
	}
	
	public function setRequester($person = array(___DEFAULT_NAME, ___DEFAULT_FIRSTNAME, ___DEFAULT_EMAIL)) {
		if (count($person) == 3) {
			$this->_params['person']['name'] = $person[0];
			$this->_params['person']['firstname'] = $person[1];
			$this->_params['person']['email'] = $person[2];
			return true;
		}
		else {
			$this->setError(97);
			return false;
		}
	}
	
	public function setFileXml($path) {
		if (is_file($path)) {
			$this->_params['file']['xmlpath'] = realpath($path);
			$this->_params['file']['xmlname'] = basename($path);
			$this->getScenario();
			return true;
		}
		else {
			$this->setError(90);
			return false;
		}
	}
	
	public function setToken($token) {
		//Should be used when a new object is created to check the validation status after file submission
		//in most cases the validation status comes 5 minutes after the submission
		if ($this->checkToken($token)) {
			$this->_token = $token;
			return true;
		}
		else {
			$this->setError(95);
			return false;
		}
	}
	
	public function setDebug($flag = false) {
		$this->_debug = $flag;
	}
		
	public function getToken() {
		return $this->_token;
	}
	
	public function getAckUri() {
		if ($this->_token != "" && $this->_error === false) {
			return ___SANDREACKURI . $this->_token;
		}
		else {
			return false;
		}
	}
	
	public function getCertUri() {
		if ($this->_token != "" && $this->_error === false) {
			return ___SANDRECERTURI . $this->_token;
		}
		else {
			return false;
		}
	}
	
	public function getCertDetailedUri() {
		if ($this->_token != "" && $this->_error === false) {
			return ___SANDRECERTFULLURI . $this->_token;
		}
		else {
			return false;
		}
	}
	
	public function getErrors() {
		if ($this->_error) {
			return $this->_errors;
		}
		else {
			return $this->_error;
		}
	}
	
	public function getLogs() {
		if($this->_debug) {
			return $this->_log;
		}
		else {
			return false;
		}
	}
	
	public function send($zip = true) {
		if ($zip) {
			$this->zipFile();
		}
		if ($this->_error === false) {
			$this->sendRequestPost();
		}
		return ($this->_error) ? false : true; //returns the reverse status of $_error as send() method status
	}
	
	public function checkValidation() {
		if($this->_token != "") {
			$this->getSandreAck();
			return $this->_sandre_valid_status;
		}
		else {
			return false;
		}
	}
	
	
	/////////////////////////////
	///////// Private methods
	/////////////////////////////
	
	
	private function getScenario() {
		//Get EDILabo scenario from the file. If failed, this is not an EDILabo file
		$xml = simplexml_load_file($this->_params['file']['xmlpath']);
		if($xml === false) {
			$this->setError(91);
		}
		else {
			if ($xml->Scenario->CodeScenario != "" && $xml->Scenario->VersionScenario != "") {
				$this->_scenario = $xml->Scenario->CodeScenario . ";" . $xml->Scenario->VersionScenario;
				if ($this->_debug) $this->logger("scenario", $this->_scenario);
			}
			else {
				$this->setError(92);
			}
		}
	}
	
	private function zipFile() {
		//zip the xml file to improve the data transfert
		$this->_params['file']['zippath'] = substr($this->_params['file']['xmlpath'], 0, -3) . 'zip';
		$this->_params['file']['zipname'] = substr($this->_params['file']['xmlname'], 0, -3) . 'zip';
		
		$zip = new ZipArchive;
		if ($zip->open($this->_params['file']['zippath'], ZipArchive::CREATE) === true) {
			$zip->addFile($this->_params['file']['xmlpath'], $this->_params['file']['xmlname']);
			$zip->close();	
		}
		else {
			$this->setError(94);
			$this->_params['file']['zipname'] = "";
			$this->_params['file']['xmlname'] = "";
		}
	}
	
	private function makeBody() {
		//make post request body
		$this->_params['boundary'] = md5(time());
		
		if ($this->_params['file']['zippath'] != '') {
			$filepath = $this->_params['file']['zippath'];
			$filename = $this->_params['file']['zipname'];
			$mimetype = ___MIMETYPE_ZIP;
		}
		else {
			$filepath = $this->_params['file']['xmlpath'];
			$filename = $this->_params['file']['xmlname'];
			$mimetype = ___MIMETYPE_XML;
		}
		
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="thematique"' . $this->_eol . $this->_eol;
		$this->_body .= 'EDILABO' . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="XSD"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_scenario . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="NomSI"' . $this->_eol . $this->_eol;
		$this->_body .= ___NOMSI . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="VersionSI"' . $this->_eol . $this->_eol;
		$this->_body .= ___VERSIONSI . $this->_eol;
		//$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		//$this->_body .= 'Content-Disposition: form-data; name="Transformation"' . $this->_eol . $this->_eol;
		//$this->_body .= '1' . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="NomIntervenant"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['entity']['NomIntervenant'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="CdIntervenant"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['entity']['cdIntervenant'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="schemeAgencyID"' . $this->_eol . $this->_eol;
		$this->_body .= 'SIRET' . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="intervenant"' . $this->_eol . $this->_eol;
		$this->_body .= '[' . $this->_params['entity']['cdIntervenant'] . '] ' . $this->_params['entity']['NomIntervenant'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="service"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['entity']['NomService'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="nom"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['person']['name'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="prenom"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['person']['firstname'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="email"' . $this->_eol . $this->_eol;
		$this->_body .= $this->_params['person']['email'] . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary']. $this->_eol;
		$this->_body .= 'Content-Disposition: form-data; name="XML"; filename="' . $filename . '"'. $this->_eol . $this->_eol;
		$this->_body .= 'Content-Type: ' . $mimetype . $this->_eol;
		$this->_body .= chunk_split(base64_encode(file_get_contents($filepath))) . $this->_eol;
		$this->_body .= '--'.$this->_params['boundary'] .'--' . $this->_eol . $this->_eol;

		
		if($this->_debug) $this->logger("PostBody", $this->_body);
	}
	
	private function sendRequestPost() {
		
		// let's make the body of the request
		$this->makeBody();
		
		//send data to webservice
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, ___UA . ' ' . ___VERSION);
		curl_setopt($ch, CURLOPT_HTTPHEADER,  array("Content-type: multipart/form-data;  boundary=".$this->_params['boundary']));
		curl_setopt($ch, CURLOPT_URL, ___SANDREWSURI);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // permissive SSL just in case
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // permissive SSL just in case
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_body);

		$response =  curl_exec($ch);
		
		if($this->_debug) $this->logger("Sandre Post Response", $response);

		if ( $response === false) {
			$this->setError(curl_errno($ch));
		}
		else {
			$this->parseResponseXml($response);
		}
		curl_close($ch);
	}
	
	private function parseResponseHtml($response) {
		//parse webservice html response to get the token
		$queryXPath = '//a';
		$dom = new DOMDocument();
		@$dom->loadHTML($response);
		$x = new DOMXPath($dom);
		$nodeList = $x->query($queryXPath);
		
		if ($nodeList === false) {
			$this->setError(93);
		}
		else {
			$liens=array();
			foreach ($nodeList as $node)
			{
				array_push($liens, utf8_decode(urldecode($node->nodeValue)));
			}
			//only the first is used, yes .... to be improved
			$token = substr(parse_url($liens[0], PHP_URL_QUERY), 6);
			
			if ($this->checkToken($token)) {
				$this->_token = $token;
			}
			else {
				$this->setError(95);
			}
		}
	}
	
	private function parseResponseXml($response) {
	
		$xml = simplexml_load_string($response);
		if($xml === false) {
			$this->setError(91);
		}
		else {
			if ($this->checkToken($xml->jeton)) {
					$this->_token = $xml->jeton;
			}
			else {
				$this->setError(93);
			}
		}
	}
	
	private function setError($code = ___DEFAULT_ERROR_CODE) {
		
		//CURL Error codes
		//https://curl.haxx.se/libcurl/c/libcurl-errors.html
		$error_codes[0] = '';
		$error_codes[1] = 'CURLE_UNSUPPORTED_PROTOCOL';
		$error_codes[2] = 'CURLE_FAILED_INIT';
		$error_codes[3] = 'CURLE_URL_MALFORMAT';
		$error_codes[4] = 'CURLE_URL_MALFORMAT_USER';
		$error_codes[5] = 'CURLE_COULDNT_RESOLVE_PROXY';
		$error_codes[6] = 'CURLE_COULDNT_RESOLVE_HOST';
		$error_codes[7] = 'CURLE_COULDNT_CONNECT';
		$error_codes[8] = 'CURLE_FTP_WEIRD_SERVER_REPLY';
		$error_codes[9] = 'CURLE_REMOTE_ACCESS_DENIED';
		$error_codes[11] = 'CURLE_FTP_WEIRD_PASS_REPLY';
		$error_codes[13] = 'CURLE_FTP_WEIRD_PASV_REPLY';
		$error_codes[14] = 'CURLE_FTP_WEIRD_227_FORMAT';
		$error_codes[15] = 'CURLE_FTP_CANT_GET_HOST';
		$error_codes[17] = 'CURLE_FTP_COULDNT_SET_TYPE';
		$error_codes[18] = 'CURLE_PARTIAL_FILE';
		$error_codes[19] = 'CURLE_FTP_COULDNT_RETR_FILE';
		$error_codes[21] = 'CURLE_QUOTE_ERROR';
		$error_codes[22] = 'CURLE_HTTP_RETURNED_ERROR';
		$error_codes[23] = 'CURLE_WRITE_ERROR';
		$error_codes[25] = 'CURLE_UPLOAD_FAILED';
		$error_codes[26] = 'CURLE_READ_ERROR';
		$error_codes[27] = 'CURLE_OUT_OF_MEMORY';
		$error_codes[28] = 'CURLE_OPERATION_TIMEDOUT';
		$error_codes[30] = 'CURLE_FTP_PORT_FAILED';
		$error_codes[31] = 'CURLE_FTP_COULDNT_USE_REST';
		$error_codes[33] = 'CURLE_RANGE_ERROR';
		$error_codes[34] = 'CURLE_HTTP_POST_ERROR';
		$error_codes[35] = 'CURLE_SSL_CONNECT_ERROR';
		$error_codes[36] = 'CURLE_BAD_DOWNLOAD_RESUME';
		$error_codes[37] = 'CURLE_FILE_COULDNT_READ_FILE';
		$error_codes[38] = 'CURLE_LDAP_CANNOT_BIND';
		$error_codes[39] = 'CURLE_LDAP_SEARCH_FAILED';
		$error_codes[41] = 'CURLE_FUNCTION_NOT_FOUND';
		$error_codes[42] = 'CURLE_ABORTED_BY_CALLBACK';
		$error_codes[43] = 'CURLE_BAD_FUNCTION_ARGUMENT';
		$error_codes[45] = 'CURLE_INTERFACE_FAILED';
		$error_codes[47] = 'CURLE_TOO_MANY_REDIRECTS';
		$error_codes[48] = 'CURLE_UNKNOWN_TELNET_OPTION';
		$error_codes[49] = 'CURLE_TELNET_OPTION_SYNTAX';
		$error_codes[51] = 'CURLE_PEER_FAILED_VERIFICATION';
		$error_codes[52] = 'CURLE_GOT_NOTHING';
		$error_codes[53] = 'CURLE_SSL_ENGINE_NOTFOUND';
		$error_codes[54] = 'CURLE_SSL_ENGINE_SETFAILED';
		$error_codes[55] = 'CURLE_SEND_ERROR';
		$error_codes[56] = 'CURLE_RECV_ERROR';
		$error_codes[58] = 'CURLE_SSL_CERTPROBLEM';
		$error_codes[59] = 'CURLE_SSL_CIPHER';
		$error_codes[60] = 'CURLE_SSL_CACERT';
		$error_codes[61] = 'CURLE_BAD_CONTENT_ENCODING';
		$error_codes[62] = 'CURLE_LDAP_INVALID_URL';
		$error_codes[63] = 'CURLE_FILESIZE_EXCEEDED';
		$error_codes[64] = 'CURLE_USE_SSL_FAILED';
		$error_codes[65] = 'CURLE_SEND_FAIL_REWIND';
		$error_codes[66] = 'CURLE_SSL_ENGINE_INITFAILED';
		$error_codes[67] = 'CURLE_LOGIN_DENIED';
		$error_codes[68] = 'CURLE_TFTP_NOTFOUND';
		$error_codes[69] = 'CURLE_TFTP_PERM';
		$error_codes[70] = 'CURLE_REMOTE_DISK_FULL';
		$error_codes[71] = 'CURLE_TFTP_ILLEGAL';
		$error_codes[72] = 'CURLE_TFTP_UNKNOWNID';
		$error_codes[73] = 'CURLE_REMOTE_FILE_EXISTS';
		$error_codes[74] = 'CURLE_TFTP_NOSUCHUSER';
		$error_codes[75] = 'CURLE_CONV_FAILED';
		$error_codes[76] = 'CURLE_CONV_REQD';
		$error_codes[77] = 'CURLE_SSL_CACERT_BADFILE';
		$error_codes[78] = 'CURLE_REMOTE_FILE_NOT_FOUND';
		$error_codes[79] = 'CURLE_SSH';
		$error_codes[80] = 'CURLE_SSL_SHUTDOWN_FAILED';
		$error_codes[81] = 'CURLE_AGAIN';
		$error_codes[82] = 'CURLE_SSL_CRL_BADFILE';
		$error_codes[83] = 'CURLE_SSL_ISSUER_ERROR';
		$error_codes[84] = 'CURLE_FTP_PRET_FAILED';
		$error_codes[84] = 'CURLE_FTP_PRET_FAILED';
		$error_codes[85] = 'CURLE_RTSP_CSEQ_ERROR';
		$error_codes[86] = 'CURLE_RTSP_SESSION_ERROR';
		$error_codes[87] = 'CURLE_FTP_BAD_FILE_LIST';
		$error_codes[88] = 'CURLE_CHUNK_FAILED';
		//custom codes for this library
		$error_codes[90] = 'SOURCE_FILE_DOES_NOT_EXIST';
		$error_codes[91] = 'XML_NOT_VALID_FILE';
		$error_codes[92] = 'XML_NOT_VALID_EDILABO_SCENARIO_NOT_FOUND';
		$error_codes[93] = 'SANDRE_RESPONSE_INVALID';
		$error_codes[94] = 'ZIP_CREATION_FAILED';
		$error_codes[95] = 'SANDRE_TOKEN_INVALID';
		$error_codes[96] = 'SANDRE_SIDE_PROCESSING_ERROR';
		$error_codes[97] = 'PERSON_NOT_VALID_ARRAY';
		$error_codes[98] = 'ENTITY_NOT_VALID_ARRAY';
		$error_codes[99] = 'UNKNOWN_ERROR';
		
		$this->_error = true;
		
		array_push($this->_errors, array($code, $error_codes[$code]));	
	}
	
	private function checkToken($token) {
		//try to determine if token seems to be a good one
		
		$pattern = ___TOKEN_REGEXP;
		if (preg_match($pattern, $token)) {
			return true;
		}
		else {
			return false;
		}
	}

	private function getSandreAck() {
		//send data to webservice
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_USERAGENT, ___UA . ' ' . ___VERSION);
		curl_setopt($ch, CURLOPT_URL, $this->getAckUri());
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // permissive SSL just in case
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // permissive SSL just in case
		
		$response =  curl_exec($ch);
		
		if($this->_debug) $this->logger("Sandre Get ACK response", $response);

		if ( $response === false) {
			$this->setError(curl_errno($ch));
		}
		else {
			$this->parseSandreAck($response);
		}
		curl_close($ch);		
	}
	
	private function parseSandreAck($response) {
		//read acquittement response
		$xml = simplexml_load_string($response);
		if($xml === false) {
			$this->setError(91);
		}
		else {
			if ($xml->AccuseReception->Acceptation != "") {
				if ($xml->AccuseReception->Acceptation == 0) {
					$this->_sandre_valid_status = array(0, "Processing");
					$this->setError(96);
				}
				elseif ($xml->AccuseReception->Acceptation == 1) {
					$this->_sandre_valid_status = array(1, "Validated", $this->getCertUri());
				}
				elseif ($xml->AccuseReception->Acceptation == 2) {
					$this->_sandre_valid_status = array(2, "Not valid, consult certificate for details", $this->getCertDetailedUri());
				}
				else {
					$this->setError(96);
				}
			}
			else {
				$this->setError(92);
			}
		}		
	}
	
	private function logger($name, $data) {
		$this->_log[$name] = $data;
	}
}


?>