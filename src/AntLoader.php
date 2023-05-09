<?php

declare(strict_types=1);

namespace AntCMS;

class AntLoader
{
    private string $classMapPath = '';

    /** @var array<string,array> **/
    private array $psr0 = [];

    /** @var array<string,array> **/
    private array $psr4 = [];

    /** @var array<string,string> **/
    private array $classMap = [];

    /**
     * Creates a new instance of AntLoader.
     * 
     * @param string $path (optional) The full path of where to save the classmap to, including the file name. It is recomended to include this for improved performance.
     * @return void 
     */
    public function __construct(string $path = '')
    {
        $this->classMapPath = $path;
    }

    /**
     * Checks for the existence of the classMap file. Will generate a new one if it doesn't exist.
     * After generating one / if it exists, the map is loaded to the classMap array to be used to speed up loading later.
     * @return void
     */
    public function checkClassMap(): void
    {
        if (empty($this->classMapPath)) {
            return;
        }

        if (!file_exists($this->classMapPath)) {
            $generator = new \Composer\ClassMapGenerator\ClassMapGenerator;

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

            $this->classMap = $classMap->getMap();
            $this->saveMap();
            return;
        }
        $this->classMap = include $this->classMapPath;
    }

    /**
     * Registers the autoloader.
     * 
     * @return void
     * */
    public function register(): void
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Un-registers the autoloader.
     * 
     * @return void
     * */
    public function unRegister(): void
    {
        spl_autoload_unregister(array($this, 'autoload'));
    }

    /**
     * Registers a namepsace and an associated path to look in.
     * 
     * @param string $namespace Use an empty string to allow this path to apply for all namespaces and classes. Paths must already have directory separators normalized for the current system.
     * @param string $path Base path associated with the namespace.
     * @param string $type (optional) The type of PSR autoloader to associate with the namespace defaults to a PSR-4 autoloader. (accepts psr4 or psr0)
     * @return void
     */
    public function addPrefix(string $namespace, string $path, string $type = 'psr4'): void
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
     * @return void
     */
    public function autoload(string $class): void
    {
        //Check if the class exists in the classMap array and then use that to require it, rather than searching for it.
        $file = $this->classMap[$class] ?? '';
        if (file_exists($file)) {
            require_once $file;
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
     * 
     * @return void
     */
    private function saveMap(): void
    {
        // If the classmap path isn't defined, don't try to save to it.
        if (empty($this->classMapPath)) {
            return;
        }

        $output = '<?php ' . PHP_EOL;
        $output .= 'return ' . var_export($this->classMap, true) . ';';
        @file_put_contents($this->classMapPath, $output);
    }
}
