<?php

/**
 * Plugin Name:       Woo Auto Apply Coupon
 * Plugin URI:        https://radicalskincare.com
 * Description:       This plugin auto-applies a coupon to the cart based on url. Requires WooCommerce. Has AffiliateWP Integration.
 * Version:           0.5.3
 * Author:            Justin Estrada
 * Author URI:        https://justinestrada.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-auto-apply-coupon
 * Domain Path:       /languages
 */

/**
 * Helpers - Affiliate WP
*/
require(plugin_dir_path( __FILE__ ) . 'helpers/affiliatewp.php');

/**
 * Helpers - Vitalibis WP
*/
require(plugin_dir_path( __FILE__ ) . 'helpers/vitalibiswp.php');

/**
 * Helpers - Get Coupons
*/
require(plugin_dir_path( __FILE__ ) . 'helpers/get-coupons.php');

/**
 * Admin - User Profile Coupons
*/
if ( is_admin() ) {
    require(plugin_dir_path( __FILE__ ) . 'admin/user-profile.php');
}

/*
 * Auto Apply Coupon - Initialze Public
*/
function aac_Init_Public() { ?>
    <script>
    (function($) {
    /*
     * Auto Apply Coupon
     * */
    const Auto_Apply_Coupon = {
        coupon: {
            type: null, // normal, affiliate
            name: null,
        },
        affiliate: {
            id: null,
            display_name: null,
            primary_coupon: null,
        },
        onLoad: function() {
            // console.log('onLoad');

            const self = this;
            const urlParams = new URLSearchParams(window.location.search);
            const this_coupon = urlParams.get('coupon');
            const aff_refferal = urlParams.get('ref');

            // if NOT affiliate referral
            // if (!aff_refferal) {
                this.coupon.name = (this_coupon) ? this_coupon : this.readCookie( 'WAAC-Stored-Coupon' );
            // } else {
            // console.log('test');
                // this.affiliate.id = (aff_refferal) ? aff_refferal : this.readCookie( 'affwp_ref' );                
                this.affiliate.id = (aff_refferal) ? aff_refferal : this.readCookie( 'vitalibiswp_ref' );
            // }
            console.log(this.coupon.name, this.affiliate.id);

            // if this.coupon.name
            if (this.coupon.name) {
                this.coupon.type = 'normal';
                if (this_coupon) {
                    // create cookie
                    this.createCookie('WAAC-Stored-Coupon', this.coupon.name, 1);                    
                }

                // if NO products in basket
                if ( !$('.xoo-wsc-product').length ) {
                    console.log('no products in basket');
                    // then exit
                    this.showNotice( false );
                    this.onHideNotice();
                    return false;
                }

                // check if coupon already applied
                let couponAlreadyApplied = false;
                $('.xoo-wsc-applied-coupons li').each(function() {
                    if (self.coupon.name === $(this).attr('data-coupon')) {
                        couponAlreadyApplied = true;
                    }
                });
                // if coupon NOT already applied
                if (!couponAlreadyApplied) {
                    // console.log('this.coupon.name', this.coupon.name);
                    this.applyCoupon( this.coupon.name );
                } else {
                    // console.log('Aff coupon already applied');
                    this.showNotice( true );
                }
            // else if affiliate referral (affwp_ref) cookie is tracking
            } else if (this.affiliate.id) {
                this.coupon.type = 'affiliate';
                this.getPrimaryCoupon(this.affiliate.id).then( (res) => {
                    const parsedResponse = JSON.parse(res)
                    // console.log('response', parsedResponse);
                    if (parsedResponse.success) {
                        self.affiliate.display_name = parsedResponse.aff_display_name;
                        self.coupon.name = self.affiliate.primary_coupon = parsedResponse.coupon;

                        // if NO products in basket
                        if ( !$('.xoo-wsc-product').length ) {
                            // then exit
                            this.showNotice( false );
                            this.onHideNotice();
                            return false;
                        }

                        // check if coupon already applied
                        let couponAlreadyApplied = false;
                        $('.xoo-wsc-applied-coupons li').each(function() {
                            if (parsedResponse.coupon === $(this).attr('data-coupon')) {
                                couponAlreadyApplied = true;
                            }
                        });
                        // if coupon NOT already applied
                        if (!couponAlreadyApplied) {
                            this.applyCoupon( self.affiliate.primary_coupon );
                        } else {
                            // console.log('Aff coupon already applied');
                            this.showNotice( true );
                        }
                    } else {
                        // do nothing, must not have a primary coupon code set
                        console.error(parsedResponse);
                    }
                }).catch( (err) => {
                    console.error(err);
                });
            }
        },
        readCookie: function( name ) {
            // console.log('Cookie read'); // test
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        },
        getPrimaryCoupon: function( affiliate_id ) {
            return new Promise( (resolve, reject) => {
                const data = {
                    'action': 'aac_get_primary_coupon_ajax',
                    'affiliate_id': affiliate_id,
                };
                $.ajax({
                  url: '<?php echo admin_url('admin-ajax.php'); ?>',
                  type: 'POST',
                  data: data,
                }).done(function(res) {
                    resolve(res);
                }).fail(function(err) {
                    reject(err);
                });
            });
        },
        getWSCNonces: function() {
            return new Promise( (resolve, reject) => {
                // TODO:
                // POST to https://radicalskincare.com/wp-admin/admin-ajax.php
                // action: xoo_wsc_create_nonces
                // to get a response with nonces
                const data = {
                    'action': 'xoo_wsc_create_nonces',
                };
                $.ajax({
                  type: "POST",
                  url: '<?php echo admin_url('admin-ajax.php'); ?>',
                  data: data,
                  // dataType: "json",
                }).done(function(res) {
                    console.log('getWSCNonces', res);
                    resolve(res);
                }).fail(function(err) {
                    reject(err);
                });
            });
        },
        applyCoupon: function( this_coupon ) {
            this.getWSCNonces().then( (NONCES) => {

                // console.log('test: ' + this.affiliate.primary_coupon );
                const self = this;
                const data = {
                    // "security": '<?php echo wp_create_nonce('apply-coupon'); ?>',
                    "security": NONCES['apply-coupon'],
                    "coupon_code": this_coupon,
                };
                $.ajax({
                  type: "POST",
                  url: '<?php echo get_site_url(); ?>/?wc-ajax=apply_coupon',
                  data: data,
                  // dataType: "json",
                }).then(function(res) {
                    // console.log('res', res);
                    if ( res.indexOf('success') !== -1 ) {
                        self.getRefreshedFragments();
                    } else {
                        self.eraseCookie('WAAC-Stored-Coupon');
                        const resMsg = $(res).text();
                        alert(resMsg);                   
                    }
                    // else if ( res.indexOf('already applied') !== -1 ) {
                    //     self.showNotice( true );
                    // } else if ( res.indexOf('does not exist') !== -1 ) {
                    //     alert('Coupon "' + this_coupon + '" does not exist.');
                    // } else if ( res.indexOf('cannot be used in conjunction') !== -1 ) {
                    //     alert('Coupon already applied and cannot be used in conjunction with other coupons.');
                    // } else if ( res.indexOf('woocommerce-error') !== -1 ) {
                    //     const resMsg = $(res).text();
                    //     alert(resMsg);
                    // }
                 });
            }).catch( (err) => {
                console.error(err);
            });
        },
        getRefreshedFragments: function() {
            const self = this;
            const data = {
                "time": + new Date,
            };
            $.ajax({
              type: "POST",
              url: '<?php echo get_site_url(); ?>/?wc-ajax=get_refreshed_fragments',
              data: data,
              dataType: "json",
            }).done(function(res) {
                // console.log('success', res);
                $('.xoo-wsc-footer-content').replaceWith(res.fragments['div.xoo-wsc-footer-content']);
                if ( $('.xoo-wsc-product').length ) {
                    self.openWooCart();
                    self.showNotice( true );
                }
                // else {
                //     console.log('not products??', $('.xoo-wsc-product').length);
                // }
            }).fail(function(err) {
                console.error('failed', err);
            });
            // time: 
        },
        showNotice: function( couponApplied ) {
            const noticeHidden = this.readCookie('WAAC-Notice-Hidden');
            // if notice for this referral id not already hidden
            if ( noticeHidden !== this.coupon.name ) {
                let msgHTML = '';
                if ( couponApplied ) {
                    msgHTML = ( this.coupon.type === 'affiliate' ) ? 'Congratulations ' + this.affiliate.display_name + '\'s affiliate coupon code: <strong class="text-uppercase aff-coupon" style="font-weight: bold;" >' + this.affiliate.primary_coupon + '</strong> has been applied to your basket!' : 'Coupon code: <strong class="text-uppercase aff-coupon" style="font-weight: bold;" >' + this.coupon.name + '</strong> has been applied to your basket!';                    
                } else {
                    msgHTML = 'We will auto-apply coupon code: <strong class="text-uppercase aff-coupon" style="font-weight: bold;" >' + this.coupon.name + '</strong> after a product is in your basket.';
                }
                $('#auto-apply-coupon-notice .notice-text').html( msgHTML );
                $('#auto-apply-coupon-notice').slideDown();
                this.onHideNotice();
            }
        },
        onHideNotice: function() {
            const self = this;
            // console.log('this.affiliate_id', this.affiliate.id);
            $('#auto-apply-coupon-notice #hide-auto-apply-coupon-notice').on('click', function() {
                self.hideNotice();
                const value = ( self.coupon.type === 'normal' ) ? self.coupon.name : self.affiliate.primary_coupon ;
                self.createCookie('WAAC-Notice-Hidden', value, 1);
            });
        },
        hideNotice: function() {
            $('#auto-apply-coupon-notice').slideUp();
        },
        openWooCart: function() {
            // if NOT active
            if ( ! $('.xoo-wsc-modal').hasClass('xoo-wsc-active') ) {
                $('.open-woo-cart')[0].click();
            }
        },
        createCookie: function(name, value, days) {
            if (days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
            }
            else var expires = "";
            document.cookie = name + "=" + value + expires + "; path=/";
        },
        onListenCartItemChanges: function() {
            // console.log('onListenCartItemChanges');
            const self = this;
            let cartItemCount = $('.xoo-wsc-product').length;
            // listen to total amount change
            $('body').on('DOMSubtreeModified', $('.xoo-wsc-product'), function() {
                // console.log('DOMSubtreeModified');
                let newCartItemCount = $('.xoo-wsc-product').length;
                if ( newCartItemCount && cartItemCount !== newCartItemCount  ) {
                    // console.log('cartItemCount changed');
                    cartItemCount = newCartItemCount;
                    self.onLoad();
                }
            });
        },
        eraseCookie: function(name) {
            this.createCookie(name, "", -1);
        },
    };
    /*
     * Document ready
     * */
    $(document).ready(function() {
        setTimeout(function() {
            Auto_Apply_Coupon.onLoad();
            Auto_Apply_Coupon.onListenCartItemChanges();
        }, 1000);
    });
    })(jQuery);
    </script>
    <?php
}

/*
 * Initial Plugin Load
*/
$is_checkout = (strpos($_SERVER['REQUEST_URI'], 'checkout') !== false);
if (! $is_checkout) {
    add_action( 'wp_head', 'aac_Init_Public');
    add_action( 'after_announcement_bar', function() { ?>
        <style>
        #auto-apply-coupon-notice {
            border-radius: 0; display: none;
        }
        #hide-auto-apply-coupon-notice {
            cursor: pointer;
            background: transparent;
            border: none;
            float: right;    
        }
        </style>
        <div id="auto-apply-coupon-notice" class="alert alert-success mb-0" role="alert" >
            <div class="container">
                <div class="row">
                    <div class="col p-0">
                        <p class="px-lg-5 mb-0">
                            <span class="notice-text" ></span>
                            <button id="hide-auto-apply-coupon-notice" ><i class="fa fa-times fa-rotate-90"></i></button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    });
}
