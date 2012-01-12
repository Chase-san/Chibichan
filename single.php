<?php 

require '_master.inc.php';

$hash = isset($_GET['h']) ? $_GET['h'] : '';

if(IS_EDITABLE) {
	if( isset($_POST['submit']) && $_POST['submit'] == 'Upload' ) {
		$data = upload();
		//print_r($data);
		if($data['ok']) {
			//upload to database
			$db->addImage($data['hash'],$data['ext'],$_POST['tags']);
			checkImageThumb(getThumbPath($data['hash']),$data['path']);
			
			$db->close();
			
			header( 'Location: ' . PHP_SELF . '?h=' . $data['hash'] ) ;
			exit();
		}
	}

	if( isset($_POST['submit']) && $_POST['submit'] == 'Save changes' ) {
		$db->setTags($hash, $_POST['tags']);
	}
}

$image = $db->getImage($hash);

if(!$hash || !$image) {
	header( 'Location: index.php' ) ;
	exit();
}

$template = $master->getBlock('tag');
$allTags = '';
$tagList = '';

foreach($image['tags'] as $tag) {
	$tmp = clone $template;
	$tmp->setBlock('tag_id',$tag);
	$tmp->setBlock('tag_count','');
	$tmp->setBlock('tag_href','index.php?q='.$tag);
	
	$allTags .= $tmp;
	$tagList .= $tag . ' ';
}

$tagList = trim($tagList);

$master->setBlock('tag',$allTags);
$child->setBlock('tag_list',$tagList);

$img_path = getImagePath($hash,$image['ext']);
$size = getimagesize($img_path);
list($width,$height) = $size;

$child->setBlock('hash',$hash);
$child->setBlock('img_age',timeAgo($image['stamp']));
$child->setBlock('img_type',$size['mime']);
$child->setBlock('img_size',$width.'x'.$height.' ('. pfilesize($img_path) .')');


$child->setBlock('img_src',$img_path);

display();

$db->close();