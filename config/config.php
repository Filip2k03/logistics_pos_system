<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "zpxcdpsz_filip";
$password = "T6#N1Hyezr#n.fSi";
$dbname = "zpxcdpsz_pos_mb";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
// echo "✅ Connected successfully!";

?>