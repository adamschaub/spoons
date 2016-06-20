<?php
$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

mysqli_query($connection, 'SET time_zone = "' . $timezone_number . '"');
?>
