<?php
/**
 * List the affiliate's coupon codes
 *
 * @since 1.0
 *
 * @return void
 */
function AAC_get_Coupons( $affiliate_id ) {

    global $wpdb;

    if ( ! $affiliate_id ) {
        return false;
    }

    $post_ids = $wpdb->get_results(
        "
        SELECT post_id
        FROM $wpdb->postmeta
        WHERE ( meta_key = 'affwp_discount_affiliate' OR meta_key = 'affwp_coupon_affiliate' )
        AND meta_value = $affiliate_id
        "
    );

    $ids = wp_list_pluck( $post_ids, 'post_id' );

    $coupons = array();

    // get enabled integrations
    $enabled_integrations = affiliate_wp()->integrations->get_enabled_integrations();

    if ( $ids ) {
        foreach ( $ids as $id ) {

            switch ( get_post_type( $id ) ) {
                // EDD
                case 'edd_discount':

                if ( array_key_exists( 'edd', $enabled_integrations ) && edd_is_discount_active( $id ) ) {
                    $coupons[$id]['code'] = edd_get_discount_code( $id );
                    $coupons[$id]['amount'] = edd_format_discount_rate( edd_get_discount_type( $id ), edd_get_discount_amount( $id ) );

                }

                    break;

                // WooCommerce
                case 'shop_coupon':

                if ( array_key_exists( 'woocommerce', $enabled_integrations ) && 'publish' == get_post_status( $id ) ) {

                    $coupons[$id]['code']   = get_the_title( $id );
                    $coupons[$id]['amount'] = esc_html( get_post_meta( $id, 'coupon_amount', true ) ) . ' (' . esc_html( wc_get_coupon_type( get_post_meta( $id, 'discount_type', true ) ) ) . ')';
                }

                    break;

                // iThemes Exchange
                case 'it_exchange_coupon':

                if ( array_key_exists( 'exchange', $enabled_integrations ) ) {

                    $coupons[$id]['code']   = get_post_meta( $id, '_it-basic-code', true );
                    $coupons[$id]['amount'] = esc_attr( it_exchange_get_coupon_discount_label( $id ) );
                }

                    break;

                // MemberPress
                case 'memberpresscoupon':

                if ( array_key_exists( 'memberpress', $enabled_integrations ) && 'publish' == get_post_status( $id ) ) {

                    $coupons[$id]['code']   = get_the_title( $id );
                    $coupons[$id]['amount'] = esc_html( get_post_meta( $id, '_mepr_coupons_discount_amount', true ) ) . ' (' . esc_html( get_post_meta( $id, '_mepr_coupons_discount_type', true ) ) . ')';
                }

                    break;

                    default:
                    break;
            }


        }
    }

    if ( ! empty( $coupons ) ) {
        return $coupons;
    }

    return false;
}


/*
 * Get Primary Coupon
*/
function aac_Get_Primary_Coupon($user_id) {
    // get primary_affiliate_coupon_code
    $coupon = get_user_meta($user_id, 'primary_affiliate_coupon_code', true);
    if ( ! $coupon ) {
        return false; // exit
    }
    return $coupon;
}


add_action( 'wp_ajax_aac_get_primary_coupon_ajax', 'aac_get_primary_coupon_ajax' );
add_action( 'wp_ajax_nopriv_aac_get_primary_coupon_ajax', 'aac_get_primary_coupon_ajax' );

function aac_get_primary_coupon_ajax() {
    $response = array( "success" => false );
    if ( ! isset($_POST['action']) || $_POST['action'] !== 'aac_get_primary_coupon_ajax'  ) {
        exit( json_encode($response) );
    }
    if ( ! isset($_POST['affiliate_id']) ) {
        exit( json_encode($response) );
    }
    $affiliate_id = $_POST['affiliate_id'];
    $user_id = AAC_get_userid_by_affid( $affiliate_id );
    if (!$user_id) {
        exit( json_encode($response) );
    }
    $coupon = aac_Get_Primary_Coupon($user_id);
    if (!$coupon) {
        $response['err_msg'] = 'Primary coupon not set.';
        exit( json_encode($response) );
    }
    $response['success'] = true;
    $response['coupon'] = $coupon;
    $response['aff_display_name'] = get_the_author_meta('display_name', $user_id);
    exit( json_encode($response) );
}

