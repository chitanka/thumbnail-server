<?php namespace Chitanka\ThumbnailServer;

class ThumbnailDimensions {

	const DEFAULT_WIDTH = 45;

	public $width;
	public $height;
	public $originalWidth;
	public $originalHeight;

	/**
	 * @param string $filename
	 * @param int $thumbnailWidth
	 */
	public function __construct($filename, $thumbnailWidth) {
		$this->width = $thumbnailWidth ?: self::DEFAULT_WIDTH;
		list($this->originalWidth, $this->originalHeight) = getimagesize($filename);
		$this->height = $this->width * $this->originalHeight / $this->originalWidth;
	}
}
