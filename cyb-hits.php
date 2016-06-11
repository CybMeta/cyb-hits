<?php
/*
Plugin Name:  Cyb Hits
Plugin URI:   http://cybmeta.com
Description:  Add hits counter to WordPress post usgin Ajax, so it is compatible with static page cache
Version:      0.1
Author:       Juan Padial (@CybMeta)
Author URI:   http://cybmeta.com
*/

$options = get_option( 'cyb_hits' );
if( !isset($options['ajax_mode']) || $options['ajax_mode'] != "shortinit" ) {
	include ( plugin_dir_path( __FILE__ ) . 'ajax_hits.php' );
	add_action( 'wp_ajax_uptdate_hits', 'cyb_uptdate_hits');
	add_action( 'wp_ajax_nopriv_uptdate_hits', 'cyb_uptdate_hits');
}


class CybHits {
	/*
	* integer
	* version of the plugin
	*/
	public $version = "0.1";
	
	/*
	* string
	* textdomain for translations
	*/
	public $textdomain = 'cyb-hits';
	
	/**
	 * Plugin initialization
	 * 
	 * @access public
	 * @since 0.1
	 */
	public function __construct( $args = array() ) {
		add_action( 'plugins_loaded', array( $this, 'translations' ) );
	}
	
	/**
	 * Translations
	 * @since 0.1
	 *
	 */
	public function translations() {
		$loaded = load_plugin_textdomain( $this->textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
}

if( !is_admin() ) {
	$CybHitsSite = new CybHitsSite;
}

class CybHitsSite extends CybHits {
	
	/**
	 * Plugin initialization
	 * 
	 * @access public
	 * @since 0.1
	 */
	public function __construct( $args = array() ) {
		parent::__construct();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
		
	}
	
	/**
	 * Scripts and styles
	 * @since 0.1
	 *
	 */
	public function enqueue_scripts() {
	
		wp_register_script('cyb-hits', plugins_url( '/js/hits.js', __FILE__ ), array( 'jquery' ), $this->version, true);
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('cyb-hits');
		
		$options = get_option( 'cyb_hits' );

		if( isset($options['ajax_mode']) && $options['ajax_mode'] == "shortinit" ) {
			$ajaxurl = plugins_url( '/ajax_hits.php', __FILE__ );
		} else {
			$ajaxurl = admin_url( 'admin-ajax.php' );
		}
		
		$theID = 0;
		
		if(is_single()) {
			$theID = get_the_ID();
		}
		
		$scriptData = array(
                      'ajax_url' => $ajaxurl,
					  'postID'	=> $theID
                    );
		wp_localize_script('cyb-hits','cyb_hits_data',$scriptData);
	}
	
}

if( is_admin() ) {
	$CybHitsAdmin = new CybHitsAdmin;
}

class CybHitsAdmin extends CybHits {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
	    parent::__construct();
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		add_filter( 'manage_post_posts_columns',  array( $this, 'add_new_columns' ) );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'register_sortable_columns' ) );
		add_filter( 'request', array( $this, 'hits_column_orderby' ) );
		add_action( 'manage_post_posts_custom_column' , array( $this, 'custom_columns' ) );
    }
	
    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Cyb Hits Settings', 
            'Hits', 
            'manage_options', 
            'cyb-hits-settings', 
            array( $this, 'settings_page' )
        );
    }

    /**
     * Options page callback
     */
    public function settings_page()
    {
        // Set class property
        $this->options = get_option( 'cyb_hits' );
        ?>
        <div class="wrap">         
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'cyb_hits' );   
                do_settings_sections( 'cyb-hits-settings' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'cyb_hits', // Option group
            'cyb_hits', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        add_settings_section(
            'settings', // ID
            __('Cyb Hits Settings', $this->textdomain), // Title
            array( $this, 'print_section_info' ), // Callback
            'cyb-hits-settings' // Page
        );

        add_settings_field(
            'ajax_mode', // ID
            __('Ajax Mode', $this->textdomain),  // Title
            array( $this, 'create_field' ),  // Callback
            'cyb-hits-settings',  // Page
            'settings', // Section
			array( 'type' => 'checkbox', 'class' => 'text', 'id' => 'ajax_mode', 'label' => __('Use ShortInit', 'cyb-hits' ), 'desc' => __( '<strong>ATTENTION</strong>: This is an advanced option. Check it only if you know what it means. If select ShortInit method, the ajax request will me be made a direct call to a PHP file where WordPress is loaded using WP_SHORTINIT enabled. This can improve performance but it may not work if custom plugins directory path is being used.', 'cyb-hits' ) )     // Arguments passed to callback
        );
		
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
		
		if( isset( $input['ajax_mode'] ) ) {
			$new_input['ajax_mode'] = sanitize_text_field( $input['ajax_mode'] );
		}

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function create_field($args) {
		switch ( $args['type'] ) {
			case 'number':
				printf(
					'<input type="number" id="'.$args['id'].'" name="cyb_hits['.$args['id'].']" value="%s" />',
					isset( $this->options[$args['id']] ) ? esc_attr( $this->options[$args['id']]) : ''
				);
				break;
			case 'text':
				printf(
					'<input type="text" id="'.$args['id'].'" name="cyb_hits['.$args['id'].']" value="%s" />',
					isset( $this->options[$args['id']] ) ? esc_attr( $this->options[$args['id']]) : ''
				);
				break;
			case 'textarea':
				echo '<textarea id="'.$args['id'].'" name="cyb_hits['.$args['id'].']" rows="5" cols="30">';
				if(isset( $this->options[$args['id']] )) {
					$content = stripslashes( $this->options[$args['id']] );
					$content = esc_html( $this->options[$args['id']] );
				} else {
					$content = '';
				}
				break;
			case 'checkbox':
				echo '<input type="checkbox" id="'.$args['id'].'" name="cyb_hits['.$args['id'].']" value="shortinit" '.checked('shortinit', isset( $this->options[$args['id']] ) ? esc_attr( $this->options[$args['id']]) : '', false).'/>'.$args['label'];
				echo '<br><span class="description">'.$args['desc'].'</span>';
				break;
				echo $content . '</textarea>';
		}

    }
	
	/**
	* Add new columns to the post table
	*
	* @param Array $columns - Current columns on the list post
	*/
	function add_new_columns($columns){

		$column_meta = array( 'hits' => 'Hits' );
		$columns = array_slice( $columns, 0, 6, true ) + $column_meta + array_slice( $columns, 6, NULL, true );
		return $columns;
	
	}
	
	// Register the columns as sortable
	function register_sortable_columns( $columns ) {
		$columns['hits'] = 'hits';
		return $columns;
	}
	
	//Add filter to the request to make the hits sorting process numeric, not string
	function hits_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'hits' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'hits',
				'orderby' => 'meta_value_num'
			) );
		}
 
		return $vars;
	}
	
	/**
	* Display data in new columns
	*
	* @param  $column Current column
	*
	* @return Data for the column
	*/
	function custom_columns($column) {
  
		global $post;

		switch ( $column ) {
			case 'hits':
				$hits = get_post_meta( $post->ID, 'hits', true );
				echo (int)$hits;
			break;
		}
	}

}

?>
