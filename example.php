<?php

require_once("vendor/autoload.php");

use RavenTools\RightscaleAPIClient\Client as RightscaleClient;

$config = json_decode(file_get_contents("/home/phil/dev/deploy/application/config/rightscale.json"));

$client = new RightscaleClient(array(
				"account_id" => $config->account_id,
				"email" => $config->username,
				"password" => $config->password
			));

/*
$response = $client->deployments(array("id"=>463292004))->show();
#$response = $client->cookbooks()->index();
$response = $client->deployments()->create(array(
				"deployment[name]"=>"test api deployment (3)",
				"deployment[description]"=>"testing deployment creation"
			));
*/

$response = $client->deployments(array("id"=>463298004))->destroy();
//$response = $client->deployments(array("id"=>463292004))->show();


if(is_array($response)) {
	print_r($response);
} elseif(get_class($response) == "RavenTools\RightscaleAPIClient\Resources") {
	foreach($response as $r) {
		echo $r;
	}
} else {
	echo $response;
}
