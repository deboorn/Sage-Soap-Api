<?php


/**
 * Sage Soap API
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Apache
 * @namespace Sage
 */


namespace Sage;

/**
 * Class Exception
 * @package Sage
 */
class Exception extends \Exception
{

    /**
     * @var null
     */
    public $response;
    /**
     * @var null
     */
    public $request;

    /**
     * @param string $message
     * @param int $code
     * @param null $response
     * @param null $request
     * @param \Exception $previous
     */
    public function __construct($message, $code = 0, $response = null, $request = null, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * @return null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return null
     */
    public function getRequest()
    {
        return $this->request;
    }

}

/**
 * Class API
 * @package Sage
 */
class API
{
    /**
     *
     */
    const VAULT_URL = 'https://gateway.sagepayments.net/web_services/wsVault/wsVault.asmx?WSDL';
    /**
     *
     */
    const CREDIT_URL = 'https://gateway.sagepayments.net/web_services/wsVault/wsVaultBankcard.asmx?WSDL';
    /**
     *
     */
    const ACH_URL = 'https://gateway.sagepayments.net/web_services/wsVault/wsVaultVirtualCheck.asmx?WSDL';

    /**
     *
     */
    const CREDIT = 'creditcard';
    /**
     *
     */
    const ACH = 'ach';

    /**
     *
     */
    const ACH_CHECKING = 'DDA';
    /**
     *
     */
    const ACH_SAVINGS = 'SAV';

    /**
     *
     */
    const DECLINED = 'DECLINED';
    /**
     *
     */
    const UNABLE_TO_PROCESS = 'UNABLE TO PROCESS';
    /**
     *
     */
    const ERROR = 'ERROR';

    /**
     * @var
     */
    protected $soap;
    /**
     * @var
     */
    protected $merchantId;
    /**
     * @var
     */
    protected $merchantKey;
    /**
     * @var
     */
    protected $applicationId;
    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @param $merchantId
     * @param $merchantKey
     */
    public function __construct($merchantId, $merchantKey)
    {
        $this->merchantId = $merchantId;
        $this->merchantKey = $merchantKey;
        $this->setSoap(self::VAULT_URL);
    }

    /**
     * @param $url
     */
    protected function setSoap($url)
    {
        $this->soap = new \SoapClient($url, array('trace' => 1));
    }

    /**
     * @param $merchantId
     * @param $merchantKey
     * @return API
     */
    public static function forge($merchantId, $merchantKey)
    {
        return new self($merchantId, $merchantKey);
    }

    /**
     * @param array $params
     * @param $paymentType
     * @return mixed
     */
    public function addCustomer(array $params, $paymentType)
    {
        $this->setSoap(self::VAULT_URL);
        $endpoint = $paymentType == self::CREDIT ? 'INSERT_CREDIT_CARD_DATA' : 'INSERT_VIRTUAL_CHECK_DATA';
        $response = $this->fetch($params, $endpoint);
        return current($response->GUID);
    }

    /**
     * @param $id
     * @param array $params
     * @param $paymentType
     * @return mixed
     */
    public function chargeCustomer($id, array $params, $paymentType)
    {
        $this->setSoap($paymentType == self::CREDIT ? self::CREDIT_URL : self::ACH_URL);
        $endpoint = $paymentType == self::CREDIT ? 'VAULT_BANKCARD_SALE' : 'VIRTUAL_CHECK_WEB_SALE';
        $params['GUID'] = $id;
        return $this->fetch($params, $endpoint);
    }

    /**
     * @param array $params
     * @param $endpoint
     * @return mixed
     * @throws Exception
     */
    public function fetch(array $params, $endpoint)
    {
        $params['M_ID'] = $this->merchantId;
        $params['M_KEY'] = $this->merchantKey;
        $response = $this->soap->{$endpoint}($params);

        if ($this->debug) {
            var_dump( //example of how to debug soap
                $params,
                $endpoint,
                $this->soap->__getLastRequestHeaders(),
                $this->soap->__getLastRequest(),
                $this->soap->__getLastResponse(),
                $this->soap->__getLastResponseHeaders()
            );
        }

        $result = new \SimpleXMLElement($response->{"{$endpoint}Result"}->any);
        if (!empty($result->NewDataSet->Table1->SUCCESS) && $result->NewDataSet->Table1->SUCCESS != 'true') {
            throw new Exception($result->NewDataSet->Table1->MESSAGE);
        }
        if (strpos($result->NewDataSet->Table1, self::DECLINED) !== false || strpos($result->NewDataSet->Table1, self::UNABLE_TO_PROCESS) !== false || strpos($result->NewDataSet->Table1, self::ERROR) !== false) {
            throw new Exception(trim($result->NewDataSet->Table1->MESSAGE));
        }
        return $result->NewDataSet->Table1;
    }

}
