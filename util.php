<?php
    $dbHost = "localhost";
    $username = "root";
    $password = "";
    $dbName = "taskmaster";

    function notify_user($msg) {
        echo <<< _END

                <script>$("#infomsg").html("$msg");</script>
        _END;
    }

    function notify_user_and_die($msg) {
        notify_user($msg);
        echo <<< _END

            </body>
        </html>
        _END;
        die();
    }

    function process_request_error($msg) {
        $error_str = "Sorry, but the server was unable to process your request. <br> <br> The error we got: $msg <br> <br> Please try again.";
        notify_user_and_die($error_str);
    }

    function console_log($data) {
        echo "<script>";
        echo "console.log(" . json_encode($data) . ");";
        echo "</script>";
    }

    //Convenience method for querying the database
    function query($conn, $query) {
        $result = $conn->query($query);
        if (!$result) process_request_error(mysqli_error($conn));
        return $result;
    }

    $temp_conn = new mysqli($dbHost, "root", "");
    if ($temp_conn->connect_error) process_request_error(mysqli_error($temp_conn));
    $temp_conn->query("CREATE DATABASE IF NOT EXISTS $dbName");
    $temp_conn->close();
    
    $conn = new mysqli($dbHost, $username, $password, $dbName);
    if ($conn->connect_error) process_request_error(mysqli_error($conn));

    query($conn, "CREATE TABLE IF NOT EXISTS credentials(
        account_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(15) NOT NULL UNIQUE,
        pass CHAR(60) NOT NULL
    )");
    query($conn, "CREATE TABLE IF NOT EXISTS emails(
        account_id INT NOT NULL PRIMARY KEY,
        email VARCHAR(256) UNIQUE
    )");
    query($conn, "CREATE TABLE IF NOT EXISTS content(
        task_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task VARCHAR(256) NOT NULL,
        task_status VARCHAR(256) NOT NULL,
        username VARCHAR(15) NOT NULL
    )");
?>