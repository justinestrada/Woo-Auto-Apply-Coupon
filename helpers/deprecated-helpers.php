<?php

function AAC_get_affid_by_referral_url() {
    return (isset($_GET['ref'])) ? $_GET['ref'] : false;
}

/*
 * Auto Apply Coupon
*/
function AAC_auto_Apply_Coupon() {
    // get affiliate id by referral url
    $affiliate_id = AAC_get_affid_by_referral_url();
    // if no referral
    if ( ! $affiliate_id ) {
        // incorrect affiliate id
        return false; // exit
    }
    // get user_id
    $user_id = AAC_get_userid_by_affid( $affiliate_id );
    // get coupon
    $coupon = aac_Get_Primary_Coupon($user_id);
    global $woocommerce;
    // if cart already has coupon code
    if ( $woocommerce->cart->has_discount( $coupon ) ) {
        // then return
        return false;
    }
    // apply affiliate coupon code to woocommerce cart
    $woocommerce->cart->add_discount( $coupon );
    // var_dump( $coupon ); // test
    // wp_die(); // test
    return true;
}

/*
 * Initial Plugin Load
function wp_loaded_init_AAC() {
    if ( ! is_admin()  ) {
        add_action('init', 'aac_On_Load_Before_Cart');
        // woocommerce_before_cart - only runs on the woocommerce cart page.
        // add_action('woocommerce_before_cart', function () { // doesnt work
        //     aac_On_Load_Before_Cart();
        // });
    }
}
*/

// Enqueue
// add_action( 'wp_enqueue_scripts', function() {
//     wp_enqueue_script( 'aac-public-js', './js/public.js', false );
// });

// Localize
// $data = array(
//     'referral_affiliate_coupon_code' => get_the_author_meta( 'primary_affiliate_coupon_code', $user->ID ),
// );
// wp_localize_script( 'aac-public-js', $name, $data );
