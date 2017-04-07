<?php

namespace Drupal\commerce_razorpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Psy\Exception\Exception;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;

/**
 * Provides the Off-site payment form.
 */
class RazorpayForm extends BasePaymentOffsiteForm {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    global $base_url;

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $redirect_method = 'post';
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $owner = \Drupal::routeMatch()->getParameter('commerce_order')->getCustomer();
    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);





    $billing_profile = $order->getBillingProfile();

    $address = $order->getBillingProfile()->address->first();

    $amount = ($payment->getAmount()->getNumber()) * 100;

    $key_id = $payment_gateway_plugin->getConfiguration()['key_id'];
    $key_secret = $payment_gateway_plugin->getConfiguration()['key_secret'];
    $currency = $payment_gateway_plugin->getConfiguration()['currency'];
    $currency = 'INR';
    $receipt = $order_id;
    $payment_capture = FALSE;

    $api = new Api($key_id, $key_secret);
    $razorpay_order = $api->order->create(array(
      'amount' => $amount,
      "currency" => $currency,
      "receipt" => $receipt,
      'payment_capture' => $payment_capture
    ));

    $merchant_order_id = $razorpay_order->id;
    $order->setData('merchant_order_id', $merchant_order_id);
    $order->save();

    $payment_method =$payment_gateway_plugin->getConfiguration();
//    $billing_address = $address;


//    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_payment')->load('testing');
//    $store_type->setDescription('The default store');
//    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');



//    $order = Order::load(1);
//    // refer payment checkout test.php file.
////    $order->payment_method = $this->orderPaymentMethod;
//    $order->save();



//    $entity_type_manager = \Drupal::entityTypeManager();
//    $order_storage = $entity_type_manager->getStorage('commerce_order');
//    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
//    $order = $order_storage->load($order_id);

//
//    $order->get('coupons')->setValue([]);
//    $order->save();


    $form['#attached']['library'][] = 'commerce_razorpay/commerce_razorpay.payment';

    $form['#attached']['drupalSettings']['commerce_razorpay'] = array(
      'amount' => $amount,
      'key' => $key_id,
      'logo' => $base_url . "/" . drupal_get_path('module', 'commerce_razorpay') . '/logo.jpg',
      'order_id' => $merchant_order_id,
      'commerce_order_id' => $order_id,
      'payment_settings' => $payment_method,
      'name' => $address->getGivenName(). " " . $address->getFamilyName(),
      'address' => $address->getAddressLine1() . " " . $address->getLocality() . " " . $address->getAdministrativeArea(),
      'email' => $order->getEmail(),
//      'phone' => $billing_profile->get('field_phone')->value,
    );

//    if ($mode == 'test') {
//      $redirect_url = self::PAYUMONEY_API_TEST_URL;
//    }
//    else {
//      $redirect_url = self::PAYUMONEY_API_URL;
//    }
    return $this->buildRedirectForm($form, $form_state);
  }

  protected function buildRedirectForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

}
