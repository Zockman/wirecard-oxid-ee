<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */

namespace Wirecard\Oxid\Tests\Acceptance;

use Wirecard\Oxid\Model\CreditCardPaymentMethod;

/**
 * Acceptance tests for the Credit Card checkout flow.
 */
class CreditCardCheckoutTest extends CheckoutTestCase
{
    public function getPaymentMethod()
    {
        return new CreditCardPaymentMethod();
    }

    public function testCheckoutForPurchaseNonThreeD()
    {
        $this->setPaymentActionPurchase();
        $this->forceNonThreeD();
        $this->goThroughCheckout();

        $this->assertPaymentSuccessful();
    }

    public function testCheckoutForAuthorizeNonThreeD()
    {
        $this->setPaymentActionAuthorize();
        $this->forceNonThreeD();
        $this->goThroughCheckout();

        $this->assertPaymentSuccessful();
    }

    public function testCheckoutForPurchaseThreeD()
    {
        $this->setPaymentActionPurchase();
        $this->forceThreeD();
        $this->goThroughCheckout();
        $this->goThroughExternalFlow();
        $this->waitForRedirectConfirmation();

        $this->assertPaymentSuccessful();
    }

    public function testCheckoutForAuthorizeThreeD()
    {
        $this->setPaymentActionAuthorize();
        $this->forceThreeD();
        $this->goThroughCheckout();
        $this->goThroughExternalFlow();
        $this->waitForRedirectConfirmation();

        $this->assertPaymentSuccessful();
    }

    private function forceThreeD()
    {
        $this->executeSql("UPDATE `oxpayments`
            SET `WDOXIDEE_MAID` = '', `WDOXIDEE_SECRET` = ''
            WHERE `OXID` = '{$this->paymentMethod::getName(true)}'");
    }

    private function forceNonThreeD()
    {
        $this->executeSql("UPDATE `oxpayments`
            SET `WDOXIDEE_THREE_D_MAID` = '', `WDOXIDEE_THREE_D_SECRET` = ''
            WHERE `OXID` = '{$this->paymentMethod::getName(true)}'");
    }

    public function goThroughCheckout()
    {
        $this->openShop();
        $this->loginMockUserToFrontend();
        $this->addMockArticleToBasket();

        // Step 1: Cart
        $this->continueToNextStep();

        // Step 2: Address
        $this->continueToNextStep();

        // Step 3: Pay
        $this->click(sprintf(
            $this->getLocator('checkout.paymentMethod'),
            $this->paymentMethod::getName(true)
        ));
        $this->continueToNextStep();

        // Step 4: Order
        $rootFrame = $this->getSelectedFrame();

        $this->waitForElement($this->getLocator('external.creditcard.frame'), 30);
        $this->selectFrame($this->getLocator('external.creditcard.frameId'));

        $this->waitForElement($this->getLocator('external.creditcard.firstName'));
        $this->type(
            $this->getLocator('external.creditcard.firstName'),
            $this->getConfig('payments.creditcard.firstName')
        );
        $this->type(
            $this->getLocator('external.creditcard.lastName'),
            $this->getConfig('payments.creditcard.lastName')
        );
        $this->type(
            $this->getLocator('external.creditcard.cardNumber'),
            $this->getConfig('payments.creditcard.cardNumber')
        );
        $this->fireEvent($this->getLocator('external.creditcard.cardNumber'), 'keyup');
        $this->type(
            $this->getLocator('external.creditcard.cvv'),
            $this->getConfig('payments.creditcard.cvv')
        );
        $this->select(
            $this->getLocator('external.creditcard.expiryMonth'),
            $this->getConfig('payments.creditcard.expiryMonth')
        );
        $this->select(
            $this->getLocator('external.creditcard.expiryYear'),
            $this->getConfig('payments.creditcard.expiryYear')
        );

        $this->selectFrame($rootFrame);
        $this->continueToNextStep();
    }

    private function goThroughExternalFlow()
    {
        $this->waitForElement($this->getLocator('external.creditcard.password'), 30);
        $this->type(
            $this->getLocator('external.creditcard.password'),
            $this->getConfig('payments.creditcard.password')
        );
        $this->click($this->getLocator('external.creditcard.continueButton'));
    }
}