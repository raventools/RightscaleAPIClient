<?php

namespace RavenTools\RightscaleAPIClient;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

class Client extends Helper {

	public $account_id = null;
	public $email = null;
	public $password = null;

	private $guzzle = null;
	private $guzzle_cookie = null;
	private $api_version = "1.5";

	private $api_url = "https://us-4.rightscale.com";
	private $root_resource = "/api/session";

	public function __construct($params) {

		$params = (object)$params;

		// set required parameters
		foreach(array("account_id","email","password") as $key) {
			if(!isset($params->$key)) {
				throw new \Exception("$key is a required parameter");
			} else {
				$this->$key = $params->$key;
			}
		}

		if(isset($params->guzzle)) {
			$this->guzzle = $params->guzzle;
		} else {
			$this->guzzle = new GuzzleClient();
		}

		if(isset($params->guzzle_cookie)) {
			$this->guzzle_cookie = $params->guzzle_cookie;
		} else {

			$this->guzzle_cookie = new CookiePlugin(new ArrayCookieJar());
		}

		$this->guzzle->addSubscriber($this->guzzle_cookie);

		$this->login();
		$this->init_methods();

	}

	public function login() {
		$params->url = "/api/session";
		$params->email = $this->email;
		$params->password = $this->password;
		$params->account_href = "/api/accounts/{$this->account_id}";
		$response = $this->post($params);
		return (is_null($response));
	}

	private function init_methods() {
		$params->url = $this->root_resource;
		$data = $this->get($params);
		$this->getAssociatedResources($this,$data->links);
	}

	private function decodeBody($response) {
		$body = (string)$response->getBody();
		return json_decode($body);
	}

	public function get($params) {
		$params = (object)$params;
		$params->get = get_object_vars($params);
		unset($params->get['url']);
		$response = $this->request("GET",$params);
		if($response === false) {
			return false;
		} elseif(get_class($response) == "Guzzle\Http\Message\Response") {
			$code = $response->getStatusCode();
			echo "code: $code\n";
			switch($code) {
				case "200":
					return $this->decodeBody($response);
				case "301":
				case "302":
					error_log("got redirect");
					break;
				case "404":
					throw new Exception("API route not found");
				default:
					throw new Exception("API error code {$code}");
			}
		} else {
			// why would this happen?
			error_log("not false and not a response object");
			return $response;
		}
	}

	public function post($params) {
		$params = (object)$params;
		$params->post = get_object_vars($params);
		unset($params->post['url']);
		$response = $this->request("POST",$params);

		if($response === false) {
			return false;
		} elseif(get_class($response) == "Guzzle\Http\Message\Response") {
			$code = $response->getStatusCode();
			echo "code: $code\n";
			switch($code) {
				case "201":
				case "202":
					$href = $response->getLocation();
					$resource_type = Helper::get_resource_type($href,-2);
					return Resource::process($this, $resource_type, $href);
				case "204":
					return null;
				case "200":
					if(substr("rightscale",$response->getContentType())) {
						$data = $this->decodeBody($response);
						$ret = array();
						foreach($data as $obj) {
							$ret[] = new ResourceDetail($this,$resource_type,$params->url,$obj);
						}
						return $ret;
					}
				case "301":
				case "302":
					// TODO update api url and repost
					throw new Exception("API route not found");
				default:
					throw new Exception("API error code {$code}");
			}
		}
		throw new Exception("shouldn't get here");
	}

	public function put($params) {
		$params = (object)$params;
		return $this->request("PUT",$params);
	}

	public function delete($params) {
		$params = (object)$params;
		return $this->request("DELETE",$params);
	}

	private function request($type,$params) {
		$request = $this->guzzle->$type("{$this->api_url}{$params->url}",$params->get,$params->post);
		$request->addHeader("X-Api-Version",$this->api_version);
		try {
			$response = $request->send();
		} catch(Exception $e) {
			error_log("request exception ".$e->getMessage());
			return false;
		}
		return $response;
	}
}
