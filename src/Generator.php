<?php namespace Chitanka\ThumbnailServer;

class Generator {

	public function makeSureDirExists($file) {
		$dir = dirname($file);
		if ( ! file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
	}

	public function generateThumbnail($filename, $thumbname, $width = null, $quality = null) {
		$this->makeSureDirExists($thumbname);

		$dimensions = new ThumbnailDimensions($filename, $width);
		if ($this->shouldReturnOriginalFile($dimensions)) {
			copy($filename, $thumbname);
			return $thumbname;
		}
		return $this->reallyGenerateThumbnail($filename, $thumbname, $dimensions, $quality);
	}

	public function convertTiff($tiffFile, $targetFile) {
		$this->makeSureDirExists($targetFile);
		shell_exec("convert $tiffFile $targetFile");
	}

	private function reallyGenerateThumbnail($filename, $thumbname, ThumbnailDimensions $dimensions, $quality) {
		switch ($this->getExtensionFromFilename($filename)) {
			case 'jpg':
			case 'jpeg':
				return $this->generateThumbnailForJpeg($filename, $thumbname, $dimensions, $quality);
			case 'png':
				return $this->generateThumbnailForPng($filename, $thumbname, $dimensions);
		}
		return $thumbname;
	}

	private function shouldReturnOriginalFile(ThumbnailDimensions $dimensions) {
		return in_array($dimensions->width, ['max', 'orig']) || $dimensions->width > $dimensions->originalWidth;
	}

	private function getExtensionFromFilename($filename) {
		return ltrim(strrchr($filename, '.'), '.');
	}

	private function generateThumbnailForJpeg($filename, $thumbname, ThumbnailDimensions $dimensions, $quality) {
		$image_p = imagecreatetruecolor($dimensions->width, $dimensions->height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $dimensions->width, $dimensions->height, $dimensions->originalWidth, $dimensions->originalHeight);
		$quality = $quality ?: 90;
		imagejpeg($image_p, $thumbname, $quality);
		return $thumbname;
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
		return $thumbname;
	}

}
