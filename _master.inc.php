<?php

// Not as important as it seems
// You don't need to change it
// you can if you want though, but it might screw with
// things if you have a database already
date_default_timezone_set('Etc/GMT+5');

require '_config.php';

class Database {
	private $db;
	private $tagdb;
	
	private $need_save = false;
	public function Database() {
		$this->load();
	}
	
	public function close() {
		if($this->need_save) {
			$this->save();
		}
	}
	
	private function save() {
		$output = '<?php $database=' . var_export($this->db, true) . ";\n";
		$output .= '$tagdatabase=' . var_export($this->tagdb, true) . ";\n";
		file_put_contents('chan.database', $output);
	}
	
	private function load() {
		$this->db = array();
		$this->tagdb = array();
		if(file_exists('chan.database')) {
			require('chan.database');
			$this->db = $database;
			$this->tagdb = $tagdatabase;
		}
	}
	
	private function createImageRow($hash, $ext, $stamp = false) {
		if($stamp === false) {
			$stamp = new DateTime('now');
			$stamp = $stamp->getTimestamp();
		}
		return array('ext' => $ext, 'stamp' => $stamp, 'tags' => array('untagged'));
	}
	
	public function addImage($hash, $ext, $tags) {
		$this->db[$hash] = $this->createImageRow($hash,$ext);
		$this->setTags($hash, $tags);
		$this->need_save = true;
	}
	
	public function setTags($hash, $tags) {
		$tags = str_replace(array("\t","\n","\r","\0","\x0B"),' ',$tags);
		$tags = $this->splitTags($tags);
		
		$this->removeTags($hash);
		if(count($tags) > 0) {
			foreach($tags as $tag) {
				$this->db[$hash]['tags'][] = $tag;
				$this->tagdb[$tag][$hash] = 1;
			}
		} else {
			$this->db[$hash]['tags'][] = 'untagged';
		}
		
		$this->need_save = true;
	}
	
	private function removeTags($hash) {
		foreach($this->db[$hash]['tags'] as $tag) {
			unset($this->tagdb[$tag][$hash]);
			
			if(count($this->tagdb[$tag]) == 0) {
				unset($this->tagdb[$tag]);
			}
		}
		unset($this->db[$hash]['tags']);
		$this->db[$hash]['tags'] = array();
		
		$this->need_save = true;
	}
	
	public function getImage($hash) {
		if(!isset($this->db[$hash])) return false;
		return $this->db[$hash];
	}
	
	public function removeImage($hash) {
		$this->removeTags($hash);
		unset($this->db[$hash]);
		
		$this->need_save = true;
	}
	
	public function splitTags($tags) {
		$finalTags = array();
		foreach(explode(' ',$tags) as $tag) {
			$tag = trim(strtolower($tag));
			if(strlen($tag) > 0 && preg_match('/^\w+$/',$tag) && !in_array($tag,$finalTags)) {
				$finalTags[] = $tag;
			}
		}
		return $finalTags;
	}
	
	public function getLatestImages() {
		return array_reverse($this->db);
	}
	
	//something like this...
	public function getLatestTaggedImages($tags) {
		$tags = $this->splitTags($tags);
		
		$keys = '';
		foreach($tags as $tag) {
			if(isset($this->tagdb[$tag])) {
				$arr = $this->tagdb[$tag];
				if($keys) {
					$keys = array_intersect_key($keys,$arr);
				} else {
					$keys = $arr;
				}
			}
		}
		
		return array_reverse(array_intersect_key($this->db, $keys));
	}
	
	public function getCombinedTags($images) {
		$tags = array();
		foreach($images as $img) {
			$tags = array_merge($tags, $img['tags']);
		}
		$tags = array_count_values($tags);
		arsort($tags);
		return $tags;
	}
}

/**
 * Lazy ass image manipulation class
 */
class Image {
	private $info;
	private $image;
	
	public function Image($filename = false) {
		if($filename !== false) {
			$this->info = getimagesize($filename);
			// all other formats can kiss my ass (no offense)
			if( $this->info[2] == IMAGETYPE_JPEG ) {
				$this->image = imagecreatefromjpeg($filename);
			} elseif( $this->info[2] == IMAGETYPE_GIF ) {
				$this->image = imagecreatefromgif($filename);
			} elseif( $this->info[2] == IMAGETYPE_PNG ) {
				$this->image = imagecreatefrompng($filename);
			}
		}
	}
	
	public function getWidth() {
		return $this->info[0];
	}
	
	public function getHeight() {
		return $this->info[1];
	}
	
	public function createThumbnail($filename, $size, $quality=80) {
		$sizeRatio = $this->getWidth() / $this->getHeight();
		
		$width = $size;
		$height = $size;
		
		if($sizeRatio < 1) { //wider
			$width = floor($height*$sizeRatio);
		} else { //taller
			$height = floor($width/$sizeRatio);
		}
		
		$thumb = imagecreatetruecolor($width, $height);

		imagecopyresampled($thumb, $this->image, 0, 0, 0, 0,
			$width, $height, $this->getWidth(), $this->getHeight());
		
		imagejpeg($thumb,$filename,$quality);
		
		imagedestroy($thumb);
	}
	
	public function destroy() {
		imagedestroy($this->image);
	}
}

/**
 * My poor man's theming class.
 */
class Template {
	const BLOCK_START = '{%s}';
	const BLOCK_END = '{/%s}';
	const BLOCK_REGEX = '/{([-_a-zA-Z0-9]+)}/';
	
	public $content;
	
	function Template($template_content) {
		$this->content = $template_content;
	}
	
	public function getBlockNames() {
		$matches = array();
		preg_match_all(self::BLOCK_REGEX, $this->content, $matches);
		return $matches[1];
	}
	
	public function getBlock($name) {
		$txtStart = sprintf(self::BLOCK_START, $name);
		$txtEnd = sprintf(self::BLOCK_END, $name);
		if(($posStart=strpos($this->content,$txtStart)) !== FALSE) {
			if(($posEnd=strpos($this->content,$txtEnd,$posStart)) !== FALSE) {
				$posStart += strlen($txtStart);
				return new Template(substr($this->content, $posStart, $posEnd-$posStart), $name);
			}
		}
		return '';
	}
	
	public function setBlock($name, $data) {
		$txtStart = sprintf(self::BLOCK_START, $name);
		$txtEnd = sprintf(self::BLOCK_END, $name);
		if(($posStart=strpos($this->content,$txtStart)) !== FALSE) {
			if(($posEnd=strpos($this->content,$txtEnd,$posStart)) !== FALSE) {
				$posEnd += strlen($txtEnd);
				$this->content = substr_replace($this->content, $data, $posStart, $posEnd-$posStart);
			} else {
				while(FALSE !== strpos($this->content, $txtStart)) {
					$this->content = str_replace($txtStart, $data, $this->content);
				}
			}
		}
	}
	
	public function __toString() {
		return $this->content;
	}
}

function load($name) {
	return new Template(file_get_contents(TEMPLATE_PATH . '/' . $name . '.html'));
}

function populate(&$base,&$child) {
	foreach($base->getBlockNames() as $blockid) {
		$base->setBlock($blockid, $child->getBlock($blockid));
	}
}

function display() {
	global $master, $child;
	populate($master,$child);
	echo $master;
}

function getThumbPath($md5) {
	return THUMB_PATH . '/' . $md5 . '.jpg';
}

function getImagePath($md5, $ext) {
	return IMAGE_PATH . '/' . $md5 . '.' . $ext;
}

function checkImageThumb($thumb_path,$img_path) {
	global $gallery_thumb_size;
	if(!file_exists($thumb_path)) {
		$img = new Image($img_path);
		$img->createThumbnail($thumb_path,$gallery_thumb_size);
		$img->destroy();
	}
}

function pfilesize($filename) {
	if(!file_exists($filename))
		return '0 B';
	$size = filesize($filename);	
	$units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}

$tAgoArr = array(
	'y' => array('1 year ago', '%d years ago'),
	'm' => array('1 month ago', '%d months ago'),
	'd' => array('1 day ago', '%d days ago'),
	'h' => array('1 hour ago', '%d hours ago'),
	'i' => array('1 minute ago', '%d minutes ago'),
	's' => array('now', '%d secons ago'),
);

function timeAgo($timestamp) {
	global $tAgoArr;
	
	$now = new DateTime('now');
	$then = new DateTime();
	$then->setTimestamp($timestamp);
	$diff = $then->diff($now);
	
	foreach($tAgoArr as $key => $value){
		if( ($text = _timeAgoText($key, $diff)) ){
			return $text;
		}
	}
}

function _timeAgoText($intervalKey, $diff){
	global $tAgoArr;
	$pluralKey = 1;
	$value = $diff->$intervalKey;
	if($value > 0){
		if($value < 2){
			$pluralKey = 0;
		}
		return sprintf($tAgoArr[$intervalKey][$pluralKey], $value);
	}
	return null;
}

function upload() {
	$md5 = '';
	$filename = '';
	$ext = '';
	$path = '';
	$ok = 0;
	
	if(count($_FILES) > 0) { 
		$md5 = md5_file($_FILES['file']['tmp_name']);
		$ext = pathinfo($_FILES['file']['name']);
		$filename = $ext['basename'];
		$ext = $ext['extension'];
		$path = getImagePath($md5,$ext);
		
		//don't even try
		//mkdir(dirname($path),0777,true);
		
		if( move_uploaded_file( $_FILES['file']['tmp_name'], $path) ) {
			$ok = true;
		}
	}
	
	return array('hash' => $md5, 'filename' => $filename, 'ext' => $ext, 'path' => $path, 'ok' => $ok);
}

define('PHP_SELF',$_SERVER['PHP_SELF']);
define('REQUEST_URI',$_SERVER['REQUEST_URI']);


$load = true;
if(isset($raw)) {
	$load = !$raw;
}

if($load) {
	$master = load('master');
	$child = load(basename(PHP_SELF,".php"));

	$master->setBlock('PHP_SELF',PHP_SELF);
	$master->setBlock('REQUEST_URI',REQUEST_URI);

	$child->setBlock('PHP_SELF',PHP_SELF);
	$child->setBlock('REQUEST_URI',REQUEST_URI);
}

$db = new Database();

//function close_database() {
	//global $db;
	//$db->close();
//}

//register_shutdown_function('close_database');