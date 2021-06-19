<?php
/*
Plugin Name: Brave Plugin - WC Featured First
Description: Displays Featured First
Version: 1.9.1
Author: Jason White
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$thisplugin = 'brave-plugin-wc-featured-first';
$thispluginowner = 'ijasonwhite';

if (!function_exists('brave_get_featured_product_ids')) {
    function brave_get_featured_product_ids() {

        // Load from cache.
        $featured_product_ids = get_transient( 'brave_featured_products_ids' );

        // Valid cache found.
        if ( false !== $featured_product_ids ) {
            return apply_filters( 'brave_featured_products_ids_sort', $featured_product_ids );
        }

        $product_visibility_term_ids = wc_get_product_visibility_term_ids();
        $featured_products_ids = get_posts(
            array(
                'post_type'      => array( 'product', 'product_variation' ),
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    array(
                        'taxonomy' => 'product_visibility',
                        'field'    => 'term_taxonomy_id',
                        'terms'    => array( $product_visibility_term_ids['featured'] ),
                    ),
                ),
                'fields'         => 'ids',
            )
        );

        $featured_products_ids = array_reverse( $featured_products_ids );
        $filtered_featured_products_ids = apply_filters( 'brave_featured_products_ids', $featured_products_ids );

        set_transient( 'brave_featured_products_ids', $filtered_featured_products_ids, DAY_IN_SECONDS * 30 );


        return apply_filters( 'brave_featured_products_ids_sort', $filtered_featured_products_ids );
    }
}




/**
 * Admin class
 */
class brave_featured_products_wc_admin {

    /**
     * Class constructor
     */
    public function __construct() {


        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'woocommerce_settings_tabs_array' ), 90 );
        add_action( 'woocommerce_settings_tabs_featured_products_first', array( $this, 'woocommerce_settings_tabs_featured_products_first' ) );
        add_action( 'woocommerce_update_options_featured_products_first', array( $this, 'woocommerce_update_options_featured_products_first' )  );

        add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts' ) );
    }


    public function woocommerce_settings_tabs_array( $settings_tabs ) {
        $settings_tabs['featured_products_first'] = __( 'Brave Featured First for Woo', 'brave-plugin-wc-featured-first' );
        return $settings_tabs;
    }


    public function woocommerce_settings_tabs_featured_products_first() {
        woocommerce_admin_fields( $this->get_settings() );
    }


    public function woocommerce_update_options_featured_products_first() {
        woocommerce_update_options( $this->get_settings() );
    }


    public function get_settings() {
        /**
         * Check the current section is what we want
         **/

        if ( empty($current_section) || 'general' == $current_section ) {
  
            $settings[] = array(
                'title' => __( 'Brave Featured First for Woo', 'brave-plugin-wc-featured-first' ),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'brave_options',
            );

            $settings[] = array(
                'title'         => esc_html__( 'Enable Featured Products First on Page', 'brave-plugin-wc-featured-first' ),
                'desc'          => esc_html__( 'Shop Page', 'brave-plugin-wc-featured-first' ),
                'id'            => 'brave_woocommerce_featured_first_enabled_on_shop',
                'default'       => 'yes',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
            );
            $settings[] = array(
                'title'         => esc_html__( 'Enable Featured Products First on Page', 'brave-plugin-wc-featured-first' ),
                'desc'          => esc_html__( 'Product Search Page', 'brave-plugin-wc-featured-first' ),
                'id'            => 'brave_woocommerce_featured_first_enabled_on_search',
                'default'       => 'yes',
                'type'          => 'checkbox',
                'checkboxgroup' => 'middle',
            );
            $settings[] = array(
                'title'         => esc_html__( 'Enable Featured Products First on Page', 'brave-plugin-wc-featured-first' ),
                'desc'          => esc_html__( 'Archive Product Category Page', 'brave-plugin-wc-featured-first' ),
                'id'            => 'brave_woocommerce_featured_first_enabled_on_archive',
                'default'       => 'yes',
                'type'          => 'checkbox',
                'checkboxgroup' => 'middle',
            );
            $settings[] = array(
                'title'             => esc_html__( 'Enable Featured Products First on Page', 'brave-plugin-wc-featured-first' ),
                'desc'              => esc_html__( 'Admin Dashboard Product Listing Page', 'brave-plugin-wc-featured-first' ) ,
                'id'                => 'brave_woocommerce_featured_first_enabled_on_admin',
                'default'           => 'yes',
                'type'              => 'checkbox',
                'checkboxgroup'     => 'end',
                'custom_attributes' => '',
            );

            $settings[] = array(
                'title'             => esc_html__( 'Place no. of Brave Featured First for Woo', 'brave-plugin-wc-featured-first' ),
                'desc'              => '<br/>' . esc_html__( '0 = Unlimited', 'brave-plugin-wc-featured-first' ),
                'id'                => 'brave_woocommerce_no_of_featured_product_first',
                'default'           => '',
                'type'              => 'number',
                'custom_attributes' => '',
            );
            $settings[] = array(
                'type' => 'sectionend',
                'id'   => 'brave_options',
            );
        }
        return  $settings;
    }


    public function admin_enqueue_scripts( $hook ) {
        if ( 'woocommerce_page_wc-settings' != $hook ) {
            return;
        }


    }
}

$GLOBALS['brave_admin'] = new brave_featured_products_wc_admin();



/**
 * Main plugin class
 */
class brave_featured_products_wc
{
    /**
     * Class constructor
     */
    public function __construct()
    {


        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 999 );
        add_filter(
            'posts_orderby',
            array( $this, 'posts_orderby' ),
            9999,
            2
        );
    }


    public function activation()
    {

        add_option( 'brave_woocommerce_featured_first_enabled_on_shop', 'no' );
        add_option( 'brave_woocommerce_featured_first_enabled_on_search', 'no' );
        add_option( 'brave_woocommerce_featured_first_enabled_on_archive', 'no' );
        add_option( 'brave_woocommerce_featured_first_enabled_on_admin', 'no' );
        add_option( 'brave_woocommerce_featured_first_enabled_everywhere', 'no' );


    }


    public function pre_get_posts( $query )
    {
        //This function is for old woocommerce
        //If woocommerce version is latest
        //then return as it is
        if ( version_compare( WC()->version, 3.0 ) > 0 ) {
            return $query;
        }

        if ( !empty($query->query_vars['wc_query']) && $query->query_vars['wc_query'] == 'product_query' && (get_option( 'brave_woocommerce_featured_first_enabled_on_shop' ) == 'yes' && empty($query->query_vars['s']) || get_option( 'brave_woocommerce_featured_first_enabled_on_search' ) == 'yes' && !empty($query->query_vars['s']) || get_option( 'brave_woocommerce_featured_first_enabled_on_archive' ) == 'yes' && empty($query->query_vars['s']) && is_tax()) && (!empty($query->query_vars['orderby']) && $query->query_vars['orderby'] == 'menu_order title' && !empty($query->query_vars['order']) && $query->query_vars['order'] == 'ASC') ) {
            $query->set( 'meta_key', '_featured' );
            $query->set( 'orderby', "meta_value " . $query->get( 'orderby' ) );
            $query->set( 'order', "DESC " . $query->get( 'order' ) );
        }

        return $query;
    }

    public function posts_orderby( $order_by, $query )
    {
        global  $wpdb ;
        //This function is for new woocommerce
        //If woocommerce version is latest
        //then return as it is
        if ( version_compare( WC()->version, 3.0 ) <= 0 ) {
            return $order_by;
        }
        $orderby_value = ( isset( $_GET['orderby'] ) ? wc_clean( (string) $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) ) );
        $orderby_value_array = explode( '-', $orderby_value );
        $orderby = esc_attr( $orderby_value_array[0] );
        $order = ( !empty($orderby_value_array[1]) ? $orderby_value_array[1] : 'ASC' );

        if ( apply_filters( 'brave_is_featured_product_first_order_applicable', $query->is_main_query() && $query->is_archive && (!empty($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'product' || 'yes' == get_option( 'brave_woocommerce_featured_first_enabled_on_archive' ) && is_tax( get_object_taxonomies( 'product', 'names' ) )) && (get_option( 'brave_woocommerce_featured_first_enabled_on_shop' ) == 'yes' && empty($query->query_vars['s']) || get_option( 'brave_woocommerce_featured_first_enabled_on_search' ) == 'yes' && !empty($query->query_vars['s']) || get_option( 'brave_woocommerce_featured_first_enabled_on_archive' ) == 'yes' && empty($query->query_vars['s']) && is_tax()) && (!defined( 'brave_i_p' ) && (!empty($query->query_vars['orderby']) && $query->query_vars['orderby'] == 'menu_order title' && !empty($query->query_vars['order']) && $query->query_vars['order'] == 'ASC' || ($orderby == 'relevance' || empty($orderby)) && ($order == 'DESC' || $order == 'ASC')) || defined( 'brave_i_p' ) && apply_filters( 'brave_is_featured_product_first_order_applicable_on_main_query', false, $query )), $query ) ) {
            $feture_product_id = brave_get_featured_product_ids();
            if ( is_array( $feture_product_id ) && !empty($feture_product_id) ) {

                if ( empty($order_by) ) {
                    $order_by = "FIELD(" . $wpdb->posts . ".ID,'" . implode( "','", $feture_product_id ) . "') DESC ";
                } else {
                    $order_by = "FIELD(" . $wpdb->posts . ".ID,'" . implode( "','", $feture_product_id ) . "') DESC, " . $order_by;
                }

            }
        }

        return $order_by;
    }

}
$GLOBALS['brave_main'] = new brave_featured_products_wc();



if( ! class_exists( 'fbu_brave_plugin_wc_featured_first' ) ){
    include_once( plugin_dir_path( __FILE__ ) .  $thisplugin.'-upd.php' );
}

$updater = new fbu_brave_plugin_wc_featured_first( __FILE__ );
$updater->set_username( $thispluginowner);
$updater->set_repository( $thisplugin );
/*
	$updater->authorize( 'abcdefghijk1234567890' );
*/
$updater->initialize();

