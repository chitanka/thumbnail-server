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
		$this->serveForQuery($this->prepareQuery());
	}

	public function serveForQuery($query) {
		$thumb = new ThumbnailDefinition($query, $this->contentDir, $this->cacheDir);
		try {
			if ($thumb->hasNoWidth()) {
				$this->serveFile($thumb->originalFile, $thumb->format);
				return;
			}
			if (!file_exists($thumb->originalFile)) {
				$thumb->originalFile = $this->tryToGenerateFromTiff($thumb);
			}
			$this->generator->generateThumbnail($thumb);
			$this->serveFile($thumb->path, $thumb->format);
		} catch (FileNotFound $exception) {
			$this->sendNotFound($exception);
		}
	}

	private function prepareQuery(): string {
		if (!empty($_SERVER['QUERY_STRING'])) {
			return $_SERVER['QUERY_STRING'];
		}
		$pathInfo = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']));
		return $pathInfo;
	}

	private function tryToGenerateFromTiff(ThumbnailDefinition $thumb) {
		$tifFile = realpath(preg_replace('/\.[^.]+$/', '.tif', $thumb->originalFile));
		if (empty($tifFile)) {
			throw FileNotFound::fromFile($thumb->originalFile);
		}
		$convertedFile = dirname($thumb->path).'/orig_'.basename($thumb->originalFile);
		if (!file_exists($convertedFile)) {
			$this->generator->convertTiff($tifFile, $convertedFile);
		}
		return $convertedFile;
	}

	private function serveFile($file, $format) {
		if (!file_exists($file)) {
			throw FileNotFound::fromFile($file);
		}
		$this->sendHeaders($file, $format);
		$this->sendFileContents($file);
	}

	private function sendHeaders($file, $format) {
		$expires = 2592000; // 30 days
		header("Cache-Control: maxage=$expires");
		header('Content-Type: '.$this->mimeType($format));
		header('Content-Length: '.filesize($file));
	}

	private function sendFileContents($file) {
		readfile($file);
	}

	private function mimeType($format) {
		return 'image/'.strtr($format, [
			'jpg' => 'jpeg',
			'tif' => 'tiff',
		]);
	}

	private function sendNotFound(FileNotFound $exception) {
		header('HTTP/1.1 404 Not Found');
		header('Exception: '.str_replace("\n", '; ', $exception->getMessage()));
		$placeholder = realpath(__DIR__.'/../assets/404.png');
		header('Content-Type: image/png');
		header('Content-Length: '.filesize($placeholder));
		readfile($placeholder);
	}
}
