/**
 * Flutterwave Payment Integration for URUHUSHYA
 */

/**
 * Initialize payment for a plan
 */
function initiatePayment(planId, planPrice, planName) {
    // Get the button that was clicked
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<span>⏳ Processing...</span>';
    
    // Make request to initialize payment
    fetch('process-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'plan_id=' + planId
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to initialize payment');
        }
        
        // Open Flutterwave payment modal
        openFlutterwaveModal(data);
        
        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Payment Error:', error);
        alert('❌ Error: ' + error.message);
        
        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

/**
 * Open Flutterwave payment modal
 */
function openFlutterwaveModal(paymentData) {
    FlutterwaveCheckout({
        public_key: paymentData.public_key,
        tx_ref: paymentData.payment_data.tx_ref,
        amount: paymentData.payment_data.amount,
        currency: paymentData.payment_data.currency,
        payment_options: paymentData.payment_data.payment_options,
        customer: paymentData.payment_data.customer,
        customizations: paymentData.payment_data.customizations,
        meta: paymentData.payment_data.meta,
        callback: function(data) {
            console.log('✅ Payment callback:', data);
            
            // Redirect to callback URL for verification
            window.location.href = 'payment-callback.php?status=' + data.status + 
                                   '&tx_ref=' + data.tx_ref + 
                                   '&transaction_id=' + data.transaction_id;
        },
        onclose: function() {
            console.log('Payment modal closed by user');
        }
    });
}