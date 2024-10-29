<?php
/**
 * WooCommerce TIKI Shipping
 *
 * Main file for the calculation and settings shipping
 *
 * @author AgenWebsite
 * @package WooCommerce TIKI Shipping
 * @since 4.0.0
 */

if ( !defined( 'WOOCOMMERCE_TIKI' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_TIKI_Shipping' ) ) :

/**
 * Class WooCommerce TIKI
 *
 * @since 4.0.0
 **/
class WC_TIKI_Shipping{

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 4.0.0
	 **/
	public function __construct(){
        /**
         * Initialise TIKI shipping method.
         *
         * @since 4.0.0
         **/
        add_action( 'woocommerce_shipping_init', array( &$this, 'shipping_method' ) );

        /**
         * Add Shipping Method
         *
         * Tell method TIKI shipping to woocommerce. Hey Woo AgenWebsite TIKI is Here !! :D
         *
         * @since 4.0.0
         **/
        add_filter( 'woocommerce_shipping_methods', array( &$this, 'add_tiki_shipping_method' ) );

        // filter default chosen shipping
        add_filter( 'woocommerce_shipping_chosen_method', array( &$this, 'get_default_method' ), 10, 2 );

        // Release the frontend
        if( $this->is_enable() && ! jne_active() ){
            new WC_TIKI_Frontend();
        }

    }

    /**
	 * Init Shipping method
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
	public function shipping_method(){
		include_once( 'shipping-method.php' );	
	}

	/**
	 * Add TIKI shipping method
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
	public function add_tiki_shipping_method( $methods ) {
		$methods[] = 'WC_TIKI';
		return $methods;	
	}

	/**
	 * Get the default method
	 * @param  array  $available_methods
	 * @param  boolean $current_chosen_method
	 * @return string
     * @since 4.0.02
	 */
	public function get_default_method( $default_method, $available_methods ) {
		$selection_priority = get_option( 'woocommerce_shipping_method_selection_priority', array() );

		if ( ! empty( $available_methods ) ) {

			// Is a method already chosen?
			if ( ! empty( $current_chosen_method ) && ! isset( $available_methods[ $current_chosen_method ] ) ) {
				foreach ( $available_methods as $method_id => $method ) {
					if ( strpos( $method->id, $current_chosen_method ) === 0 ) {
						return $method->id;
					}
				}
			}

			// Order by priorities and costs
			$prioritized_methods = array();

			foreach ( $available_methods as $method_id => $method ) {
				$priority = isset( $selection_priority[ $method_id ] ) ? absint( $selection_priority[ $method_id ] ) : 1;
				if ( empty( $prioritized_methods[ $priority ] ) ) {
					$prioritized_methods[ $priority ] = array();
				}
				$prioritized_methods[ $priority ][ $method_id ] = $method->cost;
			}

			$prioritized_methods = current( $prioritized_methods );

			return current( array_keys( $prioritized_methods ) );
		}

		return false;
	}

	/**
	 * Get total weight
	 *
	 * @access public
	 * @return integer Total weight
	 * @since 4.0.0
	 **/
	public function get_total_weight_checkout(){
        $settings = get_option( 'woocommerce_tiki_shipping_settings' );
        $default_weight = $settings['default_weight'];
        $weight = 0;
        $weight_unit = WC_TIKI()->get_woocommerce_weight_unit();

        foreach ( WC()->cart->cart_contents as $cart_item_key => $values ) {
            $_product = $values['data'];
            if( $_product->is_downloadable() == false && $_product->is_virtual() == false ) {
                $_product_weight = $_product->get_weight();

                if( $_product_weight == '' ){
                    if( $weight_unit == 'g' ){
                        $default_weight *= 1000;
                    }
                    $_product_weight = $default_weight;   
                }

                $weight += $_product_weight * $values['quantity'];

                $output['virtual'] = 'yes';
            }
        }

        if( $weight_unit == 'g' ){
            if( $weight > 1000 ){
                $weight = $weight / 1000;
                $weight = number_format((float)$weight, 2, '.', '');
                add_filter( 'weight_unit_total_weight', array( &$this, 'change_to_kg' ) );
            }
        }

        $output['weight'] = $weight;

        return $output;
    }

	/**
	 * Change to kilograms
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 **/
	public function change_to_kg(){
        return 'kg';
    }

	/**
	 * Return the number of decimals after the decimal point.
	 *
	 * @access public
	 * @return int
	 * @since 8.0.1
	 **/
	public function get_price_decimals(){
		if( function_exists( 'wc_get_price_decimals' ) )
            return wc_get_price_decimals();
        else
            return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
	}

	/**
	 * Check plugin is active
	 *
	 * @access public
	 * @return bool
	 * @since 4.0.0
	 **/
	public function is_enable(){
		$settings = get_option( 'woocommerce_tiki_shipping_settings' );
        if( $settings && is_array( $settings ) ){
            if( ! array_key_exists( 'enabled', $settings ) ) return false;

            return ( $settings['enabled'] == 'yes' ) ? TRUE : FALSE;
        }

        return false;
	}

	/**
	 * Calculate TIKI Weight
	 *
	 * @access public
	 * @param integer $weight
	 * @return integer Total Weight in Kilograms
	 * @since 4.0.0
	 **/
	public function calculate_tiki_weight( $weight ){
        if( WC_TIKI_Shipping::is_decimal( $weight ) ){
            $desimal = explode( '.', $weight );
            $tiki_weight = ( $desimal[0] == 0 || substr($desimal[1], 0, 1) > 0 || substr($desimal[1], 0, 2) > 9) ? ceil($weight) : floor($weight);
            $weight = ( $tiki_weight == 0 ) ? 1 : $tiki_weight;
        }

        return $weight;
	}

	/**
	 * Is Decimal
	 * For check the number is decimal.
	 *
	 * @access public
	 * @param integer
	 * @return bool
	 * @since 4.0.0
	 **/
	private static function is_decimal( $num ){
		return is_numeric( $num ) && floor( $num ) != $num;
	}

	/**
	 * Shipping service option default
	 *
	 * @access public
	 * @return array
	 * @since 4.0.0
	 **/
	public function default_service(){
		return array(
			array(
                'id'        => 'eco',
                'enable'    => 0,
                'name'      => 'ECO',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'reg',
                'enable'    => 1,
                'name'      => 'REG',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'ons',
                'enable'    => 0,
                'name'      => 'ONS',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'hds',
                'enable'    => 0,
                'name'      => 'HDS',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'sds',
                'enable'    => 0,
                'name'      => 'SDS',
                'extra_cost'=> 0
			)
		);			
	}

    /**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 4.0.0
	 **/
	public function form_fields(){
        $form_fields = array(
            'license_code'  => array(
                'type'          => 'license_code',
                'default'       => '',
            )
        );

        return apply_filters( 'woocommerce_tiki_form_fields_settings', $form_fields );
    }

	/**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 4.0.0
	 **/
	public function get_form_fields(){
		$form_fields = array(
            'general'   => array(
                'label' => __( 'General', 'agenwebsite' ),
                'fields'    => array(
                    'enabled' => array(
                        'title'         => __( 'Aktifkan TIKI Shipping', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan WooCommerce TIKI Shipping', 'agenwebsite' ), 
                        'default'       => 'no',
                    ), 
                    'title' => array(
                        'title'         => __( 'Label', 'agenwebsite' ), 
                        'description' 	=> __( 'Ubah label untuk fitur pengiriman kamu.', 'agenwebsite' ),
                        'type'          => 'text',
                        'default'       => __( 'TIKI Shipping', 'agenwebsite' ),
                    ),
                    'default_weight' => array(
                        'title'         => __( 'Berat default ( kg )', 'agenwebsite' ), 
                        'description' 	=> __( 'Otomatis setting berat produk jika kamu tidak setting pada masing-masing produk.', 'agenwebsite' ),
                        'type'          => 'number',
                        'custom_attributes' => array(
                            'step'	=>	'any',
                            'min'	=> '0'
                        ),
                        'placeholder'	=> '0.00',
                        'default'		=> '1',
                    ),
                    'license_code'  => array(
                        'type'          => 'license_code',
                        'default'       => '',
                    ),
                    'tiki_service' => array(
                        'type'          => 'tiki_service',
                        'default'		=> 'yes',
                    ),
                )
            ),
            'shortcodes' => array(
                'label' => __( 'Shortcodes', 'agenwebsite' ),
                'fields'=> apply_filters( 'woocommerce_tiki_shortcodes', array())
            ),
        );

        return $form_fields;
	}

}

endif;
