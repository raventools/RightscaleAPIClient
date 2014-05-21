<?php

require_once("vendor/autoload.php");

$config = json_decode(file_get_contents("/home/phil/dev/deploy/application/config/rightscale.json"));

$client = new RavenTools\RightscaleAPIClient\Client(array(
				"account_id" => $config->account_id,
				"email" => $config->username,
				"password" => $config->password
			));

