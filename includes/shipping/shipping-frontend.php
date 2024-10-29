<?php
/**
 * Frontend TIKI Shipping
 *
 * Handles for the request to frontend
 *
 * @author AgenWebsite
 * @package WooCommerce TIKI Shipping
 * @since 4.0.0
 */

if ( !defined( 'WOOCOMMERCE_TIKI' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_TIKI_Frontend' ) ) :
	
/**
 * Class WooCommerce TIKI Frontend
 *
 * @since 4.0.0
 **/
class WC_TIKI_Frontend{

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 4.0.0
	 **/
	public function __construct(){
		// TIKI reorder fields option
		add_filter( 'woocommerce_checkout_fields', array( &$this, 'tiki_reorder_fields' ), 15 );

		// TIKI reorder billing address
		add_filter( 'woocommerce_billing_fields', array( &$this, 'TIKI_reorder_billing_fields' ), 15 );

		// TIKI reorder shipping address
		add_filter( 'woocommerce_shipping_fields', array( &$this, 'TIKI_reorder_shipping_fields' ), 15 );

		// Enable city shipping calculator
		add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true' );
		
		// Show total weight in review order table
		add_action( 'woocommerce_review_order_before_shipping', array( &$this, 'show_total_weight' ) );
        
	}
    
	/**
	 * Add total weight to review order table
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 */	
	public function show_total_weight(){
        $total_weight = WC_TIKI()->shipping->get_total_weight_checkout();
        if( ( array_key_exists( 'virtual', $total_weight ) && $total_weight['virtual'] !== 'yes' ) || $total_weight['weight'] !== 0 ){
            echo '<tr>' . "\n";
            echo '<th>' . __( 'Total weight', 'agenwebsite' ) . '</th>' . "\n";
            echo '<td>' . $total_weight['weight'] . apply_filters( 'weight_unit_total_weight', WC_TIKI()->get_woocommerce_weight_unit() ) .'</td>' . "\n";
            echo '</tr>' . "\n";
        }
	}
				
	/**
	 * TIKI reorder fields option billing
	 *
	 * @access public
	 * @param array $fields
	 * @return array
	 * @since 4.0.0
	 **/
	public function TIKI_reorder_billing_fields($fields) {
        $AW_fields['billing_country']               = $fields['billing_country'];
        $AW_fields['billing_first_name']            = $fields['billing_first_name'];
        $AW_fields['billing_last_name']             = $fields['billing_last_name'];
        $AW_fields['billing_company']               = $fields['billing_company'];
        $AW_fields['billing_address_1']             = $fields['billing_address_1'];
        $AW_fields['billing_address_2']             = $fields['billing_address_2'];
        $AW_fields['billing_state']                 = $fields['billing_state'];
        $AW_fields['billing_postcode']              = $fields['billing_postcode'];

        // Tambah custom field kota untuk billing field
        $AW_fields['billing_kota']					= array();
        $AW_fields['billing_kota']['type']			= 'select';
        $AW_fields['billing_kota']['label']			= __( 'Kota / Kabupaten', 'agenwebsite' );
        $AW_fields['billing_kota']['class']			= array( 'form-row-wide','address-field', 'update_totals_on_change' );
        $AW_fields['billing_kota']['options']		= array( '' => '' );			
        $AW_fields['billing_kota']['required']		= TRUE;
        $AW_fields['billing_kota']['placeholder']	= __( 'Pilih Kota / Kabupaten', 'agenwebsite' );

        $AW_fields['billing_city']					= $fields['billing_city'];
        $AW_fields['billing_city']['class']			= array( 'form-row-wide', 'address-field', 'update_totals_on_change' );

        $AW_fields['billing_email'] 				= $fields['billing_email'];
        $AW_fields['billing_phone'] 				= $fields['billing_phone'];

        return $AW_fields;
    }

	/**
	 * TIKI reorder fields option shipping
	 *
	 * @access public
	 * @param array $fields
	 * @return array
	 * @since 4.0.0
	 **/
	public function TIKI_reorder_shipping_fields($fields) {
		$AW_fields['shipping_country']				= $fields['shipping_country'];
		$AW_fields['shipping_first_name'] 			= $fields['shipping_first_name'];
		$AW_fields['shipping_last_name']			= $fields['shipping_last_name'];
		$AW_fields['shipping_company']				= $fields['shipping_company'];
		$AW_fields['shipping_address_1']			= $fields['shipping_address_1'];
		$AW_fields['shipping_address_2']			= $fields['shipping_address_2'];
		$AW_fields['shipping_state']				= $fields['shipping_state'];
		$AW_fields['shipping_postcode']				= $fields['shipping_postcode'];
		
		// Tambah custom field kota untuk shipping field
        $AW_fields['shipping_kota']                 = array();
        $AW_fields['shipping_kota']['type']         = 'select';
        $AW_fields['shipping_kota']['label']        = __( 'Kota', 'agenwebsite' );
        $AW_fields['shipping_kota']['class']        = array( 'form-row-wide','address-field' );
        $AW_fields['shipping_kota']['options']      = array( '0' => 'Pilih Kota' );
        $AW_fields['shipping_kota']['required']     = TRUE;
        $AW_fields['shipping_kota']['placeholder']  = __( 'Pilih Kota', 'agenwebsite' );

        $AW_fields['shipping_city']                 = $fields['shipping_city'];
        $AW_fields['shipping_city']['class']        = array( 'form-row-wide','address-field', 'update_totals_on_change' );
		
		return $AW_fields;
	}

	/**
	 * TIKI reorder fields option
	 *
	 * @access public
	 * @param array $fields
	 * @return array
	 * @since 4.0.0
	 **/
	public function tiki_reorder_fields($fields) {
        $AW_fields['billing']['billing_country']                = $fields['billing']['billing_country'];
        $AW_fields['billing']['billing_first_name']             = $fields['billing']['billing_first_name'];
        $AW_fields['billing']['billing_last_name']              = $fields['billing']['billing_last_name'];
        $AW_fields['billing']['billing_company']                = $fields['billing']['billing_company'];
        $AW_fields['billing']['billing_address_1']              = $fields['billing']['billing_address_1'];
        $AW_fields['billing']['billing_address_2']              = $fields['billing']['billing_address_2'];
        $AW_fields['billing']['billing_state']                  = $fields['billing']['billing_state'];
        $AW_fields['billing']['billing_postcode']               = $fields['billing']['billing_postcode'];

        // Tambah custom field kota untuk billing field
        $AW_fields['billing']['billing_kota']                   = array();
        $AW_fields['billing']['billing_kota']['type']           = 'select';
        $AW_fields['billing']['billing_kota']['label']          = __( 'Kota / Kabupaten', 'agenwebsite' );
        $AW_fields['billing']['billing_kota']['class']          = array( 'form-row-wide','address-field', 'update_totals_on_change' );
        $AW_fields['billing']['billing_kota']['options']        = array( '' => '' );			
        $AW_fields['billing']['billing_kota']['required']       = TRUE;
        $AW_fields['billing']['billing_kota']['placeholder']    = __( 'Pilih Kota / Kabupaten', 'agenwebsite' );

        $AW_fields['billing']['billing_city']                   = $fields['billing']['billing_city'];
        $AW_fields['billing']['billing_city']['class']          = array( 'form-row-wide', 'address-field', 'update_totals_on_change' );

        $AW_fields['billing']['billing_email']                  = $fields['billing']['billing_email'];
        $AW_fields['billing']['billing_phone']                  = $fields['billing']['billing_phone'];

        $AW_fields['shipping']['shipping_country']              = $fields['shipping']['shipping_country'];
        $AW_fields['shipping']['shipping_first_name']           = $fields['shipping']['shipping_first_name'];
        $AW_fields['shipping']['shipping_last_name']            = $fields['shipping']['shipping_last_name'];
        $AW_fields['shipping']['shipping_company']              = $fields['shipping']['shipping_company'];
        $AW_fields['shipping']['shipping_address_1']            = $fields['shipping']['shipping_address_1'];
        $AW_fields['shipping']['shipping_address_2']            = $fields['shipping']['shipping_address_2'];
        $AW_fields['shipping']['shipping_state']                = $fields['shipping']['shipping_state'];
        $AW_fields['shipping']['shipping_postcode']             = $fields['shipping']['shipping_postcode'];

        // Tambah custom field kota untuk shipping field
        $AW_fields['shipping']['shipping_kota']                 = array();
        $AW_fields['shipping']['shipping_kota']['type']         = 'select';
        $AW_fields['shipping']['shipping_kota']['label']        = __( 'Kota', 'agenwebsite' );
        $AW_fields['shipping']['shipping_kota']['class']        = array( 'form-row-wide','address-field' );
        $AW_fields['shipping']['shipping_kota']['options']      = array( '0' => 'Pilih Kota' );
        $AW_fields['shipping']['shipping_kota']['required']     = TRUE;
        $AW_fields['shipping']['shipping_kota']['placeholder']  = __( 'Pilih Kota', 'agenwebsite' );

        $AW_fields['shipping']['shipping_city']                 = $fields['shipping']['shipping_city'];
        $AW_fields['shipping']['shipping_city']['class']        = array( 'form-row-wide','address-field', 'update_totals_on_change' );

        $AW_fields['account'] 	= $fields['account'];
        $AW_fields['order'] 	= $fields['order'];

        return $AW_fields;
	}
		
}
	
endif;
