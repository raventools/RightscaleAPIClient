<?php

require_once("vendor/autoload.php");

use RavenTools\RightscaleAPIClient\Client as RightscaleClient;

$config = json_decode(file_get_contents("/home/phil/dev/deploy/application/config/rightscale.json"));

$client = new RightscaleClient(array(
				"account_id" => $config->account_id,
				"email" => $config->username,
				"password" => $config->password
			));

#$response = $client->deployments(array("id"=>435838001))->show();
$response = $client->deployments()->index();

foreach($response as $r) {
	echo (string)$r;
}
