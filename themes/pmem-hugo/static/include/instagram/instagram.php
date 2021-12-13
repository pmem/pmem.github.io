<?php
session_start();

$accesstoken = ""; // Your Access Token

$interval = 3600;

$cache_file = dirname( __FILE__ ) . '/cache/instagram';

if ( file_exists( $cache_file ) ) {
	$last = filemtime($cache_file);
} else { $last = false; }

$now = time();

if ( !$last || (( $now - $last ) > $interval ) ) {

	$instagram_api = curl_init();
	curl_setopt( $instagram_api, CURLOPT_URL, "https://graph.instagram.com/me/media?fields=id&access_token=" . $accesstoken );
	curl_setopt( $instagram_api, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $instagram_api, CURLOPT_RETURNTRANSFER, true );
	$instagram_response = curl_exec( $instagram_api );

	$images = json_decode( $instagram_response, true );
	$images = $images['data'];

	$image_list = array();

	if( is_array( $images ) && ! empty( $images ) ) {
		foreach( $images as $image ) {

			$insta_image = curl_init();
			curl_setopt( $insta_image, CURLOPT_URL, "https://graph.instagram.com/" . $image['id'] . "?fields=media_url,permalink,media_type,thumbnail_url&access_token=" . $accesstoken );
			curl_setopt( $insta_image, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $insta_image, CURLOPT_RETURNTRANSFER, true );
			$insta_image_resp = curl_exec( $insta_image );

			$image_list[] = json_decode( $insta_image_resp, true );

		}
	}

	$cached_shots = serialize( $image_list );

	if ( !empty( $cached_shots ) ) {
		$cache_static = fopen( $cache_file, 'wb' );
		fwrite( $cache_static, $cached_shots );
		fclose( $cache_static );
	}

	$shots = @unserialize( file_get_contents( $cache_file ) );
} else {
	$shots = @unserialize( file_get_contents( $cache_file ) );
}

echo json_encode( $shots );

?>