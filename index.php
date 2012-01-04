<?php
/** Nano Image Gallery, by Chase */

$file_extensions = array('png','jpg','gif','jpeg');
$gallery_template_filename = "gallery.html";
$thumbnail_directory = "thumb";
$gallery_thumb_size = 150;
$gallery_thumb_quality = 80;

require('template.php');

if(!is_writeable('.')) {
	die('<h1>You must make the directory writable!</h1>');
}

if(!file_exists($thumbnail_directory)) {
	mkdir($thumbnail_directory, 0755);
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
	//global $gallery_thumb_size;
	//return '~thmb_'.$gallery_thumb_size.'_'.$gallery_thumb_size.'_'.md5($filename).'.jpg';
	global $thumbnail_directory;
	return $thumbnail_directory . '/' . md5($filename) . '.jpg';
}

function createThumbnail($filename, $max_thumb_width, $max_thumb_height, $quality) {
	$thumbname = getThumbnailName($filename);

	if(!file_exists($filename)) return;

	list($width_orig, $height_orig) = getimagesize($filename);
	$ratio_orig = $width_orig/$height_orig;

	$width = $max_thumb_width;
	$height = $max_thumb_height;
	if ($width/$height > $ratio_orig) {
	   $width = floor($height*$ratio_orig);
	} else {
	   $height = floor($width/$ratio_orig);
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
		//ignore thumbnail files obviously
		//if(strpos($img, '~thmb_') === 0)
			//continue;

		$tmp = $thumb_template->copy();
		$tmp->setBlock('url',rawurlencode($img));
		
		$thumbname = getThumbnailName($img);
		if(!file_exists($thumbname)) {
			createThumbnail($img,$gallery_thumb_size,$gallery_thumb_size,$gallery_thumb_quality);
		}
		
		$tmp->setBlock('thumb',$thumbname);
		
		$output .= $tmp->content;
	}

	$thumb_template->content = $output;
	
	$template->setSection($thumb_template);

	echo $template->content;
}

showGallery();
?>