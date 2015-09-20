#!/bin/env php
<?php
require_once("inc/simple_html_dom.php");
require_once("inc/functions.php");

// Set location of sqlite database file
$db_file = "db/dorks.db";

// Print usage if no args supplied
if ($argc < 2) {
	print_usage($argv); 
	die();
}

$db = init_db($db_file);

// Load page in to HTML DOM
$landing = str_get_html( 
	gzdecode(
		file_get_contents('http://www.exploit-db.com/google-dorks/')
	)
);

$categories = get_categories($landing);
$newest_id = get_newest_id($landing);
$highest_db_id = get_highest_db_id($db);

// Handle input
switch ($argv[1]) {
	case "update":
		get_dorks($db, $highest_db_id, $newest_id); // Get latest dorks
		break;
	case "list":
		list_dorks($db);
		break;
	case "info":
		echo "Categories: "; print_r($categories); echo "\n";
		echo "Newest ID on GHDB: "; print_r($newest_id); echo "\n";
		echo "Highest DB ID: "; print_r($highest_db_id); echo "\n";
		break;
	default:
		print_usage();
		break;
}