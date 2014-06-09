<?php

namespace RavenTools\RightscaleAPIClient;

class Resources extends Helper implements \Iterator {

	public $client = null;
	public $resource_type = null;
	public $path = null;
	public $resources = null;

	public function __construct($client, $resource_type, $path, $hash = null) {

		Helper::__construct();

		$that = &$this;

		// fix exceptional resource types
		if(array_key_exists($singular = self::get_singular($resource_type),$this->INCONSISTENT_RESOURCE_TYPES)) {
			$resource_type = $this->INCONSISTENT_RESOURCE_TYPES[$singular];
		}

		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->path = $path;

		$this->resources = array();

		$this->methods->index = function($params) use (&$that,&$client,$resource_type,$path) {

			if($resource_type == "session") {
				$hash = $client->do_get($path,$params);
				return new ResourceDetail($client,$resource_type,$path,$hash);
			} 

			$hash = $client->do_get($path,$params);
			$that->resources = Resource::process($client,$resource_type,$path,$hash);
			return $that;
		};

		$this->methods->create = function($params) use (&$client,$path) {
			return $client->do_post($path,$params);
		};

		if(isset($this->RESOURCE_SPECIAL_ACTIONS[$resource_type])) {
			foreach($this->RESOURCE_SPECIAL_ACTIONS[$resource_type] as $meth => $action) {
				$action_path = Helper::insert_in_path($path,$meth);
				$this->methods->$meth = function($params) use (&$client,$action,$action_path) {
					return $client->$action($action_path,$params);
				};
			}
		}
	}

	public function __call($method,$args) {
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
		$out->_class = "Resources";
		$out->resource_type = $this->resource_type;
		$out->path = $this->path;
		$out->resources = count($this->resources);
		$out->methods = array_keys(get_object_vars($this->methods));
		return print_r($out,true);
	}

	public function rewind() {
		return reset($this->resources);
	}

	public function current() {
		return current($this->resources);
	}

	public function key() {
		return key($this->resources);
	}

	public function next() {
		return next($this->resources);
	}

	public function valid() {
		return key($this->resources) !== null;
	}
}
