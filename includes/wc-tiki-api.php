<?php
/**
 * Main API Handles
 *
 * Handles for API AgenWebsite
 *
 * @author AgenWebsite
 * @package WooCommerce API Shipping
 * @since 4.0.0
 */

if ( !defined( 'WOOCOMMERCE_TIKI' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WC_TIKI_API' ) ):

class WC_TIKI_API{
	
	/* @var array */
	private $data;    
    
    /**
     * Constructor
     *
     * @return void
     * @since 4.0.0
     */
    public function __construct( $product, $license_code ){
        $this->product = $product;
        $this->license_code = $license_code;
        $this->url = 'http://api.agenwebsite.com';
        $this->is_get_message = TRUE;
    }
    
    /**
     * Magic Setter
     *
     * @return void
     * @since 4.0.0
     */
    public function __set( $name, $value ){
        $this->data[$name] = $value;
    }
    
    /**
     * Magic Getter
     *
     * @return mixed
     * @since 4.0.0
     */
    public function __get( $name ){
        if( array_key_exists($name, $this->data) ){
            return $this->data[$name];
        }
    }
        
    /**
     * Get URL of API AgenWebsite
     *
     * @access public
     * @param string $method 'license_auth'
     * @param array $param
     * @return string $uri
     * @since 4.0.0
     */
    public function get_uri( $method, $param = array() ){
        
        $uri = $this->url;
        
        switch( $method ){
            case 'license_auth':
                $uri .= '/license/auth/';
            break;
            case 'license_status':
                $uri .= '/license/status/';
            break;
            case 'kota':
                $uri .= '/tiki/kota/';
            break;
            case 'tarif':
                $uri .= '/tiki/tarif/';
            break;
        }
        
        $uri .= $this->build_param( $param );
        
        return $uri;
    }
    
    /**
     * Build param API from array
     *
     * @access private
     * @param array $param
     * @return string $param
     * @since 4.0.0
     */
    private function build_param( $args = array() ){
        $param = '';
        
        $args['license'] = $this->license_code;
        $args['product'] = $this->product;
        
        $i = 0;
        foreach( $args as $name => $value ){
            $param .= ( $i == 0 ) ? '?' : '';
            $param .= $name .'='. rawurlencode($value);
            $param .= ( ++$i == count($args) ) ? '' : '&';
        }

        return $param;
    }

    /**
     * Remote get
     *
     * @access public
     * @return array $result
     * @since 4.0.0
     */
    public function remote_get( $method, $param = array() ){

        if( $this->check_license() ){

            if( $method == 'kota' || $method == 'tarif' )
                $this->is_get_message = false;

            $this->response = wp_remote_get( $this->get_uri( $method, $param ), array( 'timeout' => 20 ) );

            $this->process_result();
   
        }

        return $this->result;        
    }

    /**
     * Check license empty or not
     *
     * @access public
     * @return array $result
     * @since 4.0.0
     */
    public function check_license(){
        if( $this->license_code == '' ){
            $result['status'] = 'error';
            $result['message'] = __( 'Kode Lisensi belum diisi.', 'agenwebsite' );
            $result['result'] = '';
            
            $this->result = $result;
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Process result
     *
     * @access public
     * @return void
     * @since 4.0.0
     */
    public function process_result(){

        $cant_connect = false;
        
        if( ! is_wp_error( $this->response ) ){

            $body = json_decode( $this->response['body'], TRUE );

            $result['status'] = $body['status'];

            if( !empty($result['status']) ){
                if( $body['status'] == 'success' ){

                    if( $this->is_get_message )
                        $result['message'] = $body['data']['message'];

                    $result['result'] = $body['data'];

                }else{

                    if( $this->is_get_message )
                        $result['message'] = $body['message'];

                    $result['result'] = '';

                }

            }else{

                $cant_connect = true;

            }

        }else{
            
            $cant_connect = true;            
            
        }
        
        if($cant_connect){
            $result['status'] = 'error';
            $result['message'] = __( 'Gagal terhubung dengan AgenWebsite', 'agenwebsite' );
            $result['result'] = '';
        }

        $this->result = $result;
    }

	/**
	 * Convert date
	 *
	 * @access public
	 * @param string $date
	 * @param string $format
	 * @return string
	 * @since 4.0.0
	 **/
    public function convert_date( $date, $format ){
        return date( $format, strtotime( $date ) );
    }

}
	
endif;
