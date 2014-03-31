<?php

class AMG_Media_Gallery_Admin extends CED_Post_Type_Admin {
	public $name = 'media_gallery';
	public $taxonomies = array( 'gallery_type' );
	
	function setup() {
		parent::setup();
		add_action( 'edit_form_after_title', array( $this, 'show_content')  );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );	
		add_action( 'wp_ajax_amg-media', array( $this, 'media_response' ) );
		add_action( 'wp_ajax_amg-type', array( $this, 'change_gallery_type' ) );
		add_action( 'print_media_templates', array( $this, 'print_templates' ) );	
		add_action( 'save_post_media_gallery', array( $this, 'save_gallery' ), 10, 3 );	
		add_action( 'add_meta_boxes_media_gallery', array( $this, 'add_gallery_meta_boxes' ) );	
	}
	
	function admin_enqueue_scripts() {
		global $post;
    	if( $this->name != $post->post_type )
			return;
			
		//Style
		wp_enqueue_style( 
			'amg-media-gallery', 
			AMG_URL . 'css/media-gallery.css', 
			array( 'dashicons' )
		);
		
		//Scripts
		wp_register_script( 
			'backbone-marionette', 
			AMG_URL . 'js/backbone.marionette.js', 
			array( 'backbone', 'underscore' ), 
			'1.5.1', 
			true 
		);

		wp_register_script( 
			'backbone-collection-iterator', 
			AMG_URL . 'js/backbone.collection-iterator.js',
			array( 'backbone', 'underscore' ), 
			'0.1', 
			true 
		);
		
		wp_register_script( 
			'amg-models', 
			AMG_URL . 'js/models.js', 
			array( 
				'jquery-ui-sortable', 
				'jquery-ui-widget', 
				'wp-ajax-response', 
				'backbone', 
				'underscore', 
				'backbone-marionette', 
				'backbone-collection-iterator' 
			), 
			'0.1', 
			true 
		);
		wp_register_script( 
			'amg-views', 
			AMG_URL . 'js/views.js', 
			array( 
				'jquery-ui-sortable', 
				'jquery-ui-widget', 
				'wp-ajax-response', 
				'backbone', 
				'underscore', 
				'backbone-marionette', 
				'backbone-collection-iterator' 
			),
 			'0.1', 
			true 
		);
		
		wp_enqueue_script( 
			'amg-controller', 
			AMG_URL . 'js/controller.js', 
			array( 'amg-models', 'amg-views' ), 
			1.0, 
			true 
		);

		$post = get_post();
		$data = $this->get_gallery_data( $post->ID );

		wp_localize_script(  'amg-controller', 'amgGallery', $data );
	}
	
	function add_columns( $columns ) {
		$columns =  array_slice( $columns, 0, 2, true ) + array( 'media' => 'Media' ) + array_slice( $columns, 2, NULL, true );
		$columns =  array_slice( $columns, 0, 1, true ) + array( 'gallery_type' => '' ) + array_slice( $columns, 1, NULL, true );
		return $columns;
	}
	
	function manage_columns( $column_name, $id ) {
		global $post;
		if( $post->post_type != 'media_gallery' )
			return;
		
		$types = wp_get_object_terms( $id, 'gallery_type' );		
		$type = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;
				
		switch( $column_name ) {
			case 'media':
				$type_labels = array( 'audio' => 'Audio', 'image' => 'Image', 'video' => 'Video' );
				$count = count( $post->media );
				$msg = sprintf( _n( '1 %2$s file', '%1$d %2$s files', $count, 'advanced-media-galleries'  ), $count, $type_labels[ $type ] );
				$link =  admin_url( sprintf( 'upload.php?in_gallery=%s&post_mime_type=%s', $id, $type ) );
				$output = sprintf( '<a href="%s" target="_blank">%s</a>', $link, $msg );
				if( $count < 1 ){
					echo '—';
				}
				else {
					echo $output;
				}
			break;
			
			case 'gallery_type':
				echo "<span class='dashicons-format-{$type} dashicons'></span>";
			break;
		}
	}
	
	function show_content( $post ) {		
		if( $post->post_type != 'media_gallery' ) return;

		$html = '
		<div id="amg-gallery-main">
			<div id="amg-action-bar">
			</div>
			<div id="amg-gallery-content">
			</div>
		</div>
		<script type="application/javascript">
			jQuery( document ).ready( function($){
				AMG.app = new AMG.Lib.Controller( amgGallery );
			});
		</script>';
		
		//Output
		echo $html;	
	}
	
	function add_gallery_meta_boxes( $post ) {
		add_meta_box( 'amg-gallery-type', 'Gallery Type', array( $this, 'print_gallery_type_box'), 'media_gallery', 'side', 'high' );
		
		remove_meta_box( 'postimagediv', 'media_gallery', 'side' );
	}
	
	function print_gallery_type_box( $post ) {
		echo '<div id="amg-type-wrapper"></div>';
	}
	
	function save_gallery( $post_id, $post, $update ) {

		$types = wp_get_object_terms( $post_id, 'gallery_type' );		
		$old = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;

		$new = isset( $_REQUEST['_amg_gallery_type'] ) ? $_REQUEST['_amg_gallery_type'] : false;
		
		if( $new ) {
			wp_set_object_terms( $post_id, (array) $new, 'gallery_type' );	
		} else {
			wp_set_object_terms( $post_id, (array) $old, 'gallery_type' );	
		}
			
	}

	function get_connection_data( $connection ) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare( 
			"
			SELECT      {$wpdb->p2p}.p2p_from gallery, {$wpdb->p2p}.p2p_to media
			FROM        {$wpdb->p2p}
			WHERE       {$wpdb->p2p}.p2p_id = %d
			",
			$connection
		) ); 
	}
	
	function remove_connection( $connection ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->p2p, array( 'p2p_id' => $connection ) );	
	}
	
	function get_gallery_data( $gallery ) {
		//Get Gallery
		if( ! $gallery = get_post( $gallery ) )
			return false;
			
		if( $gallery->post_type != 'media_gallery' )
			return false;
		
		//Get Type
		$types = wp_get_object_terms( $gallery->ID, 'gallery_type' );		
		$type = ( count( $types ) < 1 ) ? 'image' : $types[0]->slug;
		
		//Get nonce
		$nonce = ( current_user_can( 'edit_post', $gallery->ID ) ) ? wp_create_nonce( 'amg-gallery-' . $gallery->ID ) : 0;
		
		//Return json
		return array(
			'id' => $gallery->ID,
			'type' => $type,
			'nonce' => $nonce	
		);
	}
	
	function get_gallery_media_json( $gallery ) {		
		//Get Gallery
		if( ! $gallery = get_post( $gallery ) )
			return false;
			
		if( $gallery->post_type != 'media_gallery' )
			return false;
		
		//Get media
		$media = new WP_Query( array( 'in_gallery' => $gallery->ID, 'nopaging' => true) );
		
		$models = array();
		
		while( $media->have_posts()) {
			$m = $media->next_post();
			$model = wp_prepare_attachment_for_js( $m->ID );
			$connection = p2p_type( 'gallery_media' )->get_p2p_id( $gallery->ID, $m->ID );
			
			$model['ordinal'] = intval( p2p_get_meta( $connection, 'order', true ) );
			$model['_id'] = $connection;
			$model['gallery'] = $gallery->ID;
			$models[] = $model;				
		}		
		
		return $models;
	}
	
	function process_media_json( $connection ) {
		//Validate
		$data = $this->get_connection_data( $connection );
		if( ! $media = get_post( $data->media )  )
			return false;
			
		if( $media->post_type != 'attachment' )
			return false;
		
		$model = wp_prepare_attachment_for_js( $media->ID );
		
		$model['ordinal'] = intval( p2p_get_meta( $connection, 'order', true ) );
		$model['_id'] = $connection;
		$model['gallery'] = $data->gallery;
		return $model;		
	}
	
	function change_gallery_type() {
		$gallery =  isset( $_REQUEST['gallery'] ) ? intval( $_REQUEST['gallery'] ) : 0;	
		$type =  isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : 'image';
		
		//Verify nonce
		check_ajax_referer( 'amg-gallery-' . $gallery );	
		
		$response = wp_set_object_terms( $gallery, (array) $type, 'gallery_type' );	
		
		wp_send_json( array( 'message' => $response ));
		
	}
	
	function media_response () {
		$gallery =  isset( $_REQUEST['gallery'] ) ? intval( $_REQUEST['gallery'] ) : 0;	
		$media =  isset( $_REQUEST['media'] ) ? intval( $_REQUEST['media'] ) : 0;
		$model = json_decode( file_get_contents( "php://input" ) );
		
		//Verify nonce
		check_ajax_referer( 'amg-gallery-' . $gallery );
		
		switch( $_SERVER['REQUEST_METHOD'] ){
			//Read
			case 'GET':
				if( empty( $media ) ){
					echo json_encode( $this->get_gallery_media_json( $gallery ) );	
				} else {
					echo json_encode( $this->process_media_json( $media ) );	
				}
			break;
			
			//Create
			case 'POST': 
				$next = empty( $model->ordinal ) ? $this->get_next_order( $model->gallery ) : $model->ordinal ;
				$media = p2p_type( 'gallery_media' )->connect( $model->gallery, $model->id, array(
					'order' => $next
				) );
				echo json_encode( $this->process_media_json( $media ) );	
			break;
			
			//Update
			case 'PUT': 
				p2p_update_meta( $model->_id, 'order', $model->ordinal );
				echo json_encode( $this->process_media_json( $media ) );
			break;
			
			//Delete
			case 'DELETE': 	
				$this->remove_connection( $media );
				echo json_encode( array( 'message' => 'Media removed from gallery.') );
			break;
		}
		die;
	}
	
	function get_next_order( $gallery ) {
		global $wpdb;
		$max = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(meta_value+0) 
			FROM {$wpdb->p2pmeta} 
			INNER JOIN {$wpdb->p2p} ON {$wpdb->p2p}.p2p_id = {$wpdb->p2pmeta}.p2p_id 
			WHERE {$wpdb->p2pmeta}.meta_key = 'order' 
			AND {$wpdb->p2p}.p2p_from = %d
			AND {$wpdb->p2p}.p2p_type = 'gallery_media'",
			$gallery
		) );
		
		return empty( $max ) ? 0 : $max + 1;		
	}
	
	function print_templates() {
	?>
    <script type="text/html" id="amg-image-tmpl">
		<# if ( media.sizes.hasOwnProperty( 'amg-thumbnail' ) ) { #>
			<img src="{{ media.sizes['amg-thumbnail'].url }}">
		<# } else { #>
			<img src="{{ media.sizes.full.url }}">
		<# } #>
		<div class="amg-media-status">
			<i class="amg-media-status-icon"></i>
		</div>
		<a href="#" class="amg-media-remove">
			<i class="amg-media-remove-icon"></i>
		</a>
	</script>
    
    <script type="text/html" id="amg-audio-tmpl">
		<div class="amg-media-status">
			<i class="amg-media-status-icon"></i>
		</div>
		<a href="#" class="amg-media-remove">
			<i class="amg-media-remove-icon"></i>
		</a>
	</script>
    
    <script type="text/html" id="amg-video-tmpl">
		<div class="amg-media-status">
			<i class="amg-media-status-icon"></i>
		</div>
		<a href="#" class="amg-media-remove">
			<i class="amg-media-remove-icon"></i>
		</a>
	</script>
    
    <script type="text/html" id="amg-media-add-tmpl">
		<span class="amg-buttons-icon"></span> 
		Add Media
	</script>
	<script type="text/html" id="amg-gallery-type-tmpl">
		<option value="image">Image Gallery</option>
		<option value="audio">Audio Playlist</option>
		<option value="video">Video Playlist</option>
	</script>
    
    <script type="text/html" id="amg-action-tmpl">
		<div id="add-region"></div>
		<div id="bulk-region"></div>
	</script>
    
    <script type="text/html" id="amg-bulk-tmpl">
		<button class="remove button">Remove</button>
		<button class="clear button">Clear</button>
	</script>

    <?php	
	}

}

new AMG_Media_Gallery_Admin();