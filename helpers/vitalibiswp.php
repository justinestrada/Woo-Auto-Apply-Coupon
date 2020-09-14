<?php

/*
 * Get Affiliate ID by User ID
*/
function AAC_get_user_id_by_vitalibiswp_affiliate_id( $affiliate_id )  {
    global $wpdb;
    $table = $wpdb->prefix . 'usermeta';
    $sql = "SELECT user_id FROM $table WHERE meta_key = 'v_affiliate_id' AND meta_value = $affiliate_id";
    return $wpdb->get_var( $sql );
}
