<?php if ( !defined('BASEPATH') ) exit('No direct script access');

require_once(__DIR__ . '/oauth/lib/oauth.php');
require_once(__DIR__ . '/autoloader.php');
require_once(__DIR__ . '/exception/autoloader_exception.php');

spl_autoload_register(array('\GoogleAPI\Autoloader', 'load'));

// Register the autoloader namespace
GoogleAPI\Autoloader::register('GoogleAPI', __DIR__ . '/../');

use GoogleAPI\Exception\GoogleAPIException;

/**
 * Base class that can be used as either a standalone class or as a parent class for
 * API specific classes such as Webmaster_tools. This class provides the required methods
 * for retrieving various authorization tokens.
 *
 * ## Usage
 *
 * First you'll have to load the library as following:
 *
 *     $this->load->library('google_api', array(
 *         'consumer_key'    => 'KEY',
 *         'consumer_secret' => 'SECRET'
 *     ));
 *
 * Once the library is loaded you can request a request and authorization token using the
 * authorize_user() method. This method will automatically redirect the user back to the
 * specified callback to reduce code:
 *
 *     $this->google_api->authorize_user(SCOPE, CALLBACK);
 *
 * In this case SCOPE is a URL to a certain Google API, the returned tokens will only work
 * for this given URL. CALLBACK is the URL the user will be redirected to once he/she
 * authorized your application to access his/her data. In order to complete the authorization
 * process you'll have to call the method "get_access_token" in this callback. On top of that
 * you'll have to make sure your Codeigniter application has query strings enabled as
 * Google will set the tokens as query string parameters. The get_access_token method 
 * requires two variables which are both set in the query string:
 *
 * * oauth_verifier: a special verification token
 * * oauth_token: a token used to retrieve a long-access token from Google
 *
 * This tokens should be set as following:
 *
 *     $tokens = $this->google_api->get_access_token(TOKEN, VERIFIER);
 *
 * The return value of get_access_token is an array containing the OAuth token and secret
 * that should be used for all following API calls to Google. It doesn't really matter
 * what you do with these tokens but it's best to store them in a database so the user
 * doesn't have to re-authorize himself all the time.
 *
 * @author  Yorick Peterse, Isset Internet Professionals
 * @link    http://yorickpeterse.com/ Website of Yorick Peterse
 * @link    http://isset.nl/ Website of Isset Internet Professionals
 * @license https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version 0.1
 */
class Google_api
{
	/**
	 * Reference to the Codeigniter instance as returned by get_instance().
	 *
	 * @author Yorick Peterse
	 * @access private
	 * @var    object
	 * @since  0.1
	 */
	private $CI = NULL;

	/**
	 * Instance of the OAuth consumer wrapper.
	 *
	 * @author Yorick Peterse
	 * @access public
	 * @var    OAuth\Core\Consumer
	 * @since  0.1
	 */
	public $oauth = NULL;

	/**
	 * Array containing various URLs used by Google during the authentication and 
	 * authorization process.
	 *
	 * @author Yorick Peterse
	 * @access protected
	 * @var    array
	 * @since  0.1
	 */
	protected $google_urls = array(
		'request_token'   => 'https://www.google.com/accounts/OAuthGetRequestToken',
		'authorize_token' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
		'access_token'    => 'https://www.google.com/accounts/OAuthGetAccessToken'
	);

	/**
	 * Constructor method, called whenever a new instance of the class is created.
	 *
	 * @example
	 *  $this->load->library('google_api/google_api', array(
	 *      'consumer_key'    => '...',
	 *      'consumer_secret' => '...'
	 *  ));
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @param  array $options Array containing the consumer key and consumer secret.
	 * @return void
	 */
	public function __construct($options)
	{
		if ( !isset($options['consumer_key']) OR !isset($options['consumer_secret']) )
		{
			throw new GoogleAPIException("The following keys are required: consumer_key and consumer_secret.");
		}

		$this->CI    =& get_instance();
		$this->oauth = new OAuth\Core\Consumer($options['consumer_key'], $options['consumer_secret']);

 		$this->CI->load->helper('url');
	}

	/**
	 * Based on the specified scope and callback this method will try to get an authorization
	 * token from Google. This method will automatically redirect the user to the correct
	 * Google page which in turn will redirect the user back to the specified callback URL.
	 *
	 * This method will store the following data in a user's session:
	 *
	 * * oauth_token_secret: The token secret returned by Google, required in order to 
	 * retrieve the access token.
	 *
	 * @example
	 *  $this->google_api->authorize_user(SCOPE, CALLBACK);
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @param  string $scope The scope of the OAuth token (this should be a URL).
	 * @param  string $callback The URL to redirect the user to once he/she has finished
	 * the authorization process.
	 * @return void
	 */
	public function authorize_user($scope, $callback)
	{
		$response = $this->oauth->request(
			$this->google_urls['request_token'],
			'GET',
			array(
				'scope'          => $scope,
				'oauth_callback' => $callback
			)
		);

		// Turn the string into a $_GET like array
		parse_str($response, $response);

		$token  = $response['oauth_token'];
		$secret = $response['oauth_token_secret'];

		// Sign a new request and redirect the user so we can retrieve our authorization token
		$this->CI->session->set_userdata('oauth_token_secret', $secret);

		$response = $this->oauth->sign(
			$this->google_urls['authorize_token'],
			'GET',
			array(
				'oauth_token' => $token
			)
		);

		// STOP! REDIRECT TIME!
		redirect($response['signed_url']);
	}

	/**
	 * Given an OAuth token and verifier token as returned by Google this method will try to
	 * retrieve an access token that can be stored in a database and used for OAuth
	 * requests to the Google API.
	 *
	 * @example
	 *  $this->google_api->get_access_token(GOOGLE TOKEN, GOOGLE VERIFIER);
	 *
	 * @author Yorick Peterse
	 * @param string $oauth_token An OAuth token that was returned by the Google API.
	 * @param string $oauth_verifier A verifier token required in order to get an access
	 * token from the Google API.
	 * @return array
	 */
	public function get_access_token($oauth_token, $oauth_verifier)
	{
		$oauth_secret = $this->CI->session->userdata('oauth_token_secret');

		$this->oauth->oauth_config['oauth_token']  = $oauth_token;
		$this->oauth->oauth_config['oauth_secret'] = $oauth_secret;

		// Reset the request just to be 100% idiot proof
		$this->oauth->reset();

		$response = $this->oauth->request(
			$this->google_urls['access_token'],
			'GET',
			array(
				'oauth_verifier' => $oauth_verifier,
				'oauth_token'    => $oauth_token
			)
		);

		parse_str($response, $response);

		return $response;
	}

	/**
	 * Method used for loading XML views from the libraries directory.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @access public
	 * @param  string $view The view to load.
	 * @param  array $data Array containing all data for the view.
	 * @param  bool $return When set to TRUE the view will be returned instead of sent
	 * to the browser.
	 * @return string/void
	 */
	public function load_view($view, $data = array(), $return = FALSE)
	{
		$view = __DIR__ . "/xml/$view.php";

		$this->CI->load->vars($data);
		
		$view = $this->CI->load->file($view, TRUE);

		if ( $return === TRUE )
		{
			return $view;
		}
		else
		{
			echo $view;
		}
	}
}
