<?php

declare(strict_types=1);

namespace AntCMS;

class Antloader
{
    private string $classMapPath;

    /** @var array<string,string> **/
    private array $psr0 = [];

    /** @var array<string,string> **/
    private array $psr4 = [];

    /** @var array<string,string> **/
    private array $classMap = [];

    /**
     * Configures the path of where to save the classmap. It is recomended to save this to a cache folder that will occasionally be cleared.
     * You don't need to set the classmap path, however it is recomended for performance reasons.
     * @param string $path (optional) The full path of where to save the classmap to, including the file name.
     * @return void 
     */
    public function __construct(string $path = '')
    {
        if (!empty($path)) {
            $this->classMapPath = $path;
        }
    }

    /**
     * Checks for the existence of the classMap file. Will generate a new one if it doesn't exist.
     * After generating one / if it exists, the map is loaded to the classMap array to be used to speed up loading later.
     * @return void
     */
    public function checkClassMap(): void
    {
        if (!file_exists($this->classMapPath)) {
            $generator = new \Composer\ClassMapGenerator\ClassMapGenerator;

            foreach ($this->psr0 as $path) {
                $generator->scanPaths($path);
            }

            foreach ($this->psr4 as $path) {
                $generator->scanPaths($path);
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
     * @return void
     * */
    public function register(): void
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * @param string $prefix Class prefix. Use an empty prefix to allow this prefix to work with any path. Paths must already have directory separators normalized for the current system.
     * @param string $path Base path associated with the class prefix
     * @param string $type The type of PSR autoloader the prefix is associated with. EX: psr4
     * @return void
     */
    public function addPrefix(string $prefix, string $path, string $type = 'psr4'): void
    {
        //The loader assumes the path does NOT end in a directory separator, so let's remove it now.
        if (str_ends_with($path, DIRECTORY_SEPARATOR)) {
            $path = substr($path, 0, -1);
        }

        $type = strtolower($type);

        switch ($type) {
            case 'psr0':
                $this->psr0[$prefix] = $path;
                break;
            case 'psr4':
                $this->psr4[$prefix] = $path;
                break;
            default:
                throw new \Exception("Unknown PSR autoloader type: {$type}");
        }
    }

    /**
     * @param string $class Classname to load. If found, file will be included and execution will be completed.
     * @return void
     */
    public function autoload(string $class): void
    {
        //Check if the class exists in the classMap array and then use that to require it, rather than searching for it.
        $file = $this->classMap[$class] ?? '';
        if (file_exists($file)) {
            require $file;
            return;
        }

        /* PSR-0 Loader.
         * @see https://www.php-fig.org/psr/psr-0/
         */
        foreach ($this->psr0 as $prefix => $path) {
            if (empty($prefix) || strpos($class, $prefix) === 0) {
                $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
                $file = $this->getFile($class, $path, $prefix);

                if (file_exists($file)) {
                    //The class was found, but not defined in our classmap, so let's update it and save it.
                    $this->classMap[$class] = $file;
                    $this->saveMap();

                    require $file;
                    return;
                }
            }
        }

        /* PSR-4 Loader.
         * @see https://www.php-fig.org/psr/psr-4/
         */
        foreach ($this->psr4 as $prefix => $path) {
            if (empty($prefix) || strpos($class, $prefix) === 0) {
                $file = $this->getFile($class, $path, $prefix);

                if (file_exists($file)) {
                    //The class was found, but not defined in our classmap, so let's update it and save it.
                    $this->classMap[$class] = $file;
                    $this->saveMap();

                    require $file;
                    return;
                }
            }
        }
    }

    /**
     * @param string $class Classname, after having and specialized handling performed
     * @param string $path The path associated with the prefix
     * @param string $prefix The prefix matching the classname.
     * @return string The completed file path.
     */
    private function getFile(string $class, string $path, string $prefix): string
    {
        //Remove the "prefix" so we get just the classname
        $classname = substr($class, strlen($prefix));

        //Now convert it to a path and ensure it has a directory separator at the start, since our paths won't.
        $classname = str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
        if (!str_starts_with($classname, DIRECTORY_SEPARATOR)) {
            $classname = DIRECTORY_SEPARATOR . $classname;
        }

        return $path . $classname;
    }

    /**
     * Saves the current classMap array to the cache folder for later access.
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
