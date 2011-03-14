<?php

namespace OAuth\Core;

use \OAuth\Exception\Consumer as ConsumerException;

/**
 * Consumer class that uses OAuthSimple to sign all OAuth requests and cURL to send the request
 * to a certain URL. This class can also be used to configure both OAuthSimple and cURL.
 *
 * ## Usage
 *
 * Basic usage of this class is as following:
 *
 *     $oauth    = new OAuth\Core\Consumer( consumer key, consumer secret );
 *     $response = $oauth->request( url, options );
 *
 * By default cURL uses a very minimal configuration, if you want to modify these settings
 * (e.g. disabling SSL verification) you can do this as following:
 *
 *     $oauth->curl_config('verify_ssl', FALSE);
 *
 * The method curl_config() accepts both traditional cURL configuration names such as 
 * CURLOPT_SSL_VERIFYPEER but also has a few shortcuts such as "verify_ssl" and 
 * "return_response". For more information see the documentation of the curl_config() method.
 *
 * @author  Yorick Peterse, Isset Internet Professionals
 * @link    http://yorickpeterse.com/ Website of Yorick Peterse
 * @link    http://isset.nl/ Website of Isset Internet Professionals
 * @license https://github.com/isset/oauth-consumer/blob/master/license.txt The MIT license
 */
class Consumer
{
	/**
	 * Array containing the default configuration for each cURL request.
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    array
	 */
	private $default_curl_config = array(
		'verify_ssl'    => FALSE,
		'return_output' => TRUE
	);

	/**
	 * Array containing the cURL configuration set in the construct, used for each cURL
	 * request.
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    array
	 */
	private $curl_config = array();

	/**
	 * Variable containing a new instance of OAuthSimple.
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    OAuthSimple
	 */
	private $oauth = NULL;

	/**
	 * Array containing global configuration items used by OAuthSimple for each request.
	 *
	 * @author Yorick Peterse
	 * @access public
	 * @var    array
	 */
	public $oauth_config = array();

	/**
	 * Array containing all cURL configuration aliases.
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    array
	 */
	private $curl_config_aliases = array(
		'verify_ssl'    => CURLOPT_SSL_VERIFYPEER,
		'return_output' => CURLOPT_RETURNTRANSFER,
		'headers'       => CURLOPT_HTTPHEADER,
		'url'           => CURLOPT_URL,
		'http_get'      => CURLOPT_HTTPGET,
		'http_post'     => CURLOPT_POST,
		'http_put'      => CURLOPT_PUT
	);

	/**
	 * Variable containing an instance of cURL.
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    resource
	 */
	private $curl = NULL;

	/**
	 * Creates a new instance of both the Consumer and OAuthSimple class and configures
	 * both classes based on the specified arguments.
	 *
	 * @example
	 *  $oauth = new OAuth\Core\Consumer('test.isset.nl', 'ads87as7132jhasd');
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @param  string $consumer_key The consumer key used for all OAuth requests.
	 * @param  string $consumer_secret The consumer secret for all OAuth requests.
	 * @param  array  $curl_config The default configuration items to use for all cURL
	 * requests.
	 * @return void
	 */
	public function __construct($consumer_key, $consumer_secret, $curl_config = array())
	{
		$this->oauth        = new OAuthSimple();
		$this->oauth_config = array(
			'consumer_key'  => $consumer_key,
			'shared_secret' => $consumer_secret
		);
		$this->curl_config  = $curl_config;

		$this->reset();
	}

	/**
	 * Builds and signs an OAuth request using OAuthSimple and returns the array containing
	 * all details about the request. This method takes the same arguments as the request()
	 * method but does not directly execute a cURL request.
	 *
	 * @author Yorick Peterse
	 * @access public
	 * @since  0.1
	 * @see    Consumer::request()
	 * @return array
	 */
	public function sign($url, $method = 'GET', $options = array(), $curl_options = array())
	{
		$options               = array('parameters' => $options);
		$options['path']       = $url;
		$options['signatures'] = $this->oauth_config;
		$options['action']     = strtolower($method);

		foreach ( $curl_options as $opt => $value )
		{
			$this->curl_config($opt, $value);
		}

		// Sign the request
		$result = $this->oauth->sign($options);

		// Set the correct cURL config based on the request method
		switch ( strtolower($method) )
		{
			case 'get':
				$this->curl_config('http_get', TRUE);
				break;
			case 'post':
				$this->curl_config('http_post', TRUE);
				break;
			default:
				$this->curl_config(CURLOPT_CUSTOMREQUEST, $method);
				break;
		}

		return $result;
	}

	/**
	 * Sends an OAuth request to the specified URL. Optional items can be specified as
	 * an associative array in the second argument of this method.
	 *
	 * @example
	 *  $oauth    = new OAuth\Core\Consumer('test.isset.nl', 'ads87as7132jhasd');
	 *  $response = $oauth->request('https://www.google.com/webmasters/tools/feeds/sites/');
	 *
	 * @author Yorick Peterse
	 * @access public
	 * @since  0.1
	 * @param  string $url The URL to send the OAuth request to.
	 * @param  array $options Associative array containing all options that will be sent
	 * to OAuthSimple.
	 * @param  string $method The HTTP request method to execute (GET, POST, etc).
	 * @param  array $curl_options Associative array containing request specific options
	 * for cURL. Global options should be set using curl_config().
	 * @return string
	 */
	public function request($url, $method = 'GET', $options = array(), $curl_options = array())
	{
		$result = $this->sign($url, $method, $options, $curl_options);
		
		$this->curl_config('url', $result['signed_url']);

		return curl_exec($this->curl);
	}

	/**
	 * Helper method that resets the data in the OAuthSimple instance and reconfigures
	 * cURL to prevent any configuration collisions.
	 *
	 * @author Yorick Peterse
	 * @access public
	 * @since  0.1
	 * @return void
	 */
	public function reset()
	{
		$this->oauth->reset();

		$this->curl        = curl_init();
		$this->curl_config = array_merge($this->default_curl_config, $this->curl_config);

		// Set the default cURL configuration items
		foreach ( $this->curl_config as $opt => $value )
		{
			$this->curl_config($opt, $value);
		}
	}

	/**
	 * Method that can be used to set global cURL options used for each request.
	 * The first argument is a string or a cURL constant (e.g. CURLOPT_URL) and the second
	 * argument the value for this option. When setting a key you can use the following
	 * aliases:
	 *
	 * * verify_ssl: alias for CURLOPT_SSL_VERIFYPEER
	 * * return_output: alias for CURLOPT_RETURNTRANSFER
	 * * headers: alias for CURLOPT_HTTPHEADER
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $key The cURL configuration key or a shortcut.
	 * @param  mixed $value The value for the configuration item.
	 * @return void
	 */
	public function curl_config($key, $value)
	{
		// Replace the shortcut
		if ( in_array($key, array_keys($this->curl_config_aliases)) )
		{
			$key = $this->curl_config_aliases[$key];
		}

		curl_setopt($this->curl, $key, $value);
	}
}
