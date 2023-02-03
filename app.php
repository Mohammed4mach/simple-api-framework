<?php

namespace MFunc;

require_once "includes/Router.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
/* header("Content-Type: application/json"); */
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods,Authorization,X-Requested-With");


Router::mapGET("/api/services/:serviceId/queue/:rowId", "/api/user.php");
Router::mapGET("/api/services/:serviceId",              "/api/services/read.php");
Router::mapGET("/api/services/",                        "/api/services/readAll.php");
Router::mapPOST("/api/services",                        "/api/services/create.php");
Router::mapDELETE("/api/services/:serviceId",           "/api/services/delete.php");
Router::mapGET("/api/queue/:rowId",                     "/api/queue/read.php");
Router::mapGET("/api/queue/",                           "/api/queue/readAll.php");
Router::print_routes();
Router::route();

