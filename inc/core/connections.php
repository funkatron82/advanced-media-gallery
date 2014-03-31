<?php
//Connections class
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

class AMG_Connections {
	function __construct(){
		add_action( 'wp_loaded', array($this, 'register_connections'), 100);
		add_filter( 'the_posts', array($this, 'process_posts'), 10, 2 );
	}
	
	function process_posts( $posts, $query ) {
		remove_filter( 'the_posts', array( $this, 'process_posts' ) );
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'gallery_media' ) && $query->get( post_type ) == 'media_gallery' ) { 
			$media = new WP_Query( array(
			  'connected_type' => 'gallery_media',
			  'connected_items' => $query->posts,
			  'nopaging' => true
			) );

			if( empty( $media ) )
				return 	$query->posts;		
			
			$indexed_list = array();
		
			foreach ( $query->posts as $item ) {
				if( $item->post_type == 'media_gallery' ){
					$item->media = array();
					$indexed_list[ $item->ID ] = $item;
				}
			}
		
			$groups = scb_list_group_by( $media->posts, '_p2p_get_other_id' );
		
			foreach ( $groups as $outer_item_id => $connected_items ) {
				$types = wp_get_object_terms( $outer_item_id, 'gallery_type' );	
				$type = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;
				$media_items = array();
				foreach( $connected_items as $connected_item ){
					if( substr_count( $connected_item->post_mime_type, $type ) > 0 ) {
						$media_items[] = $connected_item;	
					}
				}
				if( ! empty( $indexed_list[ $outer_item_id ] ) ) {
					$indexed_list[ $outer_item_id ]->media = $media_items;
				}
			}
		}
		add_filter( 'the_posts', array( $this, 'process_posts' ), 10, 2 );
		return $query->posts; 			
	}
	
	function register_connections(){
		if ( !function_exists( 'p2p_register_connection_type' ) )
			return;

		p2p_register_connection_type( array(
			'name' => 'gallery_media',
			'from' => 'media_gallery',
			'to' => 'attachment',
			'cardinality' => 'many-to-many',
			'prevent_duplicates' => true,
			'admin_box' => true							
		) );			
	}

}

new AMG_Connections();