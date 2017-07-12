<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

$config =
	array(
		// set on "base_url" the relative url that point to HybridAuth Endpoint
		'base_url' => '/hauth/endpoint',

		"providers" => array (
			// openid providers
			"OpenID" => array (
				"enabled" => true
			),

			"Yahoo" => array (
				"enabled" => true,
				"keys"    => array ( "id" => "", "secret" => "" ),
			),

			"AOL"  => array (
				"enabled" => true
			),

			"Google" => array (
				"enabled" => true,
				"keys"    => array ( "id" => "648143166921-nosal101urca201ioog5l2ao8ubij7d9.apps.googleusercontent.com", "secret" => "tRmA4Yt_4__W9TE4kkXKcSgf" ),
			),

			"Facebook" => array (
				"enabled" => true,
				"keys"    => array ( "id" => "144720779417208", "secret" => "b796beb1c10b560fae33111716a52a41" ),
				"scope"   => "email, user_about_me, user_birthday, user_hometown", // optional
          		"display" => "popup" // optional
			),

			"Twitter" => array (
				"enabled" => true,
				"keys"    => array ( "key" => "wKmX7yVra7HvUlrdFlbLr0cfS", "secret" => "SCJkpkgy15gEljxTprYuuEldyDpTutXYcN8GAUtUt7RuwL2hH4" ),
				"includeEmail" => true
			),


			// windows live
			"Live" => array (
				"enabled" => true,
				"keys"    => array ( "id" => "", "secret" => "" )
			),

			"MySpace" => array (
				"enabled" => true,
				"keys"    => array ( "key" => "", "secret" => "" )
			),

			"LinkedIn" => array (
				"enabled" => true,
				"keys"    => array ( "key" => "", "secret" => "" )
			),

			"Foursquare" => array (
				"enabled" => true,
				"keys"    => array ( "id" => "", "secret" => "" )
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode" => (ENVIRONMENT == 'development'),

		"debug_file" => APPPATH.'/logs/hybridauth.log',
	);


/* End of file hybridauthlib.php */
/* Location: ./application/config/hybridauthlib.php */