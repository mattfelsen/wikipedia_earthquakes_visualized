<?php

print "Wikipedia API Data Fetcher\n";

// Script should be called like: php wikidump.php input_filename.txt
// Get the name of the file passed as an input and read its contents
// into an array. This will result in each element of the $file array
// containing an article name and a trailing newline (\n)
$file = file($argv[1]);

// Get the filename before the .txt and replace with .csv for output
// E.g. php wikidump.php earthquakes.txt will write csv output to earthquakes.csv
$split = explode('.', $argv[1]);
$output_file = $split[0] . '.csv';

// Here I build the CSV output the $output variable rather than printing
// so it can be written to a file later instead of to the command lion

// Headers for CSV file
$output .= "Article,Created,Length,Languages,Sections\n";

// Loop through all of the article names contained in the $file array
foreach ($file as $article) {

	// Trim the trailining newline after the article name
	$article = trim($article);

	// Replace spaces in titles with underscores, which is how Wikipedia
	// treats article titles
	$article = str_replace(' ', '_', $article);

	print "Fetching $article...\n";

	// Grab the creation date
	// Note the parameters in the URL:
	// action=query
	// titles=$article -- $article is the title of the current article from the $file array being looped through
	// format=json -- the data format we want back
	// prop=revisions -- prop is short for property
	// rvprop=timestamp -- the revision property we want is the the timestampt
	// rvdir=newer -- this says go oldest->newest, so return the oldest articles first
	// rvlimit=1 -- only return the first result, which will be the oldest article
	// redirects -- Follow redirects, e.g. if we query 'Black Plague' it will return results for 'Black Death'
	//              instead of just returning results for the redirect page itself
	$creation_date = fetch_json("http://en.wikipedia.org/w/api.php?action=query&titles=$article&format=json&prop=revisions&rvprop=timestamp&rvdir=newer&rvlimit=1&redirects");
	
	// Look up the part of the object that actually contains the data we want and store it in $creation_timestamp
	$timestamp_array = array_pop($creation_date['query']['pages']);
	$creation_timestamp = $timestamp_array['revisions'][0]['timestamp'];


	// Now do basically the same thing for all the different data we want: size of the
	// article (returned in bytes), number of translations, and number of sections
	$size = fetch_json("http://en.wikipedia.org/w/api.php?action=query&titles=$article&format=json&prop=revisions&rvprop=size&rvdir=older&rvlimit=1&redirects");
	$size_array = array_pop($size['query']['pages']);
	$size = $size_array['revisions'][0]['size'];

	$num_languages = fetch_json("http://en.wikipedia.org/w/api.php?action=query&titles=$article&format=json&prop=langlinks&lllimit=500&redirects");
	$languages_array = array_pop($num_languages['query']['pages']);
	$num_languages = count($languages_array['langlinks']);

	$num_sections = fetch_json("http://en.wikipedia.org/w/api.php?action=parse&page=$article&format=json&prop=sections&redirects");
	$num_sections = count($num_sections['parse']['sections']);

	// Add a line to the output with all the data for this article
	$output .= "$article,$creation_timestamp,$size,$num_languages,$num_sections\n";

}

// Finally, write the output to the csv file
file_put_contents($output_file, $output);


// Even though PHP supports remote URLs for functions like file_get_contents(), trying to use
// it to query the API was failing. The internet told me to use cURL, so I'm using cURL. It
// grabs the data from the remote URL and runs json_decode() on it which converts a JSON
// object to a PHP object or array
function fetch_json($s) {
	
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $s);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_handle, CURLOPT_USERAGENT, "Wikipedia API Fetcher");
	$query = curl_exec($curl_handle);
	curl_close($curl_handle);

	return json_decode($query, true);
}

?>