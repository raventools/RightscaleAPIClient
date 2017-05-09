<?php

namespace RavenTools\RightscaleAPIClient;

class Helper {

	protected $rels = null;
	protected $methods = null;

	// Some resource_types are not the same as the last thing in the URL, put these here to ensure consistency
	protected $INCONSISTENT_RESOURCE_TYPES = [
		"current_instance" => "instance",
		"data" => "monitoring_metric_data",
		"setting" => "multi_cloud_image_setting"
	];

	/**
	 * Some RightApi::Resources have methods that operate on the resource type itself
	 * and not on a particular one (ie: without specifying an id). Place these here:
	 */
	protected $RESOURCE_SPECIAL_ACTIONS = [
		"instances" => [
			"multi_terminate" => "do_post", 
			"multi_run_executable" => "do_post"
		],
		"inputs" => [
			"multi_update" => "do_post"
		],
		"tags" => [
			"by_tag" => "do_post", 
			"by_resource" => "do_post", 
			"multi_add" => "do_post", 
			"multi_delete" => "do_post"
		],
		"backups" => [
			"cleanup" => "do_post"
		]
	];

	// List of resources that are available as instance-facing calls
	protected $INSTANCE_FACING_RESOURCES = [
		"backups", 
		"live_tasks", 
		"volumes", 
		"volume_attachments", 
		"volume_snapshots", 
		"volume_types"
	];

	public function __construct() { 
		if(is_null($this->methods)) {
			$this->methods = new \StdClass();
		}
	}

	/**
	 * magic method to call an automatically-created closure method
	 */
	public function __call($method,$args=[]) {
		if(isset($this->methods->$method)) {
			$method = $this->methods->$method;
			if(!empty($args)) {
				return $method($args[0]);
			}
			return $method();
		}
		throw new \Exception("method not found");
	}

	/**
	 * return array of configured closure methods
	 */
	public function api_methods() {
		if(isset($this->methods)) {
			return array_keys(get_object_vars($this->methods));
		}
		return false;
	}

	/**
	 * creates instance methods out of the associated resources from links
	 */
	protected function get_associated_resources($client,$links,Set &$associations=null) {

		$rels = [];
		foreach($links as $l) {
			if(array_key_exists($l->rel,$rels)) {
				$rels[$l->rel][] = $l->href;
			} else {
				$rels[$l->rel] = [$l->href];
			}
		}

		foreach($rels as $rel => $hrefs) {

			if(!is_null($associations)) {
				$associations[] = $rel;
			}

			$that = &$this;

			$this->methods->$rel = function($params=null) use (&$that,&$client,$rel,$hrefs) {

				if(count($hrefs) == 1) {

					if(Helper::has_id($params) || Helper::is_singular($rel)) {
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
						$resource_type = Helper::get_resource_type($hrefs[0],-1);
						$path = Helper::add_id_and_params_to_path($hrefs[0],$params);
						return new Resources($client,$resource_type,$path);
					}

				} else {
					$resources = [];
					if(Helper::has_id($params) || Helper::is_singular($rel)) {
						foreach($hrefs as $href) {
							// user wants a single resource. Doing show, update, delete, etc
							if(Helper::is_singular($rel)) {
								$resource_type = Helper::get_resource_type($href,-2);
							} else {
								$resource_type = Helper::get_resource_type($href,-1);
							}
							$path = Helper::add_id_and_params_to_path($href,$params);
							$resources[] = Resource::process($client,$resource_type,$path);
						}
					} else {
						foreach($hrefs as $href) {
							$resource_type = Helper::get_resource_type($href,-1);
							$path = Helper::add_id_and_params_to_path($href,$params);
							$resources[] = new Resources($client,$resource_type,$path);
						}
					}
					return $resources;
				}
			};
		}
	}

	public static function insert_in_path($path, $term) {
		if(strpos($path,"?") !== false) {
			$new_path = str_replace("?","/{$term}?",$path);
		} else {
			$new_path = "{$path}/{$term}";
		}
		return $new_path;
	}

	public static function is_singular($str) {
		if(in_array($str,["data","audit_entry","ip_address","process"])) {
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

		if(is_array($params) && array_key_exists("filter",$params)) {
			$filters = $params['filter'];
			unset($params['filter']);
		}

		if(is_array($params)) {
			$params_esc = [];
			array_walk($params, function($v,$k) use (&$params_esc) {
						$params_esc[] = "{$k}=".urlencode($v);
					});
			$params_string = implode("&",$params_esc);
		}

		if(isset($filters) && is_array($filters)) {
			$filters = array_map(function($v) {
						return urlencode($v);
					},$filters);
			$path .= "?filter[]=".implode("&filter[]=",$filters);
			$path .= "&{$params_string}";
		} elseif(isset($params_string) && strlen($params_string) > 0) {
			$path .= "?{$params_string}";
		}
		return $path;
	}

	public static function has_id($params) {
		return (is_array($params) && array_key_exists("id",$params));
	}

	public static function has_filters($params) {
		return (is_array($params) && array_key_exists("filter",$params));
	}

	public static function get_resource_type($href,$offset=null) {
		if(!is_null($offset)) {
			return Helper::get_singular(end(array_slice(explode("/",$href),$offset,1)));
		} elseif(strstr($href,"rightscale")) {
			preg_match("/\.rightscale\.([^+]+)\+json/",$href,$matches);
			return $matches[1];
		}
	} 

	public static function get_href_from_links($links) {
		foreach($links as $l) {
			if($l->rel == "self") {
				return $l->href;
			}
		}
		return false;
	}

	public static function get_and_delete_href_from_links(&$links) {
		foreach($links as $k => $l) {
			if($l->rel == "self") {
				$href = $l->href;
				unset($links[$k]);
				return $href;
			}
		}
		return false;
	}
}
