<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password
define('DB_NAME', 'logistics');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    // If connection fails, terminate the script and display an error
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}

// Function to fetch all regions
function get_regions($conn) {
    $regions = []; // Initialize an empty array to store region data
    $sql = "SELECT region_code, region_name, price_per_kg FROM regions ORDER BY region_name ASC";
    $result = mysqli_query($conn, $sql); // Execute the query

    // Check if the query was successful
    if ($result) {
        // Fetch each row and add it to the regions array
        while ($row = mysqli_fetch_assoc($result)) {
            $regions[] = $row;
        }
        mysqli_free_result($result); // Free the memory associated with the result
    }
    return $regions; // Return the array of regions
}

?>