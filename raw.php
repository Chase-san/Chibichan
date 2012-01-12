<?php 

$raw = true;

require '_master.inc.php';

if( isset($_GET['q']) ) {
	$latest = $db->getLatestTaggedImages($_GET['q']);
	
	$first = true;
	
	foreach($latest as $hash => $image) {
		if(!$first)
			echo ';';
		$img_path = getImagePath($hash,$image['ext']);
		echo $img_path;
		
		$first = false;
	}

} else {
	echo 'no query found';
}