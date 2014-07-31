(function($) {

    $.fn.charge = function(form, error_container, progress) {

        var $form = $(form);
        var $error_container = $(error_container);
        var $progress = typeof progress !== 'undefined' ? $(progress) : $('.charge_indicator');

        $error_container.hide();
        $progress.hide();

        var stripeResponseHandler = function(status, response) {

            if (response.error) {
                    // show the errors on the form
                    $error_container.show().text(response.error.message).addClass('alert').addClass('alert-warning');

                    $form.find('button').prop('disabled', false);

                    return false;

            } else {

                var card = {
                    cardToken: response['id'],
                    cardLast4: response['card']['last4'],
                    cardType: response['card']['type'],
                    cardExpMonth: response['card']['exp_month'],
                    cardExpYear: response['card']['exp_year'],
                    cardFingerprint: response['card']['fingerprint'],
                    cardAddressLine1: response['card']['address_line1'],
                    cardAddressLine2: response['card']['address_line2'],
                    cardAddressCity: response['card']['address_city'],
                    cardAddressState: response['card']['address_state'],
                    cardAddressZip: response['card']['address_zip'],
                    cardAddressCountry: response['card']['address_country']
                }

                for (var prop in card) {
                    if(card.hasOwnProperty(prop)){
                        if(card[prop] != null) { $form.append("<input type='hidden' name=" + prop + " value='" + card[prop] + "'/>"); }
                    }
                }

                $form.get(0).submit();
            }
        }

        $form.submit(function(event) {

            $error_container.hide();
            $progress.show();

            if($form.find('[name=cardToken]').length == 0) {

                event.preventDefault();

                $form.find('button').prop('disabled', true);

                try {
                    Stripe.card.createToken($form, stripeResponseHandler);
                } catch(ex) {
                    $error_container.show().text(ex).addClass('alert').addClass('alert-warning');
                    $progress.hide();
                    $form.find('button').prop('disabled', false);
                }
            }
        });

    };

}(jQuery));
