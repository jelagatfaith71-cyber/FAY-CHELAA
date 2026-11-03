<?php
$host="localhost";
$dbname="wi_fi_sales_db";
$username="root";
$password="";

$conn=new mysqli(
    $host,
    $username,
    $password,
    $dbname
);
if($conn->connect_error){
    die("Dtabase connection failed: " .$conn->connect_error);
}
?>