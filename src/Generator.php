<?php namespace Chitanka\ThumbnailServer;

class Generator {

	public function makeSureDirExists($file) {
		$dir = dirname($file);
		if ( ! file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
	}

	public function generateThumbnail(ThumbnailDefinition $thumb) {
		if (file_exists($thumb->path)) {
			return;
		}
		$this->makeSureDirExists($thumb->path);

		$dimensions = new ThumbnailDimensions($thumb->originalFile, $thumb->width);
		if ($this->shouldReturnOriginalFile($dimensions)) {
			copy($thumb->originalFile, $thumb->path);
			return;
		}
		$this->reallyGenerateThumbnail($thumb->originalFile, $thumb->path, $dimensions);
	}

	public function convertTiff($tiffFile, $targetFile) {
		$this->makeSureDirExists($targetFile);
		shell_exec("convert $tiffFile $targetFile");
	}

	private function reallyGenerateThumbnail($filename, $thumbname, ThumbnailDimensions $dimensions) {
		ini_set('memory_limit', '256M');
		switch ($this->getExtensionFromFilename($filename)) {
			case 'jpg':
			case 'jpeg':
				return $this->generateThumbnailForJpeg($filename, $thumbname, $dimensions);
			case 'png':
				return $this->generateThumbnailForPng($filename, $thumbname, $dimensions);
		}
	}

	private function shouldReturnOriginalFile(ThumbnailDimensions $dimensions) {
		return in_array($dimensions->width, ['max', 'orig']) || $dimensions->width > $dimensions->originalWidth;
	}

	private function getExtensionFromFilename($filename) {
		return ltrim(strrchr($filename, '.'), '.');
	}

	private function generateThumbnailForJpeg($filename, $thumbname, ThumbnailDimensions $dimensions) {
		$image_p = imagecreatetruecolor($dimensions->width, $dimensions->height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $dimensions->width, $dimensions->height, $dimensions->originalWidth, $dimensions->originalHeight);
		$quality = 90;
		imagejpeg($image_p, $thumbname, $quality);
	}

	private function generateThumbnailForPng($filename, $thumbname, ThumbnailDimensions $dimensions) {
		$image_p = imagecreatetruecolor($dimensions->width, $dimensions->height);
		$image = imagecreatefrompng($filename);
		imagealphablending($image_p, false);
		$color = imagecolortransparent($image_p, imagecolorallocatealpha($image_p, 0, 0, 0, 127));
		imagefill($image_p, 0, 0, $color);
		imagesavealpha($image_p, true);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $dimensions->width, $dimensions->height, $dimensions->originalWidth, $dimensions->originalHeight);
		imagepng($image_p, $thumbname, 9);
	}

}
