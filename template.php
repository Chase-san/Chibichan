<?php

/**
 * My poor man's theming class.
 */
class Template {
	const section_start_open = '{!';
	const section_start_close = '}';
	const section_end_open = '{';
	const section_end_close = '!}';
	const block_open = '{!';
	const block_close = '!}';

	private $section_name = '';
	public $content = '';
	
	function Template($template_content, $section_name = '') {
		$this->content = $template_content;
		$this->section_name = $section_name;
	}
	
	function copy() {
		return new Template($this->content,$this->section_name);
	}
	
	function getSection($section_name) {
		$s_section = self::section_start_open . $section_name . self::section_start_close;
		$e_section = self::section_end_open . $section_name . self::section_end_close;
		if(($sp=strpos($this->content,$s_section)) !== FALSE) {
			if(($ep=strpos($this->content,$e_section)) !== FALSE) {
				$sp += strlen($s_section);
				return new Template(substr($this->content, $sp, $ep-$sp), $section_name);
			}
		}
	}
	
	function setSection($section) {
		if($section->section_name == false) return;
		$s_section = self::section_start_open . $section->section_name . self::section_start_close;
		$e_section = self::section_end_open . $section->section_name . self::section_end_close;
		if(($sp=strpos($this->content,$s_section)) !== FALSE) {
			if(($ep=strpos($this->content,$e_section)) !== FALSE) {
				$ep += strlen($e_section);
				$this->content = substr_replace($this->content, $section->content, $sp, $ep-$sp);
			}
		}
	}
	
	function setBlock($block_name, $data) {
		$block_name = self::block_open . $block_name . self::block_close;
		while(FALSE !== strpos($this->content, $block_name)) {
			$this->content = str_replace($block_name, $data, $this->content);
		}
	}
}

function loadTemplate($filename) {
	return new Template(file_get_contents($filename));
}