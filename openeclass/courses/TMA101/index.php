<?php
                session_start();
        $dbname="TMA101";
        session_register("dbname");
        include("../../modules/course_home/course_home.php");
        ?>