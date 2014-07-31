<?php

namespace Craft;

class Charge_CouponService extends BaseApplicationComponent
{

    public function getAll()
    {
        $couponRecords = Charge_CouponRecord::model()->findAll();
        return Charge_CouponModel::populateModels($couponRecords);
    }


    public function getCouponById($id)
    {
        $couponModel = $this->_getCouponModelById($id);

        return $couponModel;
    }


    /**
     * Saves an asset source.
     *
     * @param AssetSourceModel $source
     * @return bool
     */
    public function saveCoupon(Charge_CouponModel $coupon)
    {
        $couponRecord = $this->_getCouponRecordById($coupon->id);
        $couponRecord->stripeId = $coupon->stripeId;
        $couponRecord->name = $coupon->name;
        $couponRecord->code = $coupon->code;
        $couponRecord->paymentType = $coupon->paymentType;
        $couponRecord->couponType = $coupon->couponType;
        $couponRecord->percentageOff = $coupon->percentageOff;
        $couponRecord->amountOff = $coupon->amountOff;
        $couponRecord->currency = $coupon->currency;
        $couponRecord->duration = $coupon->duration;
        $couponRecord->durationInMonths = $coupon->durationInMonths;
        $couponRecord->maxRedemptions = $coupon->maxRedemptions;
        $couponRecord->redeemBy = $coupon->redeemBy;


        $createNew = FALSE;
        $removeOld = FALSE;

        if($coupon->paymentType == 'recurring') {
            // We have to deal with keeping our stripe coupon in sync too

            if($coupon->stripeId == '' OR $coupon->stripeId == null) {
                $createNew = TRUE;
            } else {
                // We have a stripeId on the coupon, but the admin may have updated the details of the coupon
                // from the last version. We can simply delete the existing coupon, and replace.
                // This won't affect any exisitng users on the old coupon
                $stripeCoupon = craft()->charge->checkCouponExists($coupon->stripeId);

                if($stripeCoupon === FALSE) {
                    // Not valid, we'll need to create a new one
                    $createNew = TRUE;
                } else {
                    // Check the details. If nothing has changed, we don't need to touch it
                    if(!$this->_compareCoupons($coupon, $stripeCoupon)) {
                        $removeOld = TRUE;
                        $createNew = TRUE;

                        // Adjust the new maxRedemptions, taking into account any previous usages on the old code
                        $coupon->maxRedemptions = $coupon->maxRedemptions - $stripeCoupon->times_redeemed;
                    }
                }
            }

            if($removeOld === TRUE) {
                // Kill an old code
                craft()->charge->deleteCoupon($coupon->stripeId);
            }
        }


        if ($coupon->validate() AND $couponRecord->validate())
        {
            $isNewCoupon = $couponRecord->isNewRecord();


            if($createNew === TRUE) {
                // No Stripe Id (or invalid), we'll create a new one
                $coupon->stripeId = $coupon->code . ' - ' . craft()->getSecurityManager()->generateRandomString(8, false);
                $stripeCoupon = craft()->charge->createCoupon($coupon);

                if($stripeCoupon === FALSE) {
                    $coupon->addErrors(craft()->charge->errors);
                    return false;
                }

                $couponRecord->stripeId = $coupon->stripeId;
            }

            $couponRecord->save(false);

            // Now that we have a coupon ID, save it on the model
            if (!$coupon->id)
            {
                $coupon->id = $couponRecord->id;
            }

            return true;
        }
        else
        {
            $coupon->addErrors($couponRecord->getErrors());
            return false;
        }
    }


    /**
    * Validates an inbound coupon, applies or adds an error
    *
    */
    public function getCouponByCode($code)
    {
        $coupon = $this->_getCouponModelByCode($code);
        return $coupon;
    }

    /**
    * Validates an inbound coupon, applies or adds an error
    *
    */
    public function handleCoupon(ChargeModel &$model)
    {
        $code = $model->planCoupon;

        // See if we have a matching record anywhere
        $coupon = $this->_getCouponModelByCode($code);
        if($coupon == FALSE) {
            // Not a valid code
            // Add an error to the model
            $model->addError('planCoupon', Craft::t('Sorry, not a valid coupon code'));
            return $model;
        }

        $type = 'one-off';
        if($model->planIntervalCount >= 1) $type = 'recurring';

        // Match to the paymentType
        if($coupon->paymentType == 'recurring' AND $type != 'recurring') {
            $model->addError('planCoupon', Craft::t('Sorry, this coupon can only be used on recurring payments'));
            return;
        } else if($coupon->paymentType == 'one-off' AND $type != 'one-off') {
            $model->addError('planCoupon', Craft::t('Sorry, this coupon can only be used on one-time payments'));
            return;
        }


        // We'll also need to branch
        // If the $type is recurring, the discount is dealt with direclty on Stripe's side,
        // if it's one-off, we have to do the leg work here
        if($type == 'recurring') {
            // We have the planCoupon market attached to the model,
            // and we'll return here, and do the work later
            return;
        }

        // Only dealing with one-time coupons now.
        $baseAmount = $model->planAmount * 100; // @todo - temp fix
        $baseCurrency = $model->planCurrency;

        $discountAmount = 0;
        $finalAmount = $baseAmount;

        if($coupon->couponType == 'percentage') {

            $percentageOff = $coupon->percentageOff;

            $discountAmount = (double) $baseAmount * ( $percentageOff / 100 );
            $finalAmount = (double) $baseAmount - $discountAmount;
        }


        if($coupon->couponType == 'amount') {

            $amountOff = $coupon->amountOff;

            $discountAmount = $amountOff;
            $finalAmount = (double) $baseAmount - $amountOff;
        }


        // Sanity Check
        if($discountAmount <= 0) {
            // nope, discount is zero.
            $model->addError('planCoupon', Craft::t('Sorry, this coupon is invalid'));
            return;
        }

        if($finalAmount >= $baseAmount) {
            // nope, somehow this 'discount' has increased the price
            $model->addError('planCoupon', Craft::t('Sorry, this coupon is invalid'));
            return;
        }

        // Check we're still above the min transaction price
        if($finalAmount <= 0.5) {
            $model->addError('planCoupon', Craft::t('Sorry, applying this coupon brings your total below the minimum we can charge'));
            return;
        }

        $finalAmount = strval($finalAmount) / 100; // @todo temp fix for coupons
        $discountAmount = strval($discountAmount);

        $model->planAmount = $finalAmount;
        $model->planDiscount = $discountAmount;
        $model->planFullAmount = $baseAmount;

        return;
    }

    /**
     * Gets a coupons's record.
     *
     * @access private
     * @param int $couponId
     * @return Charge_CouponModel
     */
    private function _getCouponRecordById($couponId = null)
    {
        if ($couponId)
        {
            $couponRecord = Charge_CouponRecord::model()->findById($couponId);

            if (!$couponRecord)
            {
                $this->_noCouponExists($couponId);
            }
        }
        else
        {
            $couponRecord = new Charge_CouponRecord();
        }

        return $couponRecord;
    }


    /**
     * Gets a coupons's model.
     *
     * @access private
     * @param int $couponId
     * @return Charge_CouponModel
     */
    private function _getCouponModelById($couponId = null)
    {
        $record = $this->_getCouponRecordById($couponId);

        $model = Charge_CouponModel::populateModel($record);

        return $model;
    }



    /**
     * Gets a coupons's model.
     *
     * @access private
     * @param varchar $code
     * @return Charge_CouponModel
     */
    private function _getCouponModelByCode($code = null)
    {
        if ($code)
        {
            $couponRecord = Charge_CouponRecord::model()->findByAttributes(
                                                    array('code' => $code));

            if (!$couponRecord)
            {
                return false;
            }

            $model = Charge_CouponModel::populateModel($couponRecord);
            return $model;
        }

        return false;
    }



    /**
     * Throws a "No source exists" exception.
     *
     * @access private
     * @param int $couponId
     * @throws Exception
     */
    private function _noCouponExists($couponId)
    {
        throw new Exception(Craft::t('No coupon exists with the ID “{id}”', array('id' => $couponId)));
    }



    /**
     * Delete a coupon from the db
     *
     * @param  int $id
     * @return int The number of rows affected
     */
    public function deleteCouponById($id)
    {
        $couponRecord = $this->_getCouponRecordById($id);

        // If this is a recurring coupon, we'll also need to remove it from the Stripe api
        if($couponRecord->paymentType == 'recurring') {
            // Delete the stripe coupon too
            craft()->charge->deleteCoupon($couponRecord->stripeId);
        }

        return $couponRecord->deleteByPk($id);
    }

    private function _compareCoupons(Charge_CouponModel &$coupon, \Stripe_Coupon &$stripeCoupon)
    {
        // Test the type
        $type = $coupon->couponType;

        if($stripeCoupon->percent_off != null) $stripeType = 'percentage';
        else $stripeType = 'amount';

        if($type != $stripeType) return FALSE;

        // The actual amounts may also have changed
        if($type == 'percentage') {
            if($coupon->percentageOff != $stripeCoupon->percent_off) return FALSE;
        }


        if($type == 'amount') {
            if($coupon->amountOff != $stripeCoupon->amount_off) return FALSE;
            if($coupon->currency != $stripeCoupon->currency) return FALSE;
        }

        // The duration may have changed too
        if($coupon->duration != $stripeCoupon->duration) return FALSE;

        if($coupon->duration == 'repeating') {
            if($coupon->durationInMonths != $stripeCoupon->duration_in_months) return FALSE;
        }

        // And Max Durations
        if($coupon->maxRedemptions != $stripeCoupon->max_redemptions) return FALSE;

        return TRUE;
    }


}
