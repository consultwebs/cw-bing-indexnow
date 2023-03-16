<?php
/**
 * Plugin Name: CW Bing IndexNow
 * Plugin URI: https://github.com/consultwebs/cw-bing-indexnow
 * Version: 1.0.0
 * Author: Consultwebs.com, Inc.
 * Description: Submits the permalink of pages and posts to Bing's IndexNow service.
 */

// Set a user-defined constant called BING_API_KEY
define( 'BING_API_KEY', 'your_bing_api_key' );

// Respond to the exact REQUEST URL path of "/<BING_API_KEY>.txt"
function cw_bing_indexnow_template_redirect() {
  $request_path = $_SERVER['REQUEST_URI'];
  $bing_api_key = BING_API_KEY;
  if ( preg_match( "/^\/$bing_api_key\.txt$/", $request_path ) ) {
    die( $bing_api_key );
  }
}
add_action( 'template_redirect', 'cw_bing_indexnow_template_redirect' );

// Send a CURL request when pages or posts are created or updated
function cw_bing_indexnow_send_request( $post_id, $post, $update ) {
  // Ignore any post revisions
  if ( wp_is_post_revision( $post_id ) ) {
    return;
  }
  
  // Ignore any custom post types
  if ( ! in_array( $post->post_type, array( 'post', 'page' ) ) ) {
    return;
  }
  
  // Ignore any pages or posts without a slug defined
  if ( empty( $post->post_name ) ) {
    return;
  }
  
  // Store the page's permalink in a variable called $permalink_url
  $permalink_url = get_permalink( $post_id );
  
  // Store just the site's domain name into a variable called $site_domain
  $site_domain = parse_url( home_url(), PHP_URL_HOST );
  
  // Send a CURL request with the following criteria
  $curl = curl_init();
  curl_setopt_array( $curl, array(
    CURLOPT_URL => 'https://www.bing.com/IndexNow',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode( array(
      'host' => $site_domain,
      'key' => BING_API_KEY,
      'keyLocation' => "https://$site_domain/" . BING_API_KEY . ".txt",
      'urlList' => array( $permalink_url )
    ) ),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json; charset=utf-8'
    ),
  ) );
  $response = curl_exec( $curl );
  curl_close( $curl );
}
add_action( 'save_post', 'cw_bing_indexnow_send_request', 10, 3 );
