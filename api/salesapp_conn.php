<?php
date_default_timezone_set("Asia/Manila");

try {
    $salesapp_conn = mysqli_connect(
        '10.121.19.181',
        'rgc',
        'rgc@$^*3579',
        'clutch'
    );
    if (!$salesapp_conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

} catch (mysqli_sql_exception $e) {
    die("MySQL Error: " . $e->getMessage());
}
?>