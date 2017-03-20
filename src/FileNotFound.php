<?php namespace Chitanka\ThumbnailServer;

class FileNotFound extends \Exception {

	public static function fromFile($file) {
		return new static("File '{$file}' does not exist.");
	}
}
