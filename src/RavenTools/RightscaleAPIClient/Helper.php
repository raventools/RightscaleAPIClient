<?php

namespace RavenTools\RightscaleAPIClient;

class Helper {

	protected $rels = null;
	protected $methods = null;

	public function __call($method,$args) {
		if(isset($this->methods->$method)) {
			$method = $this->methods->$method;
			return $method($args[0]);
		}
	}

	public function api_methods() {
		if(isset($this->methods)) {
			return array_keys(get_object_vars($this->methods));
		}
		return false;
	}

	protected function getAssociatedResources($client,$links) {
		$rels = new \StdClass();

		foreach($links as $l) {
			$rels->{$l->rel} = (is_array($l->href) ? $l->href : array($l->href));
		}

		foreach($rels as $rel => $hrefs) {
			$that = clone $this;
			$this->methods->$rel = function($params) use ($that,$client,$rel,$hrefs) {
				/*
				echo "rel $rel\n";
				print_r($hrefs);
				print_r($params);
				*/
				if(count($hrefs) == 1) {

					if(Helper::has_id($params) || Helper::is_singular($rel)) {
						echo "yeah we got an id\n";
						// user wants a single resource

						// calling data() you don't want a resource object back
						if($rel == "data") {
							return new ResourceDetail($hrefs[0],$params);
						}

						if(Helper::is_singular($rel)) {
							$resource_type = Helper::get_resource_type($hrefs[0],-2);
						} else {
							$resource_type = Helper::get_resource_type($hrefs[0],-1);
						}

						$path = Helper::add_id_and_params_to_path($hrefs[0],$params);

						Helper::get_resource_type($hrefs[0],-1);
						return Resource::Process($client,$resource_type,$path);

					} else {
						echo "no id call\n";
						$resource_type = Helper::get_resource_type($hrefs[0],-1);
						$path = Helper::add_id_and_params_to_path($hrefs[0],$params);
						return new Resources($client,$resource_type,$path);
					}

				} else {
					
				}
			};
		}
	}

	public static function is_singular($str) {
		if(in_array($str,array("data","audit_entry","ip_address","process"))) {
			return true;
		}
		return (substr($str,-1,1) != "s");
	}

	public static function get_singular($word) {
		switch((string)$word) {
			case "audit_entries":
				return "audit_entry";
			case "ip_addresses":
				return "ip_address";
			case "processes":
				return "process";
			default:
				return substr($word,0,-1);
		}
	}

	public static function add_id_and_params_to_path($orig_path,$params) {
		$path = $orig_path;
		if(Helper::has_id($params)) {
			$path .= "/{$params['id']}";
			unset($params['id']);
		}

		if(is_array($params)) {
			$params_esc = array();
			array_walk($params, function($v,$k) use ($params_esc) {
						$params_esc[] = "{$k}=".urlencode($v);
					});
			$params_string = implode("&",$params_esc);
		}

		if(Helper::has_filters($params)) {
			$filters = $params['filters'];
			$filters = array_map(function($v) {
						return urlencode($v);
					},$filters);
			$path .= "?filter[]=".implode("&filter[]=",$filters);
			$path .= "&{$params_string}";
		} elseif(strlen($params_string) > 0) {
			$path .= "?{$params_string}";
		}
		return $path;
	}

	public static function has_id($params) {
		return (is_array($params) && array_key_exists("id",$params));
	}

	public static function has_filters($params) {
		return (is_array($params) && array_key_exists("filters",$params));
	}

	public static function get_resource_type($href,$offset) {
		return Helper::get_singular(end(array_slice(explode("/",$href),$offset,1)));
	} 

	public static function get_href_from_links($links) {
		foreach($links as $l) {
			if($l->rel == "self") {
				return $l->href;
			}
		}
		return false;
	}
}
