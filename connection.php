<?php
 $db_host = "localhost";  
 $db_user = "u342202101_excelearn";        
 $db_pass = "6S+?/2^n|k?BMhhs";             
 $db_name = "u342202101_excelearn"; 

 $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}
?>