<?php

$total = array("A300" => 100, "Z821" => 10);


array_multisort($total, SORT_ASC, SORT_STRING);

$output = var_dump($total);
echo $output;

// echo "Working!";


?>