<?php

require_once "all_headers.php";

header("Access-Control-Allow-Methods: PUT, OPTIONS");

$__ALLOWED_HTTP_REQUEST_METHODS["PUT"] = true;

if(!$__ALLOWED_HTTP_REQUEST_METHODS[$_SERVER["REQUEST_METHOD"]])
{
	http_response_code(405);
	exit();
}

if($_SERVER["REQUEST_METHOD"] == "OPTIONS")
	exit;

