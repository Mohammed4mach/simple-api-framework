<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods,Authorization,X-Requested-With,X-MFunc-Token,x-mfunc-token");

$__ALLOWED_HTTP_REQUEST_METHODS = [
	"GET"     => false,
	"POST"    => false,
	"PUT"     => false,
	"PATCH"   => false,
	"DELETE"  => false,
	"OPTIONS" => true,
	"HEAD"    => true
];

