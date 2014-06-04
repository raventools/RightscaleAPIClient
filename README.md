# Rightscale API Client for PHP

This library implements Rightscale's 1.5 API in PHP.  It is currently incomplete
but the aim is to mirror the functionality of the official Rightscale ruby library

- Ruby API client: https://github.com/rightscale/right_api_client
- API Documentation: http://support.rightscale.com/12-Guides/RightScale_API_1.5
- API Reference Docs: http://reference.rightscale.com/api1.5/index.html

This is an unofficial library and is *not supported by Rightscale.*

## Installation

Installation through Composer is recommended.

composer.json:
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/raventools/RightscaleAPIClient"
        }
    ],
	"require": {
		"raventools/RightscaleAPIClient": "master"
	}
}
```

Then require Composer's autoloader
```
require 'vendor/autoload.php';
```

## Examples

This client should function in much the same way as the official ruby api client, 
for design philosophy, etc, see https://github.com/rightscale/right_api_client

Creating a new client:
```
$client = new RightscaleClient(array(
                "account_id" => 1234,
                "email" => "example@email.com",
                "password" => "54321"
            ));
```

Listing api methods available to a particular resource:
```
$methods = $client->api_methods();

$methods = $client->servers(array("id"=>995905004))->api_methods();
```

List Deployments:
```
$resources = $client->deployments()->index();
```

Get list of instances with the tag "deploy:myapp=true"
```
$resourcedetail = $client->
				tags()->
				by_tag(
					array(
						"resource_type"=>"instances",
						"tags"=>array("deploy:myapp=true")
					)
				);
```
