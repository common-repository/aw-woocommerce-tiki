<?php
/**
 *
 * @link 			http://agenwebsite.com
 * @since 			4.0.0
 * @package 		AW WooCommerce TIKI Shipping
 *
 * @wordpress-plugin
 * Plugin Name: 	AW WooCommerce TIKI Shipping ( Free Version )
 * Plugin URI:		http://www.agenwebsite.com/products/woocommerce-tiki-shipping
 * Description:		Plugin untuk WooCommerce dengan penambahan metode pengiriman TIKI.
 * Version:			4.0.3
 * Author:			AgenWebsite
 * Author URI:		http://agenwebsite.com
 * License:			GPL-2.0+
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_TIKI' ) ) :

/**
 * Initiliase Class
 *
 * @since 4.0.0
 **/
class WooCommerce_TIKI{

	/**
	 * @var string
	 * @since 4.0.0
	 */
	public $version = '4.0.3';

	/**
	 * @var string
     * @since 4.0.0
	 */
	public $db_version = '4.0.0';

	/**
	 * @var string
	 */
	public $product_version = 'woocommerce-tiki-free';

	/**
	 * @var WC_TIKI_Shipping $shipping
	 * @since 4.0.0
	 */
	public $shipping = null;

	/**
	 * @var WC_TIKI_API $api
	 * @since 4.0.0
	 */
	public $api = null;

	/**
	 * @var woocommerce tiki main class
	 * @since 4.0.0
	 */
	protected static $_instance = null;

	/**
	 * @var string
	 * @since 4.0.0
	 */
	private $nonce = '_woocommerce_tiki__nonce';

	/**
	 * Various Links
	 * @var string
	 * @since 4.0.0
	 */
	public $url_docs = 'http://docs.agenwebsite.com/products/woocommerce-tiki-shipping';
	public $url_support = 'http://www.agenwebsite.com/support';

	/**
	 * WooCommerce TIKI Instance
	 *
	 * @access public
	 * @return Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @return self
	 * @since 4.0.0
	 */
	public function __construct(){
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define Constant
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */
    private function define_constants(){
        define( 'WOOCOMMERCE_TIKI', TRUE );
        define( 'WOOCOMMERCE_TIKI_VERSION', $this->version );
    }

	/**
	 * Inititialise Includes
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */
    private function includes(){
		$this->shipping = WooCommerce_TIKI::shipping();
		$this->includes_class();
    }

    /**
	 * Hooks action and filter
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */
	private function init_hooks(){
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'add_settings_link' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts') );
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts_admin') );
        add_action( 'admin_notices', array( &$this, 'notice_set_license' ) );
	}

	/**
	 * Inititialise TIKI Shipping module
	 *
	 * @access private
	 * @return WC_TIKI_Shipping
	 * @since 4.0.0
	 */
	private static function shipping(){
	 	// Load files yang untuk modul shipping
		WooCommerce_TIKI::load_file( 'shipping' );

        return new WC_TIKI_Shipping();
	}

    /**
	 * Include file
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */
    private function includes_class(){
        require_once( 'includes/wc-tiki-ajax.php' );
        require_once( 'includes/wc-tiki-api.php' );

        $this->api = new WC_TIKI_API( $this->product_version, $this->get_license_code() );

    }

	/**
	 * Load Requires Files by modules
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */
	private static function load_file( $modules ){
		switch( $modules ){

			case 'shipping':
				require_once( 'includes/shipping/shipping.php' );
				require_once( 'includes/shipping/shipping-frontend.php' );
			break;

		}
	}

	/**
	 * Load JS & CSS FrontEnd
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
	public function load_scripts(){
        $suffix			= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $assets_path	= str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

        // select2
        $select2_js_path = $assets_path . 'js/select2/select2' . $suffix . '.js';
        $select2_css_path = $assets_path . 'css/select2.css';
        if( ! wp_script_is( 'select2', 'registered' ) ) wp_register_script( 'select2', $select2_js_path, array( 'jquery' ), '3.5.2' );
        if( ! wp_style_is( 'select2', 'registered' ) ) wp_register_style( 'select2', $select2_css_path );

        // chosen
        $chosen_js_path = $assets_path . 'js/chosen/chosen.jquery' . $suffix . '.js';
        $chosen_css_path = $assets_path . 'css/chosen.css';
        if( ! wp_script_is( 'chosen', 'registered' ) && $this->is_url_exists('http:' . $chosen_js_path) ) wp_register_script( 'chosen', $chosen_js_path, array( 'jquery' ), '1.0.0', true );
        if( ! wp_style_is( 'chosen', 'registered' ) && $this->is_url_exists('http:' . $chosen_css_path) ) wp_enqueue_style( 'woocommerce_chosen_styles', $chosen_css_path );

        wp_register_script( 'woocommerce-tiki-shipping', $this->plugin_url() . '/assets/js/shipping' . $suffix . '.js', 	array( 'jquery' ),	'1.0.0', true );

        // shipping
        if( $this->shipping->is_enable() && ! jne_active() ){
            if( is_checkout() || is_cart() || is_wc_endpoint_url( 'edit-address' ) ) {
                wp_enqueue_script( 'woocommerce-tiki-shipping');
                wp_localize_script( 'woocommerce-tiki-shipping', 'agenwebsite_woocommerce_tiki_params', $this->localize_script( 'shipping' ) );
            }
        }

        // load selec2 or chosen
        if( ( $this->shipping->is_enable() && is_cart() && ! jne_active() ) ){
            if( ! wp_script_is( 'select2' ) ) wp_enqueue_script( 'select2' );
            if( ! wp_style_is( 'select2' ) ) wp_enqueue_style( 'select2' );

            if( ! wp_script_is( 'chosen' ) ) wp_enqueue_script( 'chosen' );
            if( ! wp_style_is( 'chosen' ) ) wp_enqueue_style( 'chosen' );
        }

    }

	/**
	 * Load JS dan CSS admin
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
	public function load_scripts_admin(){
        global $pagenow;

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        // Load for admin common JS & CSS
        wp_register_script( 'woocommerce-tiki-js-admin', $this->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'zeroclipboard' ), '1.0.0', true );
        wp_register_style( 'woocommerce-tiki-admin', $this->plugin_url() . '/assets/css/admin.css' );

        if( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && $_GET['page'] == 'wc-settings' ) && ( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' ) && ( isset( $_GET['section'] ) && $_GET['section'] == 'tiki_shipping' ) ) {

            wp_enqueue_script( 'woocommerce-tiki-js-admin' );
            wp_enqueue_style( 'woocommerce-tiki-admin' );

            // Load localize admin params
            wp_localize_script( 'woocommerce-tiki-js-admin', 'agenwebsite_tiki_admin_params', $this->localize_script( 'admin' ) );
        }

        if( $this->is_page_to_notice() ){
            wp_enqueue_style( 'woocommerce-tiki-admin' );
        }

    }

	/**
	 * Localize Scripts
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
	public function localize_script( $handle ){
		switch( $handle ){
			case 'admin':
				return array(
					'i18n_reset_default'           => __( 'Peringatan! Semua pengaturan anda akan dihapus. Anda yakin untuk kembalikan ke pengaturan awal ?', 'agenwebsite' ),
					'i18n_is_available'            => __( 'sudah tersedia', 'agenwebsite' ),
					'license'                      => ( $_POST && isset($_POST['woocommerce_tiki_shipping_license_code'] ) ) ? $_POST['woocommerce_tiki_shipping_license_code'] : '',
                    'tab'                          => ( $_GET && isset($_GET['tab_tiki']) ) ? $_GET['tab_tiki'] : 'general',
					'ajax_url'                     => self::ajax_url(),
					'tiki_admin_wpnonce'           => wp_create_nonce( 'woocommerce_tiki_admin' )
				);
			break;
            case 'shipping':
				return array(
                    'i18n_placeholder_kota'         => __( 'Pilih Kota / Kabupaten', 'agenwebsite' ),
                    'i18n_placeholder_kecamatan'    => __( 'Pilih Kecamatan', 'agenwebsite' ),
                    'i18n_label_kecamatan'          => __( 'Kecamatan', 'agenwebsite' ),
                    'i18n_no_matches'               => __( 'Data tidak ditemukan', 'agenwebsite' ),
                    'i18n_required_text'            => __( 'required', 'agenwebsite' ),
                    'i18n_loading_data'             => __( 'Meminta data...', 'agenwebsite' ),
                    'wc_version'                    => self::get_woocommerce_version(),
                    'ajax_url'                      => self::ajax_url(),
                    'page'                          => self::get_page(),
                    '_wpnonce'                      => wp_create_nonce( $this->nonce )
				);
			break;
		}
	}

	/**
	 * Add setting link to plugin list table
	 *
	 * @access public
	 * @param  array $links Existing links
	 * @return array		Modified links
	 * @since 4.0.0
	 */
    public function add_settings_link( $links ){
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=tiki_shipping' ) . '">' . __( 'Settings', 'agenwebsite' ) . '</a>',
            '<a href="' . $this->url_docs . '" target="new">' . __( 'Docs', 'agenwebsite' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

	/**
	 * Notice to set license
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 */
    public function notice_set_license(){
        if( $this->is_page_to_notice() && ! $this->get_license_code() ){
            printf('<div class="updated notice_tiki_shipping woocommerce-tiki"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Kode lisensi tidak ada. Masukkan kode lisensi untuk mengaktifkan WooCommerce TIKI', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=tiki_shipping' ) . '" class="button-primary">' . __( 'Masukkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( $this->url_docs ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }
    }

	/**
	 * Check page to notice
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 */
    public function is_page_to_notice(){
        global $pagenow;
        $user = wp_get_current_user();
        $screen = get_current_screen();
        if( $pagenow == 'plugins.php' || $screen->id == "woocommerce_page_wc-settings" ){
            if( isset( $_GET['section'] ) && $_GET['section'] === 'tiki_shipping' ) return false;

            return true;
        }

        return false;
    }

	/**
	 * Check active shortcode
	 *
	 * @access public
	 * @return bool
	 * @since 4.0.0
	 */
    public function is_active_shortcode( $shortcode ){
        global $post;

        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode ) ){
            return true;
        }

        return false;
    }

	/**
	 * Get status weight
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 */
	public function get_status_weight(){
		$weight_unit = $this->get_woocommerce_weight_unit();
		$status = '';
		$status['unit']	= $weight_unit;
		if( $weight_unit == 'g' || $weight_unit == 'kg' ){
			$status['message'] = 'yes';
		}else{
			$status['message'] = 'error';
		}

		return $status;
	}

	/**
	 * Get license code
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 **/
	public function get_license_code(){
		return get_option( 'woocommerce_tiki_shipping_license_code' );
	}

	/**
	 * WooCommerce weight unit
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 **/
	public function get_woocommerce_weight_unit(){
		return get_option( 'woocommerce_weight_unit' );
	}

	/**
	 * Get nonce
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 */
	public function get_nonce(){
		return $this->nonce;
    }

    /**
     * Add shortcode to list in settings
     *
     * @access public
     * @param array $shortcodes
     * @param string $new_shortcode
     * @return array
     * @since 4.0.0
     */
    public function add_shortcode_list( $shortcodes, $new_shortcode, $desc ){

        $shortcode_copy = '<button type="button" class="copy-shortcode button-secondary" href="#" value="[' . $new_shortcode . ']" title="' . __( 'Copied!', 'woocommerce' ) . '">' . __( 'Copy Shortcode', 'agnwebsite'  ) . '</button>';

        $shortcode = array(
            $new_shortcode => array(
                'title'			=> sprintf( __( 'Shortcode : %s %s', 'agenwebsite' ), '['.$new_shortcode.']', $shortcode_copy ),
                'type'          => 'title',
                'description'	=> sprintf( __( 'Untuk menampilkan %s taruh <code>%s</code> di halaman atau post.', 'agenwebsite' ), $desc, '['.$new_shortcode.']' ),
                'default'		=> ''
            )
        );

        $output = array_merge( $shortcodes, $shortcode );

        return $output;
    }

    /**
	 * Get current page
	 *
	 * @access private
	 * @return string
	 * @since 4.0.0
	 **/
	private static function get_page(){
		// get billing or shipping
		$permalink = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$permalinks = explode( '/', $permalink );
		end($permalinks);
		$key = key( $permalinks );
		$currentPage = $permalinks[$key-1];

		if( is_cart() )
			$page = 'cart';
		elseif( is_checkout() )
			$page = 'checkout';
		elseif( $currentPage == 'billing' )
			$page = 'billing';
		elseif( $currentPage == 'shipping' )
			$page = 'shipping';
		else
			$page = '';

		return $page;
	}

    /**
	 * AJAX URL
	 *
	 * @access private
	 * @return string URL
	 * @since 4.0.0
	 **/
	private static function ajax_url(){
		return admin_url( 'admin-ajax.php' );
	}

    /**
	 * WooCommerce version
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 **/
	private function get_woocommerce_version(){
		 require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		 $data = get_plugins( '/' . plugin_basename( 'woocommerce' ) );
		 $version = explode('.',$data['woocommerce.php']['Version']);
		 return $data['woocommerce.php']['Version'];
	}

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 */
	public function plugin_url(){
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 */
	public function plugin_path(){
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

    /**
	 * Render help tip
	 *
	 * @access public
	 * @return HTML for the help tip image
	 * @since 4.0.0
	 **/
	public function help_tip( $tip, $float = 'none' ){
		return '<img class="help_tip" data-tip="' . $tip . '" src="' . $this->plugin_url() . '/assets/images/help.png" height="16" width="16" style="float:' . $float . ';" />';
	}

	/**
	 * Render link tip
	 *
	 * @access public
	 * @return HTML for the help tip link
	 * @since 4.0.0
	 **/
	public function link_tip( $tip, $text, $href, $target = NULL, $style = NULL ){
		return '<a href="' . $href . '" data-tip="' . $tip . '" target="' . $target . '" class="help_tip">' . $text . '</a>';
	}

	/**
	 * Check URL is exists
	 *
	 * @access public
	 * @return bool of the result response code
	 * @since 4.0.3
	 **/
	public function is_url_exists($url){
        $response = wp_remote_get($url);
        $response_code = wp_remote_retrieve_response_code($response);
        if(!empty($response_code) && $response_code == 200){
            return TRUE;
        }

        return FALSE;
	}
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Returns the main instance
	 *
	 * @since  4.0.0
	 * @return WooCommerce_TIKI
	 */
	function WC_TIKI(){
		return WooCommerce_TIKI::instance();
	}

    /*
     * Check JNE active
     *
     * @since 4.0.0
     * @return bool
     */
		if(!function_exists('jne_active')){
			function jne_active(){
	        if( function_exists( 'WC_JNE' ) ){
	            if( WC_JNE()->shipping->is_enable() ){
	                return TRUE;
	            }
	        }

	        return FALSE;
	    }
		}
    // Let's fucking rock n roll! Yeah!
	WooCommerce_TIKI::instance();

};

endif;
