<?php

namespace GoogleAPI\Helper;

/**
 * Helper class that can be used to create the required cURL headers for each call
 * to Google's API as well as parsing an HTTP response and returning the data as an 
 * associative array.
 *
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class HTTP
{
	/**
	 * Parses a given HTTP response and turns it into an array.
	 * The format of this array looks like the following:
	 *
	 *     array(
	 *         'headers' => array(
	 *             'Content-Type' => 'application/atom+xml',
	 *             'status'       => array(
	 *                 'code'    => 200,
	 *                 'message' => 'OK'
	 *             )
	 *         ),
	 *         'body' => '...'
	 *     )
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  string $response The raw HTTP resonse to turn into an array.
	 * @return array
	 */
	public static function parse_response($response)
	{
		// [0] contains the headers, [1] contains the body
		$response = explode("\r\n\r\n", $response);
		$result   = array(
			'headers' => array(),
			'body'    => ''
		);

		// Parse the headers
		if ( isset($response[0]) )
		{
			foreach ( explode("\r\n", $response[0]) as $header )
			{
				// Regular headers such as Content-Type
				if ( stristr($header, ':') !== FALSE )
				{
					$header = explode(':', $header);
					$result['headers'][$header[0]] = $header[1];
				}
				// The HTTP status code doesn't include a colon
				else
				{
					$header                      = explode(' ', $header, 3);
					$result['headers']['status'] = array(
						'code'    => (int)$header[1], 
						'message' => $header[2]
					);
				}
			}
		}

		// Parse the body, piece of cake
		if ( isset($response[1]) )
		{
			$result['body'] = $response[1];
		}

		return $result;
	}

	/**
	 * When using custom headers in a cURL request they should be supplied as a regular
	 * array where each value is the actual header. This method can convert an associative
	 * array into a regular array making the use of custom headers a bit easier. For example,
	 * the following array:
	 *
	 *     array(
	 *         'Content-Type' => 'application/atom+xml'
	 *     )
	 *
	 * would be converted into the following:
	 *
	 *     array(
	 *         'Content-Type: application/atom+xml'
	 *     )
	 * 
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  array $headers Associative array containing all headers to set.
	 * @return array
	 */
	public static function convert_headers($headers)
	{
		$headers_array = array();

		foreach ( $headers as $header => $value )
		{
			$headers_array[] = "$header: $value";
		}

		return $headers_array;
	}

	/**
	 * Sets the content type header, the Google API version and optionally the content
	 * length (useful for POST and PUT requests).
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @param  int $content_length The length of the POST/PUT body.
	 * @return array
	 */
	public static function default_headers($content_length = NULL)
	{
		$headers = array(
			'Content-Type'  => 'application/atom+xml',
			'GData-Version' => '2.0'
		);

		if ( isset($content_length) AND !empty($content_length) )
		{
			$headers['Content-Length'] = $content_length;
		}

		return self::convert_headers($headers);
	}
}
