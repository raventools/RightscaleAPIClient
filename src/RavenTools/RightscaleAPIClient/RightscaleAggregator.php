<?php

namespace RavenTools\RightscaleAPIClient;

use Guzzle\Http\QueryString;
use Guzzle\Http\QueryAggregator\QueryAggregatorInterface;

/**
 * Aggregates nested query string variables using PHP style [],
 * but without adding indexes
 *
 * ex: ?q[]=one&q[]=two
 */
class RightscaleAggregator implements QueryAggregatorInterface
{
    public function aggregate($key, $value, QueryString $query)
    {
		$key = "{$key}[]";
		if ($query->isUrlEncoding()) {
			return array($query->encodeValue($key) => array_map(array($query, 'encodeValue'), $value));
		} else {
			return array($key => $value);
		}
    }
}
