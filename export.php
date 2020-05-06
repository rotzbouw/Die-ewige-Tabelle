<?php

    require_once "config.php";

    // Initialize the session
    session_start();
    
    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true) {
        // create empty variable to be filled with export data
        $csv_export = '';
        // query to get data from database
        $query = mysqli_query($link, "SELECT * FROM games");
        $field = mysqli_field_count($link);
        // create line with field names
        for($i = 0; $i < $field; $i++) {
            $csv_export.= mysqli_fetch_field_direct($query, $i)->name.',';
        }
        // newline (seems to work both on Linux & Windows servers)
        $csv_export.= '
        ';
        // loop through database query and fill export variable
        while($row = mysqli_fetch_array($query)) {
            // create line with field values
            for($i = 0; $i < $field; $i++) {
                $csv_export.= '"'.$row[mysqli_fetch_field_direct($query, $i)->name].'",';
            }
            $csv_export.= '
        ';
        }
        // Export the data and prompt a csv file for download
        header("Content-type: text/x-csv");
        header("Content-Disposition: attachment; filename=ewigetabelle.csv");
        echo($csv_export);
    }
?>
