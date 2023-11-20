# AntLoader

[![Packagist Downloads](https://img.shields.io/packagist/dt/antcms/antloader)](https://packagist.org/packages/antcms/antloader)
[![PHP Tests](https://github.com/AntCMS-org/AntLoader/actions/workflows/tests.yml/badge.svg)](https://github.com/AntCMS-org/AntLoader/actions/workflows/tests.yml)
[![PHPStan](https://github.com/AntCMS-org/AntLoader/actions/workflows/phpstan.yml/badge.svg)](https://github.com/AntCMS-org/AntLoader/actions/workflows/phpstan.yml)

A small, simple, and highly performant autoloader for PHP applications.

- Supports at least PHP 8.0
- Supports PSR-0 and PSR-4 autoloader capabilities.
- Classmap functionality using [composer/class-map-generator](https://github.com/composer/class-map-generator).
  - Improves application performance.
  - Classmaps can be generated on-the-fly and doesn't require you to dump them before publishing a release of your application.
  - Classes missing from the classmap will be automatically added.
- More flexible than using composer's autoloader functionality, as it can be modified on-the-fly.
- If the `$loader` instance is globally available, you can even add new directories to the autoloader on-the-fly.
- APCu caching support for the classmap

## Installation

```bash
composer require antcms/antloader
```

## Usage

```PHP
$loader = new AntCMS\AntLoader(); // Create AntLoader with it's default options. It will attempt to automatically detect the best way to store the classmap and use it. (APCu or filesystem.)
$loader->addNamespace('', 'somepath', 'psr0'); //Add a path for a PSR-0 autoloader, by providing an empty string it'll search for all classes in this path.
$loader->addNamespace('Example\\Class\\', 'someotherpath'); //Add a path for a PSR-4 autoloader, which will only search in that directory for the "Example\Class" namespace.
$loader->checkClassMap(); // Create a new classmap if it doesn't already exist. If it does, load it now.
$loader->register(); // Register the autoloader within PHP. Optionally pass 'true' to this to prepend AntLoader to the start of the autoloader list. PHP will then use AntLoader first when attempting to load classes.

$loader->resetClassMap(); // Reset the classmap, clearing the existing one out from whatever is the current caching method. Will not regenerate one automatically.
```

## Configuration

AntLoader accepts an array to configure it's available options.
None of the configuration options are required, however at a minimum it is recommended to specify a path unless you know APCu will be usable in all environments for your application.
**Note**: Please read the "Classmap Caching Options" section of this document, as that covers the strengths and weaknesses of each caching approach.

```PHP
$config = [
    'mode' => 'auto', // Can be 'auto', 'filesystem', 'apcu', 'memory', or 'none'.
    'path' => '/path/to/save/classmap.php', // Where should AntLoader store the classmap if the file system cache option is used.
    'key' => 'customApcuKey', // The APCu key used when storing the classmap. This does not usually need to be overridden.
    'ttl' => 3600, // Allows you to set the time to live when using APCu. Value is in seconds.
    'stopIfNotFound' => true // Setting this to true will cause AntLoader to stop looking for a class if it is not found in the classmap. By default it will look in the search directories you defined with `addNamespace`.
];
$loader = new AntCMS\AntLoader($config);
```

If you are looking to build an application that is fairly portable, we recommend configuring the path and nothing else.
This configuration will allow AntLoader to use APCu when available and fallback to the filesystem when it is not.
Providing a specific path for the file system cache ensures that the classmap will be stored in a location that is persistent.

```PHP
$config = [
    'path' => __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'classMap.php', // Tells AntLoader to store the classmap in a sub-folder named "cache".
];
$loader = new AntCMS\AntLoader($config);
```

## Classmap Caching Options

### APCu

Starting from version 2.0.0, AntLoader now supports storing the Classmap in RAM using APCu.
This feature allows AntLoader to achieve optimal performance by persisting the classmap between sessions.
Here are a few things to note about the APCu mode:

- AntLoader generates a random key based on the directory it resides in. This ensures a unique key name to avoid accidentally overwriting APCu keys. The generated key remains static throughout the lifespan of the application.
  - As long as you aren't running two separate PHP applications & using the same copy of AntLoader (which you shouldn't be), this is sufficient to prevent issues.
- Depending on your web server configuration, using APCu may allow the classmap to be accessed by other PHP applications. However, in the case of AntLoader, this information only includes the namespaces/classes within your application and their respective paths.
- By default, AntLoader stores the classmap with APCu using a Time-to-Live (TTL) of 7 days.

### Filesystem

The filesystem caching method, in theory, is slower than the APCu caching method.
However, the actual performance can vary based on external variables, and on well-performing systems with minimal disk load, the difference is likely to be minimal.
Here are some details about the filesystem caching method:

- By default, AntLoader saves the classmap to the system's temporary directory, which may not survive through multiple sessions. It is recommended to override the default path and specify a more persistent location.
- The classmap file stored in the filesystem has no lifespan limit imposed by AntLoader. It will be retained until either you delete the file or call the `resetClassMap` function.
- Clearing or resetting the classmap is generally easier to perform outside of calling the `resetClassMap` function provided by AntLoader.

### Memory

If you use the `memory` caching option, this will cause AntLoader to still create a classmap and then load it, however the generated classmap will not be saved anywhere. Typically generating a classmap can be quite slow so this is likely the worst performing option (even compared to no class map), however it may be useful in testing or if you need to point the autoloader to a directory you don't have control over, as the `composer/class-map-generator` package generates a classmap regardless of PSR0 / PSR4 & don't wish to cache the result from that.

## Notes

### Performance

- While it's not strictly necessary to use the classmap functionality, we strongly recommend doing so for optimal performance.
  - Testing shows that it reduces the time to find and load `1000` random classes by `95%` (from `0.0699` seconds to `0.0037` seconds)
- Depending on the setup, prepending AntLoader may speed up the performance of your application.
  - For example, if you are using composer's autoloader and have a lot of composer packages, that may delay the time it takes to load classes within your application.
    - In this example, the classes inside of your application will load slightly faster and classes loaded through composer will be slightly slower.
  - Potential improvements are highly dependent on the specific application and environment. In most situations, the difference is likely to be very minimal.

So, we encourage you to take advantage of the classmap feature to get the best performance out of your application.

### Maintaining AntLoader

AntLoader is generally hands-off, except that we highly recommend clearing out / resetting the classmap after updating your application.
AntLoader will **never** remove outdated classes / paths from the classmap, so never allowing it to be rebuilt can negatively affect the performance of your application if classes are renamed or moved.
The best way to do this is simply to call the `resetClassMap` function that AntLoader provides. This will automatically reset the classmap for the current Cache method.

#### Pruning the classmap

If you have an application where the class list may periodically change, you can prune the classmap periodically to ensure it's not filling up with classes that no longer exist.
To do so, simply call `pruneClassmap`. This function will return the number of pruned classes.

## License

AntLoader is distributed with no warranty under the [Apache License 2.0](https://github.com/AntCMS-org/AntLoader/blob/main/LICENSE)
