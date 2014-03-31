<?php

class AMG_Media_Gallery extends CED_Post_Type {
	public $name = 'media_gallery';
	function setup_post_type() {
		$labels = array(
			'name'                => _x( 'Media Galleries', 'Post Type General Name', 'advanced_media_galley' ),
			'singular_name'       => _x( 'Media Gallery', 'Post Type Singular Name', 'advanced_media_galley' ),
			'menu_name'           => __( 'Media Galleries', 'advanced_media_galley' ),
			'parent_item_colon'   => __( 'Parent Media Gallery:', 'advanced_media_galley' ),
			'all_items'           => __( 'All Media Galleries', 'advanced_media_galley' ),
			'view_item'           => __( 'View Media Gallery', 'advanced_media_galley' ),
			'add_new_item'        => __( 'Add New Media Gallery', 'advanced_media_galley' ),
			'add_new'             => __( 'New Media Gallery', 'advanced_media_galley' ),
			'edit_item'           => __( 'Edit Media Gallery', 'advanced_media_galley' ),
			'update_item'         => __( 'Update Media Gallery', 'advanced_media_galley' ),
			'search_items'        => __( 'Search media galleries', 'advanced_media_galley' ),
			'not_found'           => __( 'No media galleries found', 'advanced_media_galley' ),
			'not_found_in_trash'  => __( 'No media galleries found in Trash', 'advanced_media_galley' ),
		);
		$rewrite = array(
			'slug'                => 'media-gallery',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'media_gallery', 'advanced_media_gallery' ),
			'description'         => __( 'Gallery of media items', 'advanced_media_gallery' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'comments', 'trackbacks', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 10,
			'menu_icon'           => 'dashicons-admin-media',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => $rewrite,
			'capability_type'     => 'post',
		);
		register_post_type( 'media_gallery', $args );	
	}
	
	function setup_taxonomies() {
		$labels = array(
			'name'                       => _x( 'Gallery Types', 'Taxonomy General Name', 'advanced_media_galley' ),
			'singular_name'              => _x( 'Gallery Type', 'Taxonomy Singular Name', 'advanced_media_galley' ),
			'menu_name'                  => __( 'Gallery Type', 'advanced_media_galley' ),
			'all_items'                  => __( 'All Gallery Types', 'advanced_media_galley' ),
			'parent_item'                => __( 'Parent Gallery Type', 'advanced_media_galley' ),
			'parent_item_colon'          => __( 'Parent Gallery Type:', 'advanced_media_galley' ),
			'new_item_name'              => __( 'New Gallery Type Name', 'advanced_media_galley' ),
			'add_new_item'               => __( 'Add New Gallery Type', 'advanced_media_galley' ),
			'edit_item'                  => __( 'Edit Gallery Type', 'advanced_media_galley' ),
			'update_item'                => __( 'Update Gallery Type', 'advanced_media_galley' ),
			'separate_items_with_commas' => __( 'Separate gallery type with commas', 'advanced_media_galley' ),
			'search_items'               => __( 'Search Gallery Types', 'advanced_media_galley' ),
			'add_or_remove_items'        => __( 'Add or remove gallery types', 'advanced_media_galley' ),
			'choose_from_most_used'      => __( 'Choose from the most used gallery typess', 'advanced_media_galley' ),
			'not_found'                  => __( 'Not Found', 'advanced_media_galley' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
		);
		register_taxonomy( 'gallery_type', 'media_gallery', $args );
	}
	
	function populate_taxonomy() {
		wp_insert_term( 'Image Gallery', 'gallery_type', array( 'slug' => 'image') );	
		wp_insert_term( 'Audio Playlist', 'gallery_type', array( 'slug' => 'audio') );	
		wp_insert_term( 'Video Playlist', 'gallery_type', array( 'slug' => 'video') );		
	}
	
	function setup_rewrite_api() {
		add_rewrite_tag('%in_gallery%','([^/]+)');	
	}
}

function media_gallery_content( $content ) {
	$gallery = get_post();
	if( $gallery->post_type !== 'media_gallery' )
		return $content;
	$types = wp_get_object_terms( $gallery->ID, 'gallery_type' );		
	$type = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;
	$media =  new WP_Query( array( 'in_gallery' => $gallery->ID, 'nopaging' => true ) );
	$ids = array();
	while( $media->have_posts() ) {
		$m = $media->next_post();
		$ids[] = $m->ID;	
	}
	$args = array( 'ids' => implode( ',', $ids ) );
	switch( $type ) {
		case 'image':
			return gallery_shortcode( $args );
		case 'video':
			return wp_get_playlist( $args, 'video' );
		break;
		
		case 'audio':
			return wp_get_playlist( $args, 'audio' );
		break;	
	}
}

add_filter( 'the_content', 'media_gallery_content');

add_action( 'after_setup_theme', 'theme_setup' );
function theme_setup() {
	$img_size_name = 'amg-thumbnail';
	add_image_size( 'amg-thumbnail', 200, 200, true);
	update_option($img_size_name.'_size_w', 200);
	update_option($img_size_name.'_size_h', 200);
	update_option($img_size_name.'_crop', 1);
}

function custom_wmu_image_sizes($sizes) {
       return array_merge( $sizes, array( 'amg-thumbnail' => 'Media Gallery Thumbnail' ) );
}
add_filter('image_size_names_choose', 'custom_wmu_image_sizes');

new AMG_Media_Gallery();