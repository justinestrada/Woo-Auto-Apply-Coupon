<?php

/*
 * Get Affiliate ID by User ID
*/
function AAC_get_userid_by_affid( $affiliate_id ) {
    global $wpdb;
    // get user_id by affiliate_id
    $table = $wpdb->prefix . "affiliate_wp_affiliates";
    $sql = "SELECT user_id FROM $table WHERE affiliate_id = $affiliate_id";
    $result = $wpdb->get_results( $sql );
    $user_id = (!empty($result)) ? (int)$result[0]->user_id : false;
    return $user_id;
}
