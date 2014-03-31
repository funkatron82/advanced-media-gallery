<?php

class AMG_Media extends CED_Post_Type {
	public $name = 'attachment';
	
	function parse_query( $query ) {
		if( $gallery_id = $query->get( 'in_gallery' ) ) {
			$gallery_id = is_numeric( $gallery_id ) ? (int) $gallery_id :  $this->slug_to_id( $gallery_id, 'media_gallery' );
			$types = wp_get_object_terms( $gallery_id, 'gallery_type' );		
			$type = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;
			$query->set( 'post_mime_type', $type );
			$query->set( 'connected_items',  $gallery_id );
			$query->set( 'connected_type', 'gallery_media' );
			$query->set( 'post_status',  'inherit' );
			$query->set( 'post_type',  'attachment' );
			
			//Order if no order set
			if( !$query->get( 'orderby' ) ) {
				$query->set( 'orderby', 'gallery_order' );
			}
			
			if( $query->get( 'orderby' ) == 'gallery_order' ){
				$query->set( 'connected_orderby', 'order' );
				$query->set( 'connected_order', 'asc' );
				$query->set( 'connected_order_num', true );
			}
		}	
	}	
}

new AMG_Media();