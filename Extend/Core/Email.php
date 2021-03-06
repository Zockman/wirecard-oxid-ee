<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */

namespace Wirecard\Oxid\Extend\Core;

use OxidEsales\Eshop\Core\Registry;

/**
 * Email extension
 *
 * @mixin \OxidEsales\Eshop\Core\Email
 *
 * @since 1.0.0
 */
class Email extends Email_parent
{
    /**
     * template for support email
     *
     * @var string
     *
     * @since 1.0.0
     */
    private $_sSupportTemplate = 'module_support_email.tpl';

    /**
     * @inheritdoc
     *
     * For custom payment method send the email in order language
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $oOrder   Order object
     * @param string                                    $sSubject user defined subject [optional]
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function sendOrderEmailToUser($oOrder, $sSubject = null)
    {
        return $this->_sendEmailWithOrderLanguage($oOrder, $sSubject, ['parent', 'sendOrderEmailToUser']);
    }

    /**
     * @inheritdoc
     *
     * For custom payment method send the email in order language
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $oOrder   Order object
     * @param string                                    $sSubject user defined subject [optional]
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function sendOrderEmailToOwner($oOrder, $sSubject = null)
    {
        return $this->_sendEmailWithOrderLanguage($oOrder, $sSubject, ['parent', 'sendOrderEmailToOwner']);
    }

    /**
     * Wrapper for parent methods.
     * If custom payment is used language will be switched to order language
     *
     * @param object   $oOrder
     * @param string   $sSubject
     * @param callable $cFunction
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function _sendEmailWithOrderLanguage($oOrder, $sSubject, $cFunction)
    {
        if (!$oOrder->isCustomPaymentMethod()) {
            return call_user_func($cFunction, $oOrder, $sSubject);
        }

        $oConfig = Registry::getConfig();
        $bWasAdminMode = $oConfig->isAdmin();
        $oConfig->setAdminMode(false);

        $oLang = Registry::getLang();
        $oOldShop = $this->_getShop();
        $iOldTplLang = $oLang->getTplLanguage();
        $iOldBaseLang = $oLang->getTplLanguage();
        $iOrderLanguage = $oOrder->oxorder__oxlang->value;

        // set new language settings before calling parent method
        $oLang->setTplLanguage($iOrderLanguage);
        $oLang->setBaseLanguage($iOrderLanguage);

        // set shop language if different then order language
        if ($oOldShop->getLanguage() !== $iOrderLanguage) {
            $this->_oShop = $this->_getShop($iOrderLanguage);
        }

        // send emails
        $iReturn = call_user_func($cFunction, $oOrder, $sSubject);

        // reset language settings to the initial state
        $oLang->setTplLanguage($iOldTplLang);
        $oLang->setBaseLanguage($iOldBaseLang);
        $this->_oShop = $oOldShop;

        // reset admin mode settings
        if ($bWasAdminMode) {
            $oConfig->setAdminMode(true);
        }

        return $iReturn;
    }

    /**
     * Send support email
     *
     * @param array $aEmailData Email data
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function sendSupportEmail($aEmailData)
    {
        $this->_clearMailer();
        $oShop = $this->_getShop();
        $oSmarty = $this->_getSmarty();

        $this->setViewData('emailData', $aEmailData);
        $this->setViewData('shopTemplateDir', $this->getConfig()->getTemplateDir(false));

        $this->_processViewArray();

        //set mail params (from, fromName, smtp)
        $this->_setMailParams($oShop);

        $this->setBody($oSmarty->fetch($this->_sSupportTemplate));
        $this->setSubject($aEmailData['subject']);

        $this->setRecipient($aEmailData['recipient'], "");
        $this->setFrom($aEmailData['from'], "");

        $this->clearReplyTos();
        $sReplyTo = !empty($aEmailData['replyTo']) ? $aEmailData['replyTo'] : $aEmailData['from'];
        $this->setReplyTo($sReplyTo, "");

        return $this->send();
    }
}
