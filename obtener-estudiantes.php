   <?php
	function getEstudiantes($cod_curso, $periodo) {
        $domain='http://test2.uao.edu.co/devswacademia';

		$service_url=$domain. '/api/estudiantes/' . $cod_curso.'/'.$periodo;

		$curl=curl_init($service_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$curl_response = curl_exec($curl);
		if ($curl_response === false) {
			$info = curl_getinfo($curl);
			curl_close($curl);
			die('error occured during curl exec. Additioanl info: ' . var_export($info));
		}
		curl_close($curl);
        
        $response_object = json_decode($curl_response);
		// var_dump($response_object);
		
		if (empty($response_object)) {
            $data = array("status" => "failed", "service" => "get estudiantes", "message" => $response_object->$statusMessage);
        } else {
            $data = array("status" => $response_object->statusMessage,  "codigo" => $response_object->statusCode, "estudiantes" => $response_object->data->estudiantes);
        }

        return $data;
	}
	/*
	$info = getEstudiantes('333204-11', '2018-03');
	
	var_dump($info);
	*/
?>