<?php

namespace GoogleAPI\Helper\WebmasterTools;


/**
 * Helper class for working with Google feeds that show details about either a single
 * or multiple websites.
 * 
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class Website
{
	/**
	 * Parses the given XML array and only returns useful elements for each website.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  string $xml_root The name of the XML root node from which to select all data.
	 * @param  array $xml_array The XML array from which to extract all website related data.
	 * @return array
	 */
	public static function parse($xml_root, $xml_array)
	{
		// Clean the XML response to make it a bit easier to use
		$response  = array(
			'etag'    => $xml_array[$xml_root]['attributes']['etag'],
			'id'      => $xml_array[$xml_root]['children']['id']['value'],
			'updated' => $xml_array[$xml_root]['children']['updated']['value'],
			'url'     => $xml_array[$xml_root]['children']['title']['value'],
			'websites'=> array()
		);

		// Array containing the names of all child nodes to select and their new names
		$child_node_keys = array(
			'id'                    => 'id',
			'updated'               => 'updated',
			'url'                   => 'title',
			'verified'              => 'wt:verified',
			'verification_method'   => 'wt:verification_method',
			'crawl_rate'            => 'wt:crawl_rate',
			'geolocation'           => 'wt:geolocation',
			'enhanced_image_search' => 'wt:enhanced_image_search',
			'preferred_domain'      => 'wt:preferred_domain'
		);

		// When processing a single website the XML response looks a bit different so we'll
		// need to take care of that.
		if ( $xml_root === 'entry' )
		{
			$nodes = array($xml_array['entry']);	
		}
		else
		{
			$nodes = $xml_array['feed']['children']['entry'];
		}

		if ( !isset($nodes[0]) )
		{
			$nodes = array($nodes);
		}
		
		// Clean up all website entries
		foreach ( $nodes as $website )
		{
			$row         = array();
			$row['etag'] = $website['attributes']['etag'];

			foreach ( $child_node_keys as $new_key => $old_key )
			{
				if ( isset($website['children'][$old_key]) )
				{
					if ( isset($website['children'][$old_key]['value']) )
					{
						$row[$new_key] = $website['children'][$old_key]['value'];
					}
					else
					{
						$row[$new_key] = $website['children'][$old_key];
					}
				}
			}

			$response['websites'][] = $row;
		}

		unset($response['entry']);

		// Slightly change the array when only selecting a single website
		if ( $xml_root === 'entry' )
		{
			$response['website'] = $response['websites'][0];

			unset($response['websites']);
		}

		return $response;
	}
}
