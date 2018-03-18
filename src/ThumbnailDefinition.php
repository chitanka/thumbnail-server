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
		$this->raw = $this->sanitizeQuery($queryString);
		list($this->name, $this->width, $this->format) = $this->parseQuery($this->raw);
		$this->originalFile = sprintf("$this->contentDir/%s/%s.%s", dirname($this->raw), $this->name, $this->format);
		$this->path = "$this->cacheDir/thumb/$this->raw";
	}

	public function hasNoWidth() {
		return empty($this->width);
	}

	private function sanitizeQuery($query) {
		$query = $this->stripHumanReadableNameFromQuery($query);
		$query = preg_replace('#[^a-z\d./-]#', '', $query);
		$query = strtr($query, ['..' => '.']);
		return $query;
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

	private function stripHumanReadableNameFromQuery($query) {
		if (preg_match('#.+\..+/.+\..+$#', $query)) {
			return dirname($query);
		}
		return $query;
	}

}
