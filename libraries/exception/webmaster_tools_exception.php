<?php

namespace GoogleAPI\Exception;

/**
 * Exception class used by the Google Webmaster tools library.
 *
 * @author  Yorick Peterse, Isset Internet Professionals
 * @link    http://yorickpeterse.com/ Website of Yorick Peterse
 * @link    http://isset.nl/ Website of Isset Internet Professionals
 * @license https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version 0.1
 */
class WebmasterToolsException extends \Exception 
{
	/**
	 * The response returned by Google.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @var    string
	 */
	public $response = NULL;

	/**
	 * Creates a new instance of the WebmasterToolsException class.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $message The exception's message.
	 * @param  array $response The response returned by Google.
	 * @return void
	 */
	public function __construct($message, $response = NULL)
	{
		$code = $response['headers']['status']['code'];

		parent::__construct($message, $code);

		// Store the response returned by Google
		$this->response = $response['body'];
	}
}
