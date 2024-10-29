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

if ( !class_exists( 'WC_TIKI' ) ) :

/**
 * Class WooCommerce TIKI
 *
 * @since 4.0.0
 **/
class WC_TIKI extends WC_Shipping_Method{
			
	/**
	 * Option name for save the settings
	 *
	 * @access private
	 * @var string
	 * @since 4.0.0
	 **/
	private $option_layanan;

	/**
	 * Notices
	 *
	 * @access private
	 * @var array
	 * @since 4.0.0
	 **/
	private $notice;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
	public function __construct(){
		$this->id                     = 'tiki_shipping';
		$this->method_title           = __('TIKI Shipping', 'agenwebsite');
		$this->method_description     = __( 'Plugin TIKI Shipping mengintegrasikan ongkos kirim dengan total belanja pelanggan Anda.', 'agenwebsite' );

		$this->option_layanan         = $this->plugin_id . $this->id . '_layanan';
        $this->option_license_code    = $this->plugin_id . $this->id . '_license_code';

        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( &$this, 'sanitize_fields' ) );
        add_filter( 'woocommerce_settings_api_form_fields_' . $this->id, array( &$this, 'set_form_fields' ) );

		$this->init();		
	}

	/**
	 * Init TIKI settings
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
	public function init(){
        //$this->load_default_options();
		// Load the settings API
		// Override the method to add TIKI Shipping settings
		$this->form_fields = WC_TIKI()->shipping->form_fields();
		// Loads settings you previously init.
		$this->init_settings();

		// Load default services options
		$this->load_default_services();
		
        if( get_option( 'woocommerce_tiki_shipping_license_code' ) ){
            // Define user set variables
            $this->enabled                    = ( array_key_exists( 'enabled', $this->settings ) ) ? $this->settings['enabled'] : '';
            $this->title                      = ( array_key_exists( 'title', $this->settings ) ) ? $this->settings['title'] : '';
            $this->default_weight             = ( array_key_exists( 'default_weight', $this->settings ) ) ? $this->settings['default_weight'] : '';
        }
		// Save settings in admin
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_tiki' ) );

	}

	/**
	 * Load default TIKI services
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 **/
	private function load_default_services(){

		$servives_options = get_option( $this->option_layanan );
		if( ! $servives_options ) {

			$data_to_save = WC_TIKI()->shipping->default_service();

			update_option( $this->option_layanan, $data_to_save );
		}
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 * @since 4.0.0
	 **/
	public function calculate_shipping( $package = array() ){

        if( ! $this->enabled ) return false;

        $layanan_tiki = get_option( $this->option_layanan );

        $country= WC()->customer->get_shipping_country();
        $state	= WC()->customer->get_shipping_state();
        $city	= WC()->customer->get_shipping_city();

        if( $country != 'ID' ) return false;

        $total_weight = $this->calculate_weight( $package['contents'] );
        $tiki_weight = WC_TIKI()->shipping->calculate_tiki_weight( $total_weight );
        $weight = $tiki_weight;

        $cost = $this->_get_costs( $state, $city, $weight );

        $totalamount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );

        if( empty( $cost ) ) return false;

        if( sizeof( $package ) == 0 ) return;

        foreach( $layanan_tiki as $service ){
            $service_id = $service['id'];
            $service_name = $service['name'];
            $service_enable = $service['enable'];
            $service_extra_cost = $service['extra_cost'];

            if( array_key_exists( $service_id, $cost) ){
                $tarif = $cost[$service_id]['harga'];
            }else{
                $tarif = 0;
            }

            if( ! empty($tarif) && $tarif != 0 && $service_enable == 1) {

                $tarif = $tarif * $weight;
                $label = $this->title .' '. $service_name .' ';
                $etd = '';
                $label = $this->set_label( $label, $etd, $service_id );

                if( ! empty( $service_extra_cost ) ) $tarif += $service_extra_cost;

                $rate = array(
                    'id'	=> $this->id . '_' . $service_id,
                    'label'	=> $label,
                    'cost'	=> $tarif
                );

                $this->add_rate( $rate );

            }

        }//end foreach layanan

    }

	/**
	 * Calculate Total Weight
	 * This function will calculated total weight for all product
	 *
	 * @access private
	 * @param mixed $products
	 * @return integer Total Weight in Kilograms
	 * @since 4.0.0
	 **/
	private function calculate_weight( $products ){
        $weight = 0;
        $weight_unit = WC_TIKI()->get_woocommerce_weight_unit();
        $default_weight = $this->default_weight;

        // Default weight TIKI settings is Kilogram
        // Change default weight settings to gram if woocommerce unit is gram
        if( $weight_unit == 'g' )
            $default_weight = $default_weight * 1000;

        foreach( $products as $item_id => $item ){
            $product = $item['data'];

            if( $product->is_downloadable() == false && $product->is_virtual() == false ) {
                $product_weight = $product->get_weight() ? $product->get_weight() : $default_weight;
                $product_weight = ( $product_weight == 0 ) ? $default_weight : $product_weight;

                $product_weight = $product_weight * $item['quantity'];

                // Change product weight to kilograms
                if ($weight_unit == 'g')
                    $product_weight = $product_weight / 1000;

                $weight += $product_weight;

            }
        }

        $weight = number_format((float)$weight, 2, '.', '');

        return $weight;
    }

    /**
     * Set Label
     *
     * @access private
     * @param string $label
     * @param string $etd
     * @param string $service_id
     * @return string
     * @since 4.0.0
     */
    private function set_label( $label, $etd, $service_id ){
        $etd = '';

        $new_label = sprintf( '%s%s', $label, $etd );

        return $new_label;
    }

    /**
     * Sanitize Fields
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.0
     */
    public function sanitize_fields( $sanitize_fields ){
        /*
         * replace option settings with sanitize fields
         */
        $sanitize_fields = array_replace( $this->settings, $sanitize_fields );

        $new_sanitize_fields = $sanitize_fields;
        $options = get_option( $this->plugin_id . $this->id .'_settings' );
        $options_backup = get_option( $this->plugin_id . $this->id . '_settings_backup' );

        /*
         * jika license code kosong maka kosongkan post sanitize
         * dan lakukan update option ke settings backup
         * settings backup berfungsi untuk mengembalikan option ke option utama
         * jika license code ada dan option utama kosong maka sanitize field diisi dengan option settings backup
         */
        if( empty( $sanitize_fields['license_code'] ) ){
            $new_sanitize_fields = '';
            if( is_array( $options ) && ! empty( $options ) ){
                update_option( $this->plugin_id . $this->id . '_settings_backup', $options );
            }
        }else{
            if( $options_backup ){
                if( ! $options || empty( $options ) ){
                    $new_sanitize_fields = $options_backup;
                }
            }
        }

        return $new_sanitize_fields;
    }
    
    /**
     * Set form fields
     * before show fields, check license code exists or not
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.0
     */
    public function set_form_fields( $form_fields ){

        if( get_option( $this->option_license_code ) ){

            $current_tab = empty( $_GET['tab_tiki'] ) ? 'general' : sanitize_title( $_GET['tab_tiki'] );

            $form_field = WC_TIKI()->shipping->get_form_fields();
            foreach( $form_field as $name => $data ){
                if( $name == $current_tab ){
                    $form_fields = $data['fields'];
                }
            }
        }
        return $form_fields;
    }

	/**
	 * Settings Tab
	 *
	 * @access private 
	 * @return HTML
	 * @since 4.0.0
	 */
    private function settings_tab(){

        $tabs = array();

        if( get_option( $this->option_license_code ) ){

            foreach( WC_TIKI()->shipping->get_form_fields() as $name => $data ){
                if( is_array($data['fields']) && count($data['fields']) > 0 ){
                    $tab[$name] = $data['label'];
                    $tabs = array_merge( $tabs, $tab );
                }
            }

        }

        $current_tab = empty( $_GET['tab_tiki'] ) ? 'general' : sanitize_title( $_GET['tab_tiki'] );

        $tab  = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

        foreach( $tabs as $name => $label ){
            $tab .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_tiki&tab_tiki=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
        }

        $tab .= '</h2>';

        return $tab;
    }

    /**
     * Validate license code
     * check license code to api
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.0
     */
    public function validate_license_code_field( $key ){
        $text = $this->get_option( $key );
        $field = $this->get_field_key( $key );

        if( isset( $_POST[ $field ] ) ){
            $text = wp_kses_post( trim( stripslashes( $_POST[ $field ] ) ) );   

            $valid_license = $this->validate_license_code( $text );

            update_option( $this->option_license_code, $valid_license );
        }

        return $valid_license;
    }

    /**
	 * Process admin TIKI shipping
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
	public function process_admin_tiki(){

		// If click button reset option
		if( isset( $_POST['reset_default'] ) && ! empty( $_POST['reset_default'] ) ){
			$default = $this->_reset_option();

			$save_layanan = $default['save_layanan'];
            $valid_license = '';

            update_option( $this->option_layanan, $save_layanan );
			update_option( $this->plugin_id . $this->id . '_settings', $default['save_settings'] );
		}

    }

	/**
	 * Reset option to default
	 * Fungsi untuk tombol reset option pada halaman setting tiki shipping
	 *
	 * @access private
	 * @return array
	 * @since 4.0.0
	 **/
	private function _reset_option(){				
		$tiki_settings = array();

		foreach( WC_TIKI()->shipping->form_fields() as $key => $value ){
			$tiki_settings[$key] = $value['default'];
		}

		$data['save_layanan'] = WC_TIKI()->shipping->default_service();
		$data['save_settings'] = $tiki_settings;

		return $data;
	}

	/**
	 * Validate license code
	 * Fungsi untuk tombol reset option pada halaman setting tiki shipping
	 *
	 * @access private
	 * @return array
	 * @since 4.0.0
	 **/
    private function validate_license_code( $code ){

        $saved_license = get_option( $this->option_license_code );

        if( empty( $code ) || $saved_license == $code ) return $code;

        WC_TIKI()->api->license_code = $code;
        $response = WC_TIKI()->api->remote_get( 'license_auth' );

        $this->notice['type'] = $response['status'];
        $this->notice['message'] = $response['message'];

        if( $response['status'] == 'error' ){
            $code = '';
        }

        add_action( 'tiki_admin_notices', array( &$this, 'notice' ) );

        return $code;
    }

	/**
	 * TIKI cost
	 * Mendapatkan harga dari api
	 *
	 * @access private
	 * @param string $state
	 * @param string $city
	 * @return array
	 * @since 4.0.0
	 **/
	private function _get_costs( $state, $city, $weight ){

        $tarif = 0;

		if( ! empty( $city ) ){

            // explode field city to kecamatan and kota
            // pattern is {kecamatan}, {kota}
            $explode_field_city = explode(', ', $city);

            $params = array(
                'provinsi'  => $this->_get_provinsi_name( $state ),
                'kecamatan' => trim($explode_field_city[0]),
                'kota'      => $explode_field_city[1],
                'berat'     => $weight
            );

            // get data from API
            $response = WC_TIKI()->api->remote_get( 'tarif', $params );

            // validate response status
            if( $response['status'] != 'error' ){

                $tarif = $response['result']['tarif'];

            }

		}

        return $tarif;

	}
	
	/**
	 * Get nama provinsi Indonesia
	 * Mendapatkan nama provinsi berdasarkan id provinsi dari woocommerce
	 *
	 * @access private
	 * @param string $id of provinsi
	 * @return string
	 * @since 4.0.0
	 **/
	private function _get_provinsi_name( $id ){		
		$provinsi = '';
		$states = WC()->countries->get_states( 'ID' );

		foreach( $states as $id_provinsi => $nama_provinsi ){
			if( $id_provinsi == $id ){
				$provinsi = $nama_provinsi;
			}
		}

		return $provinsi;
	}

	/**
	 * Notice
	 *
	 * @access private 
	 * @return HTML
	 * @since 4.0.0
	 */
    public function notice(){
        $type = ( $this->notice['type'] == 'error' ) ? 'error' : 'updated';
        echo '<div class="' . $type . '"><p><strong>' . $this->notice['message'] . '</strong></p></div>';
    }

	/**
	 * Admin Options
	 * Setup the gateway settings screen.
	 *
	 * @access public
	 * @return HTML of the admin tiki settings
	 * @since 4.0.0
	 */
	public function admin_options() {
        $class = empty( $_GET['tab_tiki'] ) ? 'general' : sanitize_title( $_GET['tab_tiki'] );

		$html  = '<div id="agenwebsite_woocommerce_tiki" class="' . $class . '">' . "\n";

            $html .= sprintf( '<h3>%s %s</h3>', $this->method_title, __( 'Settings', 'agenwebsite' ) ) . "\n";
            $html .= '<p>' . $this->method_description . '</p>' . "\n";			

            $html .= '<div class="banner">';
            ob_start();
            $this->generate_banner_html();
            $html .= ob_get_clean();
            $html .= '</div>';

            $html .= '<div class="agenwebsite_shipping_settings">';

                $html .= $this->settings_tab();

                $html .= '<div id="agenwebsite_notif">';
                ob_start();
                do_action( 'tiki_admin_notices' );
                $html .= ob_get_clean();
                $html .= '</div>';

                $html .= '<table class="form-table hide-data">' . "\n";

                    ob_start();
                    $this->generate_settings_html();
                    $html .= ob_get_clean();

                $html .= '</table>' . "\n";

            $html .= '</div>';

			// AW head logo and links and table status
			ob_start();
			$this->aw_head();
			$html .= ob_get_clean();

		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * AgenWebsite Head
	 *
	 * @access private static
	 * @return HTML for the admin logo branding and usefull links.
	 * @since 4.0.0
	*/
	private function aw_head(){			
		$html  = '<div class="agenwebsite_head">';
		$html .= '<div class="logo">' . "\n";
		$html .= '<a href="' . esc_url( 'http://agenwebsite.com/' ) . '" target="_blank"><img id="logo" src="' . esc_url( apply_filters( 'aw_logo', WC_TIKI()->plugin_url() . '/assets/images/logo.png' ) ) . '" /></a>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<ul class="useful-links">' . "\n";
			$html .= '<li class="documentation"><a href="' . esc_url( WC_TIKI()->url_docs ) . '" target="_blank">' . __( 'Dokumentasi', 'agenwebsite' ) . '</a></li>' . "\n";
			$html .= '<li class="support"><a href="' . esc_url( WC_TIKI()->url_support ) . '" target="_blank">' . __( 'Bantuan', 'agenwebsite' ) . '</a></li>' . "\n";
		$html .= '</ul>' . "\n";

        if( WC_TIKI()->get_license_code() != '' ){
            ob_start();
            include_once( WC_TIKI()->plugin_path() . '/views/html-admin-tiki-settings-status.php' );
            $html .= ob_get_clean();
        }

		$html .= '</div>';
		echo $html;
	}

    public function generate_banner_html(){
        $img_src = WC_TIKI()->plugin_url() . '/assets/images/upgrade-728x90.png';
        ?>
        <a href="http://agenwebsite.com/products/woocommerce-tiki-shipping" title="Upgrade Plugin" target="_blank"><img src="<?php echo $img_src;?>" width="728px" height="90px" /></a>
        <?php
    }

    /**
	 * Field type license_code
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 **/
    public function generate_license_code_html(){
        $license_code = get_option( $this->option_license_code );
        $html = '';
        if( ! $license_code && empty( $license_code ) ){
            $html .= sprintf('<div class="notice_wc_tiki woocommerce-tiki"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Masukkan kode lisensi untuk mengaktifkan WooCommerce TIKI', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="http://www.agenwebsite.com/?add-to-cart=404" target="new" class="button-primary">' . __( 'Dapatkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( WC_TIKI()->url_docs ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }

        $html .= '<tr valid="top">';
            $html .= '<th scope="row" class="titledesc">';
                $html .= '<label for="' . $this->option_license_code . '">' . __( 'Kode Lisensi', 'agenwebsite' ) . '</label>';
            $html .= '</th>';
            $html .= '<td class="forminp">';
                $html .= '<fieldset>';
                    $html .= '<legend class="screen-reader-text"><span>' . __( 'Kode Lisensi', 'agenwebsite' ) . '</span></legend>';
                    $html .= '<input class="input-text regular-input " type="text" name="' . $this->option_license_code . '" id="' . $this->option_license_code . '" style="" value="' . esc_attr( get_option( $this->option_license_code ) ) . '" placeholder="' . __( 'Kode Lisensi', 'agenwebsite' ) . '">';
                    $html .= '<p class="description">' . __( 'Masukkan kode lisensi yang kamu dapatkan dari halaman akun agenwebsite. ', 'agenwebsite' ) . '</p>';
                $html .= '</fieldset>';
            $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

	/**
	 * Field type tiki_service
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 **/
	public function generate_tiki_service_html(){
		$html = '<tr valign="top" class="premium-version">';
			$html .= '<th scope="row" class="titledesc">' . __( 'Layanan TIKI', 'agenwebsite' ) . ' <span>' . __('Fitur Premium', 'agenwebsite') . '</span></th>';
			$html .= '<td class="forminp">';
				$html .= '<table class="widefat wc_input_table sortable" cellspacing="0">';
					$html .= '<thead>';
						$html .= '<tr>';
							$html .= '<th class="sort">&nbsp;</th>';
							$html .= '<th>Nama Pengiriman ' . WC_TIKI()->help_tip( 'Metode pengiriman yang digunakan.' ) . '</th>';
							$html .= '<th>Tambahan Biaya ' . WC_TIKI()->help_tip( 'Biaya tambahan, bisa disetting untuk tambahan biaya packing dan lain-lain.' ) . '</th>';
							$html .= '<th style="width:14%;text-align:center;">Aktifkan</th>';
						$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';

						$i = 0;
						foreach( get_option( $this->option_layanan ) as $service ) :

							$html .= '<tr class="service">';
								$html .= '<td class="sort"></td>';
								$html .= '<td><input type="text" value="' . $service['name'] . '" name="service_name[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td><input type="number" value="' . $service['extra_cost'] . '" name="service_extra_cost[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td style="text-align:center;"><input type="checkbox" value="1" ' . checked( $service['enable'], 1, FALSE ) . ' name="service_enable[' . $i . '][' . $service['id'] . ']" /><input type="hidden" value="' . $service['id'] . '" name="service_id[' . $i . ']" /></td>';
							$html .= '</tr>';

							$i++;
						endforeach;

					$html .= '</tbody>';
				$html .= '</table>';
			$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}

	/**
	 * Field type button
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 **/
    public function generate_button_html( $key, $data ){

        $field = $this->get_field_key( $key );
        $defaults = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array()
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php esc_attr( $field );?>"><?php echo wp_kses_post( $data['label'] );?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] );?></span></legend>
                        <button type="submit" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $field ); ?>" class="button <?php echo esc_attr( $data['class'] );?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data );?>><?php echo wp_kses_post( $data['placeholder'] );?></button>
                        <?php echo $this->get_description_html( $data ); ?>
                    </fieldset>
                </td>
            </tr>
        <?php
        return ob_get_clean();

    }
		
}

endif;