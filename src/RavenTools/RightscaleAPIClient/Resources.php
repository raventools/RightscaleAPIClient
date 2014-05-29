<?php

namespace RavenTools\RightscaleAPIClient;

class Resources extends Helper implements \Iterator {

	public $resource_type = null;
	public $path = null;
	public $resources = null;

	public function __construct($client, $resource_type, $path, $hash = null) {

		$that = &$this;

		$this->resource_type = $resource_type;
		$this->path = $path;

		$this->resources = array();

		$this->methods->index = function($params) use (&$that,$client,$resource_type,$path) {

			if($resource_type == "session") {
				$params['url'] = $path;
				$hash = $client->get($params);
				return new ResourceDetail($client,$resource_type,$path,$hash);
			} 

            $params['url'] = $path;
            $hash = $client->get($params);
            $that->resources = Resource::process($client,$resource_type,$path,$hash);
			return $that;
		};

		$this->methods->create = function($params) use ($client,$path) {
			$params['url'] = $path;
			return $client->post($params);
		};
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
