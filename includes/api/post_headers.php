<?php

require_once "all_headers.php";

header("Access-Control-Allow-Methods: POST, OPTIONS");

$__ALLOWED_HTTP_REQUEST_METHODS["POST"] = true;

if(!$__ALLOWED_HTTP_REQUEST_METHODS[$_SERVER["REQUEST_METHOD"]])
{
	http_response_code(405);
	exit();
}

if($_SERVER["REQUEST_METHOD"] == "OPTIONS")
	exit;

