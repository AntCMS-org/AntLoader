<?php
function setupLoader(bool $cache = false)
{
    $pathClasses = __DIR__ . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR;

    $path = ($cache) ? __DIR__ . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR . 'classMap.php' : '';
    $loader = new AntCMS\AntLoader($path);
    $loader->addPrefix('',  $pathClasses . 'PSR0', 'psr0');
    $loader->addPrefix('', $pathClasses . 'PSR4');
    $loader->addPrefix('', $pathClasses . 'Random');
    $loader->checkClassMap();
    $loader->register();
    return $loader;
}

function removeClassMap()
{
    @unlink(__DIR__ . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR . 'classMap.php');
}


/**
 * Generates any number of random classes. Default is 250 classes, which is probably more than most applications will see. But this is a test to just show that the class map is infact optimizing things.
 * If things are slower with the classmap, we are doing something very wrong.
 * 
 * @param int $count Number of random classes to generate
 * @return array 
 */
function createRandomClasses(int $count = 250): array
{
    $classTemplate =
        '<?php
        namespace :namespace:;
        class :classname:
        {
            public function testResult()
            {
                return get_class($this);
            }
        }';

    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'Random';
    if (!file_exists($directory)) {
        mkdir($directory);
    }

    $classes = [];

    for ($i = 0; $i < $count; $i++) {
        $namespace = 'Random' . substr(md5(rand()), 0, 8);
        $classname = 'Class' . $i;

        $classContent = strtr($classTemplate, [
            ':namespace:' => $namespace,
            ':classname:' => $classname,
        ]);

        $directoryPath = str_replace('\\', '/', $namespace);
        $directoryPath = rtrim($directory, '/') . '/' . $directoryPath;

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }

        $filePath = $directoryPath . '/' . $classname . '.php';

        file_put_contents($filePath, $classContent);

        $classes[] = $namespace . '\\' . $classname;
    }

    return $classes;
}



function deleteRandomClasses()
{

    $pathClasses = __DIR__ . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR . 'Random';
    $pathClasses = new RecursiveDirectoryIterator($pathClasses);
    $pathClasses = new RecursiveIteratorIterator($pathClasses, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($pathClasses as $file) {
        $file->isDir() ?  @rmdir($file) : @unlink($file);
    }
}
