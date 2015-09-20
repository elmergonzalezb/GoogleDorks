<?php
/*
 * Functions for the ghdb_scrape script
 */
date_default_timezone_set('UTC'); // Set default timezone



// Open dorks db and create dorks table if needed
function init_db($db_filename = "db/dorks.db") {
	$db = new PDO('sqlite:'.$db_filename);
	$db->exec("CREATE TABLE IF NOT EXISTS dorks(id integer PRIMARY KEY," .
			"dork text, category text)");
	return $db;
}



// Get highest ID from local db
function get_highest_db_id($db = null) {
	if ($db == null) die("get_highest_db_id() - No database provided.");
	$query = "SELECT id FROM dorks ORDER BY id DESC";
	$stmt = $db->prepare($query);
	$stmt->execute();
	$row = $stmt->fetch();
	return $row['id'];
}



// Get ID of latest dork
function get_newest_id($html) {
	$latest_ids = array();
	foreach ($html->find('td a') as $element) { // get links from table
		//echo $element->href;
		$ghdb_pos = strpos($element->href, "/ghdb/"); // Identify category links
		if ($ghdb_pos !== false) {  // if link points to /ghdb/ page
			 $id_pos = $ghdb_pos + 6;
			 $latest_ids[] = substr($element->href, $id_pos, -1);
		}
	}
	arsort($latest_ids); // sort highest first
	$latest_id = array_shift($latest_ids); // grab top
	return ($latest_id);
}



// Get ID/Names of categories
function get_categories($html) {
	$categories = array();
	foreach($html->find('a') as $element) {
	   // echo $element->href . "\n"; // Output full href
	   $dork_pos = strpos($element->href, "/google-dorks/"); // Identify category links
		if ($dork_pos !== false) { // link does point to category
			$start_pos = $dork_pos + 14; // move starting position to / after google-dorks
			$cat_id = substr($element->href, $start_pos, -1); // -1 to cut off last slash
			$cat_name = $element->plaintext;
			if (!empty($cat_id)) {
				$categories[$cat_id] = $cat_name;
			}
		}		
	}
	return ($categories);
} // get_categories()



// Get dorks from site given start and end it
function get_dorks($db, $highest_db_id, $newest_id) {

	if ($newest_id > $highest_db_id) {
		$start_id = $highest_db_id + 1; 
		for ($id = $start_id; $id <= $newest_id; $id++) {
			$dork_page = str_get_html(file_get_contents('http://www.exploit-db.com/ghdb/' . $id . '/'));
			$dork_text = "";
			$comment = "";
			foreach ($dork_page->find('h2 a') as $a) {
				$dork_text = $a->href;
			}
			foreach ($dork_page->find('p.text') as $dork_comment) {
				$comment = $dork_comment->plaintext;
			}
			if (!empty($dork_text)) {
				echo "Adding dork: $id - $dork_text\n";
				add_dork($db, $id, $dork_text, $comment);
			}
		}
	}

}



// Add google dork to database
function add_dork($db, $id, $dork, $category) {
	$db->exec("INSERT INTO dorks (id, dork, category) VALUES (" . $id . ", \"" . $dork . "\", \"" . $category . "\")");
}



// Get all dorks and print
function list_dorks($db, $start_id = 1, $end_id = 1000000) {
	$ret = $db->query("SELECT * FROM dorks"); // Add start/end id
	foreach ($ret as $row) {
		echo "$row[id] - $row[dork]\n$row[category]\n\n";
	}
}



// Print usage instructions
function print_usage($argv) {
	echo "Usage: $argv[0] [info|list|update]\n";
}