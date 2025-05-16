<?php
// Dynamically determine the base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$projectFolder = '/Sci-Bono_Clubhoue_LMS'; // Adjust this to match your project folder

define('BASE_URL', $protocol . '://' . $host . $projectFolder . '/');