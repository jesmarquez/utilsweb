<?php
	echo date("Y-m-d H:i:s");
	
	$grupo = "hee88";
	$hay_espacio = strrchr($grupo, " ");
	if ($hay_espacio) 
		printf("hay espacio");
	else
		printf("no hay espacio");
	
	$username =  "JUAN_P.GONZALEZ";
	echo(strtolower($username));
	
?>