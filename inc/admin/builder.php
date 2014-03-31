<?php
if( !class_exists( 'CED_Post_Type_Admin' ) ) {
	class CED_Post_Type_Admin {
		public $name;
		
		function __construct() {
			if( !is_admin() )
				return;
			add_action( $this->name . '_admin_setup', array( $this, 'setup' ) );	
		}
		
		function setup() {
			add_filter( "manage_{$this->name}_posts_columns", array($this,'add_columns' ) );
			add_filter( "manage_edit-{$this->name}_sortable_columns", array($this,'add_sortable_columns' ) );
			add_action( "manage_{$this->name}_posts_custom_column", array($this,'manage_columns'), 10, 2 );
			add_action( 'init', array( $this,'setup_metaboxes' ), 30 );
			add_action( 'admin_head-edit.php', array( $this,'hide_publishing_actions' ) );
			//Filtering
			add_action( 'restrict_manage_posts',array( $this, 'restrict_posts' ) );
			add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );		
		}
		
		function remove_meta_boxes() 
		{
			if( !property_exists(  $this, 'name' ) || !property_exists(  $this, 'taxonomies' ) ) 
				return;
				
			foreach( (array) $this->taxonomies as $tax )
			{
				remove_meta_box( 'tagsdiv-' . $tax, $this->name, 'core' );	
				remove_meta_box( $tax . 'div', $this->name, 'core' );	
			}
		}
			
		function is_post_type ($posts, $query) {
			$post_type = (array) $query->get('post_type');
			
			
			if(!in_array($this->name, $post_type )|| count($posts) <=0)
				return false;
			
			
			return true;
		}
		
		function generate_taxonomy_select($filters = array()) {
			foreach ($filters as $tax_slug) {
				// retrieve the taxonomy object
				$tax_obj = get_taxonomy($tax_slug);
				if( ! $tax_name = $tax_obj->labels->name ) return;;
		
				// output html for taxonomy dropdown filter
				echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
				echo "<option value=''>Show All $tax_name</option>";
				$this->generate_taxonomy_options($tax_slug,0,0);
				echo "</select>";
			}
		}
		
		function generate_taxonomy_options($tax_slug, $parent = 0, $level = 0) {
			if(!is_null($parent)) {
				$args = array('parent' => $parent);
			} else {
				$args = array();
			}
			$terms = get_terms($tax_slug,$args);
			$tab='';
			for($i=0;$i<$level;$i++){
				$tab.="-";
			}
			$slug = null;
			if(isset($_GET[$tax_slug]))
				$slug = $_GET[$tax_slug];
			foreach ($terms as $term) {
				// output each select option line, check against the last $_GET to show the current option selected
				echo '<option value='. $term->slug, $slug == $term->slug ? ' selected="selected"' : '','>' .$tab. $term->name .' (' . $term->count .')</option>';
				$this->generate_taxonomy_options($tax_slug, $term->term_id, $level+1);
			}
			
		}
		
		function generate_post_select($select_id, $post_type, $selected = 0) {
			$post_type_object = get_post_type_object($post_type);
			$label = $post_type_object->label;
			$posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));
			echo '<select name="'. $select_id .'" id="'.$select_id.'">';
			echo '<option value = "" >All '.$label.' </option>';
			foreach ($posts as $post) {
				echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
			}
			echo '</select>';
		}
		
		function add_columns($columns) {
			return $columns;
		}
		
		function add_sortable_columns($columns) {
			return $columns;
		}
		
		function manage_columns($columns, $id) {
			
		}
		
		function restrict_posts() {
			
		}
		function setup_metaboxes() {
			
		}
		
		function hide_publishing_actions(){
		
		}
		
		function remove_taxonomy_metaboxes( $post_type, $taxonomies = array() ) {
			foreach($taxonomies as $taxonomt){	
				remove_meta_box( 'tagsdiv-' . $taxonomy, $post_type, 'core' );
				remove_meta_box($taxonomy. 'div' ,$post_type, 'core' );					
			}	
		}
		
		function meta_months_dropdown( $meta_key, $name, $default = 'Show all dates' ) {
			global $wpdb, $wp_locale;
			//$name = $meta_key . '_m';
		
			$months = $wpdb->get_results( $wpdb->prepare( "
				SELECT DISTINCT YEAR( meta_value ) AS year, MONTH( meta_value ) AS month
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				ORDER BY meta_value DESC
			", $meta_key ) );
			
			$month_count = count( $months );
			
			if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
				return;
		
			$m = isset( $_GET[$name] ) ?  $_GET[$name] : '0000-00-00';
		?>
			<select name='<?php echo $name; ?>'>
				<option<?php selected( $m, '0000-00-00' ); ?> value='0'><?php _e( $default ); ?></option>
		<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year )
					continue;
		
				$month = zeroise( $arc_row->month, 2 );
				$year = $arc_row->year;
		
				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . '-' . $month . '-00', false ),
					esc_attr( $year . '-' . $month . '-00' ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
		?>
			</select>
		<?php
		}
	}
}
?>