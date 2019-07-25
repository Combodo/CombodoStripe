<?php
namespace Combodo\StripeV3;

use Combodo\StripeV3\Action\Api\CreateTokenAction;
use Combodo\StripeV3\Action\Api\ObtainTokenAction;
use Combodo\StripeV3\Action\Api\PollFullfilledPaymentsAction;
use Combodo\StripeV3\Action\AuthorizeAction;
use Combodo\StripeV3\Action\CheckoutCompletedEventAction;
use Combodo\StripeV3\Action\ConvertPaymentAction;
use Combodo\StripeV3\Action\CaptureAction;
use Combodo\StripeV3\Action\FindLostPaymentsAction;
use Combodo\StripeV3\Action\HandleLostPaymentsAction;
use Combodo\StripeV3\Action\NotifyAction;
use Combodo\StripeV3\Action\NotifyUnsafeAction;
use Combodo\StripeV3\Action\RefundAction;
use Combodo\StripeV3\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class StripeV3GatewayFactory extends GatewayFactory
{
    const FACTORY_NAME = 'stripe_checkout_v3';

    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => static::FACTORY_NAME,
            'payum.factory_title' => 'Stripe checkout V3',

            'payum.template.obtain_token' => '@CombodoStripeV3/redirect_to_checkout.html.twig',

            'payum.action.capture' => new CaptureAction(),                              // standard action
            'payum.action.convert_payment' => new ConvertPaymentAction(),               // standard action, required by \Sylius\Bundle\PayumBundle\Action\CapturePaymentAction
            'payum.action.status' => new StatusAction(),                                // standard action

            'payum.action.obtain_token' => function (ArrayObject $config) {             // stripe specific action
                return new ObtainTokenAction($config['payum.template.obtain_token']);
            },        // stripe specific action + injection of configuration!

            'payum.action.notify_unsafe' => new NotifyUnsafeAction(),                   // modified standard action to handle "unsafe" ie without the token webhooks

            'payum.action.poll_fullfilled_payements' => new PollFullfilledPaymentsAction(), // custom action
            'payum.action.handle_lost_payements'     => new HandleLostPaymentsAction(),     // custom action
            'payum.action.chackout_completed'        => new CheckoutCompletedEventAction(), // custom action



//            'payum.action.create_token' => new CreateTokenAction(),                     // stripe specific action

//  this comment block consist in action generated by the payum skeleton, but not present in the legacy stripe "v2" integration: maybe I should implement them, so I keep trace here for the moment!
//            'payum.action.authorize' => new AuthorizeAction(),                            // standard action
//            'payum.action.refund' => new RefundAction(),                                  // standard action
//            'payum.action.cancel' => new CancelAction(),                                  // standard action
//  end of the comment block

//  this comment block consist in action present in the legacy stripe "v2" that I choose not to implement because they are out of the scope I need (ie: I do not handle credit cards)
//            'payum.action.get_credit_card_token' => new GetCreditCardTokenAction(),       // stripe specific action
//            'payum.action.create_customer' => new CreateCustomerAction(),                 // stripe specific action
//            'payum.action.create_plan' => new CreatePlanAction(),                         // stripe specific action
//            'payum.action.create_subscription' => new CreateSubscriptionAction(),         // stripe specific action
//            'payum.extension.create_customer' => new CreateCustomerExtension(),           // stripe specific action
//            'payum.action.create_charge' => new CreateChargeAction(),                     // stripe specific action
//  end of the comment block
        
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'publishable_key' => '',
                'secret_key' => '',
                'endpoint_secret' => '',
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['publishable_key', 'secret_key', 'endpoint_secret'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Keys($config['publishable_key'], $config['secret_key'], $config['endpoint_secret']);
            };
        }

        $config['payum.paths'] = array_replace([
            'CombodoStripeV3' => __DIR__.'/../templates',
        ], $config['payum.paths'] ?: []);
    }
}
