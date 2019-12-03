<?php
    class data_reset {

    }
    $data = new data_reset();

    $data->id = 5;
    $data->reset_start_date = 0;
    $data->reset_end_date = 0;
    $data->reset_events = "1";
    $data->reset_notes = "1";
    $data->reset_roles_local ="1";
    $data->reset_gradebook_grades="1";
    $data->feedback_reset_data_1="1";
    $data->reset_forum_all="1";

    $data->reset_start_date_old = "1560229200";
    $data->reset_end_date_old = "1591765200";

    $str_data = var_dump($data);
    echo $str_data;
?>