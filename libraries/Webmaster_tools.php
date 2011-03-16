<?php if ( !defined('BASEPATH') ) exit('No direct script access');

use GoogleAPI\Exception\WebmasterToolsException;

use GoogleAPI\Helper\XML;
use GoogleAPI\Helper\HTTP;
use GoogleAPI\Helper\WebmasterTools\Sitemap;
use GoogleAPI\Helper\WebmasterTools\Keyword;
use GoogleAPI\Helper\WebmasterTools\Website;
use GoogleAPI\Helper\WebmasterTools\Crawler;

/**
 * Library for working with the Google webmaster tools feed. This class allows developers
 * to retrieve websites, add sitemaps, view crawl issues and so on. 
 *
 * ## Usage
 *
 * In order to use this library you'll first need to load the main library, for more 
 * information on how to do this see the class Google_api in Google_api.php. Once the 
 * main library is loaded and configured you can start using this library. In order
 * to load this library do the following:
 *
 *     $this->load->library('google_api/webmaster_tools');
 *
 * From this point on you can use it as any other library. For example, if we wanted to
 * add a new website to the user's account we'd do the following:
 *
 *     $this->webmaster_tools->add_website('http://google.com/');
 *
 * The return value would be an array containing the details about the newly added website.
 *
 * ## Error Handling
 *
 * This class uses PHP exceptions to handle most issues. This means that instead of validating
 * the return value of a method you should *always* wrap your calls in a try/catch block.
 * In the case of an error the status code of the exception will be set to the HTTP status
 * code returned by Google.
 *
 * ## Available Features
 *
 * * Adding, retrieving, removing and updating websites
 * * Adding, retrieving and removing sitemaps
 * * Retrieving keywords
 * * Retrieving crawl issues
 *
 * Please note that this library currently does not have any methods for retrieving the
 * messages feed provided by Google Webmaster tools, simply because we don't need it at 
 * this time.
 *
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class Webmaster_tools
{
	/**
	 * Reference to the current Codeigniter instance.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access private
	 * @var    object
	 */
	private $CI = NULL;

	/**
	 * String containing the base URL for all calls to the Google webmaster tools API.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @var    string
	 */
	public $api_url = 'https://www.google.com/webmasters/tools/feeds/';

	/**
	 * Constructor method, called whenever this class is initialized.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Sets the tokens for the OAuthConsumer class.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $oauth_token The OAuth token to use for all following requests.
	 * @param  string $oauth_token_secret The OAuth secret to use for all following requests.
	 * @return void
	 */
	public function set_tokens($oauth_token, $oauth_token_secret)
	{
		$this->CI->google_api->oauth->oauth_config['oauth_token']  = $oauth_token;
		$this->CI->google_api->oauth->oauth_config['oauth_secret'] = $oauth_token_secret;
	}

	/**
	 * Fetches all websites for the current user from Google and returns the result
	 * as an associative array. This methods's return value has the following format:
	 *
	 *     array(
	 *         'etag'     => '',
	 *         'id'       => '',
	 *         'updated'  => '',
	 *         'title'    => '',
	 *         'websites' => arary()
	 *     )
	 *
	 * The arrays in the "websites" key have the following format:
	 *
	 *     array(
	 *         'etag'                  => '',
	 *         'id'                    => '',
	 *         'updated'               => '',
	 *         'website'               => '',
	 *         'verified'              => TRUE/FALSE,
	 *         'verification_method'   => array(),
	 *         'geolocation'           => '',
	 *         'enhanced_image_search' => '',
	 *         'preferred_domain'      => ''
	 *     )
	 *
	 * If you only want to retrieve the list of websites if they've been changed you can do
	 * so by specifying the etag stored under the global "etag" key.
	 *
	 * @example
	 *  $this->webmaster_tools->get_websites( WEBSITE URL, ETAG );
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The URL of the website to retrieve, this value will be URL
	 * encoded automatically.
	 * @param  string $etag The etag of the list of websites, can be used to only retrieve
	 * the list of websites if they've been modified.
	 * @return array
	 * @throws WebmasterToolsException Thrown when the response from Google was different
	 * that 200 OK, the exception code is the matching HTTP response code.
	 */
	public function get_websites($website = NULL, $etag = NULL)
	{
		$this->CI->google_api->oauth->reset();

		$headers = HTTP::default_headers();

		if ( isset($etag) AND !empty($etag) )
		{
			$headers[] = "If-None-Match: $etag";
		}

		$google_url = $this->api_url . 'sites/';

		// Determine the URL for the request
		if ( isset($website) AND !empty($website) )
		{
			$google_url .= urlencode($website);
			$xml_root    = 'entry';
		}
		else
		{
			$xml_root = 'feed';
		}

		// Send the OAuth request
		$response = $this->CI->google_api->oauth->request(
			$google_url,
			'GET',
			NULL,
			array(
				CURLOPT_HEADER  => TRUE,
				'headers'       => $headers
			)
		);

		$response = HTTP::parse_response($response);

		// Are we authorized?
		if ( $response['headers']['status']['code'] !== 200 )
		{
			throw new WebmasterToolsException(
				"The list of websites could not be retrieved.", $response
			);
		}

		// Parse the XML response
		$xml_array = XML::to_array($response['body']);

		return Website::parse($xml_root, $xml_array);
	}

	/**
	 * Adds the given website URL to the current user's Google webmaster tools account.
	 *
	 * @example
	 *  $this->webmaster_tools->add_website('http://google.com/');
	 *
	 * @author Yorick Peterse 
	 * @since  0.1
	 * @access public
	 * @param  string $website_url The URL of the website to add to the user's account.
	 * @return void
	 * @throws WebmasterToolsException Thrown whenever the website couldn't be added to 
	 * the user's account.
	 */
	public function add_website($website_url)
	{
		$this->CI->google_api->oauth->reset();

		// Get our XML data to send to Google
		$post_body = $this->CI->google_api->load_view(
			'add_website', array('website' => $website_url), TRUE
		);

		$post_body = trim($post_body);
		$response  = $this->CI->google_api->oauth->request(
			$this->api_url . 'sites/', 'POST', NULL, array(
				CURLOPT_HEADER     => TRUE,
				'headers'          => HTTP::default_headers(strlen($post_body)),
				CURLOPT_POSTFIELDS => $post_body
			)
		);

		$response = HTTP::parse_response($response);

		// Process the response headers
		if ( $response['headers']['status']['code'] !== 201 )
		{
			throw new WebmasterToolsException(
				"The website $website_url could not be added", $response
			);
		}

		// Get the XML from the body
		$xml_array = XML::to_array($response['body']);

		return Website::parse('entry', $xml_array);
	}

	/**
	 * Removes a website from the user's Webmaster tools account.
	 *
	 * Upon failure this method will throw an exception containing all details about why
	 * the website couldn't be removed. If the request was successful this method will
	 * return TRUE.
	 *
	 * @example
	 *  $this->webmaster_tools->delete_website('http://yorickpeterse.com/');
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The URL of the website to delete.
	 * @return bool
	 * @throws WebmasterToolsException Thrown whenever the website couldn't be removed.
	 */
	public function delete_website($website)
	{
		$this->CI->google_api->oauth->reset();

		$headers  = array('GData-Version: 2.0');
		$website  = $this->api_url . 'sites/' . urlencode($website);

		$response = $this->CI->google_api->oauth->request(
			$website, 'DELETE', NULL, array(
				CURLOPT_HEADER => TRUE,
				'headers'      => $headers,
			)
		);

		$response = HTTP::parse_response($response);

		if ( $response['headers']['status']['code'] === 200 )
		{
			return TRUE;
		}
		else
		{
			throw new WebmasterToolsException(
				"The website $website couldn't be removed.", $response
			);
		}
	}

	/**
	 * Verifies the given website using the specified verification method (meta tags 
	 * or HTML files). In order to verify a website you'll need to specify the website URL
	 * and the verification method to use, the latter can either be "meta" or "html".
	 *
	 * @example
	 *  $this->webmaster_tools->verify_website('http://google.com/', 'metatag');
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The URL of the website to verify.
	 * @param  string $verification_method The verification method to use, can either be
	 * "metatag" or "htmlpage".
	 * @return bool TRUE if the website was validated, FALSE otherwise.
	 * @throws WebmasterToolsException Thrown whenever the verification method was unknown
	 * or the website couldn't be verified (e.g. an incorrect URL was specified).
	 */
	public function verify_website($website, $verification_method)
	{
		$this->CI->google_api->oauth->reset();

		if ( $verification_method !== 'metatag' AND $verification_method !== 'htmlpage' )
		{
			throw new WebmasterToolsException(
				"The verification method $verification_method is unknown."
			);
		}

		// Generate the required XML
		$request_body = $this->CI->google_api->load_view(
			'verify_website', array(
				'website_id'          => $website, 
				'verification_method' => $verification_method
			), 
			TRUE
		);

		$request_body = trim($request_body);
		$website      = $this->api_url . 'sites/' . urlencode($website);
		$response     = $this->CI->google_api->oauth->request(
			$website, 'PUT', NULL, array(
				CURLOPT_HEADER     => TRUE,
				'headers'          => HTTP::default_headers(strlen($request_body)),
				CURLOPT_POSTFIELDS => $request_body
			)
		);

		$response = HTTP::parse_response($response);

		if ( $response['headers']['status']['code'] === 200 )
		{
			// Get the XML from the body
			$xml_array = XML::to_array($response['body']);

			return $xml_array['entry']['children']['wt:verified']['value'];
		}
		else
		{
			throw new WebmasterToolsException(
				"The website $website could not be verified", $response
			);
		}
	}

	/**
	 * Updates an existing website with the given option and it's value.
	 * The option names can either use hyphens (crawl-rate) or underscores (crawl_rate).
	 *
	 * @example
	 *  $this->webmaster_tools->update_website('http://google.nl/', 'geolocation', 'NL');
	 *
	 * @author Yorick Peterse
	 * @param  string $website The website URL for which to update the given setting.
	 * @param  string $option The name of the setting to update.
	 * @param  string $value The new value for the setting.
	 * @return bool
	 * @throws WebmasterToolsException Thrown whenever the option isn't recognized or
	 * the website couldn't be updated.
	 */
	public function update_website($website, $option, $value)
	{
		$this->CI->google_api->oauth->reset();

		$options = array('geolocation', 'crawl-rate', 'preferred-domain');
		$option  = str_replace('_', '-', $option);

		if ( !in_array($option, $options) )
		{
			throw new WebmasterToolsException("The option $option isn't recognized.");
		}

		// Load the XML
		$request_body = $this->CI->google_api->load_view('update_website', array(
				'website_id'   => $website,
				'option_key'   => $option,
				'option_value' => $value
			), 
			TRUE
		);

		$request_body = trim($request_body);
		$website      = $this->api_url . 'sites/' . urlencode($website);
		$response     = $this->CI->google_api->oauth->request(
			$website, 'PUT', NULL, array(
				CURLOPT_HEADER     => TRUE,
				'headers'          => HTTP::default_headers(strlen($request_body)),
				CURLOPT_POSTFIELDS => $request_body
			)
		);

		$response = HTTP::parse_response($response);

		// Process the response headers
		if ( $response['headers']['status']['code'] === 200 )
		{
			// Get the XML from the body
			$xml_array = XML::to_array($response['body']);

			return Website::parse('entry', $xml_array);
		}
		else
		{
			throw new WebmasterToolsException(
				"The settings for the website $website could not be updated.", $response 
			);
		}
	}

	/**
	 * Retrieves a list of all keywords for the given website ID.
	 *
	 * @example
	 *  $this->webmaster_tools->get_keywords('http://yorickpeterse.com/');
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The URL of the website for which to retrieve all keywords.
	 * @return array
	 * @throws WebmasterToolsException Thrown whenever the keywords couldn't be retrieved.
	 */
	public function get_keywords($website)
	{
		$this->CI->google_api->oauth->reset();

		$website   = $this->api_url . urlencode($website) . '/keywords/';
		$response  = $this->CI->google_api->oauth->request(
			$website, 'GET', NULL, array(
				CURLOPT_HEADER => TRUE,
				'headers'      => HTTP::default_headers(),
			)
		);

		$response = HTTP::parse_response($response);

		// Process the response headers
		if ( $response['headers']['status']['code'] === 200 )
		{
			// Get the XML from the body
			$xml_array = XML::to_array($response['body']);

			return Keyword::parse($xml_array);
		}
		else
		{
			throw new WebmasterToolsException(
				"The keywords for the website $website could not be retrieved.", $response 
			);
		}
	}

	/**
	 * Retrieves all sitemaps for the given website. Optionally you can retrieve just a 
	 * single sitemap by setting the second parameter of this method to the URL of the
	 * sitemap. Note that this URL should be an _exact_ match.
	 *
	 * @example
	 *  $this->webmaster_tools->get_sitemaps('http://yorickpeterse.com/');
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The website URL for which to retrieve all sitemaps
	 * @param  string $sitemap A URL of a specific sitemap to retrieve.
	 * @return array
	 * @throws WebmasterToolsException Thrown whenever the request couldn't be executed.
	 */
	public function get_sitemaps($website, $sitemap = NULL)
	{
		$this->CI->google_api->oauth->reset();

		$website     = urlencode($website);
		$request_url = $this->api_url . $website . '/sitemaps/';

		if ( isset($sitemap) AND !empty($sitemap) )
		{
			$request_url .= urlencode($sitemap);
			$xml_root     = 'entry';
		}
		else
		{
			$xml_root = 'feed';
		}

		$response = $this->CI->google_api->oauth->request(
			$request_url, 'GET', NULL, array(
				CURLOPT_HEADER => TRUE,
				'headers'      => HTTP::default_headers()
			)
		);

		$response = HTTP::parse_response($response);

		// Process the response headers
		if ( $response['headers']['status']['code'] === 200 )
		{
			// Get the XML from the body
			$xml_array = XML::to_array($response['body']);
			
			return Sitemap::parse($xml_root, $xml_array);
		}
		else
		{
			throw new WebmasterToolsException(
				"The sitemaps for the website $website could not be retrieved.", $response 
			);
		}
	}

	/**
	 * Adds a new sitemap for the given website. By default the sitemap type is set to
	 * "web" but this can be changed by setting the third parameter to one of the following
	 * values:
	 *
	 * * web
	 * * video
	 * * code
	 *
	 * @example
	 *  $this->webmaster_tools->add_sitemap(WEBSITE, SITEMAP, TYPE);
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The website for which to add the sitemap.
	 * @param  string $sitemap The URL to the sitemap to add.
	 * @param  string $type The type of sitemap to add.
	 * @return array
	 * @throws WebmasterToolsException Thrown whenever the sitemap couldn't be added.
	 */
	public function add_sitemap($website, $sitemap, $type = 'web')
	{
		$this->CI->google_api->oauth->reset();

		$url         = $this->api_url . urlencode($website) . '/sitemaps/';
		$sitemap_xml = $this->CI->google_api->load_view('add_sitemap', array(
				'sitemap'      => $sitemap,
				'sitemap_type' => strtoupper($type)
			), 
			TRUE 
		);

		$sitemap_xml = trim($sitemap_xml);
		$response    = $this->CI->google_api->oauth->request(
			$url, 'POST', NULL, array(
				CURLOPT_HEADER     => TRUE,
				'headers'          => HTTP::default_headers(strlen($sitemap_xml)),
				CURLOPT_POSTFIELDS => $sitemap_xml
			)
		);

		$response = HTTP::parse_response($response);
		$status   = $response['headers']['status']['code'];

		if ( $status !== 201 )
		{
			throw new WebmasterToolsException(
				"The sitemap $sitemap could not be added.", $response
			);
		}

		$xml_array = XML::to_array($response['body']);

		return Sitemap::parse('entry', $xml_array);
	}

	/**
	 * Removes the given sitemap from the specified website.
	 *
	 * @example
	 *  $this->webmaster_tools->delete_sitemap(WEBSITE, SITEMAP);
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The URL of the website for which to remove the given sitemap.
	 * @param  string $sitemap The URL of the sitemap to remove.
	 * @return bool
	 */
	public function delete_sitemap($website, $sitemap)
	{
		$this->CI->google_api->oauth->reset();

		$url = $this->api_url . urlencode($website) . '/sitemaps/' . urlencode($sitemap);

		$response = $this->CI->google_api->oauth->request(
			$url, 'DELETE', NULL, array(
				CURLOPT_HEADER => TRUE,
				'headers'      => HTTP::default_headers()
			)
		);

		$response = HTTP::parse_response($response);
		$status   = $response['headers']['status']['code'];

		if ( $status !== 200 )
		{
			throw new WebmasterToolsException(
				"The sitemap $sitemap could not be removed from the website $website.",
				$response
			);
		}

		return TRUE;
	}

	/**
	 * Retrieves all crawl issues for a given website.
	 *
	 * @example
	 *  $this->webmaster_tools->get_crawl_issues(WEBSITE);
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $website The website for which to retrieve all crawl issues.
	 * @return array
	 */
	public function get_crawl_issues($website)
	{
		$this->CI->google_api->oauth->reset();

		$url      = $this->api_url . urlencode($website) . '/crawlissues/';
		$response = $this->CI->google_api->oauth->request(
			$url, 'GET', NULL, array(
				CURLOPT_HEADER => TRUE,
				'headers'      => HTTP::default_headers()
			)
		);

		$response = HTTP::parse_response($response);
		$status   = $response['headers']['status']['code'];

		if ( $status !== 200 )
		{
			throw new WebmasterToolsException(
				"The crawl issues feed for $website could not be retrieved.", $response
			);
		}

		$response = XML::to_array($response['body']);

		return Crawler::parse($response);
	}
}
