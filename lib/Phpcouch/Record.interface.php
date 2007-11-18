<?php

interface PhpcouchIRecord
{
	public function __construct(PhpcouchConnection $connection = null);
	
	public function __get($name);
	public function __isset($name);
	public function __set($name, $value);
	public function __unset($name);
	
	public function hydrate($data);
	public function dehydrate();
	
	public function getConnection();
	public function setConnection(PhpcouchConnection $connection = null);
}

?>