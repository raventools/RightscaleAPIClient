<?php

require_once("vendor/autoload.php");

use RavenTools\RightscaleAPIClient\Client as RightscaleClient;

$config = json_decode(file_get_contents("/home/phil/dev/deploy/application/config/rightscale.json"));

$client = new RightscaleClient(array(
				"account_id" => $config->account_id,
				"email" => $config->username,
				"password" => $config->password
			));

#$response = $client->deployments(array("id"=>463292004))->show()->alerts()->index();
#$response = $client->deployments(array("id"=>453652001))->show()->alerts()->index();
#$response = $client->deployments()->index();
#$response = $client->alerts(array("id"=>31245779004))->show()->alert_spec();


#$response = $client->cookbooks()->index();
/*
$response = $client->deployments()->create(array(
				"deployment[name]"=>"test api deployment",
				"deployment[description]"=>"testing deployment creation"
			));

#$response = $client->deployments(array("id"=>453652001))->show();
*/

#$response = $client->deployments()->index();
#$response = $client->deployments(array("id"=>435838001))->show()->servers()->index();

$response = $client->tags()->by_tag(array(
                    "resource_type" => "instances",
                    "tags" => array("deploy:type=testing")
                ));

$response = $response[0]->resource();

//$response = $client->deployments(array("id"=>463298004))->destroy();
//$response = $client->deployments(array("id"=>463298004))->show();

#$response = $client->deployments()->index();

if(is_bool($response)) {
	var_dump($response);
} elseif(is_array($response)) {
	foreach($response as $r) {
		if(is_string($r)) {
			echo "$r\n";
		} else {
			echo $r;
		}
	}
} elseif(get_class($response) == "RavenTools\RightscaleAPIClient\Resources") {
	echo "Resources:\n";
	echo $response;
	foreach($response as $r) {
		echo $r;
	}
} elseif(get_class($response) == "RavenTools\RightscaleAPIClient\ResourceDetail") {
	echo $response;
} elseif(get_class($response) == "RavenTools\RightscaleAPIClient\Resource") {
	echo $response;
} else {
	var_dump($response);
}
