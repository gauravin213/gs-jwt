<?php

/*
Plugin Name: GS JWT auth and OTP varification
Description: This is GS JWT auth and OTP varification plugin
Author: Gaurav Sharma
Version: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: gs-jwt-rest-api
*/


//pre: gs_wp_jwt, GS_WP_JWT
require_once 'includes/vendor/autoload.php';

use Firebase\JWT\JWT;

add_action( 'init', 'gs_wp_jwt_endpoints_init');
function gs_wp_jwt_endpoints_init(){
  add_action('rest_api_init', 'gs_wp_jwt_endpoints');
  add_filter('rest_api_init', 'gs_wp_jwt_add_cors_support');
  add_filter('rest_pre_serve_request', 'gs_wp_jwt_rest_pre_serve_request', 0, 4 );
  add_filter('rest_pre_dispatch', 'gs_wp_jwt_rest_pre_dispatch', 10, 2 );
}

function gs_wp_jwt_rest_pre_dispatch( $request ) { 

	global $wp_json_basic_auth_error;
	
	if (is_wp_error($wp_json_basic_auth_error)) {
	    return $wp_json_basic_auth_error;
	}
	return $request;

}


/*
* Add CORs suppot to the request.
*/
function gs_wp_jwt_add_cors_support() {
  $enable_cors = true;
  if ($enable_cors) {
    $headers = 'Access-Control-Allow-Headers, X-Requested-With, Content-Type, Accept, Origin, Authorization';
    header( sprintf( 'Access-Control-Allow-Headers: %s', $headers ) );
  }
}

/*
* Add CORs suppot to the request.
*/
function gs_wp_jwt_rest_pre_serve_request() {
  header( 'Access-Control-Allow-Origin: *');
  header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
  header( 'Access-Control-Allow-Credentials: true' );
  header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With' );
}


function gs_wp_jwt_endpoints($request) {

  /*
  * Login endpoint
  */
  register_rest_route('gs-jwt/v1', 'login', array(
    'methods' => 'POST',
    'callback' => 'gs_wp_jwt_endpoint_handler',
    'permission_callback' => function($request){    
      return true;
    }
  ));

  /*
  * Login endpoint
  */
  register_rest_route('gs-jwt/v1', 'token/validate', array(
    'methods' => 'POST',
    'callback' => 'gs_wp_jwt_validate_jwt_token',
    'permission_callback' => function($request){    
      return true;
    }
  ));

  /*
  * OTP Login endpoint
  */
  register_rest_route('gs-jwt/v1', 'get-otp', array(
    'methods' => 'POST',
    'callback' => 'gs_wp_jwt_otp_endpoint_handler',
    'permission_callback' => function($request){    
      return true;
    }
  ));

  /*
  * OTP verification endpoint
  */
  register_rest_route('gs-jwt/v1', 'verify-otp', array(
    'methods' => 'POST',
    'callback' => 'gs_wp_jwt_otp_verify_endpoint_handler',
    'permission_callback' => function($request){    
      return true;
    }
  ));

  /*
  * Register endpoint
  */
  register_rest_route('gs-jwt/v1', 'register', array(
    'methods' => 'POST',
    'callback' => 'gs_wp_jwt_register_endpoint_handler',
    'permission_callback' => function($request){    
      return true;
    }
  ));

  /*
  * Tes endpoint
  */
  register_rest_route('gs-jwt/v1', 'login_test', array(
    'methods' => 'GET',
    'callback' => 'gs_wp_jwt_test_endpoint_handler',
    'permission_callback' => function($request){    
      //return true;
      return is_user_logged_in();
    }
  ));

}

/*
* Register user 
*/
function gs_wp_jwt_register_endpoint_handler($request = null){

  $response = array();

  $username = sanitize_text_field($request['username']);
  $password = sanitize_text_field($request['password']);
  $email = sanitize_text_field($request['email']);
  $mobile = sanitize_text_field($request['mobile']);

  //$error = new WP_Error();
  //$error->add(406, __("Username already exist.", 'wp-rest-user'), array('status' => 406));
  //return $error;

  //Validate
  if ($username == ''){
    $response['message'] = 'Please enter username';
    $response['code'] = 200;
    $response['data'] = array('status' => 200);
    return new WP_REST_Response($response);
  }

  if ($password == ''){
    $response['message'] = 'Please enter password';
    $response['code'] = 200;
    $response['data'] = array('status' => 200);
    return new WP_REST_Response($response);
  }

  if ($email == ''){
    $response['message'] = 'Please enter email';
    $response['code'] = 200;
    $response['data'] = array('status' => 200);
    return new WP_REST_Response($response);
  }

  if ($mobile == ''){
    $response['message'] = 'Please enter mobile';
    $response['code'] = 200;
    $response['data'] = array('status' => 200);
    return new WP_REST_Response($response);
  }

  //If already exist
  if ( username_exists( $username ) ){
    $response['message'] = 'Username already exist';
    $response['code'] = 406;
    $response['data'] = array('status' => 406);
    return new WP_REST_Response($response);
  }

  if ( email_exists( $email) ){
    $response['message'] = 'Email address already exist';
    $response['code'] = 406;
    $response['data'] = array('status' => 406);
    return new WP_REST_Response($response);
  }

  global $wpdb;
  $meta_key = 'billing_phone';
  $meta_value = $mobile;
  $q = "
    SELECT user_id, meta_value as mobile FROM {$wpdb->prefix}usermeta 
    WHERE meta_key = '{$meta_key}' AND meta_value = '{$meta_value}'
  ";
  $if_exist_mobile = $wpdb->get_results($q);
  if (count($if_exist_mobile)!=0) {
    $response['message'] = 'Mobile number already exist';
    $response['code'] = 406;
    $response['data'] = array('status' => 406);
    return new WP_REST_Response($response);
  }

  //create new user
  if ( !username_exists( $username ) && !email_exists( $email ) ) {

    $user_id = wp_create_user( $username, $password, $email );
    $user = new WP_User( $user_id );
    $user->set_role( 'customer' );
    update_user_meta($user_id, 'billing_phone', $mobile);

    $user = get_userdata($user_id);
    unset($user->user_pass);
    $billing_phone = get_user_meta($user_id, 'billing_phone', true);
    $user_data = array(
      'id' => $user_id,
      'user_login' => $user->data->user_login,
      'user_pass' => $user->data->user_pass,
      'user_nicename' => $user->data->user_nicename,
      'user_email' => $user->data->user_email,
      'user_url' => $user->data->user_url,
      'user_registered' => $user->data->user_registered,
      'user_activation_key' => $user->data->user_activation_key,
      'user_status' => $user->data->user_status,
      'display_name' => $user->data->display_name,
      'roles' => $user->roles,
      'billing_phone' => $billing_phone
    );
    $response['data'] = $user_data;
    $response['code'] = 200;
    $response['message'] = 'Registration was Successful';

  }

  return new WP_REST_Response($response);

}

/*
* Test endpoint
*/
function gs_wp_jwt_test_endpoint_handler(){
  $response = array(123);
  return new WP_REST_Response($response);
}


/*
* Encode jwt user data
*/
function gs_wp_jwt_encode_token($user_id){

	$user = get_userdata($user_id);
	unset($user->user_pass);
	$user_data = array(
		'id' => $user_id,
		'user_login' => $user->data->user_login,
		'user_pass' => $user->data->user_pass,
		'user_nicename' => $user->data->user_nicename,
		'user_email' => $user->data->user_email,
		'user_url' => $user->data->user_url,
		'user_registered' => $user->data->user_registered,
		'user_activation_key' => $user->data->user_activation_key,
		'user_status' => $user->data->user_status,
		'display_name' => $user->data->display_name,
		'roles' => $user->roles
	);

	$secret_key = GS_WP_JWT_SECRET_KEY;
	$issued_at  = time();
	$not_before = $issued_at;
	$expire     = $issued_at + GS_WP_JWT_EXPIRY; //time + seconds * minuts 
	$payload = array(
	  'iss'  => get_bloginfo( 'url' ),
	  'iat'  => $issued_at,
	  'nbf'  => $not_before,
	  'exp'  => $expire,
	  'data' => array(
      'user' => array(
        'id' => $user->data->ID
      ),
	  ),
	);
	// Let the user modify the token data before the sign.
	$token = JWT::encode( $payload, $secret_key, GS_WP_JWT_ALGO );

	$user_data['token'] = $token;

	return $user_data;
}

/*
* Authenticate by username and password
*/
function gs_wp_jwt_endpoint_handler($request = null){

  $response = array();

  $username = sanitize_text_field($request['username']);
  $password = sanitize_text_field($request['password']);

  $user = wp_authenticate( $username, $password );
  
  if ( is_wp_error( $user ) ) {
    $response = array('data'=> $user);
  }else{
  	$user_id = $user->data->ID;
	 //JWT
	   $user_data = gs_wp_jwt_encode_token($user_id);
    //JWT
    $response = array('data'=> $user_data);
  }

  return new WP_REST_Response($response);

}


/*
* Get OTP for login by mobile number
*/
function gs_wp_jwt_otp_endpoint_handler($request = null){
  global $wpdb;
  $response = array();
  $top_data = array();
  $mobile = sanitize_text_field($request['mobile']);

  $meta_key = 'billing_phone';
  $meta_value = $mobile;

  $q = "
  SELECT user_id, meta_value as mobile FROM {$wpdb->prefix}usermeta WHERE meta_key = '{$meta_key}' AND meta_value = '{$meta_value}'
  ";

  $if_exist_mobile = $wpdb->get_results($q);
  if (count($if_exist_mobile)!=0) {

    $user_id = $if_exist_mobile[0]->user_id;
    $mobile = $if_exist_mobile[0]->mobile;

    $_gs_jwt_fs_otp_use_staus = get_user_meta($user_id, '_gs_jwt_fs_otp_use_staus', true);

    $OTP = rand(111111,999999);
    $top_data['otp'] = $OTP;
    $top_data['message'] = 'SUCCESS';
    $top_data['otp_use_staus'] = $_gs_jwt_fs_otp_use_staus;

    update_user_meta($user_id, '_gs_jwt_fs_otp', $OTP);
    update_user_meta($user_id, '_gs_jwt_fs_otp_use_staus', 0);
    update_user_meta($user_id, '_gs_jwt_fs_otp_exp', date("Y-m-d h:i:s"));

    //otp notification
    $top_data['notification'] = apply_filters('gs_wp_jwt_send_notification', array(
      'mail_send_status' => 0,
      'sms_send_status' => 0
    ), $user_id, $OTP, $mobile );

  }else{
    $top_data['message'] = 'Mobile number not found';
  }

  $response = array('data'=> $top_data);

  return new WP_REST_Response($response);

}


/*
* Send notification 
* Default mail_send_status = 0, sms_send_status = 0
*/
/*
function gs_wp_jwt_send_notification_fun( $data, $user_id, $otp, $mobile ) {

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
  if ($sms_send_status) { 

    $data['sms_send_status'] = 1;

  } //ens sms send

  return $data;
}
add_filter( 'gs_wp_jwt_send_notification', 'gs_wp_jwt_send_notification_fun', 10, 4 );
*/



/*
* verify OTP for login
*/
function gs_wp_jwt_otp_verify_endpoint_handler($request = null){
  global $wpdb;
  $response = array();
  $top_data = array();
  $OTP = sanitize_text_field($request['otp']);
  $mobile = sanitize_text_field($request['mobile']);

  $meta_key = '_gs_jwt_fs_otp';
  $meta_value = $OTP;

  $q = "
  SELECT t1.user_id, t1.meta_value as mobile FROM {$wpdb->prefix}usermeta as t1 INNER JOIN {$wpdb->prefix}usermeta as t2 ON t1.user_id = t2.user_id 
  WHERE 
  t1.meta_key = '_gs_jwt_fs_otp' AND t1.meta_value = '{$OTP}' AND 
  t2.meta_key = 'billing_phone' AND t2.meta_value = '{$mobile}'
  ";

  $if_exist_otp = $wpdb->get_results($q);

  if (count($if_exist_otp)!=0) {

    $user_id = $if_exist_otp[0]->user_id;
    $_gs_jwt_fs_otp = get_user_meta($user_id, '_gs_jwt_fs_otp', true);
    $_gs_jwt_fs_otp_use_staus = get_user_meta($user_id, '_gs_jwt_fs_otp_use_staus', true);
    $_gs_jwt_fs_otp_exp = get_user_meta($user_id, '_gs_jwt_fs_otp_exp', true);

    $minutes_to_add = GS_WP_OTP_EXPIRY; // add minute
    $time = new DateTime($_gs_jwt_fs_otp_exp); //ymd
    $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
    $stamp = $time->format('Y-m-d H:i:s');

    $now = date("Y-m-d h:i:s");

    if ($stamp > $now) {
      //JWT
      $user_data = gs_wp_jwt_encode_token($user_id);
      //JWT
      $user_data['message'] = 'SUCCESS';
      update_user_meta($user_id, '_gs_jwt_fs_otp_use_staus', 1);
    }else{

      $user_data['message'] = 'OTP EXPIRED';
    }

  }else{
    $user_data['message'] = 'OTP NOT FOUND';
  }

  $response = array('data'=> $user_data);

  return new WP_REST_Response($response);

}



/*
* Validate jwt token 
*/
function gs_wp_jwt_validate_jwt_token($output = true){

  /*
  * Looking for the HTTP_AUTHORIZATION header, if not present just
  * return the user.
  */
  $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;

  /* 
  * Double check for different auth header string (server dependent) 
  */
  if (!$auth) {
    $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
  }

  if (!$auth) {
      return new WP_Error(
          'jwt_auth_no_auth_header',
          'Authorization header not found.',
          array(
              'status' => 403,
          )
      );
  }

  /*
   * The HTTP_AUTHORIZATION is present verify the format
   * if the format is wrong return the user.
   */
  list($token) = sscanf($auth, 'Bearer %s');
  if (!$token) {
      return new WP_Error(
          'jwt_auth_bad_auth_header',
          'Authorization header malformed.',
          array(
              'status' => 403,
          )
      );
  }

    
  // Get the Secret Key.
	$secret_key = GS_WP_JWT_SECRET_KEY;
	if (!$secret_key) {
            return new WP_Error(
                'jwt_auth_bad_config',
                'JWT is not configurated properly, please contact the admin',
                array(
                    'status' => 403,
                )
            );
        }

  	try{

	    $token = JWT::decode($token, $secret_key, array('HS256'));

	    /** The Token is decoded now validate the iss */
	    if ($token->iss != get_bloginfo('url')) {
	        /** The iss do not match, return error */
	        return new WP_Error(
	            'jwt_auth_bad_iss',
	            'The iss do not match with this server',
	            array(
	                'status' => 403,
	            )
	        );
	    }
	    /** So far so good, validate the user id in the token */
	    if (!isset($token->data->user->id)) {
	        /** No user id in the token, abort!! */
	        return new WP_Error(
	            'jwt_auth_bad_request',
	            'User ID not found in the token',
	            array(
	                'status' => 403,
	            )
	        );
	    }
	    /** Everything looks good return the decoded token if the $output is false */
	    if (!$output) {
	        return $token;
	    }
	    /** If the output is true return an answer to the request to show it */
	    return array(
	        'code' => 'jwt_auth_valid_token',
	        'data' => array(
	            'status' => 200,
	        ),
	    );

    }catch(\Firebase\JWT\ExpiredException $e){
		/** Something is wrong trying to decode the token, send back the error */
        return new WP_Error(
            'jwt_auth_invalid_token',
            $e->getMessage(),
            array(
                'status' => 403,
            )
        );
    }
}



/*
* middleware check http request authorization
*/
function gs_wp_jwt_middleware_determine_current_user( $user ) {

  global $wp_json_basic_auth_error;

  $wp_json_basic_auth_error = null;

   /**
   * This hook only should run on the REST API requests to determine
   * if the user in the Token (if any) is valid, for any other
   * normal call ex. wp-admin/.* return the user.
   *
   * @since 1.2.3
   **/
  $rest_api_slug = rest_get_url_prefix();  
  $valid_api_uri = strpos($_SERVER['REQUEST_URI'], $rest_api_slug);
  if (!$valid_api_uri) {
      return $user;
  }

  /*
   * if the request URI is for validate the token don't do anything,
   * this avoid double calls to the validate_token function.
   */
  $validate_uri = strpos($_SERVER['REQUEST_URI'], 'gs-jwt/v1/login');
  if ($validate_uri > 0) {
      return $user;
  }

  $token = gs_wp_jwt_validate_jwt_token(false);

  if (is_wp_error($token)) {

      if($token->get_error_code() == 'jwt_auth_bad_auth_header'){
          return $user;
      }
      
      if ($token->get_error_code() != 'jwt_auth_no_auth_header') {
          /** If there is a error, store it to show it after see rest_pre_dispatch */
          $wp_json_basic_auth_error = $token;
          return $user;
      } else {
          return $user;
      }
  }
  /** Everything is ok, return the user ID stored in the token*/
  return $token->data->user->id;

}
add_filter( 'determine_current_user', 'gs_wp_jwt_middleware_determine_current_user', 20 );