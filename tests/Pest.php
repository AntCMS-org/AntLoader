<?php
function setupLoader(bool $cache = false)
{
    $pathClasses = __DIR__ . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR;

    $path = ($cache) ? __DIR__ . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR . 'classMap.php' : '';
    $loader = new AntCMS\AntLoader($path);
    $loader->addPrefix('',  $pathClasses . 'PSR0', 'psr0');
    $loader->addPrefix('', $pathClasses . 'PSR4');
    $loader->checkClassMap();
    $loader->register();
    return $loader;
}

function removeClassMap()
{
    @unlink(__DIR__ . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR . 'classMap.php');
}
