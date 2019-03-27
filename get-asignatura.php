   <?php
	function getAsignatura($cod_asignatura) {
        $domain='http://test2.uao.edu.co/devswacademia';

		$service_url=$domain. '/api/asignatura/' . $cod_asignatura;

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
            $data = array("status" => "failed", "service" => "get asignatura", "message" => "Asignatura ".$codi_asignatura." no fue encontrado");
        } else {
            $data = array("status" => "success",  "codigo" => $response_object->data->status, "codigo" => $response_object->data->codigo, "nombre" => $response_object->data->nombre, "facultad" => $response_object->data->facultad, "departamento" => $response_object->data->departamento);
        }

        return $data;
	}
?>