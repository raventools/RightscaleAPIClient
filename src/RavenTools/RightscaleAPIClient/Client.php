<?php

namespace RavenTools\RightscaleAPIClient;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Http\Exception\BadResponseException;

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
		$params['email'] = $this->email;
		$params['password'] = $this->password;
		$params['account_href'] = "/api/accounts/{$this->account_id}";
		$response = $this->do_post("/api/session",$params);
		return (is_null($response));
	}

	private function init_methods() {
		$data = $this->do_get($this->root_resource,$params);
		$this->get_associated_resources($this,$data->links);
	}

	private function decodeBody($response) {
		$body = (string)$response->getBody();
		return json_decode($body);
	}

	public function do_get($url,$params) {
		$response = $this->request("GET",$url,$params);

		if($response === false) {
			return false;
		} elseif(get_class($response) == "Guzzle\Http\Message\Response") {
			$code = $response->getStatusCode();
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

	public function do_post($url,$params) {
		$response = $this->request("POST",$url,$params);

		if($response === false) {
			return false;
		} elseif(get_class($response) == "Guzzle\Http\Message\Response") {
			$code = $response->getStatusCode();
			switch($code) {
				case "201":
				case "202":
					$href = $response->getLocation();
					$resource_type = Helper::get_resource_type($href,-2);
					return Resource::process($this, $resource_type, $href);
				case "204":
					return null;
				case "200":
					$content_type = $response->getContentType();
					$ret = array();
					if(strpos($content_type,"rightscale") !== false) {
						$resource_type = Helper::get_resource_type($content_type);
						$data = $this->decodeBody($response);
						foreach($data as $obj) {
							$ret[] = new ResourceDetail($this,$resource_type,$url,$obj);
						}
					}
					return $ret;
				case "301":
				case "302":
					// TODO update api url and repost
					throw new \Exception("API route not found");
				default:
					throw new \Exception("API error code {$code}");
			}
		}
		throw new Exception("shouldn't get here");
	}

	public function do_put($url,$params) {
		$params = (object)$params;
		return $this->request("PUT",$url,$params);
	}

	public function do_delete($url,$params) {
		$params = (object)$params;
		return $this->request("DELETE",$url,$params);
	}

	private function request($type,$url,$params) {
		try {
			$request = $this->guzzle->$type(
						"{$this->api_url}{$url}",
						array("X-Api-Version"=>$this->api_version)
					);

			$query = $request->getQuery();
			$query->setAggregator(new RightscaleAggregator);

			// add query parameters, array-ify if necessary
			if(is_array($params)) {
				foreach($params as $name => $value) {
					if(is_array($value) && count($value) == 1) {
						$query->add("{$name}[]",end($value));
					} elseif(is_array($value)) {
						foreach($value as $array_element) {
							$query->add($name,$array_element);
						}
					} else {
						$query->add($name,$value);
					}
				}
			}

			$response = $request->send();
		} catch(BadResponseException $e) {
			error_log("request exception ".$e->getMessage());
			echo 'Request exception: ' . $e->getMessage();
			echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . "\n";
			echo 'HTTP request: ' . $e->getRequest() . "\n";
			echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . "\n";
			echo 'HTTP response: ' . $e->getResponse() . "\n";
			return false;
		}
		return $response;
	}
}
