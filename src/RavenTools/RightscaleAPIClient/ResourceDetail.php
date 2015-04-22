<?php

namespace RavenTools\RightscaleAPIClient;

class ResourceDetail extends Helper {

	public $client = null;
	public $resource_type = null;

	public function __construct($client,$resource_type,$href,$hash) {

		Helper::__construct();

		$that = &$this;

		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->href = $href;

		if(!is_null($hash) && isset($hash->links)) {
			$links = $hash->links;
			unset($hash->links);
		} else {
			$links = array();
		}

		if(!is_null($hash) && isset($hash->actions)) {
			$raw_actions = $hash->actions;
			unset($hash->actions);
		} else {
			$raw_actions = array();
		}

		if(is_null($hash)) {
			$hash = new \StdClass();
		}

		$hash->href = Helper::get_and_delete_href_from_links($links);

		// Add links to attributes set and create a method that returns the links
		$this->attributes = new Set();
		$this->attributes[] = "links";
		$this->links = $links;

		foreach($raw_actions as $a) {
			$action_name = $a->rel;

			$this->methods->$action_name = function($params=null) use (&$client,$hash,$action_name) {
				return $client->do_post("{$hash->href}/{$action_name}",$params);
			};
		}

		$associations = new Set();

		$this->get_associated_resources($client,$links,$associations);

		switch($resource_type) {
			case "instance":
				$this->methods->live_tasks = function($params=null) use (&$client,$href) {
					if(Helper::has_id($params)) {
						$path = "{$href}/live/tasks";
						$path = Helper::add_id_and_params_to_path($path,$params);
						return Resource::process($client,"live_task",$path);
					}
				};
		}

		foreach($hash as $k => $v) {
			if($associations[$k]) {
				// has links...?
			} else {
				// add to attributes and create a getter method
				$this->attributes[] = $k;
				$this->$k = $v;
			}
		}

		$this->methods->destroy = function($params=null) use (&$client,$href) {
			return $client->do_delete($href,$params);
		};

		$this->methods->update = function($params=null) use (&$client,$href,$resource_type) {

			if($resource_type == "account") {
				// HACK: handle child_account update specially
				$href = strstr("account","child_account");
			}
			return $client->do_put($href,$params);
		};

		$this->methods->show = function($params=null) use (&$that) {
			return $that;
		};
	}

	public function __tostring() {
		$out = new \StdClass();
		$out->_class = "ResourceDetail";
		$out->resource_type = $this->resource_type;
		$out->href = $this->href;
		$out->methods = array_keys(get_object_vars($this->methods));
		foreach($this->attributes as $a => $v) {
			$out->$a = $this->$a;
		}
		return print_r($out,true);
	}
}
