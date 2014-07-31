<?php

namespace Craft;

class ChargeService extends BaseApplicationComponent
{
    private $apiVersion = '2013-12-03';
    protected $stripeRecord;
    public $errors = array();
    private $_mode = 'test';

    private $activeCoupon;

    public function init()
    {
        require(craft()->path->getPluginsPath().'charge/vendor/stripe/lib/Stripe.php');

        $plugin = craft()->plugins->getPlugin('charge');

        if (!$plugin)
        {
            throw new Exception('Couldn’t find the Charge plugin!');
        }

        $settings = $plugin->getSettings();

        $this->_mode = $settings->stripeAccountMode;

        $keyName = 'stripe'.ucwords($settings->stripeAccountMode).'CredentialsSK';

        if(isset($settings->$keyName)) \Stripe::setApiKey($settings->$keyName);

        \Stripe::setApiVersion($this->apiVersion);

    }

    public function getChargeById($id)
    {
        return craft()->elements->getElementById($id, 'Charge');
    }

    public function getChargeByHash($hash)
    {
        $criteria = craft()->elements->getCriteria('Charge');
        $criteria->hash = $hash;
        return $criteria->first();
    }

    public function updateChargeDetails($id, $details)
    {
        $record = ChargeRecord::model()->findByPk($id);

        foreach($details as $key => $val)
        {
            $record->$key = $val;
        }

        return $record->save();
    }

    public function getPublicKey()
    {
        $plugin = craft()->plugins->getPlugin('charge');

        $settings = $plugin->getSettings();


        $keyName = 'stripe'.ucwords($settings->stripeAccountMode).'CredentialsPK';

        $this->_mode = $settings->stripeAccountMode;

        if(isset($settings->$keyName)) return $settings->$keyName;
        return '';
    }


    public function handlePayment(ChargeModel &$model)
    {
        $plugin = craft()->plugins->getPlugin('charge');

        $settings = $plugin->getSettings();

        $model->planAmount = $model->planAmount * 100;

        if($model->planCurrency == '') $model->planCurrency = $settings->stripeDefaultCurrency;

        // Fire an 'onBeforeCharge' event
        $this->onBeforeCharge(new Event($this, array(
            'charge' => $model
        )));

        $type = 'charge';
        // Are we Single or Recurring?
        if($model->planIntervalCount >= 1) {
            // Handle Recurring
            if($model->planInterval == '') $model->planInterval = 'month';
            $type = 'recurring';

            // If we have a coupon, deal with it now
            if($model->planCoupon != '') {
                // We have some work to do
                $stripeCoupon = $this->_findCoupon($model);
            }
        }

        $success = $this->_handlePayment($model, $type);

        if ($success) {
            // Fire an 'onCharge' event
            $this->onCharge(new Event($this, array(
                'charge' => $model
            )));
        }

        return $success;
    }

    /**
     * Fires an 'onBeforeCharge' event.
     *
     * @param Event $event
     */
    public function onBeforeCharge(Event $event)
    {
        $this->raiseEvent('onBeforeCharge', $event);
    }

    /**
     * Fires an 'onCharge' event.
     *
     * @param Event $event
     */
    public function onCharge(Event $event)
    {
        $this->raiseEvent('onCharge', $event);
    }

    private function _handlePayment(ChargeModel &$model, $type = 'charge')
    {
        if($type != 'recurring') $type = 'charge';

        try {

            $plan = FALSE;
            $customer = FALSE;

            if($type == 'recurring') {
                // Get or Create the Recurring plan
                $plan = $this->_findOrCreate($model);
                if($plan == FALSE) return FALSE;

                // Create the customer
                $customer = $this->_createAddToPlan($model, $plan);
                if($customer == FALSE) return FALSE;

                // Adjust the amount so our records are correct
                if($model->planCoupon != null) {
                    // This will only affect one-off payments, not recurring
                    // recurring payment coupons are handled on the stripe side
                    $this->_adjustPlanForCoupon($model);
                }

            } else {
                $stripeCustomer = $this->_createCustomer($model);
                if($stripeCustomer == FALSE) return FALSE;

                $stripeCharge = \Stripe_Charge::create(array(
                    'customer' => $stripeCustomer,
                    'amount' => $model->planAmount,
                    'currency' => $model->planCurrency,
                    'description' => $model->description
                ));

                // Now wipe the card from the customer
                $this->_wipeCustomerCard($stripeCustomer);
            }


            $user = craft()->userSession->getUser();

            // Create the element
            if (craft()->elements->saveElement($model, false))
            {
                $model->hash        = craft()->getSecurityManager()->generateRandomString(32);
                $model->sourceUrl   = craft()->request->getPath();
                $model->planType    = $type;
                $model->mode        = $this->_mode;
                $model->timestamp   = new DateTime();

                $record = new ChargeRecord();
                $record->setAttributes($model->getAttributes());

                if ($user) $record->userId      = $user->id;
                $record->id                     = $model->id;
                $record->hash                   = $model->hash;
                $record->sourceUrl              = $model->sourceUrl;
                $record->mode                   = $model->mode;
                $record->timestamp              = $model->timestamp;
                $record->description            = $model->description;

                $record->customerName           = $model->customerName;
                $record->customerEmail          = $model->customerEmail;

                $record->planAmount             = $model->planAmount;
                $record->planCurrency           = $model->planCurrency;
                $record->planType               = $model->planType;
                $record->planInterval           = $model->planInterval;
                $record->planIntervalCount      = $model->planIntervalCount;
                $record->planCoupon             = $model->planCoupon;
                $record->planCouponStripeId     = $model->planCouponStripeId;
                $record->planDiscount           = $model->planDiscount;
                $record->planFullAmount         = $model->planFullAmount;

                $record->cardType               = $model->cardType;
                $record->cardLast4              = $model->cardLast4;
                $record->cardExpMonth           = $model->cardExpMonth;
                $record->cardExpYear            = $model->cardExpYear;
                $record->cardName               = $model->cardName;
                $record->cardAddressLine1       = $model->cardAddressLine1;
                $record->cardAddressLine2       = $model->cardAddressLine2;
                $record->cardAddressCity        = $model->cardAddressCity;
                $record->cardAddressState       = $model->cardAddressState;
                $record->cardAddressZip         = $model->cardAddressZip;
                $record->cardAddressCountry     = $model->cardAddressCountry;

                $record->insert();
            }


            craft()->search->indexElementAttributes($model);

        } catch(\Exception $e) {
            $this->errors[] = $e->getMessage();
            return FALSE;
        }

        return TRUE;
    }

    private function _createCustomer(ChargeModel &$model)
    {
        try {
            $customer = \Stripe_Customer::create(array(
              "card"    => $model->cardToken,
              "email"   => $model->customerEmail
            ));

            return $customer;

        } catch(\Exception $e) {
            $this->errors[] = $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }

    private function _wipeCustomerCard(\Stripe_Customer $stripeCustomer)
    {
        try {
            // Pull out the card id from the \Stripe_List on the Stripe_Customer
            $cards = $stripeCustomer->cards->__toArray();
            $card = current($cards['data']);

            $stripeCustomer->cards->retrieve($card->id)->delete();

            return TRUE;

        } catch(\Exception $e) {
            $this->errors[] = $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }

    private function _createAddToPlan(ChargeModel &$model, \Stripe_Plan &$plan)
    {
        try {

            $customer = array(
              "card"    => $model->cardToken,
              "email"   => $model->customerEmail,
              "plan"    => $plan);

            if($model->planCouponStripeId != '') $customer['coupon'] = $model->planCouponStripeId;

            $customer = \Stripe_Customer::create($customer);

            return $customer;

        } catch(\Exception $e) {
            $this->errors[] = $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }


    private function _findOrCreate(ChargeModel &$model)
    {
        $planName = $this->constructPlanName($model);

        $recurringPlan = $this->_checkPlanExists($planName);

        if($recurringPlan === FALSE) {
            // We must create a new plan first
            $recurringPlan = $this->_createPlan($planName, $model);
        }

        return $recurringPlan;
    }


    private function _createPlan($planName, ChargeModel &$model)
    {
        $response = array();
        $planId = $planName;

        try {
            $p = \Stripe_Plan::create(array(
                "amount"            => $model->planAmount,
                "interval"          => $model->planInterval,
                "interval_count"    => $model->planIntervalCount,
                "name"              => $planName,
                "currency"          => $model->planCurrency,
                "id"                => $planId,
                "trial_period_days" => null));


            return $p;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }




    private function _checkPlanExists($planName)
    {
        try {
            $p = \Stripe_Plan::retrieve($planName);

            return $p;

        } catch (\Stripe_InvalidRequestError $e) {
            if ($e->getHttpStatus() == '404') {
                return FALSE;
            }
        } catch(\Exception $ex) {
            return FALSE;
        }

        return FALSE;
    }



    public function checkCouponExists($couponCode)
    {
        try {
            $c = \Stripe_Coupon::retrieve($couponCode);

            return $c;

        } catch (\Stripe_InvalidRequestError $e) {
            if ($e->getHttpStatus() == '404') {
                return FALSE;
            }
        } catch(\Exception $ex) {
            return FALSE;
        }

        return FALSE;
    }

    public function deleteCoupon($stripeId)
    {
        $response = array();

        try {

            $c = \Stripe_Coupon::retrieve($stripeId);
            $c->delete();

            return TRUE;

        } catch (\Exception $e) {
            $this->errors[] = 'Failed to delete coupon - '. $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }

    public function createCoupon(Charge_CouponModel &$model)
    {
        $response = array();

        try {
            // Collect our neat array of attributes
            $coupon = array();
            $coupon['id'] = $model->stripeId;

            if($model->couponType == 'amount') {
                $coupon['amount_off'] = $model->amountOff;
                $coupon['currency'] = $model->currency;
            } elseif($model->couponType == 'percentage') {
                $coupon['percent_off'] = $model->percentageOff;
            }

            $coupon['duration'] = $model->duration;
            if($model->duration == 'repeating') {
                $coupon['duration_in_months'] = $model->durationInMonths;
            }

            if($model->maxRedemptions != '' AND $model->maxRedemptions != '0') $coupon['max_redemptions'] = $model->maxRedemptions;
            if($model->redeemBy != '' AND $model->redeemBy != 0) $coupon['redeem_by'] = $model->redeemBy;


            $c = \Stripe_Coupon::create($coupon);

            return $c;

        } catch (\Exception $e) {
            $this->errors[] = 'Coupon - '. $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }

    public function constructPlanName(ChargeModel &$model, $format = 'safe')
    {
        // Allow this to be overridden from the model, so if a dev want's to set
        // a specific name using the onBeforeCharge event, they can
        if(isset($model->planName) and $model->planName != '') {
            return $model->planName;
        }

        // 75 Every [x] Month(s)
        if($format == 'symbol') {
            $currency = ChargePlugin::getCurrencies($model->planCurrency);
            $planName[] = $currency['symbol'] . number_format($model->planAmount/100,2);
        } else {
            $planName[] = number_format($model->planAmount/100,2);
            $planName[] = strtoupper($model->planCurrency);
        }

       // $plan_name[] = $plan['amount'];

        if($model->planInterval == '') $model->planInterval = 'month';

        if( $model->planIntervalCount > 1 ) {
            // every [x] [period]s
            $planName[] = 'Every '.$model->planIntervalCount . ' ' . ucwords( $model->planInterval.'s' );
        } else {
            $planName[] = ucwords( $model->planInterval . 'ly' );
        }

        return implode(' ', $planName);
    }


    private function _constructPlanDescription($period, $period_count)
    {
        $plan_name = array();

        if( $period_count > 1 ) {
            // every [x] [period]s
            $plan_name[] = 'Every '.$period_count . ' ' . ucwords( $period.'s' );
        } else {
            $plan_name[] = ucwords( $period . 'ly' );
        }

        return implode(' ', $plan_name);
    }


    /**
     * Creates a new ElementRecord, saves and returns it.
     *
     * @access private
     * @return ElementRecord
     */
    private function _createNewElementRecord()
    {
        $elementRecord = new ElementRecord();
        $elementRecord->type = 'Charge';
        $elementRecord->enabled = 1;
        $elementRecord->save();

        return $elementRecord;
    }


    public function getAll()
    {
        $criteria = craft()->elements->getCriteria('Charge');
        $criteria->limit = null;

        return $criteria->find();
    }


    private function _findCoupon(ChargeModel &$model)
    {
        if($model->planCoupon == '') return;

        // Pull the coupon from our coupon model
        $coupon = craft()->charge_coupon->getCouponByCode($model->planCoupon);
        if($coupon == FALSE OR empty($coupon)) return;

        $this->activeCoupon = $coupon;

        $stripeCoupon = $this->checkCouponExists($coupon->stripeId);

        if($stripeCoupon !== FALSE) {
            $model->planCouponStripeId = $coupon->stripeId;
        }
        return $stripeCoupon;
    }

    private function _adjustPlanForCoupon(ChargeModel &$model)
    {
        if($model->planCoupon == '') return;
        if(!isset($this->activeCoupon->paymentType)) return;
        if($this->activeCoupon->paymentType != 'recurring') return;

        $fullAmount = $model->planAmount;
        $discountAmount = 0;

        switch($this->activeCoupon->couponType) {
            case 'percentage' :
                $discountAmount = $fullAmount * ( $this->activeCoupon->percentageOff / 100 );
            break;
            case 'amount' :
                $discountAmount = $this->activeCoupon->amountOff;
            break;
        }

        if($discountAmount <= 0) return;

        // Have an adjustment
        // All of these amounts are in cents/pence
        $newAmount = $fullAmount - $discountAmount;
        $model->planAmount = $newAmount;
        $model->planDiscount = $discountAmount;
        $model->planFullAmount = $fullAmount;

        return $model;
    }
}
