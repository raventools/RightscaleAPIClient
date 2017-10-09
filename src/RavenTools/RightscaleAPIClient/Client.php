<?php

namespace RavenTools\RightscaleAPIClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\BadResponseException;

class Client extends Helper {

	public $account_id = null;
	public $email = null;
	public $password = null;

	private $guzzle = null;
//	private $guzzle_cookie = null;
	private $api_version = "1.5";

	private $api_url = "https://us-4.rightscale.com";
	private $root_resource = "/api/session";

	public function __construct($params) {

		Helper::__construct($params);

		$params = (object)$params;

		// set required parameters
		foreach(["account_id","email","password"] as $key) {
			if(!isset($params->$key)) {
				throw new \Exception("$key is a required parameter");
			} else {
				$this->$key = $params->$key;
			}
		}

		if(isset($params->guzzle)) {
			$this->guzzle = $params->guzzle;
		} else {
			$this->guzzle = new GuzzleClient([
				'cookies' => true
			]);
		}

		/*
		if(isset($params->guzzle_cookie)) {
			$this->guzzle_cookie = $params->guzzle_cookie;
		} else {

			$this->guzzle_cookie = new CookiePlugin(new ArrayCookieJar());
		}
		 */

//		$this->guzzle->addSubscriber($this->guzzle_cookie);

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
		list($resource_type,$href,$data) = $this->do_get($this->root_resource);
		$this->get_associated_resources($this,$data->links);
	}

	private function decodeBody($response) {
		$body = (string)$response->getBody();
		return json_decode($body);
	}

	public function do_get($url,$params = null) {
		$response = $this->request("GET",$url,$params);

		if($response instanceof Response) {
			$code = $response->getStatusCode();
			switch($code) {
				case "200":

					$content_type = $response->getHeader('Content-Type')[0];
					if(strstr($content_type,"rightscale")) {
						$resource_type = Helper::get_resource_type($content_type);
					} elseif(strstr("text/plain",$content_type)) {
						$resource_type = "text";
					} else {
						$resource_type = "";
					}

				case "301":
				case "302":
					break;
				case "404":
					throw new \Exception("API route not found");
				default:
					throw new \Exception("API error code {$code}");
			}
		} else {
			// why would this happen?
			throw new \Exception("got exceptional response from do_get. TODO meaningful error message");
		}

		if($resource_type == "text") {
			$data = (object)["text" => (string)$response->getBody()];
		} else {
			$data = $this->decodeBody($response);
		}

		return [$resource_type, $url, $data];
	}

	public function do_post($url,$params) {
		$response = $this->request("POST",$url,$params);

		if($response === false) {
			return false;
		} elseif($response instanceof Response) {
			$code = $response->getStatusCode();
			switch($code) {
				case "201":
				case "202":
					$href = $response->getHeader('Location');
					$resource_type = Helper::get_resource_type($href,-2);
					return Resource::process($this, $resource_type, $href);
				case "204":
					return null;
				case "200":
					$content_type = $response->getHeader('Content-Type')[0];
					$ret = [];
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
		throw new \Exception("shouldn't get here");
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
			$headers = [
				"X-Api-Version" => $this->api_version
			];

			$query = $this->prepareQuery($params);

			$response = $this->guzzle->request(
				$type,
				"{$this->api_url}{$url}",
				[
					'headers' => $headers, 
					'query' => $query
				]
			);

		} catch(BadResponseException $e) {
			error_log("request exception ".$e->getMessage());
			echo 'Request exception: ' . $e->getMessage()."\n";
			return false;
		}
		return $response;
	}

	/**
	 * rightscale requires arrays in query parameters to have no index,
	 * ex: tag[]=deploy:type=testing&tag[]=deploy:type=production
	 */
	private function prepareQuery($params = []) {
		if(!is_array($params)) {
			return $params;
		}

		$query = [];

		foreach($params as $key => $value) {
			if(is_array($value)) {
				foreach($value as $element) {
					$query[] = sprintf(
						'%s[]=%s',
						$key,
						urlencode($element)
					);
				}
			} else {
				$query[] = sprintf(
					'%s=%s',
					$key,
					urlencode($value)
				);
			}
		}

		return implode("&",$query);
	}
}
