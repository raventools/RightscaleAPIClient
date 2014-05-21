<?php


use \Guzzle\Plugin\Cookie\CookiePlugin;
use \Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

namespace RavenTools\RightscaleAPIClient;

class Client extends Helper {

	public $account_id = null;
	public $email = null;
	public $password = null;

	private $guzzle = null;
	private $guzzle_cookie = null;
	private $api_version = "1.5";

	private $api_url = "https://us-4.rightscale.com";

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
			$this->guzzle = new \Guzzle\Http\Client();
		}

		if(isset($params->guzzle_cookie)) {
			$this->guzzle_cookie = $params->guzzle_cookie;
		} else {

			$this->guzzle_cookie = new \Guzzle\Plugin\Cookie\CookiePlugin(new \Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar());
		}

		$this->guzzle->addSubscriber($this->guzzle_cookie);

		print_r($this->login());
	}

	public function login() {
		$params->url = "/api/session";
		$params->post['email'] = $this->email;
		$params->post['password'] = $this->password;
		$params->post['account_href'] = "/api/accounts/{$this->account_id}";
		return $this->post($params);
	}

	public function get($params) {
		$params = (object)$params;
		$request = $this->request("GET",$params);
		return $request->send();
	}

	public function post($params) {
		$params = (object)$params;
		$request = $this->request("POST",$params);
		return $request->send();
	}

	public function put($params) {
		$params = (object)$params;
		$request = $this->request("PUT",$params);
		return $request->send();
	}

	public function delete($params) {
		$params = (object)$params;
		$request = $this->request("DELETE",$params);
		return $request->send();
	}

	private function request($type,$params) {
		$request = $this->guzzle->$type("{$this->api_url}{$params->url}",$params->get,$params->post);
		$request->addHeader("X-Api-Version",$this->api_version);
		return $request;
	}
}
