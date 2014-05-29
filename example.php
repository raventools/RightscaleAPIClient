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
$response = $client->deployments(array("id"=>435838001))->show()->servers()->index();

echo get_class($response)."\n";

//$response = $client->deployments(array("id"=>463298004))->destroy();
//$response = $client->deployments(array("id"=>463298004))->show();

#$response = $client->deployments()->index();

if(is_array($response)) {
	print_r($response);
} elseif(get_class($response) == "RavenTools\RightscaleAPIClient\Resources") {
	echo "Resources:\n";
	echo $response;
	foreach($response as $r) {
		echo $r;
	}
} else {
	var_dump($response);
}
