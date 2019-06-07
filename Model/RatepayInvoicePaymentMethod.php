<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */

namespace Wirecard\Oxid\Model;

use DateTime;
use OxidEsales\Eshop\Core\Exception\InputException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Basket;

use Wirecard\Oxid\Core\Helper;
use Wirecard\Oxid\Core\PaymentMethodHelper;
use Wirecard\Oxid\Core\SessionHelper;
use Wirecard\Oxid\Extend\Model\Order;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;

/**
 * Payment method implementation for Ratepay Invoice
 *
 * @since 1.2.0
 */
class RatepayInvoicePaymentMethod extends PaymentMethod
{

    const UNIQUE_TOKEN_VARIABLE = 'wd_ratepay_unique_token';

    /**
     * @inheritdoc
     *
     * @since 1.2.0
     */
    protected static $_sName = "ratepay-invoice";

    /**
     * @inheritdoc
     *
     * @return Config
     *
     * @since 1.2.0
     */
    public function getConfig()
    {
        $oConfig = parent::getConfig();

        $oPaymentMethodConfig = new PaymentMethodConfig(
            RatepayInvoiceTransaction::NAME,
            $this->_oPayment->oxpayments__wdoxidee_maid->value,
            $this->_oPayment->oxpayments__wdoxidee_secret->value
        );

        $oConfig->add($oPaymentMethodConfig);
        return $oConfig;
    }

    /**
     * @inheritdoc
     *
     * @return RatepayInvoiceTransaction
     *
     * @since 1.2.0
     */
    public function getTransaction()
    {
        return new RatepayInvoiceTransaction();
    }

    /**
     * @inheritdoc
     *
     * @return array
     *
     * @since 1.2.0
     */
    public function getConfigFields()
    {
        $aAdditionalFields = [
            'descriptor' => [
                'type' => 'select',
                'field' => 'oxpayments__wdoxidee_descriptor',
                'options' => [
                    '1' => Helper::translate('wd_yes'),
                    '0' => Helper::translate('wd_no'),
                ],
                'title' => Helper::translate('wd_config_descriptor'),
                'description' => Helper::translate('wd_config_descriptor_desc'),
            ],
            'additionalInfo' => [
                'type' => 'select',
                'field' => 'oxpayments__wdoxidee_additional_info',
                'options' => [
                    '1' => Helper::translate('wd_yes'),
                    '0' => Helper::translate('wd_no'),
                ],
                'title' => Helper::translate('wd_config_additional_info'),
                'description' => Helper::translate('wd_config_additional_info_desc'),
            ],
            'deleteCanceledOrder' => [
                'type' => 'select',
                'field' => 'oxpayments__wdoxidee_delete_canceled_order',
                'options' => [
                    '1' => Helper::translate('wd_yes'),
                    '0' => Helper::translate('wd_no'),
                ],
                'title' => Helper::translate('wd_config_delete_cancel_order'),
                'description' => Helper::translate('wd_config_delete_cancel_order_desc'),
            ],
            'deleteFailedOrder' => [
                'type' => 'select',
                'field' => 'oxpayments__wdoxidee_delete_failed_order',
                'options' => [
                    '1' => Helper::translate('wd_yes'),
                    '0' => Helper::translate('wd_no'),
                ],
                'title' => Helper::translate('wd_config_delete_failure_order'),
                'description' => Helper::translate('wd_config_delete_failure_order_desc'),
            ],
            'allowedCurrencies' => [
                'type' => 'multiselect',
                'field' => 'oxpayments__allowed_currencies',
                'options' => PaymentMethodHelper::getCurrencyOptions(),
                'title' => Helper::translate('wd_config_allowed_currencies'),
                'description' => Helper::translate('wd_config_allowed_currencies_desc'),
                'required' => true,
            ],
            'shippingCountries' => [
                'type' => 'multiselect',
                'field' => 'oxpayments__shipping_countries',
                'options' => PaymentMethodHelper::getCountryOptions(),
                'title' => Helper::translate('wd_config_shipping_countries'),
                'description' => Helper::translate('wd_config_shipping_countries_desc'),
                'required' => true,
            ],
            'billingCountries' => [
                'type' => 'multiselect',
                'field' => 'oxpayments__billing_countries',
                'options' => PaymentMethodHelper::getCountryOptions(),
                'title' => Helper::translate('wd_config_billing_countries'),
                'description' => Helper::translate('wd_config_billing_countries_desc'),
                'required' => true,
            ],
            'billingShipping' => [
                'type' => 'select',
                'field' => 'oxpayments__billing_shipping',
                'options' => [
                    '1' => Helper::translate('wd_yes'),
                    '0' => Helper::translate('wd_no'),
                ],
                'title' => Helper::translate('wd_config_billing_shipping'),
                'description' => Helper::translate('wd_config_billing_shipping_desc'),
            ],
        ];

        return parent::getConfigFields() + $aAdditionalFields;
    }

    /**
     * @inheritdoc
     *
     * @return array
     *
     * @since 1.2.0
     */
    public function getPublicFieldNames()
    {
        return array_merge(
            parent::getPublicFieldNames(),
            [
                'descriptor',
                'additionalInfo',
                'deleteCanceledOrder',
                'deleteFailedOrder',
                'allowedCurrencies',
                'shippingCountries',
                'billingCountries',
                'billingShipping',
            ]
        );
    }

    /**
     * @inheritdoc
     *
     * @return array
     *
     * @since 1.2.0
     */
    public function getMetaDataFieldNames()
    {
        return [
            'allowed_currencies',
            'shipping_countries',
            'billing_countries',
            'billing_shipping',
        ];
    }

    /**
     * @inheritdoc
     *
     * @return array
     *
     * @since 1.2.0
     */
    public function getCheckoutFields()
    {
        $aCheckoutFields = null;

        $aCheckoutFields = [
            'dateOfBirth' => [
                'type' => $this->_getCheckoutFieldType(SessionHelper::isDateOfBirthSet()),
                'title' => Helper::translate('wd_birthdate_input'),
                'description' => Helper::translate('wd_date_format_user_hint'),
                'required' => true,
            ],
        ];

        $aCheckoutFields = array_merge($aCheckoutFields, [
            'phone' => [
                'type' => $this->_getCheckoutFieldType(SessionHelper::isPhoneValid()),
                'title' => Helper::translate('wd_phone'),
                'required' => true,
            ],
        ]);

        if ($this->_checkSaveCheckoutFields($aCheckoutFields)) {
            $aCheckoutFields = array_merge($aCheckoutFields, [
                'saveCheckoutFields' => [
                    'type' => 'select',
                    'options' => [
                        '1' => Helper::translate('wd_yes'),
                        '0' => Helper::translate('wd_no'),
                    ],
                    'title' => Helper::translate('wd_save_to_user_account'),
                ],
            ]);
        }

        return $aCheckoutFields;
    }

    /**
     * @inheritdoc
     *
     * @param RatepayInvoiceTransaction $oTransaction
     * @param Order                     $oOrder
     *
     * @since 1.2.0
     */
    public function addMandatoryTransactionData(&$oTransaction, $oOrder)
    {
        $oSession = Registry::getSession();
        $oBasket = $oSession->getBasket();
        $oWdBasket = $oBasket->createTransactionBasket();

        $oTransaction->setBasket($oWdBasket);
        $oTransaction->setShipping($oOrder->getShippingAccountHolder());
        $oTransaction->setOrderNumber($oOrder->oxorder__oxid->value);
        
        $oAccountHolder = $oOrder->getAccountHolder();
        $oAccountHolder->setDateOfBirth(new DateTime(SessionHelper::getDbDateOfBirth()));
        $oAccountHolder->setPhone(SessionHelper::getPhone());
        $oTransaction->setAccountHolder($oAccountHolder);
    }

    /**
     * @inheritdoc
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function isPaymentPossible()
    {
        $oSession = Registry::getSession();
        $oBasket = $oSession->getBasket();
        $oBillingCountryId = $oSession->getUser()->oxuser__oxcountryid->value;
        $oShippingCountryId = $oBillingCountryId;

        if ($oSession->getVariable('deladrid')) {
            if ($this->getPayment()->oxpayments__billing_shipping->value) {
                return false;
            }

            $oShippingAddress = oxNew(Address::class);
            $oShippingAddress->load($oSession->getVariable('deladrid'));
            $oShippingCountryId = $oShippingAddress->oxaddress__oxcountryid->value;
        }

        // if basket amount is within range is checked by oxid, no need to handle that
        return $this->_checkDateOfBirth()
            && $this->_checkSettings($oBasket, $oBillingCountryId, $oShippingCountryId);
    }

    /**
     * Checks if given articles are allowed for this payment.
     *
     * @param array $aArticles
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _areArticlesAllowed($aArticles)
    {
        foreach ($aArticles as $oArticle) {
            if ($oArticle->oxarticles__oxisdownloadable->value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the selected currency is allowed for this payment.
     *
     * @param object $oCurrency
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _isCurrencyAllowed($oCurrency)
    {
        $oPayment = $this->getPayment();

        return in_array($oCurrency->name, $oPayment->oxpayments__allowed_currencies->value ?? []);
    }

    /**
     * Checks if given billing and shipping countries are allowed for this payment.
     *
     * @param string $oBillingCountryId
     * @param string $oShippingCountryId
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _areAddressesAllowed($oBillingCountryId, $oShippingCountryId)
    {
        $oPayment = $this->getPayment();
        $oBillingCountry = oxNew(Country::class);
        $oShippingCountry = oxNew(Country::class);

        $oBillingCountry->load($oBillingCountryId);
        $oShippingCountry->load($oShippingCountryId);

        return in_array(
            $oBillingCountry->oxcountry__oxisoalpha2->value,
            $oPayment->oxpayments__billing_countries->value ?? []
        ) && in_array(
            $oShippingCountry->oxcountry__oxisoalpha2->value,
            $oPayment->oxpayments__shipping_countries->value ?? []
        );
    }

    /**
     * Returns true if the save checkout fields selection option should be shown (fields are shown, user is logged in)
     *
     * @param array $aCheckoutFields
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _checkSaveCheckoutFields($aCheckoutFields)
    {
        $bDataToSave = false;

        foreach ($aCheckoutFields as $aCheckoutField) {
            if ($aCheckoutField['type'] !== 'hidden') {
                $bDataToSave = true;
            }
        }

        return $bDataToSave && Registry::getSession()->getUser()->oxuser__oxpassword->value !== '';
    }


    /**
     * Returns 'hidden' if the field value is already valid, 'text' otherwise
     *
     * @param bool $bIsValid
     *
     * @return string
     *
     * @since 1.2.0
     */
    private function _getCheckoutFieldType($bIsValid)
    {
        return $bIsValid ? 'hidden' : 'text';
    }

    /**
     * @inheritdoc
     *
     * @since 1.2.0
     */
    public function onBeforeOrderCreation()
    {
        $this->checkPayStepUserInput();
    }

    /**
     * Checks the user data if mandatory fields are set correctly for guaranteed invoice and saves them if needed
     *
     * @since 1.2.0
     */
    public function checkPayStepUserInput()
    {
        $oUser = Registry::getSession()->getUser();

        if (SessionHelper::isDateOfBirthSet()) {
            $oUser->oxuser__oxbirthdate = new Field(SessionHelper::getDbDateOfBirth());
        }

        if (SessionHelper::isPhoneValid()) {
            $oUser->oxuser__oxfon = new Field(SessionHelper::getPhone());
        }

        if (SessionHelper::getSaveCheckoutFields() === '1') {
            $oUser->save();
        }

        $this->_validateUserInput();
    }

    /**
     * Validates the user input and throws a specific error if an input is wrong
     *
     * @since 1.2.0
     */
    private function _validateUserInput()
    {
        if (!SessionHelper::isUserOlderThan(18)) {
            throw new InputException(Helper::translate('wd_ratepayinvoice_fields_error'));
        }

        if (!SessionHelper::isPhoneValid()) {
            throw new InputException(Helper::translate('wd_text_generic_error'));
        }
    }

    /**
     * Checks if the user is older than 18 or the date of birth needs to be entered
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _checkDateOfBirth()
    {
        return !SessionHelper::isDateOfBirthSet() || SessionHelper::isUserOlderThan(18);
    }

    /**
     * Checks if the merchant's settings are matched
     *
     * @param Basket $oBasket
     * @param string $oBillingCountryId
     * @param string $oShippingCountryId
     *
     * @return bool
     *
     * @since 1.2.0
     */
    private function _checkSettings($oBasket, $oBillingCountryId, $oShippingCountryId)
    {
        return $this->_areArticlesAllowed($oBasket->getBasketArticles()) &&
            $this->_isCurrencyAllowed($oBasket->getBasketCurrency()) &&
            $this->_areAddressesAllowed($oBillingCountryId, $oShippingCountryId);
    }
}
