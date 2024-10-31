<?php
/**
 * Plugin Name: PW WooCommerce BOGO Free
 * Plugin URI: https://www.pimwick.com/pw-bogo/
 * Description: Makes Buy One, Get One promotions so easy!
 * Version: 3.1
 * Author: Pimwick, LLC
 * Author URI: https://www.pimwick.com
 * Text Domain: pw-woocommerce-bogo-free
 * WC requires at least: 4.0
 * WC tested up to: 9.3
 * Requires Plugins: woocommerce
*/

/*
Copyright (C) Pimwick, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Verify this isn't called directly.
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! class_exists( 'PW_BOGO' ) ) :

final class PW_BOGO {

    private $use_coupons = true;

    function __construct() {
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );

        // WooCommerce High Performance Order Storage (HPOS) compatibility declaration.
        add_action( 'before_woocommerce_init', function() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        } );
    }

    function plugins_loaded() {
        load_plugin_textdomain( 'pw-woocommerce-bogo-free', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    function woocommerce_init() {

        $this->use_coupons = boolval( get_option( 'pw_bogo_use_coupons', true ) );

        // If WooCommerce does not have Coupons enabled, we can't utilize them.
        if ( 'no' === get_option( 'woocommerce_enable_coupons', 'no' ) ) {
            $this->use_coupons = false;
        }

        add_action( 'init', array( $this, 'register_post_types' ), 9 );

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_filter( 'manage_edit-pw_bogo_columns', array( $this, 'edit_pw_bogo_columns' ) );
            add_action( 'add_meta_boxes_pw_bogo', array( $this, 'meta_boxes' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts') );
            add_filter( 'wp_count_posts', array( $this, 'wp_count_posts' ), 10, 3 );
            add_filter( 'woocommerce_order_get_items', array( $this, 'woocommerce_order_get_items' ), 10, 2 );
        }

        add_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );

        if ( true === $this->use_coupons ) {
            add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'woocommerce_get_shop_coupon_data' ), 10, 2 );
            add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_apply_bogo_coupon' ) );
            add_action( 'woocommerce_check_cart_items', array( $this, 'maybe_apply_bogo_coupon' ) );
            add_filter( 'woocommerce_coupon_message', array( $this, 'woocommerce_coupon_message' ), 10, 3 );
            add_filter( 'woocommerce_coupon_is_valid', array( $this, 'woocommerce_coupon_is_valid' ), 99, 2 );
            add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'woocommerce_cart_totals_coupon_label' ), 10, 2 );

            if ( $this->wc_min_version( '3.1' ) ) {
                add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 3 );
            } else {
                add_action( 'woocommerce_order_add_coupon', array( $this, 'woocommerce_order_add_coupon' ), 10, 5 );
            }
        } else {
            add_action( 'woocommerce_cart_calculate_fees' , array( $this, 'woocommerce_cart_calculate_fees' ) );
            add_action( 'woocommerce_cart_contents_total' , array( $this, 'woocommerce_cart_contents_total' ) );
        }
    }

    function register_post_types() {
        if ( post_type_exists('pw_bogo') ) {
            return;
        }

        $labels = array(
            'name'                  => _x( 'PW BOGO', 'Post Type General Name', 'pw-woocommerce-bogo-free' ),
            'singular_name'         => _x( 'PW BOGO', 'Post Type Singular Name', 'pw-woocommerce-bogo-free' ),
            'menu_name'             => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'name_admin_bar'        => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'archives'              => __( 'PW BOGO Archives', 'pw-woocommerce-bogo-free' ),
            'parent_item_colon'     => __( 'Parent PW BOGO:', 'pw-woocommerce-bogo-free' ),
            'all_items'             => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'add_new_item'          => __( 'Add New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'add_new'               => __( 'Create New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'new_item'              => __( 'New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'edit_item'             => __( 'Edit PW BOGO', 'pw-woocommerce-bogo-free' ),
            'update_item'           => __( 'Update PW BOGO', 'pw-woocommerce-bogo-free' ),
            'view_item'             => __( 'View PW BOGO', 'pw-woocommerce-bogo-free' ),
            'search_items'          => __( 'Search PW BOGO', 'pw-woocommerce-bogo-free' ),
            'not_found'             => __( 'Not found', 'pw-woocommerce-bogo-free' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'pw-woocommerce-bogo-free' ),
            'featured_image'        => __( 'PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'set_featured_image'    => __( 'Set PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'remove_featured_image' => __( 'Remove PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'use_featured_image'    => __( 'Use as PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'insert_into_item'      => __( 'Insert into item', 'pw-woocommerce-bogo-free' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'pw-woocommerce-bogo-free' ),
            'items_list'            => __( 'PW BOGO list', 'pw-woocommerce-bogo-free' ),
            'items_list_navigation' => __( 'PW BOGO list navigation', 'pw-woocommerce-bogo-free' ),
            'filter_items_list'     => __( 'Filter PW BOGO list', 'pw-woocommerce-bogo-free' ),
        );

        $args = array(
            'label'                 => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'description'           => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'show_ui'               => true,
            'show_in_menu'          => current_user_can( 'manage_woocommerce' ) ? 'pimwick' : false,
            'has_archive'           => true
        );

        register_post_type( 'pw_bogo', $args );
    }

    public static function version() {
        $data = get_plugin_data( __FILE__ );
        return $data['Version'];
    }

    public static function wc_min_version( $version ) {
        return version_compare( WC()->version, $version, ">=" );
    }

    function meta_boxes( $post ) {
        require( 'ui/meta-boxes.php' );

        add_meta_box( 'pw-bogo-about', __( 'About', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::about', 'pw_bogo', 'side', 'default' );
        add_meta_box( 'pw-bogo-description', __( 'Yes, it really is this easy!', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::description', 'pw_bogo', 'normal', 'default' );
        add_meta_box( 'pw-bogo-pro', __( 'Want more control?', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::pro', 'pw_bogo', 'normal', 'default' );
    }

    function admin_menu() {
        if ( empty ( $GLOBALS['admin_page_hooks']['pimwick'] ) ) {
            add_menu_page(
                __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
                __( 'Pimwick Plugins', 'pw-woocommerce-bogo-free' ),
                'manage_woocommerce',
                'pimwick',
                '',
                plugins_url( '/assets/images/pimwick-icon-120x120.png', __FILE__ ),
                6
            );

            add_submenu_page(
                'pimwick',
                __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
                __( 'Pimwick Plugins', 'pw-woocommerce-bogo-free' ),
                'manage_woocommerce',
                'pimwick',
                ''
            );

            remove_submenu_page( 'pimwick', 'pimwick' );
        }
    }

    function admin_enqueue_scripts() {
        global $post_type;

        $data = get_plugin_data( __FILE__ );
        $version = $data['Version'];

        if ( 'pw_bogo' == $post_type ) {
            wp_register_style( 'pw-bogo', plugins_url( '/assets/css/style.css', __FILE__ ), array( 'woocommerce_admin_styles' ), $version );
            wp_enqueue_style( 'pw-bogo' );
        }

        wp_register_style( 'pw-bogo-icon', plugins_url( '/assets/css/icon-style.css', __FILE__ ), array(), $version );
        wp_enqueue_style( 'pw-bogo-icon' );
    }

    function edit_pw_bogo_columns( $gallery_columns ) {
        $new_columns['cb'] = '<input type="checkbox" />';

        $new_columns['title'] = _x( 'Name', 'pw-woocommerce-bogo-free' );

        return $new_columns;
    }

    function woocommerce_cart_totals_coupon_label( $label, $coupon ) {
        if ( PW_BOGO::wc_min_version( '3.1' ) ) {
            if ( $this->is_bogo_coupon( $coupon->get_code() ) ) {
                $label = sprintf( __( 'Coupon: %s', 'woocommerce' ), $coupon->get_description() );
            }
        } else {
            $bogo = $this->get_active_bogo();
            if ( $bogo && $this->is_bogo_coupon( $coupon->code, $bogo ) ) {
                $label = sprintf( __( 'Coupon: %s', 'woocommerce' ), $bogo->post_title );
            }
        }

        return $label;
    }

    function woocommerce_new_order_item( $order_item_id, $item, $order_id ) {
        if ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
            $this->maybe_add_coupon_to_order_item( $order_item_id, $item->get_code() );
        }
    }

    function woocommerce_order_add_coupon( $order_id, $order_item_id, $code, $discount_amount, $discount_amount_tax ) {
        $this->maybe_add_coupon_to_order_item( $order_item_id, $code );
    }

    function maybe_add_coupon_to_order_item( $order_item_id, $code ) {
        $bogo = $this->get_active_bogo();
        if ( $bogo && $this->is_bogo_coupon( $code, $bogo ) ) {
            wc_add_order_item_meta( $order_item_id, 'pw_bogo_id', $bogo->ID );
        }
    }

    function woocommerce_coupon_message( $msg, $msg_code, $coupon ) {
        if ( $msg_code == WC_Coupon::WC_COUPON_SUCCESS ) {
            if ( PW_BOGO::wc_min_version( '3.1' ) ) {
                $code = $coupon->get_code();
            } else {
                $code = $coupon->code;
            }

            if ( $this->is_bogo_coupon( $code ) ) {

                if ( PW_BOGO::wc_min_version( '3.1' ) ) {
                    $bogo_title = $coupon->get_description();
                } else {
                    $bogo_title = get_post_meta( $coupon->id, 'description', true );
                }

                if ( function_exists( 'mb_strtolower' ) ) {
                    $msg = $bogo_title . ' ' . mb_strtolower( $msg, 'UTF-8' );
                } else {
                    $msg = $bogo_title . ' ' . strtolower( $msg );
                }
            }
        }

        return $msg;
    }

    function woocommerce_coupon_is_valid( $valid_for_cart, $coupon ) {
        // Fix for interfering plugins such as WooCommerce Coupon Schedule.
        if ( !$valid_for_cart ) {
            if ( $this->wc_min_version( '3.1' ) ) {
                $coupon_code = $coupon->get_code();
            } else {
                $coupon_code = $coupon->code;
            }

            if ( $this->is_bogo_coupon( $coupon_code ) ) {
                $valid_for_cart = true;
            }
        }

        return $valid_for_cart;
    }

    function woocommerce_cart_calculate_fees( $cart ) {
        $discount = $this->get_discount();
        if ( !empty( $discount ) ) {
            $cart->add_fee( $bogo->post_title, -$discount );
        }
    }

    function maybe_apply_bogo_coupon() {
        if ( false === $this->use_coupons ) {
            return;
        }

        $discount = $this->get_discount();
        if ( empty( $discount ) ) {
            // Delete any invalid coupons.
            foreach ( WC()->cart->applied_coupons as $coupon_code ) {
                if ( $this->is_bogo_coupon( $coupon_code ) ) {
                    WC()->cart->remove_coupon( $coupon_code );
                }
            }

        } else {
            // Get an existing BOGO coupon.
            foreach ( WC()->cart->applied_coupons as $coupon_code ) {
                if ( $this->is_bogo_coupon( $coupon_code ) ) {
                    $code = $coupon_code;
                    break;
                }
            }

            if ( !isset( $code ) ) {
                $bogo = $this->get_active_bogo();
                if ( $bogo ) {
                    $bogo_coupon_code = $this->get_bogo_coupon_code( $bogo );
                    WC()->cart->add_discount( $bogo_coupon_code );
                    WC()->session->set( 'refresh_totals', true );
                }
            }
        }
    }

    function get_discount() {
        // If we only have 1 item no need for the hokey pokey.
        if ( WC()->cart->cart_contents_count < 2 ){
            return;
        }

        $bogo = $this->get_active_bogo();

        if ( $bogo ) {
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                // Add a record per quantity for this product to the list of products for this BOGO.
                for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {

                    $price = 0;
                    if ( function_exists( 'wc_get_price_excluding_tax' ) && function_exists( 'wc_get_price_including_tax' ) ) {
                        $product = $cart_item['data'];

                        if ( PW_BOGO::wc_min_version( '4.4' ) ) {
                            $tax_mode = WC()->cart->get_tax_price_display_mode();
                        } else {
                            $tax_mode = WC()->cart->tax_display_cart;
                        }

                        if ( 'incl' === $tax_mode ) {
                            $product_price = wc_get_price_including_tax( $product );
                        } else {
                            $product_price = wc_get_price_excluding_tax( $product );
                        }
                        $price = apply_filters( 'woocommerce_cart_product_price', $product_price, $product );
                    }

                    // Old way of getting price.
                    if ( empty( $price ) ) {
                        $price = $cart_item['data']->get_price();
                    }

                    $product_prices[] = $price;
                }
            }

            // Sort products in cart from highest price to lowest price so we can apply the discount
            // to the cheaper option.
            rsort( $product_prices );

            // Loop over each product, sorted by price from highest to lowest.
            $discount = 0;
            $bottom_index = count( $product_prices ) - 1;
            foreach ( $product_prices as $index => $price ) {
                if ( $bottom_index > $index ) {
                    $discount += $product_prices[ $bottom_index ];
                    $bottom_index--;
                }
            }

            if ( !empty( $discount ) ) {
                return $discount;
            }
        }

        return 0;
    }

    function is_bogo_coupon( $code, $bogo = '' ) {
        if ( !empty( $bogo ) ) {
            $bogo_code = $this->get_bogo_coupon_code( $bogo );
            if ( strtolower( $code ) == strtolower( $bogo_code ) ) {
                return true;
            }
        } else {
            $all_bogos = get_posts( array(
                'posts_per_page' => 1,
                'post_type' => 'pw_bogo',
                'post_status' => 'publish'
            ) );
            foreach ( $all_bogos as $bogo ) {
                if ( $this->is_bogo_coupon( $code, $bogo ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    function get_bogo_coupon_code( $bogo ) {
        return wc_sanitize_taxonomy_name( $bogo->post_title ) . '-' . $bogo->ID;
    }

    function pre_get_posts( $query ) {
        // Prevents our internal BOGO coupons from appearing in the admin area.
        if ( $query->is_main_query() || $query->is_search() ) {
            if ( $query->query['post_type'] == 'shop_coupon' ) {
                $query->set( 'meta_query', array(
                    'relation' => 'AND',
                     array(
                        'key' => '_pw_bogo_id',
                        'compare' => 'NOT EXISTS',
                     ),
                ) );

            }
        }
    }

    function wp_count_posts( $counts, $type, $perm ) {
        global $wpdb;

        // Subtract the total number of published Store Credit coupons from the counter.
        if ( 'shop_coupon' === $type && property_exists( $counts, 'publish' ) ) {
            $store_credit_coupons = $wpdb->get_var( "SELECT COUNT(*) AS total FROM {$wpdb->posts} AS p JOIN {$wpdb->postmeta} AS m ON (m.post_id = p.ID AND m.meta_key = '_pw_bogo_id') WHERE p.post_type = 'shop_coupon' AND p.post_status = 'publish'" );
            $counts->publish -= $store_credit_coupons;
        }

        return $counts;
    }

    function woocommerce_order_get_items( $items, $order ) {
        foreach ( $items as $order_item_id => &$order_item ) {
            if ( is_a( $order_item, 'WC_Order_Item_Coupon' ) && !empty( $order_item->get_meta( 'pw_bogo_id' ) ) ) {
                $bogo_id = is_array( $order_item->get_meta( 'pw_bogo_id' ) ) ? $order_item->get_meta( 'pw_bogo_id' )[0] : $order_item->get_meta( 'pw_bogo_id' );
                $bogo = get_post( absint( $bogo_id ) );
                $order_item->set_name( 'PW BOGO: ' . $bogo->post_title );
            }
        }
        return $items;
    }

    function woocommerce_after_calculate_totals( ) {
        // Don't want to recurse.
        remove_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );

        $this->maybe_apply_bogo_coupon();

        add_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );
    }

    function woocommerce_get_shop_coupon_data( $data, $code ) {
        if ( empty( $code ) || empty( WC()->cart ) ) {
            return $data;
        }

        $discount = $this->get_discount();

        $bogo = $this->get_active_bogo();
        if ( $bogo && $this->is_bogo_coupon( $code, $bogo ) ) {
            // Creates a virtual coupon
            $data = array(
                'id' => -1,
                'code' => $code,
                'description' => $bogo->post_title,
                'amount' => $discount,
                'coupon_amount' => $discount
            );
        }

        return $data;
    }

    function woocommerce_cart_contents_total( $cart_contents_total ) {
        WC()->cart->calculate_fees();
        $fees = WC()->cart->get_fees();
        $bogo = $this->get_active_bogo();
        if ( $bogo ) {
            $fee_id = sanitize_title( $bogo->post_title );
            foreach ( $fees as $fee ) {
                if ( $fee->id == $fee_id ) {
                    return wc_price( WC()->cart->cart_contents_total + $fee->amount );
                }
            }
        }

        return $cart_contents_total;
    }

    function get_active_bogo() {
        $active_bogo = get_posts( array(
            'posts_per_page' => 1,
            'post_type' => 'pw_bogo',
            'post_status' => 'publish'
        ) );

        if ( count( $active_bogo ) > 0 ) {
            return $active_bogo[0];
        } else {
            return false;
        }
    }
}

new PW_BOGO();

endif;

if ( !function_exists( 'boolval' ) ) {
    function boolval( $val ) {
        return (bool) $val;
    }
}
