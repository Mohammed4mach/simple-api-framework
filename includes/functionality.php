<?php

namespace MFunc
{
	date_default_timezone_set('Africa/Cairo'); // Set Timezone to Cairo

	class Func
	{
		// Encapsulate input sanitize functions in one
		public static function sanInput($input) {
			$sanitized = stripslashes($input);
			$sanitized = htmlspecialchars($sanitized);
			$sanitized = trim($sanitized);

			return $sanitized;
		}


		// Unique id function generator
		public static function genId($length) {
			$min = (int)(0.1 * (10**$length));
			$max = (int)(0.99999999999 * (10**$length));
			$random = \rand($min, $max);
			return $random;
		}


		// Decode url parameter
		public static function parseURIParam(string $param)
		{
			return str_replace("-", " ", $param);
		}


		// Print Array
		public static function printArr(&$arr)
		{
			echo "<pre>";
			print_r($arr);
			echo "</pre>";
		}


		// Encapsulate API (IP to Location API) request in a function
		public static function get_ip_location($ip)
		{
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.apilayer.com/ip_to_location/$ip",
				CURLOPT_HTTPHEADER => array(
					"Content-Type: text/plain",
					"apikey: MKuDl0tTC20bw3zvHtZQS7Shc25SRKO5"
				),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET"
			));

			$response = curl_exec($curl);

			curl_close($curl);

			return $response;
		}

		// Process IP to Location API response
		public static function process_ip_api_resp($response)
		{
			$respArr = json_decode($response, true);

			if(array_key_exists("message", $respArr))
				return;

			$result = array(
				"country" => $respArr["country_name"],
				"city" => $respArr["city"],
				"isp" => $respArr["connection"]["isp"]
			);

			return $result;
		}

		// Upload image to destination
		// @param $formName — name of the file in the form
		// @param $new_img_name — new image name
		// @param $folder — desired folder to upload in the 'upload' folder
		public static function uploadFile($formName, $new_img_name, $folder)
		{
			if(isset($_FILES[$formName]['name']))
			{
				$img_name = $_FILES[$formName]['name'];
				$tmp_name = $_FILES[$formName]['tmp_name'];
				$size = $_FILES[$formName]['size'];
				$error = $_FILES[$formName]['error'];
				
				$name_arr = explode('.', $img_name);
				$img_extension = strtolower(end($name_arr));
				$allowed_extensions = array('jpg', 'jpeg', 'png', 'jfif', 'svg', 'webp', 'mp4');


				// Error handling
				if($error)
					return array(0, "try_again");
				if($size > 160000000)
					return array(0, "large_size");
				if(!in_array($img_extension, $allowed_extensions))
					return array(0, "invalid_extension");

				// Rename the image and move to uploads
				$new_img_name = $new_img_name . '.' . $img_extension;
				$img_destination = "../uploads/$folder/$new_img_name";
				$uploaded = move_uploaded_file($tmp_name, $img_destination);

				if(!$uploaded)
					return array(0, "upload_failed");

				return array(1, $new_img_name);
			}
			return "noway";
		}

		public static function executeCMD($cmd)
		{
			if(substr(php_uname(), 0, 7) == "Windows")
				pclose(popen("start /B " . $cmd, "r"));
			else
				exec($cmd . " > /dev/null &");
		}

		public static function ordinal($number) {
			$ends = array('th','st','nd','rd','th','th','th','th','th','th');
			if ((($number % 100) >= 11) && (($number%100) <= 13))
				return 'th';
			else
				return $ends[$number % 10];
		}

		/**
		*   Process markdown format to HTML elements
		*   @param string $text Text contains markdown formats
		*   
		*   @return string|NULL
		*/
		public static function processMarkdown(&$text): string|NULL
		{
			$pattern = array(
				'/!#\(.*v=([^&]*).*\)/m', // Youtube iframe     !#(URL)
				'/!\[(.*?)\]\((.*?)\)/m', // Image                ![Alt text](URL)
				'/\[(.*?)\]\((.*?)\)/m', // Link                  [Text](link)
				'/\*\*(.*)\*\*/m', // Bold						**Text**
				'/__(.*)__/m', // Italic						__Text__
				'/#### *(.*)/m',
				'/### *(.*)/m',
				'/## *(.*)/m',  // Heading                      ## Text
				'/\n/m'   // Newline  \n
			);

			$replace = array(
				'<div class="iframe_container"><iframe loading="lazy" width="65%" height="100%" src="https://www.youtube.com/embed/$1" title="YouTube video player" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
				'<img loading="lazy" src="$2" alt="$1"/>',
				'<a href="$2" rel="noopener noreference nofollower">$1</a>',
				'<strong>$1</strong>',
				'<i>$1</i>',
				'<h4>$1</h4>',
				'<h3>$1</h3>',
				'<h2>$1</h2>',
				'<br>'
			);

			return preg_replace($pattern, $replace, $text);
		}

		public static function processMarkdownCard(&$text) : string|NULL
		{
			$pattern = array(
				'/!#\(.*v=([^&]*).*\)/m', // Youtube iframe     !#(URL)
				'/!\[(.*)\]\((.*)\)/m', // Image                ![Alt text](URL)
				'/\[(.*)\]\((.*)\)/m', // Link                  [Text](link)
				'/## *(.*)/m',  // Heading                      ## Text
				'/\n/m'   // Newline  \n
			);

			$replace = array(
				'',
				'',
				'<a href="$2" rel="noopener noreference nofollower">$1</a>',
				'',
				'<br>'
			);

			return preg_replace($pattern, $replace, $text);
		}
	}
}

