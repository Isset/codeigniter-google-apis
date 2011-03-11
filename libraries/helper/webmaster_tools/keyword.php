<?php

namespace GoogleAPI\Helper\WebmasterTools;

/**
 * Helper class for the Webmaster tools keywords feed.
 * 
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class Keyword
{
	/**
	 * Parses the given XML array and only returns useful elements for each keyword.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  array $xml_array The XML array from which to extract all keyword related data.
	 * @return array
	 */
	public static function parse($xml_array)
	{
		$response = array(
			'etag'     => $xml_array['feed']['attributes']['etag'],
			'id'       => $xml_array['feed']['children']['id']['value'],
			'updated'  => $xml_array['feed']['children']['updated']['value'],
			'keywords' => array()
		);

		// Extract the data we actually need
		foreach ( $xml_array['feed']['children']['wt:keyword'] as $keyword )
		{
			$response['keywords'][] = array(
				'value'  => $keyword['value'],
				'source' => $keyword['attributes']['source']
			);
		}

		return $response;
	}
}
