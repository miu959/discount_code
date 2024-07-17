jQuery(document).ready(function($) {
    // Generate discount code
    jQuery(document).on('click', '.generate_password_btn', function(e) {
        var length = 8,
            charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=",
            retVal = "";
        for (var i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        jQuery('#'+jQuery(this).attr('data-id')).val(retVal);

    });

    // Save discount value to database
    jQuery(document).on('click', '.submit-discount-btn', function(e) {
        var user_id = jQuery(this).data('id');
        var platformfee = jQuery('#' + user_id + '_platformfee_textfield').val();
        var generatedPassword = jQuery('#generatedPassword_' + user_id).val();
        var expiryDate = jQuery('#expiryDate_' + user_id).val();
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'store_value',
                security: ajax_object.security,
                value: {
                    platformfee: platformfee,
                    generatedpassword: generatedPassword,
                    user_id: user_id,
                    expiry_date: expiryDate
                }
            },
            success: function(response) {
                if(response.success){
                    alert('Value stored successfully and email sent.');
                    location.reload(); // Refresh the page to show updated values
                    //window.location.href = ajax_object.redirect_url;
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Mark discount code as used and disable button
    jQuery(document).on('click', '.mark-as-used-btn', function() {
        var discountId = $(this).data('id');
        var status = $(this).data('status');
        var button = $(this);

        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'update_discount_status',
                security: ajax_object.security,
                discount_id: discountId,
                status: status
            },
            success: function(response) {
                if(response.success){
                    button.prop('disabled', true);
                    button.closest('tr').find('td').eq(7).text(status); 
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
