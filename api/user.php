<?php

/* header("Content-Type: application/json"); */
header("Access-Control-Allow-Origin: *");

echo "api/user.php";

echo "<pre>";
echo "URI Params: ";
var_dump($__ROUTER_URI_PARAMS);
echo "Query String: ";
var_dump($__ROUTER_QUERY_STRING);
echo "Request Body: ";
var_dump($__ROUTER_REQUEST_BODY);

