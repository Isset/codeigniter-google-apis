# README

This repository contains a set of libraries that can be used to communicate with Google's
OAuth based API. While the libraries are meant to be used in Codeigniter projects it shouldn't
be too hard to port them to other frameworks.

## Requirements

* PHP >= 5.3
* Codeigniter >= 1.7.2
* An OAuth token and a secret, these can be retrieved by registering your application
* Query string support
* Sessions enabled

PHP 5.3 is required since the library uses namespaces for internal classes such as the
helper classes used for parsing certain XML. In theory it shouldn't be too hard to backport
this to PHP 5.2 using the PEAR convention but don't expect us to do it ;)

## Installation

Drop config/google_api.php into APPPATH/config and move all files in libraries/ to
APPPATH/libraries/google_api. Don't forget to update the configuration file!

If your version of Codeigniter does not have support for query strings (1.7 doesn't for example)
you'll either need to install [this][dhorrigan query string] library, otherwise you'll have
to download Codeigniter Reactor.

## General Usage

In order to use any of the sub libraries you'll first need to load the main library
and authenticate and authorize the user. This can be done as following:

    $this->load->library('google_api/google_api', array(
        'consumer_key'    => 'foobar.domain.tld',
        'consumer_secret' => '......' 
    ));

The next step is to authenticate the user and authorize any future OAuth requests by
retrieving an access token:

    $this->google_api->authorize_user( SCOPE, CALLBACK );

SCOPE in this case is a URL that points to a Google feed. It's important to choose the correct
scope as a certain token will only work for a single scope. CALLBACK is the URL the user
will be redirected to once he/she has finished the authorization process. This callback
is very important as it will be used by Google to send the required tokens for retrieving
an access token back to your application. In your callback you'll need to retrieve these
tokens and call the method get_access_token() as following:

    $this->google_api->get_access_token(TOKEN, VERIFIER);

This method will send a request to Google and return the access token and secret. Once 
you have these tokens you should save them somewhere so the user doesn't have to re-authorize
the application over and over again. At work we're storing these tokens in our users table
in a field called "mem_oauth_tokens". This field contains a serialized array with the
following format:

    array(
        'scope' => array(
             'oauth_token'        => '',
             'oauth_token_secret' => ''
         )
    )

The tokens are stored per scope so that multiple tokens can be used for different scopes
and services without having to create extra database fields.

## Available Libraries

* Google Webmaster Tools

More will most likely be added soon (Analytics will most likely be the first).

## Error Handling

All libraries use *exceptions* when throwing errors, **always** wrap your method calls
in [try/catch][php exceptions] blocks instead of checking to see if the return value matches TRUE or FALSE
(which is quite common in Codeigniter libraries/projects). When an exception is thrown
it's status code is set to the HTTP status code returned by Google. Each exception instance
also has an attribute "response" that will contain the body returned by Google in case of
an error.

## Library Usage

### Google Webmaster Tools

The webmaster tools library offers methods for adding sitemaps, updating websites,
retrieving keywords and so on. This library offers the following methods:

* set_tokens
* add_website
* delete_website
* update_website
* verify_website
* get_websites
* add_sitemap
* delete_sitemap
* get_sitemaps
* get_keywords
* get_crawl_issues

Before we can use any of these methods we'll need to load the library and set our tokens 
to use for each request. This can be done as following:

    $this->load->library('google_api/webmaster_tools');
    $this->webmaster_tools->set_tokens( TOKEN, SECRET );

TOKEN and SECRET should be the tokens retrieved by the get_access_token() method, without
these tokens all requests will be denied by Google. Once these tokens have been set we
can start adding websites, updating them or managng sitemaps. If we want to add a new
website to the user's account all we'd have to do is the following:

    $response = $this->webmaster_tools->add_website( URL );

URL should be a full URL that points to the website to add. Upon success this method will
return an array containing all details about the new website. If we wanted to retrieve the
verification status of this website all we have to do is the following:

    $response['website']['verified']; // => TRUE or FALSE

Removing a website works the same way but instead of calling add_website() you'll have to
call delete_website(). Note that instead of an array this method will return TRUE upon success.

Retrieving all sitemaps for a given website works as following:

    $sitemaps = $this->webmaster_tools->get_sitemaps( WEBSITE );

For more information on all individual methods see the source documentation in 
the file libraries/Webmaster\_tools.php, they're pretty well documented so don't worry.

## License

All code in this project is licensed under the MIT license, a copy of this license can
be found in the file "license.txt".

## Support

If you happen to encounter a bug or would like to request a feature you can use the
bugtracker provided by GitHub or send an Email to github@isset.nl.

[dhorrigan query string]: https://github.com/dhorrigan/codeigniter-query-string
[php exceptions]: http://php.net/manual/en/language.exceptions.php
