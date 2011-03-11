<?php

namespace GoogleAPI\Helper\WebmasterTools;

/**
 * Helper class for working with the crawl issues feed.
 * 
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class Crawler
{
	/**
	 * Parses the given XML array and only returns all data that's actually useful.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  array $xml_array The XML array from which to extract all crawl issue related data.
	 * @return array
	 */
	public static function parse($xml_array)
	{
		$children = $xml_array['feed']['children'];
		$response = array(
			'etag'           => $xml_array['feed']['attributes']['etag'],
			'id'             => $children['id']['value'],
			'updated'        => $children['updated']['value'],
			'total_results'  => $children['open_search:total_results']['value'],
			'start_index'    => $children['open_search:start_index']['value'],
			'items_per_page' => $children['open_search:items_per_page']['value'],
			'issues'         => array()
		);

		$child_node_keys = array(
			'id'            => 'id',
			'updated'       => 'updated',
			'title'         => 'title',
			'crawl_type'    => 'wt:crawl_type',
			'issue_type'    => 'wt:issue_type',
			'url'           => 'wt:url',
			'date_detected' => 'wt:date_detected',
			'message'       => 'wt:detail'
		);

		// Loop through all the issues and clean them up
		foreach ( $children['entry'] as $entry )
		{
			$row = array();

			foreach ( $child_node_keys as $new_key => $old_key )
			{
				if ( isset($entry['children'][$old_key]) )
				{
					$row[$new_key] = $entry['children'][$old_key]['value'];
				}
			}

			$response['issues'][] = $row;
		}

		return $response;
	}
}
