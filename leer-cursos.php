<?php
	function leer_courses() {
        if (!is_readable("courses-to-campus.txt")) {
            exit("no esta el archivo");
        }
        $gestor = fopen("courses-to-campus.txt", "r");
        $conexiondb = ["attr" => []];
        $i = 0;
        while($linea = fgets($gestor)) {
            /*
			$cleanline = trim($linea);
            $conexiondb["attr"][$i] = $cleanline;
			*/
			printf('%s', $linea);
            $i++;
        }
        fclose($gestor);
        
        return(true);
    }
	
	$r = leer_courses();
	if ($r) {
		exit(0);
	} else {
		exit('error');
	}
	
?>