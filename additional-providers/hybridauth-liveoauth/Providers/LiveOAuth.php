<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Windows Live OAuth2 Class
 * 
 * @package             HybridAuth additional providers package 
 * @author              Lukasz Koprowski <azram19@gmail.com>
 * @version             0.1
 * @license             BSD License
 */ 

/**
 * Hybrid_Providers_LiveOAuth - Windows Live provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_LiveOAuth extends Hybrid_Provider_Model_OAuth2
{
	// default permissions 
	public $scope = "wl.basic wl.emails wl.signin wl.share wl.birthday";

	
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://apis.live.net/v5.0/";
		$this->api->authorize_url  = "https://oauth.live.com/authorize";
		$this->api->token_url = 'https://oauth.live.com/token';

		$this->api->curl_authenticate_method  = "GET";
	}

	/**
	* grab the user profile from the api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( "me" ); 

		if ( ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		}

		$data = $response;

		$this->user->profile->identifier    = (property_exists($data,'profile_guid'))?$data->id:"";
		$this->user->profile->firstName     = (property_exists($data,'first_name'))?$data->first_name:"";
		$this->user->profile->lastName      = (property_exists($data,'last_name'))?$data->last_name:"";
		$this->user->profile->displayName   = (property_exists($data,'name'))?trim( $data->name ):"";
		$this->user->profile->gender        = (property_exists($data,'gender'))?$data->gender:"";

		//wl.basic
		$this->user->profile->profileURL    = (property_exists($data,'link'))?$data->link:"";

		//wl.emails
		$this->user->profile->email         = (property_exists($data,'emails'))?$data->emails->preferred:"";
		$this->user->profile->emailVerified = (property_exists($data,'emails'))?$data->emails->account:"";

		//wl.birthday
		$this->user->profile->birthDay 		= (property_exists($data,'birth_day'))?$data->emails->account:"";
		$this->user->profile->birthMonth 	= (property_exists($data,'birth_month'))?$data->emails->account:"";
		$this->user->profile->birthYear 	= (property_exists($data,'birth_year'))?$data->emails->account:"";

		return $this->user->profile;
	}


	/**
	* load the current logged in user contacts list from the IDp api client  
	*/

	/* Windows Live api does not support retrieval of email addresses (only hashes :/) */
	function getUserContacts() 
	{
		$response = $this->api->get( 'me/contacts' );

		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User contacts request failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if ( !$response->data && ( $response->error != 0 ) )
		{
			return array();
		}
		
		$contacts = array();

		foreach( $response->data as $item ) {
			$uc = new Hybrid_User_Contact();
			$uc->identifier   = (property_exists($item,'id'))?$item->id:"";
			$uc->displayName  = (property_exists($item,'name'))?$item->name:"";

			$contacts[] = $uc;
		}
		
		return $contacts;
	}
}
