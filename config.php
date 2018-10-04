<?php

$db = array (
	"host" => "localhost",
	"port" => "3306",
	"user" => "",
	"pass" => "",
	"name" => "",
	"conn" => new stdClass(),
);

$db['conn'] = new mysqli($db['host'],$db['user'],$db['pass'],$db['name'],$db['port']);

