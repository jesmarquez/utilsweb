<?php
	/*
	ASIGNA FACULTAD Y DEPARTAMENTO A LAS SOLICITUDES
		*/
	include("get-asignatura.php");
	ini_set('max_execution_time', 300);
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
    
    /* retrieve all rows from solicutds */
	/*
	0 -> numero de solicituds
	1-> periodo
    2->	username docente
	3-> codigo_asignatura
	*/
    $query = "SELECT * FROM solicituds";
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_row()) {
			$data = getAsignatura($row[3]);
            // printf("%d %s %s %s %s %s %s %s<br>", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $data['facultad'], $data['departamento']);
			
			//update solicituds with facultad and departamento
			
			
			$sql = "UPDATE solicituds SET facultad=".'"'.$data['facultad'].'"'.", departamento=".'"'.$data['departamento'].'"'." WHERE id=".$row[0];
			if ($mysqli->query($sql) === true) {
				printf('Solicituds updated: %d <br>', $row[0]);
			} else {
				printf('Error updating: %s <br>', $mysqli->error);
			}
			
			// printf($sql);
			//printf("<br>");
        }
        /* free result set */
        $result->close();
    }
    mysqli_close($mysqli);
?>

