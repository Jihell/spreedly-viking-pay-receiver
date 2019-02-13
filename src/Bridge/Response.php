<?php

namespace Jihel\VikingPayReceiver\Bridge;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\RedirectResponseInterface;

class Response extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var array|null
     */
    protected $response = null;

    /**
     * Response constructor.
     *
     * @param RequestInterface $request
     * @param                  $data
     * @param int              $statusCode
     */
    public function __construct(RequestInterface $request, $data, $statusCode = 200)
    {
        parent::__construct($request, $data);

        if ($data && isset($data['transaction'])) {
            $data = $data['transaction'];
            if (isset($data['response']['body'])) {
                $this->response = json_decode($data['response']['body'], true);
            }
        } else {
            $this->response = $data;
        }

        $this->statusCode = $statusCode;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        if (null === $this->response || !isset($this->response['result']['code'])) {
            return false;
        }

        list( $first, $second, ) = explode('.', (string) $this->response['result']['code']);

        return ! $this->isRedirect() && ! $this->isPending()
            && $this->getCode() < 400 && $first == '000' && $second < '200';
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return $this->response && isset($this->response['redirect']['url']);
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->response && isset($this->response['result']['code']) && substr($this->response['result']['code'], 0, 7) == '000.200';
    }

    /**
     * @return string|null
     */
    public function getTransactionReference()
    {
        if ($this->response && isset($this->response['registrationId'])) {
            return $this->response['registrationId'];
        }
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        if ($this->response && isset($this->response['id'])) {
            return $this->response['id'];
        }
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return $this->response['redirect']['url'];
        }
    }

    /**
     * @return string|null
     */
    public function getDescriptor()
    {
        if ($this->isSuccessful()) {
            return $this->response['descriptor'];
        }
    }

    /**
     * @return string
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * @return array
     */
    public function getRedirectData()
    {
        $list = [];

        if ($this->response) {
            foreach ($this->response['redirect']['parameters'] as $pair) {
                $list[$pair['name']] = $pair['value'];
            }
        }

        return  $list;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        if ($this->response && isset($this->response['result']['description'])) {
            return $this->response['result']['description'];
        }
        if (isset($this->data['errors'])) {
            $messages = [];
            foreach ($this->data['errors'] as $error) {
                $messages[] = sprintf('%s: %s', $error['key'], $error['message']);
            }

            return implode(PHP_EOL, $messages);
        }
        if (isset($this->data['message'])) {
            return $this->data['message'];
        }

        return null;
    }

    /**
     * @return int|string|null
     */
    public function getCode()
    {
        return $this->statusCode;
    }
}
