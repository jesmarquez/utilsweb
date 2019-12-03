<?php

$fd = array('name' => 'ingenieria', 
            'departments' => array(0 => array('name' => 'control y automatica', 'total' => 342),
                                   1 => array('name' => 'electrónica', 'total' => 983)));

$fd_courses_b = array(0 => $fd);

// var_dump($fd_courses_b);

$fdCoursesB = json_encode($fd_courses_b);

var_dump($fdCoursesB);

?>