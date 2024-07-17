jQuery(document).ready(function($) {
    $('#apply-discount-code-form').on('submit', function(e) {
        e.preventDefault();

        var discountCode = $('#discount-code-input').val();

        $.ajax({
            url: discountCodeAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'apply_discount_code',
                discount_code: discountCode,
                security: discountCodeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var platformFee = response.data.platform_fee;
                    $('#discount-code-message').text('Discount applied! Platform fee: ' + platformFee + '%');
                    $('#discount-code-message').css('color', 'green');
                    updateTotalCost(platformFee);
                } else {
                    $('#discount-code-message').text(response.data);
                    $('#discount-code-message').css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                $('#discount-code-message').text('An error occurred: ' + error);
                $('#discount-code-message').css('color', 'red');
                console.error('AJAX Error: ', error);
                console.error('Response: ', xhr.responseText);
            }
        });
    });

    function updateTotalCost(platformFee) {
        var t_sum = 0;
        jQuery('.total-amount').each(function() {
			var t = jQuery(this).html();
			t_sum += Number(t);
		});
        var origin = t_sum;
        var pf_value = platformFee / 100;
        var pf = t_sum * pf_value;
        var u_sf = t_sum * pf_value * 0.13;
        var total_amount = Number(origin) + pf + u_sf;

        jQuery('.total-print .total_amount').html('$' + total_amount.toFixed(2));
        jQuery('.pf').html('$' + pf.toFixed(2));
        jQuery('.u_sf').html('$' + u_sf.toFixed(2));
        jQuery('.pf1').html('$' + pf.toFixed(2));
        jQuery('.u_sf1').html('$' + u_sf.toFixed(2));
        $('.total-print tbody tr:last-child .pf').html('$' + pf.toFixed(2));
        $('.total-print tbody tr:last-child .u_sf').html('$' + u_sf.toFixed(2));
        $('.total-print tbody tr:last-child .total_own').html('$' + total_amount.toFixed(2));
        $('.total_amount1').html('$' + total_amount.toFixed(2));
        $('.c_ff').html('$' + (0.05 * t_sum).toFixed(2));
        $('.c_ff').html('5%');
        var own_sum = t_sum * 0.95;
        jQuery('.creative_own').html('$' + own_sum.toFixed(2));
    }
});
