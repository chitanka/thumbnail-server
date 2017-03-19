<?php
require __DIR__.'/../../vendor/autoload.php';
$server = new Chitanka\ThumbnailServer\Server();
$server->serve();
