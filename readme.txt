=== GS JWT Authentication for WP REST API ===
Contributors: gauravin213
Tags: wp-json, jwt, json web authentication, wp-api, otp
Requires at least: 4.2
Tested up to: 5.8.3
Requires PHP: 5.3.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Extends the WP REST API using JSON Web Tokens as an authentication method.
GS JWT plugin provides to encode and decode JSON Web Tokens (JWT), conforming to RFC 7519.

GET OTP and send notification by mail or SMS service 

**Support and Requests please in Github:** https://github.com/gauravin213/gs-jwt

### REQUIREMENTS

### PHP

**Minimum PHP version: 5.3.0**

### PHP HTTP Authorization Header enable

Most of the shared hosting has disabled the **HTTP Authorization Header** by default.

To enable this option you'll need to edit your **.htaccess** file adding the following

	RewriteEngine on
	RewriteCond %{HTTP:Authorization} ^(.*)
	RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]

#### WPENGINE

To enable this option you'll need to edit your **.htaccess** file adding the following


	SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1


#### CONFIGURATION
The JWT needs to Add constant in the wp-confige.php file

	define( 'GS_WP_JWT_SECRET_KEY', 'your-top-secret-key' );
	define( 'GS_WP_JWT_ALGO', 'HS256' );
	define( 'GS_WP_JWT_EXPIRY', (60 * 60) ); //seconds * minuts
	define( 'GS_WP_OTP_EXPIRY', 10);  //minuts



### Namespace and Endpoints

When the plugin is activated, a new namespace is added

`
/gs-jwt/v1
`

Also, two new endpoints are added to this namespace

Endpoint | HTTP Verb

*/wp-json/gs-jwt/v1/login* | POST

*/wp-json/gs-jwt/v1/get-otp* | POST

*/wp-json/gs-jwt/v1/verify-otp* | POST


### USAGE

1. Get JSON web token

#### Request method:
	POST /wp-json/gs-jwt/v1/login

	Body{
		"username": "enter username",
		"password": "enter password"
	}
#### Reponse
	{
	    "data": {
	        "id": "1",
	        "user_login": "admin",
	        "user_pass": null,
	        "user_nicename": "admin",
	        "user_email": "example@gmail.com",
	        "user_url": "",
	        "user_registered": "2020-08-11 07:35:37",
	        "user_activation_key": "",
	        "user_status": "0",
	        "display_name": "admin",
	        "roles": [
	            "administrator"
	        ],
	        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjFcL3dvcmRwcmVzcyIsImlhdCI6MTY0MTYyMzM2NiwibmJmIjoxNjQxNjIzMzY2LCJleHAiOjE2NDE2MjY5NjYsImRhdGEiOnsidXNlciI6eyJpZCI6IjEiLCJ1c2VyX2xvZ2luIjoiYWRtaW4iLCJ1c2VyX3Bhc3MiOm51bGwsInVzZXJfbmljZW5hbWUiOiJhZG1pbiIsInVzZXJfZW1haWwiOiJnYXVyYXZpbjIxM0BnbWFpbC5jb20iLCJ1c2VyX3VybCI6IiIsInVzZXJfcmVnaXN0ZXJlZCI6IjIwMjAtMDgtMTEgMDc6MzU6MzciLCJ1c2VyX2FjdGl2YXRpb25fa2V5IjoiIiwidXNlcl9zdGF0dXMiOiIwIiwiZGlzcGxheV9uYW1lIjoiYWRtaW4iLCJyb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19fX0.V-IsDSaURDSxkOMYV0HOSSuIjfQVqQfvQBT5JSy9iCQ"
	    }
	}

2. Get otp by billing mobile number
#### Request method:
	POST /wp-json/gs-jwt/v1/get-otp

	Body{
		"mobile": "enter mobile number"
	}
#### Reponse
	{
	    "data": {
	        "otp": 249225,
	        "message": "SUCCESS",
	        "otp_use_staus": "0",
	        "notification": {
	            "mail_send_status": 0,
	            "sms_send_status": 0
	        }
	    }
	}
	

3. Verify otp and mobile number to login 
#### Request method:
	POST /wp-json/gs-jwt/v1/verify-otp

	Body{
		"otp": "enter otp",
		"mobile": "enter mobile number"
	}
#### Reponse
	{
	    "data": {
	        "id": "1",
	        "user_login": "admin",
	        "user_pass": null,
	        "user_nicename": "admin",
	        "user_email": "example@gmail.com",
	        "user_url": "",
	        "user_registered": "2020-08-11 07:35:37",
	        "user_activation_key": "",
	        "user_status": "0",
	        "display_name": "admin",
	        "roles": [
	            "administrator"
	        ],
	        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjFcL3dvcmRwcmVzcyIsImlhdCI6MTY0MTYyMzM2NiwibmJmIjoxNjQxNjIzMzY2LCJleHAiOjE2NDE2MjY5NjYsImRhdGEiOnsidXNlciI6eyJpZCI6IjEiLCJ1c2VyX2xvZ2luIjoiYWRtaW4iLCJ1c2VyX3Bhc3MiOm51bGwsInVzZXJfbmljZW5hbWUiOiJhZG1pbiIsInVzZXJfZW1haWwiOiJnYXVyYXZpbjIxM0BnbWFpbC5jb20iLCJ1c2VyX3VybCI6IiIsInVzZXJfcmVnaXN0ZXJlZCI6IjIwMjAtMDgtMTEgMDc6MzU6MzciLCJ1c2VyX2FjdGl2YXRpb25fa2V5IjoiIiwidXNlcl9zdGF0dXMiOiIwIiwiZGlzcGxheV9uYW1lIjoiYWRtaW4iLCJyb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19fX0.V-IsDSaURDSxkOMYV0HOSSuIjfQVqQfvQBT5JSy9iCQ"
	    }
	}
	

#### Sample add SMS and email notification
	
	/*
	* Send notification 
	* Default mail_send_status = 0, sms_send_status = 0
	*/
	function gs_wp_jwt_send_notification_fun( $data, $user_id, $OTP, $mobile ) {

	  //Write mail send code here
	  $from = get_option('admin_email');
	  $to = get_user_meta($user_id, 'billing_email', true);
	  $subject = "OTP Verification";
	  $message = "OTP number: {$opt} will expire in 10 min";
	  $headers = "From: ".$from;
	  $result = wp_mail( $to, $subject, $message, $headers);

	  if ($result) { 

	    $data['mail_send_status'] = 1;

	  } //end mail send


	  //Write sms send api code here
	  /*if ($sms_send_status) { 

	    $data['sms_send_status'] = 1;

	  } //ens sms send*/

	  return $data;
	}
	add_filter( 'gs_wp_jwt_send_notification', 'gs_wp_jwt_send_notification_fun', 10, 4 );