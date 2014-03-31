<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AMG_Admin' ) ) 
{
	class AMG_Admin {
		public $components = array( 
			'builder', 
			'media',
			'media-gallery'
		);
		function __construct(){
			$this->load();	
		}
		
		function load(){
			if( is_admin() ) 
			{				
				foreach( $this->components as $component ) {
					require_once AMG_INC_DIR . 'admin/' . $component . '.php';
				}
				
				do_action( 'media_gallery_admin_setup' );
				do_action( 'attachment_admin_setup' );
								
			}
		}	
	}
}
new AMG_Admin();