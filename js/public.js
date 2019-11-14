
(function($) {
/*
 * Auto Apply Coupon
 * */
const Auto_Apply_Coupon = {
    onLoad: function() {
        // if url query ref exists
        if ( window.location.search.indexOf('ref') !== -1 ) {
            // if cart has items
            // if ( jQuery('.xoo-wsc-product').length ) {
            // }
            // AAC_Settings = 
        }
    },
    openWooCart: function() {
        $('.open-woo-cart')[0].click();
    },
};
/*
 * Document ready
 * */
$(document).ready(function() {
    Auto_Apply_Coupon.onLoad();
});
})(jQuery);
