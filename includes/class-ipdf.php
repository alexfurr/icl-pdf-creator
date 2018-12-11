<?php

class IPDF {
	
	/**
	*	The core plugin settings, checked for compatibility.
	*	---
	*/
	private static $settings = array();
	
    
    /**
	*	The list of sub-menu pages to add under the plugin's main menu.
	*	---
	*/
	private static $admin_submenus = array();
    
    
	/**
	*	Dev dumping ground.
	*	---
	*/
	public static $debug = array();
	
  

  
/**
*   Start up
*	---
*/
    /**
	*
	*/
    public static function initialize () {
		add_action( 'plugins_loaded',	'IPDF::on_plugins_loaded',        10 );
		add_action( 'wp_head', 	        'IPDF::on_wp_head',               10 );
		//add_action( 'wp_footer', 		'IPDF::on_wp_footer', 			  10 );	    // Final chance to enqueue, priority <= 20.
		//add_action( 'wp_footer', 		'IPDF::add_initialization_js',    50 ); 	// Initialization js, later than priority 20.
        
        add_shortcode( 'icl-pdf', 'IPDF::shortcode__icl_pdf' );
        add_action( 'wp_ajax_iclpdf_make_pdf', 'iclpdf_make_pdf' );
        
        //if ( wp_doing_ajax() ) {
        //    iclpdf_replace_shortcode_handlers();
        //}
	}
	
    
    
    
    public static function shortcode__icl_pdf ( $atts, $content = '' ) {
        
        
        $default_atts = array(
            'image'     => '',
            'style'     => 'std',
            'notes'     => 'true',
        );
        
        $html = '';
        
        $html .= '<div class="iclpdf-wrap">';
        $html .=     '<div class="text"><h3>Download a PDF of this course:</h3></div>';
        $html .=     '<div class="controls">';
		
		if(class_exists('ekNotesDraw') )
		{	
			$html .=        '<label for="pdf_include_notes"><input type="checkbox" id="pdf_include_notes" name="pdf_include_notes" value="1" /> Include my notes</label>';
			//$html .=        '<label for="pdf_include_notes_1"><input type="radio" id="pdf_include_notes_1" name="pdf_include_notes" value="1" /></label>';
			$html .=        '<br><br>';
		}
        $html .=        '<div class="button" id="make_pdf">Create PDF</div><div id="pdf_request_spinner"></div>';
        $html .=     '</div>';
        $html .=     '<div id="PDF_downloadFeedback"></div>';
        $html .= '</div>';
        
        
        return $html;
    
    }
    
    
    
    public static function on_wp_head () {
        
        /*
        //write JS WP ajax url, and any other vars.
        wp_localize_script( 
            'iclpdf-ajax', 
            'iclpdfAjax', 
            array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ) 
            ) 
        );
        */
        
        
        wp_enqueue_script( 'iclpdf-ajax', IPDF_PLUGIN_URL . '/js/frontend.js', array('jquery') );
        
        $params = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
		);
		wp_localize_script( 'iclpdf-ajax', 'iclpdf_ajax', $params );	
        
       
        
        
        
        ?>
        
        <script>
        var ICLPDF_USER_ID = '<?php echo get_current_user_id(); ?>';
        var ICLPDF_SITE_ID = '<?php echo get_current_blog_id(); ?>';
        </script>
        
        <style>
        .waitingDiv { 
            padding:40px 0 0 30px; 
            height:20px; 
            background:url('<?php echo IPDF_PLUGIN_URL . '/images/loader.gif'; ?>') no-repeat;
            margin: 23px 0 0 20px;
        }
        </style>
        <?php
        
        // styles
        wp_enqueue_style( 'iclpdf-frontend', IPDF_PLUGIN_URL . '/css/frontend.css' );
    }
    
    
    
    /**
	*
	*/
	public static function on_plugins_loaded () {
		// - Run Hook -  
		do_action( 'ipdf_initialize' );
        
        // Whether to show the menu. If defined as false then the menu pages will not exist at all, and can't be visited even if the url is known.
        if ( ! defined( 'IPDF_HAS_ADMIN_MENU' ) ) {
            define( 'IPDF_HAS_ADMIN_MENU', true );
        }
        
        // The plugin name as it appears on the admin menu, settings page, and widget list.
        if ( ! defined( 'IPDF_MENU_NAME' ) ) {
            define(	'IPDF_MENU_NAME', 'ICL PDF' );
        }

        // The slug used admin-side for the settings page, also prepended to any child-page slugs.
        if ( ! defined( 'IPDF_MENU_SLUG' ) ) {
            define(	'IPDF_MENU_SLUG', 'ipdf' );
        }

        if ( is_admin() ) {
            if ( IPDF_HAS_ADMIN_MENU ) {
                add_action( 'admin_menu', 'IPDF::create_admin_menu', 10 );
                add_filter( 'plugin_action_links', 'IPDF::add_plugin_list_settings_link', 10, 2 );
            }
		}
	}
    



/**
*   API Methods
*	---
*/
    /**
	*	Adds a sub-menu page to the admin-side menu. 
	*	---
	*	@param Array $params - The sub-menu specification as follows:
	*
	*		Required keys:
	*			String 'title'				- The browser window/tab title.
	*			String 'menu_name'			- The name shown in the menu.
	*			String 'capability'			- User's capability, only supports 'manage_options' currently.
	*			String 'slug'				- The slug to append to the parent slug shown in the address bar.
	*			String 'draw_function'		- The function name that will render the page, and handle it's features.
	*
	*		Optional key:	
	*			String 'scripts_function'	- The function name that will enqueue scripts for the page.
	*/
	public static function add_submenu_page ( $params ) {
		self::$admin_submenus[ ($params['slug']) ] = $params;
	}
    
    /**
	*
	*/
	public static function get_settings () {
		if ( empty( self::$settings ) ) {
			self::$settings = self::sanitize_settings();
		}
		return self::$settings;
	}

    /**
	*
	*/
	public static function save_settings ( $settings ) {
		$changed = update_option( IPDF_SETTINGS_NAME, $settings );
		if ( $changed ) {
			self::$settings = $settings;
		}
		return self::$settings;
	}
	
	/**
	*
	*/
	public static function delete_settings () {
		$success = delete_option( IPDF_SETTINGS_NAME );
		if ( $success ) {
			self::$settings = array();
		}
		return $success;
	}

 

	
/**
*   Admin-side setup
*	---
*/    
	/**
	*
	*/
	public static function add_plugin_list_settings_link ( $links, $file ) {
		if ( $file == 'icl-pdf-creator/icl-pdf-creator.php' ) {
			$settings_link = '<a href="admin.php?page=' . IPDF_MENU_SLUG . '">' . __( 'Dashboard' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
    
    /**
	*	
	*/
	public static function create_admin_menu () {	
		$browser_title 		= 'Dashboard | ' . IPDF_MENU_NAME;
		$menu_name 			= IPDF_MENU_NAME;
		$capability 		= 'manage_options';
		$slug 				= IPDF_MENU_SLUG;
		$draw_function 		= 'IPDF::root_page_render';
		
		// Add the root menu page.
		$root_page = add_menu_page( $browser_title, $menu_name, $capability, $slug, $draw_function );				
        
        // Add the scripts callback for the root page.
        add_action( 'admin_head-'. $root_page, 'IPDF::root_page_add_scripts' );
		
		// Create all registered sub pages.
		foreach ( self::$admin_submenus as $menu ) 
        {
			$submenu = add_submenu_page(
				IPDF_MENU_SLUG, 
				$menu['title'] . ' | ' . IPDF_MENU_NAME, 
				$menu['menu_name'], 
				$menu['capability'], 
				IPDF_MENU_SLUG . '-' . $menu['slug'], 
				$menu['draw_function'] 
			);
			if ( ! empty( $menu['scripts_function'] ) ) {
				add_action( 'admin_head-'. $submenu, $menu['scripts_function'] );
			}
		}
	}
    
    /**
	*
	*/
	public static function root_page_render () {
		include_once( IPDF_PLUGIN_DIR . '/template/admin-page-root.php' );
	}
    
    /**
	*
	*/
	public static function root_page_add_scripts () {		
		//wp_enqueue_script( 'ipdf-settings', IPDF_PLUGIN_URL . '/js/admin.js' );
		//wp_enqueue_style( 'ipdf-settings', IPDF_PLUGIN_URL . '/css/admin.css' );
	}
    
    


/**
*   Settings management
*	---
*/
	/**
	*
	*/
	private static function sanitize_settings () {
		$settings = get_option( IPDF_SETTINGS_NAME );
		if ( empty( $settings ) ) {
			$settings = self::plugin_default_settings();
			self::save_settings( $settings );
		} else {
			if ( $settings['version'] !== IPDF_VERSION ) {
				$settings = self::merge_settings( $settings );
				self::save_settings( $settings );
			}
		}
		return $settings;
	}

	/**
	*
	*/
	private static function merge_settings ( $old_settings ) {
		$settings = self::plugin_default_settings();
		foreach ( $settings as $k => $op ) {
			if ( array_key_exists( $k, $old_settings ) ) {
				$settings[ $k ] = $old_settings[ $k ];
			}
		}
		$settings['version'] = IPDF_VERSION; //set last!
		return $settings;	
	}	
	
	/**
	*
	*/
	private static function plugin_default_settings () {
		return array(
			'version'				=> IPDF_VERSION,
			'remember_settings'		=> true,
        );
	}
	    

 
 
/**
*   Dev tools
*	---
*/
	/**
	*
	*/
    public static function debug ( $data = null, $key = null ) {
		if ( $data !== null ) {
			if ( $key ) {
				self::$debug[ $key ] = $data;
			} else {
				self::$debug[] = $data;
			}
		} else {
			echo '<pre>';
			if ( $key ) {
				print_r( self::$debug[ $key ] );
			} else {
				print_r( self::$debug );
			}
			echo '</pre>';
		}
	}
    
}

?>