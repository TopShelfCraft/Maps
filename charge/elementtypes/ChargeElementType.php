<?php
namespace Craft;

/**
 * Charge element type
 */
class ChargeElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Charges');
	}


	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		return array(
			'*' => array('label' => Craft::t('All charges')),
		);
	}


	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'id'           => Craft::t('ID'),
			'mode'         => Craft::t('Mode'),
			'planAmount'   => Craft::t('Amount'),
			'customerName' => Craft::t('Customer'),
			'cardLast4'    => Craft::t('Payment'),
			'planType'     => Craft::t('Type'),
			'timestamp'    => Craft::t('Date')
		);
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('mode', 'planAmount', 'customerName', 'customerEmail', 'planType', 'cardLast4', 'hash');
	}



	/**
	 * Returns the table view HTML for a given attribute.
	 *
	 * @param BaseElementModel $element
	 * @param string $attribute
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		switch ($attribute)
		{
			case 'mode':
			{
				return ucwords($element->mode);
			}

			case 'planAmount':
			{
				if($element->planType == 'recurring') {
					return $element->formatPlanName();
				}
				else return $element->formatPlanAmount();

			}

			case 'customerName':
			{
				return $element->customerName.' <a href="mailto:'.$element->customerEmail.'">'.$element->customerEmail.'</a>';
			}

			case 'cardLast4':
			{
				return '<span class="cardType type'.$element->cardType.'"></span> '.$element->formatCard();
			}

			case 'planType':
			{
				if($element->planType == 'recurring') {
					return ucwords($element->planType);
				} else return 'One-time';
			}

			case 'timestamp':
			{
				if ($element->timestamp)
				{
					return $element->timestamp->localeDate();
				}
				else
				{
					return '';
				}
			}
		}
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'userId'    => AttributeType::Mixed,
			'timestamp' => AttributeType::Mixed,
			'hash'		=> AttributeType::String,
			'order'     => array(AttributeType::String, 'default' => 'timestamp desc'),
			'customerName' => AttributeType::String,
			'customerEmail' => AttributeType::Email,
			'planType'	=> AttributeType::Enum
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('charges.userId, charges.planCurrency, charges.planAmount, charges.planType, charges.planInterval, charges.planIntervalCount, charges.cardName, charges.cardExpMonth, charges.cardExpYear, charges.cardAddressLine1, charges.cardAddressLine2, charges.cardAddressCity, charges.cardAddressState, charges.cardAddressZip, charges.cardAddressCountry, charges.cardLast4, charges.cardType, charges.customerName, charges.customerEmail, charges.stripe, charges.mode, charges.description, charges.timestamp, charges.hash, charges.notes, charges.planCoupon, charges.planCouponStripeId, charges.planDiscount, charges.planFullAmount')
			->join('charges charges', 'charges.id = elements.id');

		if ($criteria->userId)
		{
			$query->andWhere(DbHelper::parseParam('charges.userId', $criteria->userId, $query->params));
		}

		if ($criteria->timestamp)
		{
			$query->andWhere(DbHelper::parseDateParam('charges.timestamp', $criteria->timestamp, $query->params));
		}

		if ($criteria->hash)
		{
			$query->andWhere(DbHelper::parseParam('charges.hash', $criteria->hash, $query->params));
		}


		if ($criteria->customerEmail)
		{
			$query->andWhere(DbHelper::parseParam('charges.customerEmail', $criteria->customerEmail, $query->params));
		}


		if ($criteria->customerName)
		{
			$query->andWhere(DbHelper::parseParam('charges.customerName', $criteria->customerName, $query->params));
		}



	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return ChargeModel::populateModel($row);
	}
}
