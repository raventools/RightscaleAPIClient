<?php

namespace RavenTools\RightscaleAPIClient;

class ResourceDetail extends Helper {

	public $client = null;
	public $resource_type = null;

	public function __construct($client,$resource_type,$href,$hash) {
		$this->client = $client;
		$this->resource_type = $resource_type;
		$this->href = $href;
		$this->name = $hash->name;

		$links = $hash->links;
		unset($hash->links);

		$raw_actions = $hash->actions;
		unset($hash->actions);

		$hash->href = Helper::get_and_delete_href_from_links($links);

#		print_r($links);
#		print_r($raw_actions);
#		print_r($hash);

		foreach($raw_actions as $a) {
			$action_name = $a->rel;
			$this->methods->$action_name = function($params) use ($client,$hash,$action_name) {
				$params['url'] = "{$hash->href}/{$action_name}";
				return $client->post($params);
			};
		}

		$this->get_associated_resources($client,$links,$associations);

		$this->methods->destroy = function($params) {
		};
		$this->methods->update = function($params) {
		};
		$this->methods->show = function($params) {
		};
	}

	public function __tostring() {
		$out = new \StdClass();
		$out->_class = "ResourceDetail";
		$out->resource_type = $this->resource_type;
		$out->href = $this->href;
		$out->name = $this->name;
		$out->methods = array_keys(get_object_vars($this->methods));
		return print_r($out,true);
	}
}
