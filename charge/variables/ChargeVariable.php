<?php

namespace Craft;

class ChargeVariable
{
    public function getPublicKey()
    {
        return craft()->charge->getPublicKey();
    }

    /**
     * Returns an ElementCriteriaModel set to find charges.
     *
     * @param array|null $criteria
     * @return ElementCriteriaModel
     */
    public function charges($criteria = null)
    {
    	return craft()->elements->getCriteria('Charge', $criteria);
    }


    public function setProtected($values)
    {
        return implode('-',array_keys($values));
    }

}
