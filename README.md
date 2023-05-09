# AntLoader

A small and simple autoloader for PHP applications.

- Supports at least PHP 8.0
- Supports PSR-0 and PSR-4 autoloader capabilities.
- Classmap functionality using [composer/class-map-generator](https://github.com/composer/class-map-generator).
  - Improves application performance.
  - Classmaps can be generated on-the-fly and doesn't require you to dump them before publishing a release of your application.
  - Classes missing from the classmap will be automatically added.
- More flexible than using composer's autoloader functionality, as it can be modified on-the-fly.
- If the `$loader` instance is globally available, you can even add new directories to the autoloader on-the-fly.

## Installation

```bash
composer require antcms/antloader
```

## Usage

```PHP
$classMapPath = __DIR__  . DIRECTORY_SEPARATOR .  'Cache'  . DIRECTORY_SEPARATOR .  'classMap.php';
$loader = new AntCMS\AntLoader($classMapPath );
$loader->addPrefix('', 'somepath', 'psr0'); //Add a path for a PSR-0 autoloader, by providing an empty string it'll search for all classes in this path.
$loader->addPrefix('Example\\Class\\', 'someotherpath'); //Add a path for a PSR-4 autoloader, which will only search in that directory for the "Example\Class" namespace.
$loader->checkClassMap(); // Create a new classmap if it doesn't already exist. If it does, load it now.
$loader->register(); // Register the autoloader within PHP.
```

### Notes

- Technically, you don't need to use the classmap functionality, however it is recommended for better performance.
  - RAID0 SSD Array: 85% faster from 0.0691 seconds to 0.01 seconds when instancing 1000 random classes.
  - Standard HDD: 91% faster from 0.0796 seconds to 0.0072 seconds when instancing 1000 random classes.

### License

AntLoader is distributed with no warranty under the [Apache License 2.0](https://github.com/AntCMS-org/AntLoader/blob/main/LICENSE)
