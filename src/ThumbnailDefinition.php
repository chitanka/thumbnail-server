<?php namespace Chitanka\ThumbnailServer;

class ThumbnailDefinition {

	public $name;
	public $width;
	public $format;
	public $originalFile;
	public $path;
	public $raw;
	private $contentDir;
	private $cacheDir;

	public function __construct($queryString, $contentDir, $cacheDir) {
		$this->contentDir = $contentDir;
		$this->cacheDir = $cacheDir;
		$this->raw = $this->sanitize($queryString);
		list($this->name, $this->width, $this->format) = $this->parseQuery($this->raw);
		$this->originalFile = sprintf("$this->contentDir/%s/%s.%s", dirname($this->raw), $this->name, $this->format);
		$this->path = "$this->cacheDir/thumb/$this->raw";
	}

	public function hasNoWidth() {
		return empty($this->width);
	}

	private function sanitize($s) {
		$s = preg_replace('#[^a-z\d./]#', '', $s);
		$s = strtr($s, ['..' => '.']);
		return $s;
	}

	private function parseQuery($query) {
		if (substr_count($query, '.') == 2) {
			list($name, $width, $format) = explode('.', basename($query));
		} else {
			list($name, $format) = explode('.', basename($query));
			$width = null;
		}
		return [$name, $width, $format];
	}

}
