<?php

namespace GoogleAPI;

use \GoogleAPI\Exception\Autoloader as AutoloaderException;

/**
 * The Autoloader class can be used to register namespaces and their include paths.
 * This makes it very easy to load classes automatically and only requires a single line
 * of code. In order to automatically load a class based on it's namespace there are a few
 * rules the namespace must follow:
 *
 * * classes should be PascalCased and their corresponding filenames should be snake_cased.
 * For example, BaseClass would result in base_class.php
 * * The first segment of the namespace is used to identify your namespace and should be
 * used as a vendor identifier (e.g. Zend, Koi, Bob, etc)
 * * the namespace should have a matching filepath under the registered base directory.
 * Foo\Bar\Baz would result in foo/bar/baz.php
 *
 * In order to register a class we call the static method "register":
 *
 *     // Syntax: Autoloader::register( namespace, base path );
 *     Autoloader::register('Yorick', __DIR__ . '/yorick');
 *
 * Once the namespace is registered all you have to do is calling the classes, they'll
 * be loaded automatically from now on.
 *
 * ## Examples
 *
 * * Koi\Application\Base => koi/application/base.php
 * * Koi\Cache\File => koi/cache/file.php
 * * Koi\Cache\CacheInterface => koi/cache/cache_interface.php
 *
 * @author   Yorick Peterse
 * @link     http://yorickpeterse.com/
 * @license  MIT License
 * @package  Koi
 *
 * Copyright (c) 2010 - 2011, Yorick Peterse
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class Autoloader
{
    /**
     * Array containing a set of namespaces and their base paths.
     *
     * @access public
     * @static
     * @since  0.3
     */
    public static $namespaces = array();

    /**
     * Method used for registrating a new namespace and it's base path. The first
     * argument is the namespace and the second the base path from which to load all classes
     * defined under the specified namespace.
     *
     * **IMPORTANT**: namespaces are case sensitive so make sure they're typed correctly!
     *
     * @author Yorick peterse
     * @since  0.3
     * @access public
     * @param  string $namespace The namespace to register.
     * @param  string $path The path from which to load all classes defined under the namespace.
     * @return void
     */
    public static function register($namespace, $path)
    {
        if ( isset(self::$namespaces[$namespace]) )
        {
            return;
        }

        if ( !is_dir($path) )
        {
            throw new AutoloaderException("Failed to register $namespace to $path as the path does not exist.");
        }

        self::$namespaces[$namespace] = $path;
    }

    /**
     * Tries to load the given class based on it's namespace. If the namespace of the
     * class hasn't been registered it will be ignored. This allows you to use multiple
     * autoloaders without causing any problems.
     *
     * @author Yorick Peterse
     * @since  0.3
     * @access public
     * @static
     * @param  string $class The full namespace/class name of the class to load.
     * @return void
     * @throws Koi\Exception\Autoloader Thrown whenever the class couldn't be loaded.
     */
    public static function load($class)
    {
        $namespace = ltrim($class, '\\');
        $basename  = explode('\\', $namespace);

        if ( isset($basename[0]) )
        {
            $basename = $basename[0];
        }
        else
        {
            $basename = NULL;
        }
        
        // This prevents any namespace collisions when using multiple autoloaders
        if ( !in_array($basename, array_keys(self::$namespaces)) )
        {
            return;
        }
        
        // Turn Koi\Application\Base into koi/application/base.php
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        // Convert the PascalCased names to Snake_Case
        $path = preg_replace('/([a-z])([A-Z])/', '$1_$2', $path);
        $path = strtolower($path);
        $path = self::$namespaces[$basename] . '/' . $path . '.php';

        if ( file_exists($path) )
        {
            require_once $path;
        }
        else
        {
            throw new AutoloaderException("The class $class could not be loaded from $path");
        }
    }
}
