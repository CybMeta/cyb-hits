<?php
//No direct access
if( !defined('ABSPATH') ) {
    //We are in a direct call
	//Defining this custom ajax file with SHORTINIT will load less functions from Wordpres core reducing overhead.
	//Wee need only to update_post_meta
	//See http://wordpress.stackexchange.com/questions/41808/ajax-takes-10x-as-long-as-it-should-could/41812#41812

	define('SHORTINIT', true);

	require( '../../../wp-load.php' );
	require( ABSPATH . WPINC . '/formatting.php' );
	require( ABSPATH . WPINC . '/meta.php' );
	require( ABSPATH . WPINC . '/post.php' );
	require( ABSPATH . WPINC . '/revision.php' );

	cyb_uptdate_hits();
	
}

function cyb_uptdate_hits(){

  	if( isset($_GET['postID']) ) {
  	
		$post_id = intval( $_GET['postID']);
		
		if( $post_id > 0 ) {
		
			$get_meta = get_post_custom($post_id);
			
			if( isset($get_meta['hits'][0]) ) {
			
				$prev = intval($get_meta['hits'][0]);
			} else {
				$prev = 0;
			}
			
			update_post_meta($post_id, 'hits', $prev + 1);
			$res = array('postID' => $post_id, 'hits' => $prev + 1);
			wp_send_json_success($res);
			
		} else {
			wp_send_json_error('No post to update.');
		}
		
	} else {
		wp_send_json_error('No post to update.');
	}
	
	die('You die!');
	
}

?>