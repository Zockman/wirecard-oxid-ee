<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */

namespace Wirecard\Oxid\Extend\Model;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\OrderArticle;

use Wirecard\PaymentSdk\Entity\AccountHolder;

use Wirecard\Oxid\Core\Helper;
use Wirecard\Oxid\Model\Transaction;
use Wirecard\Oxid\Core\AccountHolderHelper;

/**
 * Class Order
 *
 * @package Wirecard\Extend
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class Order extends Order_parent
{
    const STATE_PENDING = 'pending';
    const STATE_AUTHORIZED = 'authorized';
    const STATE_PROCESSING = 'processing';
    const STATE_CANCELED = 'canceled';
    const STATE_REFUNDED = 'refunded';


    /**
     * Loads order data from DB.
     * Returns true on success.
     *
     * @param string $sTransactionId
     *
     * @return bool
     */
    public function loadWithTransactionId(string $sTransactionId)
    {
        //getting at least one field before lazy loading the object
        $this->_addField('wdoxidee_transactionid', 0);
        $query = $this->buildSelectString([$this->getViewName() . '.wdoxidee_transactionid' => $sTransactionId]);
        $this->_isLoaded = $this->assignRecord($query);

        return $this->_isLoaded;
    }

    /**
     * Returns the country associated with the order billing address.
     *
     * @return Country
     */
    public function getOrderBillingCountry(): Country
    {
        $oCountry = oxNew(Country::class);
        $oCountry->load($this->oxorder__oxbillcountryid->value);

        return $oCountry;
    }

    /**
     * Returns the country associated with the order shipping address.
     *
     * @return Country
     */
    public function getOrderShippingCountry(): Country
    {
        $oCountry = oxNew(Country::class);
        $oCountry->load($this->oxorder__oxdelcountryid->value);

        return $oCountry;
    }

    /**
     * Returns the payment associated with the order.
     *
     * @return Payment
     */
    public function getOrderPayment(): Payment
    {
        $oPayment = oxNew(Payment::class);
        $oPayment->load($this->oxorder__oxpaymenttype->value);

        return $oPayment;
    }

    /**
     * Returns true if the payment is one of the module's.
     *
     * @return bool
     */
    public function isCustomPaymentMethod()
    {
        return $this->getOrderPayment()->isCustomPaymentMethod();
    }

    /**
     * Checks if the payment is pending
     *
     * @return bool
     */
    public function isPaymentPending()
    {
        $sTransactionId = $this->oxorder__wdoxidee_transactionid->value;
        if ($sTransactionId) {
            $oTransaction = oxNew(Transaction::class);
            if ($oTransaction->loadWithTransactionId($sTransactionId)) {
                return strpos($oTransaction->wdoxidee_ordertransactions__type->value, 'pending') !== false;
            }
        }

        return true;
    }

    /**
     * Checks if the payment was successful
     *
     * @return bool
     */
    public function isPaymentSuccess()
    {
        return $this->oxorder__wdoxidee_orderstate->value === self::STATE_AUTHORIZED
            || $this->oxorder__wdoxidee_orderstate->value === self::STATE_PROCESSING;
    }

    /**
     * Returns true if it is the last article in the order
     *
     * @param OrderArticle $oOrderItem
     * @return bool
     */
    public function isLastArticle($oOrderItem)
    {
        $aArticles = $this->getOrderArticles(true)->getArray();
        foreach ($aArticles as $oArticle) {
            $oLastItem = $oArticle;
        }

        return $oLastItem->oxorderarticles__oxartid->value === $oOrderItem->oxorderarticles__oxartid->value;
    }

        /**
     * Returns an associative array of available states and their translation.
     *
     * @return array
     */
    public static function getTranslatedStates()
    {
        return [
            self::STATE_PENDING => Helper::translate('order_status_pending'),
            self::STATE_AUTHORIZED => Helper::translate('order_status_authorized'),
            self::STATE_PROCESSING => Helper::translate('order_status_purchased'),
            self::STATE_CANCELED => Helper::translate('order_status_cancelled'),
            self::STATE_REFUNDED => Helper::translate('order_status_refunded'),
        ];
    }

    /**
     * Creates an AccountHolder object for the order.
     *
     * @return AccountHolder
     */
    public function getAccountHolder(): AccountHolder
    {
        $oCountry = $this->getOrderBillingCountry();
        $oUser = $this->getOrderUser();

        $oAccHolderHelper = new AccountHolderHelper;

        return $oAccHolderHelper->createAccountHolder([
            'countryCode' => $oCountry->oxcountry__oxisoalpha2->value,
            'city' => $this->oxorder__oxbillcity->value,
            'street' => $this->oxorder__oxbillstreet->value . ' ' . $this->oxorder__oxbillstreetnr->value,
            'state' => $this->oxorder__oxbillstateid->value,
            'postalCode' => $this->oxorder__oxbillzip->value,
            'firstName' => $this->oxorder__oxbillfname->value,
            'lastName' => $this->oxorder__oxbilllname->value,
            'phone' => $this->oxorder__oxbillfon->value,
            'email' => $this->oxorder__oxbillemail->value,
            'gender' => Helper::getGenderCodeForSalutation($this->oxorder__oxbillsal->value),
            'dateOfBirth' => Helper::getDateTimeFromString($oUser->oxuser__oxbirthdate->value),
        ]);
    }

    /**
     * Creates a shipping AccountHolder object for the order.
     *
     * @return AccountHolder
     */
    public function getShippingAccountHolder(): AccountHolder
    {
        $oAccHolderHelper = new AccountHolderHelper;

        // use shipping info if available
        $oCountry = $this->getOrderShippingCountry();
        if (!empty($oCountry->oxcountry__oxisoalpha2->value)) {
            return $oAccHolderHelper->createAccountHolder([
                'countryCode' => $oCountry->oxcountry__oxisoalpha2->value,
                'city' => $this->oxorder__oxdelcity->value,
                'street' => $this->oxorder__oxdelstreet->value . ' ' . $this->oxorder__oxdelstreetnr->value,
                'state' => $this->oxorder__oxdelstateid->value,
                'postalCode' => $this->oxorder__oxdelzip->value,
                'firstName' => $this->oxorder__oxdelfname->value,
                'lastName' => $this->oxorder__oxdellname->value,
                'phone' => $this->oxorder__oxdelfon->value,
            ]);
        }

        // fallback to billing info
        $oCountry = $this->getOrderBillingCountry();
        return $oAccHolderHelper->createAccountHolder([
            'countryCode' => $oCountry->oxcountry__oxisoalpha2->value,
            'city' => $this->oxorder__oxbillcity->value,
            'street' => $this->oxorder__oxbillstreet->value . ' ' . $this->oxorder__oxbillstreetnr->value,
            'state' => $this->oxorder__oxbillstateid->value,
            'postalCode' => $this->oxorder__oxbillzip->value,
            'firstName' => $this->oxorder__oxbillfname->value,
            'lastName' => $this->oxorder__oxbilllname->value,
            'phone' => $this->oxorder__oxbillfon->value,
        ]);
    }

    /**
     * Returns an array of available states.
     *
     * @return array
     */
    public static function getStates()
    {
        return array_keys(self::getTranslatedStates());
    }

    /**
     * Returns the translation for the order's state.
     *
     * @return string
     */
    public function getTranslatedState()
    {
        return self::getTranslatedStates()[$this->oxorder__wdoxidee_orderstate->value] ?? '';
    }
}
