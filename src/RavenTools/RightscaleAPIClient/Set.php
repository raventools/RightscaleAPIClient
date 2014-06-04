<?php

namespace RavenTools\RightscaleAPIClient;

class Set implements \ArrayAccess, \Iterator {

	private $set;

	public function __construct() {
		$this->set = array();
	}

	public function offsetExists($offset) {
		isset($this->set[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->set[$offset]);
	}

	public function offsetSet($offset, $value) {
		$this->set[$value] = true;
	}

	public function offsetUnset($offset) {
		unset($this->set[$offset]);
	}

	public function rewind() {
		reset($this->set);
	}

	public function current() {
		current($this->set);
	}

	public function key() {
		return key($this->set);
	}

	public function next() {
		return next($this->set);
	}

	public function valid() {
		return key($this->set) !== null;
	}

	public function __tostring() {
		return print_r(array_keys($this->set),true);
	}
}
