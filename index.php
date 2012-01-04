<?php
/** Nano Image Gallery, by Chase */

$file_extensions = array('png','jpg','gif','jpeg');
$gallery_template_filename = "gallery.html";
$thumbnail_directory = "thumb";
$gallery_thumb_size = 150;
$gallery_thumb_quality = 80;

require('template.php');


if(!file_exists($thumbnail_directory)) {
	mkdir($thumbnail_directory, 0777);
}

if(!is_writeable($thumbnail_directory)) {
	die('<h1>You must make the thumbnail directory writable!</h1>');
}


/**
 * Thumbnail Image Load Helper
 */
function loadImage($filename) {
	$image_info = getimagesize($filename);
	if( $image_info[2] == IMAGETYPE_JPEG ) {
		return imagecreatefromjpeg($filename);
	} elseif( $image_info[2] == IMAGETYPE_GIF ) {
		return imagecreatefromgif($filename);
	} elseif( $image_info[2] == IMAGETYPE_PNG ) {
		return imagecreatefrompng($filename);
	}
}

/**
 * Gets the current list of files
 */
function getFileList() {
	global $file_extensions;
	$files = array();
	foreach($file_extensions as &$ext) {
		$gf = glob('*.'.$ext,GLOB_NOSORT);
		if(FALSE !== $gf) {
			$files = array_merge($files,$gf);
		}
	}
	sort($files);
	return $files;
}

function getThumbnailName($filename) {
	global $thumbnail_directory;
	return $thumbnail_directory . '/' . md5($filename) . '.jpg';
}

function createThumbnail($filename, $quality) {
	global $gallery_thumb_size;
	$thumbname = getThumbnailName($filename);

	if(!file_exists($filename)) return;

	list($width_orig, $height_orig) = getimagesize($filename);
	$ratio = $width_orig/$height_orig;

	$width = $gallery_thumb_size;
	$height = $gallery_thumb_size;
	if (1 > $ratio) {
	   $width = floor($height*$ratio);
	} else {
	   $height = floor($width/$ratio);
	}

	$image = loadImage($filename);
	$thumb = imagecreatetruecolor($width, $height);
	imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

	imagejpeg($thumb,$thumbname,$quality);

	imagedestroy($image);
	imagedestroy($thumb);
}

/**
 * Do the actual image gallery deal.
 */
function showGallery() {
	global $gallery_thumb_size, $gallery_template_filename, $gallery_thumb_quality;
	
	$template = loadTemplate($gallery_template_filename);
	
	$template->setBlock('title','Image Gallery');
	$template->setBlock('thumb_max_size',$gallery_thumb_size);
	
	$thumb_template = $template->getSection('thumb_block');

	$output = '';
	
	foreach(getFileList() as $img) {
		$tmp = $thumb_template->copy();
		$tmp->setBlock('url',rawurlencode($img));
		
		$thumbname = getThumbnailName($img);
		if(!file_exists($thumbname)) {
			createThumbnail($img,$gallery_thumb_quality);
		}
		
		$tmp->setBlock('thumb',$thumbname);
		
		$output .= $tmp->content;
	}

	$thumb_template->content = $output;
	
	$template->setSection($thumb_template);

	echo $template->content;
}

showGallery();
