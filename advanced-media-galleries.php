<?php
/*
Plugin Name: Advanded Media Galleries
Plugin URI: http://www.crosseyedesign.com/advanced-media-galleries/
Description: Advanced, persistant galleries
Version: 0.1
Author: Manny "Funkatron" Fleurmond
Author URI: http://www.crosseyedesign.com
License: GPL2
*/

function amg_plugin_load() {
	//Config
	require_once( 'config.php' );
	
	//Core
	require_once( AMG_INC_DIR . 'core/core.php' );
	
	//Admin
	if( is_admin() ){
		require_once( AMG_INC_DIR . 'admin/admin.php' );
	}
	
	//Front end
	else{
		//require_once( AMG_INC_DIR . 'core/front-end.php'  );
	}
}

add_action( 'plugins_loaded', 'amg_plugin_load' );
