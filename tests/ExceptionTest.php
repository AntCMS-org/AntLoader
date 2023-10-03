<?php
it('Throws unknown PSR autoloader', function () {
    $loader = new \AntCMS\AntLoader();
    $loader->addNamespace('', '', 'psr14');
})->throws(Exception::class, "Unknown PSR autoloader type: psr14");

it('Throws unknown caching type', function () {
    $loader = new \AntCMS\AntLoader(['mode' => 'invalid']);
})->throws(Exception::class, "Unsupported cache mode. Please ensure you are specifying 'auto', 'filesystem', 'apcu', or 'none'.");
