<?php

namespace RavenTools\RightscaleAPIClient;

class Resource extends Helper{

	public $client = null;
	public $resource_type = null;
	public $path = null;
	public $data = null;

	public function __construct($client,$resource_type,$href,$hash=null) {

		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->path = $href;
		$this->hash = $hash;

		// add delete method
		$this->methods->destroy = function($params) use ($client,$href) {
			$params['url'] = $href;
			return $client->delete($params);
		};

		// add update method
		$this->methods->update = function($params) use ($client,$href) {
			$params['url'] = $href;
			$client->put($params);
		};

		// add show method
		$this->methods->show = function($params) use ($client,$resource_type,$href) {
			$params['url'] = $href;
			$hash = $client->get($params);
			return new ResourceDetail($client,$resource_type,$href,$hash);
		};
	}

	public static function process($client, $resource_type, $path, $data = null) {
		if(is_array($data)) {
			$ret = array();
			foreach($data as $obj) {
				$obj_href = (Helper::get_href_from_links($obj->links) ?: $path);
				$ret[] = new ResourceDetail($client, $resource_type, $obj_href, $obj);
			}
			return $ret;
		} else {
			return new Resource($client, $resource_type, $path, $data);
		}
	}

	/**
	 * if we haven't defined a method, append to href and post
	 */
	public function __call($method,$args) {
		try {
			return parent::__call($method,$args);
		} catch(\Exception $e) {
			$params = $args[0];
			$params->url = "{$this->path}/{$method}";
			return $this->client->post($params);
		}
	}

	public function __tostring() {
		$out = new \StdClass();
		$out->_class = "Resource";
		$out->resource_type = $this->resource_type;
		$out->path = $this->path;
		$out->methods = array_keys(get_object_vars($this->methods));
		return print_r($out,true);
	}
}
