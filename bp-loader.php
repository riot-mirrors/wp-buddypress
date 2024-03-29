<?php
/**
 * The BuddyPress Plugin
 *
 * BuddyPress is social networking software with a twist from the creators of WordPress.
 *
 * $Id$
 *
 * @package BuddyPress
 * @subpackage Main
 */

/**
 * Plugin Name: BuddyPress
 * Plugin URI:  http://buddypress.org
 * Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
 * Author:      The BuddyPress Community
 * Author URI:  http://buddypress.org/community/members/
 * Version:     1.7-beta2
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Constants *****************************************************************/

if ( !class_exists( 'BuddyPress' ) ) :
/**
 * Main BuddyPress Class
 *
 * Tap tap tap... Is this thing on?
 *
 * @since BuddyPress (1.6)
 */
class BuddyPress {

	/** Magic *****************************************************************/

	/**
	 * BuddyPress uses many variables, most of which can be filtered to customize
	 * the way that it works. To prevent unauthorized access, these variables
	 * are stored in a private array that is magically updated using PHP 5.2+
	 * methods. This is to prevent third party plugins from tampering with
	 * essential information indirectly, which would cause issues later.
	 *
	 * @see BuddyPress::setup_globals()
	 * @var array
	 */
	private $data;

	/** Not Magic *************************************************************/

	/**
	 * @var array Primary BuddyPress navigation
	 */
	public $bp_nav = array();

	/**
	 * @var array Secondary BuddyPress navigation to $bp_nav
	 */
	public $bp_options_nav = array();

	/**
	 * @var array The unfiltered URI broken down into chunks
	 * @see bp_core_set_uri_globals()
	 */
	public $unfiltered_uri = array();

	/**
	 * @var array The canonical URI stack
	 * @see bp_redirect_canonical()
	 * @see bp_core_new_nav_item()
	 */
	public $canonical_stack = array();

	/**
	 * @var array Additional navigation elements (supplemental)
	 */
	public $action_variables = array();

	/**
	 * @var array Required components (core, members)
	 */
	public $required_components = array();

	/**
	 * @var array Additional active components
	 */
	public $loaded_components = array();

	/**
	 * @var array Active components
	 */
	public $active_components = array();

	/** Option Overload *******************************************************/

	/**
	 * @var array Optional Overloads default options retrieved from get_option()
	 */
	public $options = array();

	/** Singleton *************************************************************/

	/**
	 * @var BuddyPress The one true BuddyPress
	 */
	private static $instance;

	/**
	 * Main BuddyPress Instance
	 *
	 * BuddyPress is great
	 * Please load it only one time
	 * For this, we thank you
	 *
	 * Insures that only one instance of BuddyPress exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since BuddyPress (1.7)
	 *
	 * @staticvar array $instance
	 * @uses BuddyPress::constants() Setup the constants (mostly deprecated)
	 * @uses BuddyPress::setup_globals() Setup the globals needed
	 * @uses BuddyPress::includes() Include the required files
	 * @uses BuddyPress::setup_actions() Setup the hooks and actions
	 * @see buddypress()
	 *
	 * @return The one true BuddyPress
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new BuddyPress;
			self::$instance->constants();
			self::$instance->setup_globals();
			self::$instance->legacy_constants();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent BuddyPress from being loaded more than once.
	 *
	 * @since BuddyPress (1.7)
	 * @see BuddyPress::instance()
	 * @see buddypress()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent BuddyPress from being cloned
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddypress' ), '1.7' ); }

	/**
	 * A dummy magic method to prevent BuddyPress from being unserialized
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddypress' ), '1.7' ); }

	/**
	 * Magic method for checking the existence of a certain custom field
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting BuddyPress varibles
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Magic method for setting BuddyPress varibles
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __set( $key, $value ) { $this->data[$key] = $value; }

	/**
	 * Magic method for unsetting BuddyPress variables
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/** Private Methods *******************************************************/

	/**
	 * Bootstrap constants
	 * 
	 * @since BuddyPress (1.6)
	 *
	 * @uses is_multisite()
	 * @uses get_current_site()
	 * @uses get_current_blog_id()
	 * @uses plugin_dir_path()
	 * @uses plugin_dir_url()
	 */
	private function constants() {

		// Place your custom code (actions/filters) in a file called
		// '/plugins/bp-custom.php' and it will be loaded before anything else.
		if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) )
			require( WP_PLUGIN_DIR . '/bp-custom.php' );

		// Define on which blog ID BuddyPress should run
		if ( !defined( 'BP_ROOT_BLOG' ) ) {

			// Default to 1
			$root_blog_id = 1;

			// Root blog is the main site on this network
			if ( is_multisite() && !defined( 'BP_ENABLE_MULTIBLOG' ) ) {
				$current_site = get_current_site();
				$root_blog_id = $current_site->blog_id;

			// Root blog is every site on this network
			} elseif ( is_multisite() && defined( 'BP_ENABLE_MULTIBLOG' ) ) {
				$root_blog_id = get_current_blog_id();
			}

			define( 'BP_ROOT_BLOG', $root_blog_id );
		}

		// Path and URL
		if ( !defined( 'BP_PLUGIN_DIR' ) )
			define( 'BP_PLUGIN_DIR', trailingslashit( WP_PLUGIN_DIR . '/buddypress' ) );

		if ( !defined( 'BP_PLUGIN_URL' ) ) {
			$plugin_url = plugin_dir_url( __FILE__ );

			// If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
			if ( is_ssl() )
				$plugin_url = str_replace( 'http://', 'https://', $plugin_url );

			define( 'BP_PLUGIN_URL', $plugin_url );
		}

		// The search slug has to be defined nice and early because of the way
		// search requests are loaded
		//
		// @todo Make this better
		if ( !defined( 'BP_SEARCH_SLUG' ) )
			define( 'BP_SEARCH_SLUG', 'search' );
	}

	/**
	 * Component global variables
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses plugin_dir_path() To generate BuddyPress plugin path
	 * @uses plugin_dir_url() To generate BuddyPress plugin url
	 * @uses apply_filters() Calls various filters
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version    = '1.7-beta2-6846';
		$this->db_version = 6067;

		/** Loading ***********************************************************/

		$this->load_deprecated  = true;

		/** Toolbar ***********************************************************/

		/**
		 * @var string The primary toolbar ID
		 */
		$this->my_account_menu_id = '';

		/** URI's *************************************************************/

		/**
		 * @var int The current offset of the URI
		 * @see bp_core_set_uri_globals()
		 */
		$this->unfiltered_uri_offset = 0;

		/**
		 * @var bool Are status headers already sent?
		 */
		$this->no_status_set = false;

		/** Components ********************************************************/

		/**
		 * @var string Name of the current BuddyPress component (primary)
		 */
		$this->current_component = '';

		/**
		 * @var string Name of the current BuddyPress item (secondary)
		 */
		$this->current_item = '';

		/**
		 * @var string Name of the current BuddyPress action (tertiary)
		 */
		$this->current_action = '';

		/**
		 * @var bool Displaying custom 2nd level navigation menu (I.E a group)
		 */
		$this->is_single_item = false;

		/** Root **************************************************************/

		// BuddyPress Root blog ID
		$this->root_blog_id = (int) apply_filters( 'bp_get_root_blog_id', BP_ROOT_BLOG );

		/** Paths *************************************************************/

		// BuddyPress root directory
		$this->file           = __FILE__;
		$this->basename       = plugin_basename( $this->file );
		$this->plugin_dir     = BP_PLUGIN_DIR;
		$this->plugin_url     = BP_PLUGIN_URL;

		// Languages
		$this->lang_dir       = $this->plugin_dir . 'bp-languages';

		// Templates (theme compatability)
		$this->themes_dir     = $this->plugin_dir . 'bp-templates';
		$this->themes_url     = $this->plugin_url . 'bp-templates';

		// Themes (for bp-default)
		$this->old_themes_dir = $this->plugin_dir . 'bp-themes';
		$this->old_themes_url = $this->plugin_url . 'bp-themes';

		/** Theme Compat ******************************************************/

		$this->theme_compat   = new stdClass(); // Base theme compatibility class
		$this->filters        = new stdClass(); // Used when adding/removing filters

		/** Users *************************************************************/

		$this->current_user   = new stdClass();
		$this->displayed_user = new stdClass();
	}

	/**
	 * Legacy BuddyPress constants
	 *
	 * Try to avoid using these. Their values have been moved into variables
	 * in the instance, and have matching functions to get/set their values.
	 *
	 * @since BuddyPress (1.7)
	 */
	private function legacy_constants() {

		// Define the BuddyPress version
		if ( !defined( 'BP_VERSION'    ) ) define( 'BP_VERSION',    $this->version   );

		// Define the database version
		if ( !defined( 'BP_DB_VERSION' ) ) define( 'BP_DB_VERSION', $this->db_version );
	}

	/**
	 * Include required files
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses is_admin() If in WordPress admin, load additional file
	 */
	private function includes() {

		// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
		require( BP_PLUGIN_DIR . '/bp-core/bp-core-wpabstraction.php' );

		// Setup the versions (after we include multisite abstraction above)
		$this->versions();

		/** Update/Install ****************************************************/

		// Theme compatability
		require( $this->plugin_dir . 'bp-core/bp-core-template-loader.php'     );
		require( $this->plugin_dir . 'bp-core/bp-core-theme-compatibility.php' );

		// Require all of the BuddyPress core libraries
		require( $this->plugin_dir . 'bp-core/bp-core-dependency.php' );
		require( $this->plugin_dir . 'bp-core/bp-core-actions.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-caps.php'       );
		require( $this->plugin_dir . 'bp-core/bp-core-cache.php'      );
		require( $this->plugin_dir . 'bp-core/bp-core-cssjs.php'      );
		require( $this->plugin_dir . 'bp-core/bp-core-update.php'     );
		require( $this->plugin_dir . 'bp-core/bp-core-options.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-classes.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-filters.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-avatars.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-widgets.php'    );
		require( $this->plugin_dir . 'bp-core/bp-core-template.php'   );
		require( $this->plugin_dir . 'bp-core/bp-core-adminbar.php'   );
		require( $this->plugin_dir . 'bp-core/bp-core-buddybar.php'   );
		require( $this->plugin_dir . 'bp-core/bp-core-catchuri.php'   );
		require( $this->plugin_dir . 'bp-core/bp-core-component.php'  );
		require( $this->plugin_dir . 'bp-core/bp-core-functions.php'  );
		require( $this->plugin_dir . 'bp-core/bp-core-moderation.php' );
		require( $this->plugin_dir . 'bp-core/bp-core-loader.php'     );

		// Skip or load deprecated content
		if ( false !== $this->load_deprecated ) {
			require( $this->plugin_dir . 'bp-core/deprecated/1.5.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.6.php' );
			require( $this->plugin_dir . 'bp-core/deprecated/1.7.php' );
		}
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses register_activation_hook() To register the activation hook
	 * @uses register_deactivation_hook() To register the deactivation hook
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'bp_activation'   );
		add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If BuddyPress is being deactivated, do not add any actions
		if ( bp_is_deactivation( $this->basename ) )
			return;

		// Array of BuddyPress core actions
		$actions = array(
			'setup_theme',              // Setup the default theme compat
			'setup_current_user',       // Setup currently logged in user
			'register_post_types',      // Register post types
			'register_post_statuses',   // Register post statuses
			'register_taxonomies',      // Register taxonomies
			'register_views',           // Register the views
			'register_theme_directory', // Register the theme directory
			'register_theme_packages',  // Register bundled theme packages (bp-themes)
			'load_textdomain',          // Load textdomain
			'add_rewrite_tags',         // Add rewrite tags
			'generate_rewrite_rules'    // Generate rewrite rules
		);

		// Add the actions
		foreach( $actions as $class_action )
			add_action( 'bp_' . $class_action, array( $this, $class_action ), 5 );

		// All BuddyPress actions are setup (includes bbp-core-hooks.php)
		do_action_ref_array( 'bp_after_setup_actions', array( &$this ) );
	}

	/**
	 * Private method to align the active and database versions
	 *
	 * @since BuddyPress (1.7)
	 */
	private function versions() {

		// Get the possible DB versions (boy is this gross)
		$versions               = array();
		$versions['1.6-single'] = get_blog_option( $this->root_blog_id, '_bp_db_version' );

		// 1.6-single exists, so trust it
		if ( !empty( $versions['1.6-single'] ) ) {
			$this->db_version_raw = (int) $versions['1.6-single'];

		// If no 1.6-single exists, use the max of the others
		} else {
			$versions['1.2']        = get_site_option(                      'bp-core-db-version' );
			$versions['1.5-multi']  = get_site_option(                           'bp-db-version' );
			$versions['1.6-multi']  = get_site_option(                          '_bp_db_version' );
			$versions['1.5-single'] = get_blog_option( $this->root_blog_id,      'bp-db-version' );

			// Remove empty array items
			$versions             = array_filter( $versions );
			$this->db_version_raw = (int) ( !empty( $versions ) ) ? (int) max( $versions ) : 0;
		}
	}

	/** Public Methods ********************************************************/

	/**
	 * Setup the BuddyPress theme directory
	 *
	 * @since BuddyPress (1.5)
	 * @todo Move bp-default to wordpress.org/extend/themes and remove this
	 */
	public function register_theme_directory() {
		register_theme_directory( $this->old_themes_dir );
	}

	/**
	 * Register bundled theme packages
	 *
	 * Note that since we currently have complete control over bp-themes and
	 * the bp-legacy folders, it's fine to hardcode these here. If at a
	 * later date we need to automate this, an API will need to be built.
	 *
	 * @since BuddyPress (1.7)
	 */
	public function register_theme_packages() {

		// Register the default theme compatibility package
		bp_register_theme_package( array(
			'id'      => 'legacy',
			'name'    => __( 'BuddyPress Default', 'buddypress' ),
			'version' => bp_get_version(),
			'dir'     => trailingslashit( $this->themes_dir . '/bp-legacy' ),
			'url'     => trailingslashit( $this->themes_url . '/bp-legacy' )
		) );

		// Register the basic theme stack. This is really dope.
		bp_register_template_stack( 'get_stylesheet_directory', 10 );
		bp_register_template_stack( 'get_template_directory',   12 );
		bp_register_template_stack( 'bp_get_theme_compat_dir',  14 );
	}

	/**
	 * Setup the default BuddyPress theme compatability location.
	 *
	 * @since BuddyPress (1.7)
	 */
	public function setup_theme() {

		// Bail if something already has this under control
		if ( ! empty( $this->theme_compat->theme ) )
			return;

		// Setup the theme package to use for compatibility
		bp_setup_theme_compat( bp_get_theme_package_id() );
	}
}

/**
 * The main function responsible for returning the one true BuddyPress Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $bp = buddypress(); ?>
 *
 * @return The one true BuddyPress Instance
 */
function buddypress() {
	return buddypress::instance();
}

/**
 * Hook BuddyPress early onto the 'plugins_loaded' action.
 *
 * This gives all other plugins the chance to load before BuddyPress, to get
 * their actions, filters, and overrides setup without BuddyPress being in the
 * way.
 */
if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
	add_action( 'plugins_loaded', 'buddypress', (int) BUDDYPRESS_LATE_LOAD );

// "And now here's something we hope you'll really like!"
} else {
	$GLOBALS['bp'] = &buddypress();
}

endif;
