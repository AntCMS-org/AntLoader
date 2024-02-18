<?php

declare(strict_types=1);

namespace AntCMS;

use Composer\ClassMapGenerator\ClassMap;

class AntLoader
{
    private string $classMapPath = '';

    /** @var array<string,array<string>> */
    private array $psr0 = [];

    /** @var array<string,array<string>> */
    private array $psr4 = [];

    /** @var array<string,string> */
    private array $classMap = [];

    private int $cacheType = 0;
    private string $cacheKey = '';
    private int $cacheTtl = 604800; // 1 Week in seconds.
    private bool $stopIfNotFound = false;

    public const noCache   = 0;
    public const inMemory  = 1;
    public const fileCache = 2;
    public const apcuCache = 3;

    /**
     * Creates a new instance of AntLoader.
     *
     * @param array{mode?:string,path?:string,key?:string,ttl?:int,stopIfNotFound?:bool} $config (optional) Configuration options for AntLoader.
     *   Available keys:
     *   - 'mode': What mode to use for storing the classmap. Can be 'auto', 'filesystem', 'apcu', 'memory', or 'none'.
     *   - 'path': Where to save the classmap to. By default, this will be saved to a random temp file.
     *             If you are using the file system cache, it is recommended to manually specify this path to one that is persistent between sessions.
     *   - 'key': Use this option to override the unique key that AntLoader uses with its cache.
     *            By default, this will be created off of an MD5 hash of the directory where AntLoader resides. This is sufficient for most situations.
     *   - 'ttl': Time-to-Live for the cache in seconds. Default is 604800 (1 week).
     *   - 'stopIfNotFound': Setting this to true will cause AntLoader to stop looking for a class if it is not found in the classmap.
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'mode' => 'auto',
            'path' => '',
            'key' => '',
            'ttl' => 604800,
            'stopIfNotFound' => false,
        ];

        $config = array_merge($defaultConfig, $config);

        if (empty($config['key'])) {
            $generatedID = 'AntLoader_' . hash('md5', __FILE__);
        } else {
            $generatedID = $config['key'];
        }

        if (empty($config['path'])) {
            $this->classMapPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $generatedID;
        } else {
            $this->classMapPath = $config['path'];
        }

        $cacheOptions = [
            'none' => [
                'type' => self::noCache,
            ],
            'auto' => [
                'type' => extension_loaded('apcu') && apcu_enabled() ? self::apcuCache : self::fileCache,
                'key'  => $generatedID,
            ],
            'filesystem' => [
                'type' => self::fileCache,
            ],
            'apcu' => [
                'type' => self::apcuCache,
                'key'  => $generatedID,
            ],
            'memory' => [
                'type' => self::inMemory,
            ],
        ];

        if (array_key_exists($config['mode'], $cacheOptions)) {
            $this->cacheType = intval($cacheOptions[$config['mode']]['type']);
            $this->cacheKey = strval($cacheOptions[$config['mode']]['key'] ?? '');
        } else {
            throw new \Exception("Unsupported cache mode. Please ensure you are specifying 'auto', 'filesystem', 'apcu', 'memory', or 'none'.");
        }

        $this->cacheTtl = $config['ttl'];
        $this->stopIfNotFound = (bool) $config['stopIfNotFound'];
    }

    /**
     * Checks for the existence of the classMap file. Will generate a new one if it doesn't exist.
     * After generating one / if it exists, the map is loaded to the classMap array to be used to speed up loading later.
     */
    public function checkClassMap(): void
    {
        switch ($this->cacheType) {
            case self::inMemory:
                $classMap = $this->generateMap();
                $this->classMap = $classMap->getMap();
                return;
            case self::noCache:
                return;
            case self::fileCache:
                // If the classmap doesn't yet exist, generate a new one now.
                if (!file_exists($this->classMapPath)) {
                    $classMap = $this->generateMap();
                    $this->classMap = $classMap->getMap();
                    $this->saveMap();
                } else {
                    // Otherwise, load the existing one.
                    $this->classMap = include $this->classMapPath;
                }
                return;
            case self::apcuCache:
                if (apcu_exists($this->cacheKey)) {
                    $map = apcu_fetch($this->cacheKey);
                    if (is_array($map)) {
                        $this->classMap = $map;
                    }
                } else {
                    $classMap = $this->generateMap();
                    $this->classMap = $classMap->getMap();
                    $this->saveMap();
                }
                return;
        }
    }

    /**
     * Deletes the existing classmap. Does not automatically create a new one.
     */
    public function resetClassMap(): void
    {
        switch ($this->cacheType) {
            case self::apcuCache:
                @apcu_delete($this->cacheKey);
                break;
            case self::fileCache:
                @unlink($this->classMapPath);
                break;
        }

        $this->classMap = [];
    }

    /**
     * Registers the autoloader.
     * @param bool $prepend (optional) Set to true to cause the autoloader to be added to the start of the PHP autoloader list.
     *                     This will make AntLoader take priority over other autoloaders.
     */
    public function register(bool $prepend = false): void
    {
        spl_autoload_register([$this, 'autoload'], true, $prepend);
    }

    /**
     * Un-registers the autoloader.
     */
    public function unRegister(): void
    {
        spl_autoload_unregister([$this, 'autoload']);
    }

    /**
     * Registers a namepsace and an associated path to look in.
     *
     * @param string $namespace Use an empty string to allow this path to apply for all namespaces and classes. Paths must already have directory separators normalized for the current system.
     * @param string $path Base path associated with the namespace.
     * @param string $type (optional) The type of PSR autoloader to associate with the namespace defaults to a PSR-4 autoloader. (accepts psr4 or psr0)
     */
    public function addNamespace(string $namespace, string $path, string $type = 'psr4'): void
    {
        //The loader assumes the path does NOT end in a directory separator, so let's remove it now.
        if (str_ends_with($path, DIRECTORY_SEPARATOR)) {
            $path = substr($path, 0, -1);
        }

        $type = strtolower($type);

        switch ($type) {
            case 'psr0':
                $this->psr0[$namespace][] = $path;
                break;
            case 'psr4':
                $this->psr4[$namespace][] = $path;
                break;
            default:
                throw new \Exception("Unknown PSR autoloader type: {$type}");
        }
    }

    /**
     * The autoloder function. You don't need to call this. Just use the register function and then PHP will automatically call the autoloader.
     *
     * @param string $class Classname to load. If found, file will be included and execution will be completed.
     */
    public function autoload(string $class): void
    {
        //Check if the class exists in the classMap array and then use that to require it, rather than searching for it.
        $file = $this->classMap[$class] ?? '';
        if (file_exists($file)) {
            require_once $file;
            return;
        }

        if ($this->stopIfNotFound) {
            return;
        }

        /* PSR-0 Loader.
         * @see https://www.php-fig.org/psr/psr-0/
         */
        foreach ($this->psr0 as $namespace => $paths) {
            if (str_starts_with($class, $namespace)) {
                $classModifiedClass = str_replace('_', DIRECTORY_SEPARATOR, $class);
                foreach ($paths as $path) {
                    $file = $this->getFile($classModifiedClass, $path, $namespace);

                    if (file_exists($file)) {
                        //The class was found, but not defined in our classmap, so let's update it and save it.
                        $this->classMap[$class] = $file;
                        $this->saveMap();

                        require_once $file;
                        return;
                    }
                }
            }
        }

        /* PSR-4 Loader.
         * @see https://www.php-fig.org/psr/psr-4/
         */
        foreach ($this->psr4 as $namespace => $paths) {
            if (str_starts_with($class, $namespace)) {
                foreach ($paths as $path) {
                    $file = $this->getFile($class, $path, $namespace);

                    if (file_exists($file)) {
                        //The class was found, but not defined in our classmap, so let's update it and save it.
                        $this->classMap[$class] = $file;
                        $this->saveMap();

                        require_once $file;
                        return;
                    }
                }
            }
        }
    }

    /**
     * Prunes the classmap cache of any non-existant classes
     *
     * @return int The number of classes that was pruned.
     */
    public function pruneClassmap(): int
    {
        $pruned = 0;
        foreach ($this->classMap as $class => $path) {
            if (!file_exists($path)) {
                $pruned++;
                unset($this->classMap[$class]);
            }
        }

        if ($pruned > 0) {
            $this->saveMap();
        }

        return $pruned;
    }

    /**
     * @param string $class Classname, after having any specialized handling performed
     * @param string $path The path associated with the namespace
     * @param string $namespace The namespace matching the classname.
     * @return string The completed file path.
     */
    private function getFile(string $class, string $path, string $namespace): string
    {
        //Remove the namespace so we get just the classname
        $classname = substr($class, strlen($namespace));

        //Now convert it to a path and ensure it has a directory separator at the start, since our paths won't.
        $classname = str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
        if (!str_starts_with($classname, DIRECTORY_SEPARATOR)) {
            $classname = DIRECTORY_SEPARATOR . $classname;
        }

        return $path . $classname;
    }

    /**
     * Saves the current classMap array to the cache folder for later access.
     */
    private function saveMap(): void
    {
        switch ($this->cacheType) {
            case self::inMemory:
            case self::noCache:
                return;
            case self::fileCache:
                $output = '<?php ' . PHP_EOL;
                $output .= 'return ' . var_export($this->classMap, true) . ';';
                @file_put_contents($this->classMapPath, $output);
                return;
            case self::apcuCache:
                apcu_store($this->cacheKey, $this->classMap, $this->cacheTtl);
                return;
        }
    }

    /**
     * Uses the composer ClassMapGenerator function to generate a classmap for the configured paths and then returns it.
     */
    private function generateMap(): ClassMap
    {
        $generator = new \Composer\ClassMapGenerator\ClassMapGenerator();

        foreach ($this->psr0 as $paths) {
            foreach ($paths as $path) {
                $generator->scanPaths($path);
            }
        }

        foreach ($this->psr4 as $paths) {
            foreach ($paths as $path) {
                $generator->scanPaths($path);
            }
        }

        $classMap = $generator->getClassMap();
        $classMap->sort();
        return $classMap;
    }
}
