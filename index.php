<?php

namespace MFunc;

include "includes/JWT/JWT.php";
include "config/Configurations.php";

$header = [
	"alg" => "RS256",
	"type" => "JWT"
];

$payload = [
	"id" => "31ededfqwfwqfqwfqfw",
	"name" => "Mojaf",
	"role" => "Secretary"
];




$jwt = new JWT();

$jwt_str = $jwt->genToken($payload, $Configurations->authGenKey);
echo $jwt_str;

$tmp = explode(".", $jwt_str);
$body = $tmp[1];
$body = JWT::base64url_decode($body);
$body2 = substr($body, 0, strlen($body) - 19) . '"role": "Admin"}';
$bodyEnc = JWT::base64url_encode($body2);


$jwt_str2 = "{$tmp[0]}.$bodyEnc.{$tmp[2]}";

$jwt = new JWT($jwt_str);
var_dump($jwt->verify($Configurations->authVerKey));

