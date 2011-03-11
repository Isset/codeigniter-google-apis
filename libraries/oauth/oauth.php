<?php

/**
 * Base script for the OAuth library that registers a custom namespace
 * and autoloaders all classes.
 *
 * @author  Yorick Peterse, Isset Internet Professionals
 * @link    http://yorickpeterse.com/ Website of Yorick Peterse
 * @link    http://isset.nl/ Website of Isset Internet Professionals
 * @license https://github.com/isset/oauth/blob/master/license.txt The MIT license
 */

// Load our base classes and exceptions
require_once(__DIR__ . '/oauth/exception/autoloader.php');
require_once(__DIR__ . '/oauth/exception/oauth_simple_exception.php');
require_once(__DIR__ . '/oauth/core/autoloader.php');

// Register our autoloader
spl_autoload_register(array('\OAuth\Core\Autoloader', 'load'));

// Register our namespace
\OAuth\Core\Autoloader::register('OAuth', __DIR__);
