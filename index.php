<?php 

require '_master.inc.php';

$latest = '';
$tagged = false;

if(IS_EDITABLE) {
	if( isset($_GET['hash']) && isset($_GET['delete']) && $_GET['delete'] == 'Delete' ) {
		$hash = $_GET['hash'];
		
		if($db->getImage($hash) !== false) {
			$db->removeImage($hash);
		}
		
		$db->close();
		
		header( 'Location: index.php' ) ;
		exit();
	}
}

$myTags = '';

if( isset($_GET['q']) ) {
	$latest = $db->getLatestTaggedImages($_GET['q']);
	$tagged = true;
	$tags = $db->splitTags($_GET['q']);
	$myTags = '';
	foreach($tags as $tag) {
		$myTags .= $tag . ' ';
	}
	$myTags = trim($myTags);
} else {
	$latest = $db->getLatestImages();	
}

$page = 0;
if( isset($_GET['p']) ) {
	$page = $_GET['p'];
}

$tags = $db->getCombinedTags($latest);

//now cut the latest to size

$pages = ceil(count($latest)/$per_page);

$offset = $page * $per_page;

$latest = array_slice($latest,$offset,$per_page);

//do the pagination stuff
$pagination = '';

$tmp_page = $child->getBlock('page');

for($i = 1; $i<=$pages; ++$i) {
	if($i == $page + 1) {
		$tmp_cur_page = $child->getBlock('current_page');
		$tmp_cur_page->setBlock('page_num',$i);
		$pagination .= $tmp_cur_page;
	} else {
		$tmp = clone $tmp_page;
		$tmp->setBlock('page_num',$i);
		
		if($tagged) {
			$tmp->setBlock('page_href','index.php?'.http_build_query(array('q'=>$myTags,'p'=>($i-1))));
		} else {
			$tmp->setBlock('page_href','index.php?p='.($i-1));
		}
		
		$pagination .= $tmp;
	}
}

$child->setBlock('pagination', $pagination);


//do the thumbnail stuff

$template = $child->getBlock('thumbnail');
$template->setBlock('alt','');

$thumbs = '';
foreach($latest as $hash => $image) {
	$tmp = clone $template;
	
	$img_path = getImagePath($hash,$image['ext']);
	$thumb_path = getThumbPath($hash);

	checkImageThumb($thumb_path,$img_path);
	
	$tmp->setBlock('href','single.php?h='.$hash);
	$tmp->setBlock('src',$thumb_path);
	
	$thumbs .= $tmp;
}

$template = $master->getBlock('tag');
$allTags = '';

$tagnum = 0;
foreach($tags as $tag => $count) {
	$tmp = clone $template;
	$tmp->setBlock('tag_id',$tag);
	$tmp->setBlock('tag_count',$count);
	$tmp->setBlock('tag_href','index.php?q='.$tag);
	
	$allTags .= $tmp;
	
	if(++$tagnum == 24) break;
}

$master->setBlock('tag',$allTags);
if($tagged) {
	$master->setBlock('tag_list',$myTags);
	$child->setBlock('raw_href', 'raw.php?'.http_build_query(array('q'=>$myTags)));
} else {
	$master->setBlock('tag_list','');
	$child->setBlock('raw_href', 'raw.php');
}

$child->setBlock('thumbnail',$thumbs);
$child->setBlock('gallery_thumb_size',$gallery_thumb_size);


display();

$db->close();