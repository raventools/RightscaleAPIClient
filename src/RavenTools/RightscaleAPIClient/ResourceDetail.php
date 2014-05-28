<?php

namespace RavenTools\RightscaleAPIClient;

class ResourceDetail extends Helper {

	public $client = null;
	public $resource_type = null;

	public function __construct($client,$resource_type,$href,$hash) {
		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->href = $href;
		foreach($hash as $k => $v) {
			$this->$k = $v;
		}
	}

	public function __tostring() {
		$out = new \StdClass();
		$out->_class = "ResourceDetail";
		$out->resource_type = $this->resource_type;
		$out->href = $this->href;
		$out->name = $this->name;
		return print_r($out,true);
	}
}
