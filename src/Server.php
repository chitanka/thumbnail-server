<?php namespace Chitanka\ThumbnailServer;

class Server {

	private $contentDir;
	private $cacheDir;

	/**
	 * @param string $contentDir
	 * @param string $cacheDir
	 */
	public function __construct($contentDir, $cacheDir) {
		$this->contentDir = realpath($contentDir);
		$this->cacheDir = realpath($cacheDir);
	}

	public function serve() {
		$generator = new Generator();
		$query = ltrim($this->sanitize(filter_input(INPUT_SERVER, 'QUERY_STRING')), '/');

		if (substr_count($query, '.') == 2) {
			list($name, $width, $format) = explode('.', basename($query));
		} else {
			list($name, $format) = explode('.', basename($query));
			$width = null;
		}
		$file = sprintf("$this->contentDir/%s/%s.%s", dirname($query), $name, $format);

		if ($width === null) {
			if (file_exists($file)) {
				return $this->sendFile($file, $format);
			}
			return $this->notFound($file);
		}

		$thumb = "$this->cacheDir/thumb/$query";

		if (!file_exists($file)) {
			$tifFile = realpath(preg_replace('/\.[^.]+$/', '.tif', $file));
			if (!$tifFile) {
				return $this->notFound($file);
			}
			$file = dirname($thumb) . '/orig_' . basename($file);
			if (!file_exists($file)) {
				$generator->makeSureDirExists($file);
				shell_exec("convert $tifFile $file");
			}
		}
		if (!file_exists($thumb)) {
			ini_set('memory_limit', '256M');
			$thumb = $generator->generateThumbnail($file, $thumb, $width, 90);
		}

		return $this->sendFile($thumb, $format);
	}

	private function sanitize($s) {
		$s = preg_replace('#[^a-z\d./]#', '', $s);
		$s = strtr($s, ['..' => '.']);
		return $s;
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
