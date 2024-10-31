<?php

/*
Copyright (C) 2017 Pimwick, LLC

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

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PW_BOGO_Meta_Boxes' ) ) :

class PW_BOGO_Meta_Boxes {

    public static function description( $post ) {
        ?>
        <input type="hidden" name="pw_bogo_meta_nonce" id="pw_bogo_meta_nonce" value="<?php echo wp_create_nonce( 'pw_bogo_save_data' ); ?>" />
        <p><?php _e( 'Just enter a title and click Publish. The title will be shown to the customer in the cart and all WooCommerce products in your catalog will be eligible for "Buy One, Get One of equal or lesser value for free."', 'pw-woocommerce-bogo-free' ); ?></p>
        <p><?php _e( 'To end the promotion, simply click Move to Trash.', 'pw-woocommerce-bogo-free' ); ?></p>
        <?php
    }

    public static function pro( $post ) {
        ?>
        <a href="https://pimwick.com/pw-bogo" target="_blank"><img src="<?php echo plugins_url( 'assets/images/logo.jpg', dirname( __FILE__ ) ); ?>" style="width: 100%; max-width: 722px; max-height: 250px;" border="0" class="pw-bogo-logo"></a>
        <div class="pw-bogo-features-header"><a href="https://pimwick.com/pw-bogo" target="_blank">PW WooCommerce BOGO Pro</a> <?php _e( 'features', 'pw-woocommerce-bogo-free' ); ?></div>
        <div id="pw-bogo-features">
            <ul class="pw-bogo-features-list">
                <li><?php _e( 'Buy One, Get One Free', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Buy X, Get X e.g. Buy 3, Get 2 Free', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( '% Off e.g. Buy Two, Get One 75% Off', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Buy X, Get Y for % off', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Spend a certain amount, Get Y for % Off', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Consecutive discounts e.g. “Buy 1 Get 1 Half Off or Buy 2 Get 1 Free”', 'pw-woocommerce-bogo-free' ); ?></li>
            </ul>

            <?php _e( 'Common sense configuration options make customization easy:', 'pw-woocommerce-bogo-free' ); ?>
            <ul class="pw-bogo-features-list">
                <li><?php _e( 'Schedule begin and end dates for the promotions', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Specify products by category', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Include or exclude individual products', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Eligible products can be different than the Discounted products', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Automatically add discounted products to the customer’s cart', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Require a coupon code to activate the BOGO', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Limit the number of times the BOGO deal can be applied per order or for all customers', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Specify that only identical products or variations are discounted', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Options to exclude sale items or not allow other coupons in conjunction with BOGO offer', 'pw-woocommerce-bogo-free' ); ?></li>
                <li><?php _e( 'Compatible with all major payment gateways such as PayPal and Klarna', 'pw-woocommerce-bogo-free' ); ?></li>
            </ul>
        </div>
        <a href="https://pimwick.com/pw-bogo" class="button button-primary pw-bogo-buy-button" target="_blank"><?php _e( 'Learn more!', 'pw-woocommerce-bogo-free' ); ?></a>
        <?php
    }

    public static function about( $post ) {
        ?>
        <span class="pw-bogo-title">PW WooCommerce BOGO</span>
        <span class="pw-bogo-version">v<?php echo PW_BOGO::version(); ?></span>
        <div>by <a href="https://www.pimwick.com" target="_blank" class="pw-bogo-link">Pimwick, LLC</a></div>
        <?php
    }

}

endif;

?>