<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AMG_Core' ) ) 
{
	class AMG_Core {
		public $components = array( 
			'p2p', 
			'type-factory', 
			'connections',
			//'template-tags', 
			//'widgets', 
			//'shortcodes',
			'media-gallery',
			'media'
		);
		function __construct() {
			//Activation
			register_activation_hook( AMG_DIR . 'advanced-media-galleries.php', array($this,'activate'));
			//Deactivation
			register_deactivation_hook( AMG_DIR . 'advanced-media-galleries.php',array($this,'deactivate'));
			
			$this->load();
			
			add_action( 'amg_refresh', array( $this, 'refresh' ) );
		}
		
		//Activation
		function activate() {	
			$this->refresh();
		}
		
		function upgrade() {
		}
	
	
		//Deactivation
		function deactivate() {
			flush_rewrite_rules();
		}
		
		function load(){
			foreach( $this->components as $component ) {
				require_once AMG_INC_DIR . 'core/' . $component . '.php';	
			}
			
			do_action( 'media_gallery_setup' );
			do_action( 'attachment_setup' );
			
			add_image_size( 'amg_thumbnail', 250, 250, true );
		}
		
		function refresh() {
			do_action( 'media_gallery_activate' );
			do_action( 'attachment_activate' );
			flush_rewrite_rules();
		}
		
	}
}

new AMG_Core();