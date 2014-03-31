<?php

class AMG_Media_Admin extends CED_Post_Type_Admin {
	public $name = 'attachment';
	
	function setup() {
		parent::setup();
		add_filter( 'manage_media_columns' , array( $this, 'add_columns' ) );
		add_action( 'manage_media_custom_column',  array( $this, 'manage_columns'), 10, 2 );	
	}
	
	function restrict_posts() {			
		global $wp_query;
		if(	!in_array($this->name, (array) $wp_query->get('post_type') ) ) return;
		$gallery = ( $gallery = $wp_query->get( 'in_gallery' )  ) ? $gallery : 0;
			
		$this->generate_post_select( 'in_gallery', 'media_gallery', $gallery);
	}
	
	function add_columns( $columns ) {
		return  array_slice( $columns, 0, 5, true ) + array( 'galleries' => 'Media Galleries' ) + array_slice( $columns, 3, NULL, true );
	}
	
	function manage_columns( $column_name, $id ) {
		global $post;
		if( $column_name != 'galleries' )
			return;
			
		$out = array();
		$galleries = new WP_Query( array( 
			'connected_type' => 'gallery_media',
			'connected_items' => $id,
			'nopaging' => true
		 ) );
		while( $galleries->have_posts() ) {
			$gallery = $galleries->next_post();
			$out[]= sprintf( 
				'<strong><a href="%s" target="_blank">%s</a></strong>',
				 get_edit_post_link( $gallery->ID ),
				 get_the_title( $gallery->ID ) 
			);
		}
		
		echo empty( $out ) ? '—' : implode( ', ', $out );		
	}
}

new AMG_Media_Admin();