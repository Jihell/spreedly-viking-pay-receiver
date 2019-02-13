<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayReceiver\Bridge;

use Jihel\OmnipaySpreedlyBridgeBundle\Factory\ReceiverAbstract;
use Omnipay\Common\Http\Client;
use Omnipay\Common\Message\RequestInterface;

/**
 * Class VikingPayReceiver
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class VikingPayReceiver extends ReceiverAbstract
{
    const API_VERSION = 'v1';

    const ENDPOINT_SANDBOX = 'https://test.oppwa.com';
    const ENDPOINT_PROD = 'https://oppwa.com';

    /**
     * 4 -> VISA
     * 51 to 55 & 22 to 27 -> MASTER
     * 60, 56 to 58, 639 and 67 -> MAESTRO
     *
     * @param $bin
     * @return string
     * @throws Exception\UnsupportedBankNumberException
     */
    public function guessBand($bin)
    {
        if (0 === strpos($bin, '4')) {
            return 'VISA';
        }

        if (preg_match('/^(5[1-5]{1})|^(2[2-7]{1})/', $bin)) {
            return 'MASTER';
        }

        if (preg_match('/^(5[6-8]{1})|^60|^639|^67/', $bin)) {
            return 'MAESTRO';
        }

        throw new Exception\UnsupportedBankNumberException($bin);
    }

    /**
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? static::ENDPOINT_SANDBOX : static::ENDPOINT_PROD;

        return $base . '/' . self::API_VERSION;
    }

    /**
     * @param RequestInterface $request
     * @param                  $data
     * @param int              $statusCode
     * @return Response|\Omnipay\Common\Message\AbstractResponse
     */
    public function createResponse(RequestInterface $request, $data, $statusCode = 200)
    {
        return new Response($request, $data, $statusCode);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function encodeParameters(array $data) :string
    {
        array_walk($data, function(&$val, $index) {
            $val = sprintf('%s=%s', $index, $val);
        });

        return implode('&', $data);
    }

    /**
     * @param array $options
     * @return array
     */
    public function authorize(array $options = [])
    {
        $body = [
            'authentication.userId' => '{{viking_user}}',
            'authentication.password' => '{{viking_pass}}',
            'authentication.entityId' => '{{viking_mid}}',
            'paymentBrand' => '{{#card_type_mapping}}visa:VISA,master:MASTER,maestro:MAESTRO,american_express:AMERICAN_EXPRESS{{/card_type_mapping}}',
            'card.holder' => '{{credit_card_first_name}} {{credit_card_last_name}}',
            'card.number' => '{{credit_card_number}}',
            'card.expiryMonth' => '{{#format_date}}%m,credit_card_month{{/format_date}}',
            'card.expiryYear' => '{{credit_card_year}}',
            'card.cvv' => '{{credit_card_verification_value}}',
            'shopperResultUrl' => $options['landingUrl'],
        ];

        return [
            'continue_caching' => true,
            'headers' => 'Content-Type: application/x-www-form-urlencoded',
            'request_method' => $this->getHttpMethod(),
            'url' => sprintf('%s/registrations', $this->getEndpoint()),
            'body' => $this->encodeParameters($body),
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    public function purchase(array $options = [])
    {
        // Token is used instead of credit card data
        if (isset($options['token'])) {
            $body = [
                'authentication.userId' => $options['receiverUser'],
                'authentication.password' => $options['receiverPass'],
                'authentication.entityId' => $options['receiverMid'],
                'amount' => $options['amount'],
                'currency' => $options['currency'],
                'paymentType' => 'DB',
                'recurringType' => 'REPEATED',
            ];
        } else {
            $body = [
                'authentication.userId' => '{{viking_user}}',
                'authentication.password' => '{{viking_pass}}',
                'authentication.entityId' => '{{viking_mid}}',
                'amount' => $options['amount'],
                'currency' => $options['currency'],
                'paymentType' => 'DB',
                'paymentBrand' => '{{#card_type_mapping}}visa:VISA,master:MASTER,maestro:MAESTRO,american_express:AMERICAN_EXPRESS{{/card_type_mapping}}',
                'card.holder' => '{{credit_card_first_name}}+{{credit_card_last_name}}',
                'card.number' => '{{credit_card_number}}',
                'card.expiryMonth' => '{{#format_date}}%m,credit_card_month{{/format_date}}',
                'card.expiryYear' => '{{credit_card_year}}',
                'card.cvv' => '{{credit_card_verification_value}}',
                'createRegistration' => 'true',
                'shopperResultUrl' => $options['landingUrl'],
            ];
        }

        $data = [
            'continue_caching' => true,
            'headers' => 'Content-Type: application/x-www-form-urlencoded',
            'request_method' => $this->getHttpMethod(),
            'url' => sprintf('%s/payments', $this->getEndpoint()),
            'body' => $this->encodeParameters($body),
        ];

        // Override if there is a token
        if (isset($options['token'])) {
            $data['url'] = sprintf('%s/registrations/%s/payments', $this->getEndpoint(), $options['token']);
        }

        return $data;
    }

    /**
     * @param array $options
     * @return array
     */
    public function completeAuthorize(array $options = [])
    {
        return $this->build3dsValidationParameters($options);
    }

    /**
     * @param array $options
     * @return array
     */
    public function completePurchase(array $options = [])
    {
        return $this->build3dsValidationParameters($options);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function build3dsValidationParameters(array $options = [])
    {
        return [
            'id' => $options['3ds'],
            'endPoint' => $this->getEndpoint(),
            'auth' => $this->encodeParameters([
                'authentication.userId' => $options['receiverUser'],
                'authentication.password' => $options['receiverPass'],
                'authentication.entityId' => $options['receiverMid'],
            ])
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    public function refund(array $options = [])
    {
        $body = [
            'authentication.userId' => $options['receiverUser'],
            'authentication.password' => $options['receiverPass'],
            'authentication.entityId' => $options['receiverMid'],
            'amount' => $options['amount'],
            'currency' => $options['currency'],
            'paymentType' => 'RF',
        ];

        $data = [
            'continue_caching' => true,
            'headers' => 'Content-Type: application/x-www-form-urlencoded',
            'request_method' => $this->getHttpMethod(),
            'url' => sprintf('%s/payments/%s?%s', $this->getEndpoint(), $options['token'], http_build_query($body)),
        ];

        return $data;
    }
}
