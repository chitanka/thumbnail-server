<?php namespace Chitanka\ThumbnailServer;

class Server {

	private $contentDir;
	private $cacheDir;
	private $generator;

	/**
	 * @param string $contentDir
	 * @param string $cacheDir
	 */
	public function __construct($contentDir, $cacheDir) {
		$this->contentDir = realpath($contentDir);
		$this->cacheDir = realpath($cacheDir);
		$this->generator = new Generator();
	}

	public function serve() {
		$query = ltrim($this->sanitize(filter_input(INPUT_SERVER, 'QUERY_STRING')), '/');

		list($name, $width, $format) = $this->parseQuery($query);
		$file = sprintf("$this->contentDir/%s/%s.%s", dirname($query), $name, $format);

		if ($width === null) {
			return $this->tryToSendFile($file, $format);
		}

		$thumb = "$this->cacheDir/thumb/$query";

		if (!file_exists($file)) {
			$tifFile = realpath(preg_replace('/\.[^.]+$/', '.tif', $file));
			if (!$tifFile) {
				return $this->notFound($file);
			}
			$file = $this->convertTiff($thumb, $tifFile, $file);
		}
		$thumb = $this->generateThumbnail($thumb, $file, $width);
		return $this->sendFile($thumb, $format);
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

	private function generateThumbnail($thumb, $file, $width) {
		if (file_exists($thumb)) {
			return $thumb;
		}
		ini_set('memory_limit', '256M');
		return $this->generator->generateThumbnail($file, $thumb, $width, 90);
	}

	private function convertTiff($thumb, $tifFile, $file) {
		$file = dirname($thumb) . '/orig_' . basename($file);
		if (!file_exists($file)) {
			$this->generator->convertTiff($tifFile, $file);
		}
		return $file;
	}

	private function tryToSendFile($file, $format) {
		if (file_exists($file)) {
			return $this->sendFile($file, $format);
		}
		return $this->notFound($file);
	}

	private function sendFile($file, $format) {
		$format = strtr($format, [
			'jpg' => 'jpeg',
			'tif' => 'tiff',
		]);
		$expires = 2592000; // 30 days
		header("Cache-Control: maxage=$expires");
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		header('Content-Type: image/'.$format);
		header('Content-Length: '.filesize($file));
		return readfile($file);
	}

	private function notFound($file) {
		header('HTTP/1.1 404 Not Found');
		return print "File '{$file}' does not exist.";
	}
}
