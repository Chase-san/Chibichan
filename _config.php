<?php

//Error Reporting
//ini_set('display_errors', true);
//error_reporting(E_ALL & ~E_STRICT);

//You should make these directories by the way
$thumbnail_directory = "thumb";
$image_directory = "image";
$template_directory = "template";

//temporary, if you have a LARGE gallery, and you delete the thumbnails
//regenerating them could take considerable time
$gallery_thumb_size = 150;

//can people upload and change tags?
//$editable = false;
$editable = true;

$per_page = 28;

define('TEMPLATE_PATH',$template_directory);
define('IMAGE_PATH',$image_directory);
define('THUMB_PATH',$thumbnail_directory);
define('IS_EDITABLE',$editable);