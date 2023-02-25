<?php

namespace MFunc;

require_once "includes/Router.php";
require_once "config/Configurations.php";
require_once "includes/auth/Auth.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
/* header("Content-Type: application/json"); */
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods,Authorization,X-Requested-With");

$moj = function($par, $par2)
{
	echo $par + $par2;
};
$mo = function()
{
	echo "Callback Test Passed";
};


Router::mapGET("/api/services/:serviceId/queue/:rowId", "/api/user.php", $moj, 21, 9);
Router::mapGET("/api/services/:serviceId",              "/api/services/read.php");
Router::mapGET("/api/services/",                        "/api/services/readAll.php");
Router::mapPOST("/api/services",                        "/api/services/create.php");
Router::mapDELETE("/api/services/:serviceId",           "/api/services/delete.php");
Router::mapGET("/api/queue/:rowId",                     "/api/queue/read.php");
Router::mapGET("/api/queue/",                           "/api/queue/readAll.php", function() { Auth::authenticate(); });
Router::mapGET("/api/authToken/", "/index.php");
/* Router::print_routes(); */
Router::route();

