<?php

namespace GoogleAPI\Helper\WebmasterTools;

/**
 * Helper class for Google sitemaps and parsing them.
 * 
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class Sitemap
{
	/**
	 * Parses the given XML array and only returns useful elements for each sitemap
	 * such as the Etag and the ID of each sitemap.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  string $xml_root The name of the XML root node from which to select all
	 * sub data.
	 * @param  array $xml_array The XML array from which to extract all sitemap related data.
	 * @return array
	 */
	public static function parse($xml_root, $xml_array)
	{
		$response = array(
			'etag'     => $xml_array[$xml_root]['attributes']['etag'],
			'id'       => $xml_array[$xml_root]['children']['id']['value'],
			'updated'  => $xml_array[$xml_root]['children']['updated']['value'],
			'sitemaps' => array()
		);

		// Array of all child nodes to select and their new key names
		$children_keys = array(
			'id'              => 'id', 
			'updated'         => 'updated', 
			'title'           => 'title', 
			'type'            => 'wt:sitemap_type', 
			'status'          => 'wt:sitemap_status', 
			'last_downloaded' => 'wt:sitemap_last_downloaded', 
			'url_count'       => 'wt:sitemap_url_count',
			'news_publication_label' => 'wt:sitemap-news-publication-label',
			'mobile_markup_language' => 'wt:sitemap-mobile-markup-language'
		);

		if ( isset( $xml_array[$xml_root]['children']['wt:sitemap_mobile']) )
		{
			$response['sitemap_mobile'] = $xml_array[$xml_root]['children']['wt:sitemap_mobile']['value'];
		}

		if ( isset($xml_array[$xml_root]['children']['wt:sitemap_news']) )
		{
			$response['sitemap_news'] = $xml_array[$xml_root]['children']['wt:sitemap_news']['value'];
		}

		// Based on a single or multiple sitemaps different keys have to be used
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

		// Loop through all sitemaps and fetch the data we really need
		foreach ( $nodes as $sitemap )
		{
			$row = array(
				'etag' => $sitemap['attributes']['etag']
			);

			// Select all keys if they exist
			foreach ( $children_keys as $new_key => $old_key )
			{
				if ( isset($sitemap['children'][$old_key]) )
				{
					$row[$new_key] = $sitemap['children'][$old_key]['value'];
				}
			}

			$response['sitemaps'][] = $row;
		}

		// Do we want to return a single sitemap?
		if ( $xml_root === 'entry' )
		{
			$response['sitemap'] = $response['sitemaps'][0];
			unset($response['sitemaps']);
		}

		return $response;
	}
}
