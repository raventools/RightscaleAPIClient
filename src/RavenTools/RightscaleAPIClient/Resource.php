<?php

namespace RavenTools\RightscaleAPIClient;

class Resource extends Helper{

	public $client = null;
	public $resource_type = null;
	public $path = null;
	public $data = null;

	public function __construct($client,$resource_type,$href,$hash=null) {

		Helper::__construct();

		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->path = $href;
		$this->hash = $hash;

		// add delete method
		$this->methods->destroy = function($params=array()) use (&$client,$href) {
			return $client->do_delete($href,$params);
		};

		// add update method
		$this->methods->update = function($params=array()) use (&$client,$href) {
			$client->do_put($href,$params);
		};

		// add show method
		$this->methods->show = function($params=array()) use (&$client,$resource_type,$href) {
			$hash = $client->do_get($href,$params);
			return new ResourceDetail($client,$resource_type,$href,$hash);
		};
	}

	public static function process(&$client, $resource_type, $path, $data = null) {
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
	public function __call($method,$args=array()) {
		try {
			// throws exception when method isn't found
			return parent::__call($method,$args);
		} catch(\Exception $e) {
			$params = $args[0];
			return $this->client->do_post("{$this->path}/{$method}",$params);
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
