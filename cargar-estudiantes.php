<?php
	/*
		CARGA DATOS EN SOLICITUD_GRUPO_ESTUDIANTE
	*/
	include("obtener-estudiantes.php");
	
	ini_set('max_execution_time', 1800);
    $mysqli = new mysqli("localhost", "admcit", "12345", "swebsiga");
	
	if (!$mysqli) {
        echo "Error: Unable to connect to MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }
	
	echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
	echo "<br>";
    echo "Host information: " . mysqli_get_host_info($mysqli) . PHP_EOL;
	echo "<br>";
	
	$query = "select s.id as idsolicitud, s.periodo, s.codigo_asignatura as codasignatura, g.id as idgrupo, g.grupo as grupo from solicituds as s, grupos as g WHERE s.id = g.solicitud_id AND s.periodo = '2018-03'";
	/*
	0 id solicitud
	1 periodo
	2 cod asignatura
	3 id grupo
	4 nombre grupo
	*/
	
	
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_row()) {
			$hay_espacio = strrchr($row[4], " ");

            if ($hay_espacio) {
				printf("ERROR espacio en grupo %d %s %s %d %s<br>", $row[0], $row[1], $row[2], $row[3], $row[4]);
				continue;
			}

			printf("%d %s %s %d %s<br>", $row[0], $row[1], $row[2], $row[3], $row[4]);
			$data = getEstudiantes($row[2].'-'.$row[4],$row[1]);
			// var_dump($data['estudiantes']);

			foreach($data['estudiantes'] as $username) {
				// $query = "INSERT INTO solicitud_grupos_estudiante VALUES ";
				$timenow = date("Y-m-d H:i:s");
				$query = sprintf("INSERT INTO solicitud_grupos_estudiante (grupo_id, username, created_at, updated_at) VALUES (%d, '%s', '%s', '%s')", $row[3], strtolower($username), $timenow, $timenow);
				// printf($query);
				// echo "<br>";
				
				if ($mysqli->query($query) === true) {
					printf('Solicituds updated: %d <br>', $row[0]);
				} else {
					printf('Error updating: %s <br>', $mysqli->error);
				}
				
			}
			// break;
        }
        /* free result set */
        $result->close();
    }
    mysqli_close($mysqli);
?>