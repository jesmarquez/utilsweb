<?php
    $total = array();

    $totala = array('ciencias' => 100);
    array_push($total, $totala);
    $totalb = array('humanidades' => 100);
    array_push($total, $totalb);
    // var_dump($total);

    $faculties = ["ciencias", "matematicas"];
    
    foreach($faculties as $f) {
        $totald[$f] = 100;
    }
    
    $totald[$faculties[1]] = 39993;
    var_dump($totald);
?>