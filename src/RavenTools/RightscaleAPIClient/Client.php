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
		$params->post['email'] = $this->email;
		$params->post['password'] = $this->password;
		$params->post['account_href'] = "/api/accounts/{$this->account_id}";
		$response = $this->post($params);
		return ($response->getStatusCode() == 204);
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
		$response = $this->request("GET",$params);
		if(get_class($response) == "Guzzle\Http\Message\Response") {
			return $this->decodeBody($response);
		} else {
			return $response;
		}
	}

	public function post($params) {
		$params = (object)$params;
		return $this->request("POST",$params);
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
