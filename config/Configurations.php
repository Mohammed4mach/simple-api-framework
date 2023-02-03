<?php

namespace MFunc
{
	use \Exception;

	$env_mode = getenv("PHP_ENV");

	$conf_path = "{$_SERVER['DOCUMENT_ROOT']}/config/config.$env_mode.json";

	global $Configurations;
	$Configurations = json_decode(file_get_contents($conf_path));

	if(!$Configurations)
		throw new Exception("Issue in reading configurations !");
}

