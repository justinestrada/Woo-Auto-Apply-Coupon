<?php

/**
 * Add Additional Affilaite WP Fields section to user profile under Affiliate WP
 * 
 * Adds Primary Affiliate Coupon Code select dropdown field
*/
function AAC_extra_profile_fields( $user ) {
    global $wpdb;
    ?>
    <h3>Additional Affilaite WP Fields</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="primary_affiliate_coupon_code">Primary Affiliate Coupon Code</label>
            </th>
            <td>
                <?php if ( class_exists('Affiliate_WP') ) {
                    // get affiliate_id by user_id
                    $table = $wpdb->prefix . 'affiliate_wp_affiliates';
                    $sql = "SELECT affiliate_id FROM $table WHERE user_id = $user->ID";
                    $affiliate_id = $wpdb->get_results($sql);
                    // if affiliate_id true
                    if ( ! empty($affiliate_id) ) {
                        $affiliate_id = $affiliate_id[0]->affiliate_id;

                        $saved_coupon = get_the_author_meta( 'primary_affiliate_coupon_code', $user->ID );
                        $coupons = AAC_get_Coupons( $affiliate_id );
                        // var_dump($coupons);
                        if ( $coupons ) : ?>
                            <select name="primary_affiliate_coupon_code"
                                value="<?php echo esc_attr( get_the_author_meta( 'primary_affiliate_coupon_code', $user->ID ) ); ?>"
                                style="text-transform: uppercase;" >
                                <?php foreach ( $coupons as $coupon ) { ?>
                                    <option value="<?php echo $coupon['code']; ?>"
                                        <?php echo ($saved_coupon === $coupon['code']) ? 'selected="selected"':''; ?>
                                        >
                                        <?php echo $coupon['code']; ?> : <?php echo $coupon['amount']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php endif; ?>
                    <?php } ?>
                <?php } else { ?>
                    <input type="text" name="primary_affiliate_coupon_code" id="primary_affiliate_coupon_code"
                        value="<?php echo (isset($saved_coupon)) ? $saved_coupon:''; ?>" class="regular-text" placeholder="VIP30" />
                    <br />
                    <div class="alert alert-warning">
                        <p>Enable plugin: affiliatewp-show-affiliate-coupons to show related affiliate coupon codes.</p>
                    </div>
                <?php } ?>
            </td>
        </tr>
    </table>
<?php }
add_action( 'show_user_profile', 'AAC_extra_profile_fields', 10 );
add_action( 'edit_user_profile', 'AAC_extra_profile_fields', 10 );

/**
 * Save the fields when the values are changed on the profile page
*/
function AAC_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
    }
    if (!isset($_POST['primary_affiliate_coupon_code'])) {
        return false;
    }
	update_user_meta( $user_id, 'primary_affiliate_coupon_code', $_POST['primary_affiliate_coupon_code'] );
}
add_action( 'personal_options_update', 'AAC_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'AAC_save_extra_profile_fields' );