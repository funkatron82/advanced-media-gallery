<?php
//vVrsion
if (!defined('AMG_VERSION'))
	define("AMG_VERSION", "1.0" ); 
	
//Plugin dir
if (!defined('AMG_DIR'))
	define('AMG_DIR', plugin_dir_path( __FILE__ ) );
define('AMG_INC_DIR', trailingslashit( AMG_DIR . 'inc' ) );


//Plugin url
if (!defined('AMG_URL'))
	define('AMG_URL',  plugin_dir_url( __FILE__ ));
define('AMG_INC_URL', trailingslashit( AMG_URL . 'inc' ) );
