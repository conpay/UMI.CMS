<?php
class ConpayProxyModel
{
	/**
	 * @var int
	 */
	private $merchantId;
	/**
	 * @var string
	 */
	private $serviceUrl = 'https://www.conpay.ru/service/proxy';
	/**
	 * @var string
	 */
	private $serviceAction;
	/**
	 * @var string
	 */
	private $charset = 'UTF-8';
	/**
	 * @var string
	 */
	private $conpayCharset = 'UTF-8';

	public function __construct()
	{
		if (!$this->isSelfRequest()) {
			throw new Exception('Incorrect request', 403);
		}

		$this->serviceAction = isset($_POST['conpay-action']) ? $_POST['conpay-action'] : '';
		$this->serviceUrl = rtrim($this->serviceUrl.'/'.$this->serviceAction, '/');
	}

	/**
	 * @return boolean
	 */
	public function isSelfRequest() {
		return isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === $_SERVER['HTTP_HOST'];
	}

	/**
	 * @return boolean
	 */
	public function isPostRequest() {
		return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
	}

	/**
	 * @return string
	 */
	public function sendRequest()
	{
		$response = function_exists('curl_init') ? $this->getViaCurl() : $this->getViaFileGC();
		return $this->convertCharset($this->conpayCharset, $this->charset, $response);
	}

	/**
	 * @param int $id
	 * @return ConpayProxyModel
	 */
	public function setMerchantId($id)
	{
		$this->merchantId = (int)$id;
		return $this;
	}

	/**
	 * @param string $charset
	 * @return ConpayProxyModel
	 */
	public function setCharset($charset)
	{
		$this->charset = strtoupper($charset);
		return $this;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function getViaCurl()
	{
		$ch = curl_init($this->serviceUrl);

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getQueryData());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);

		if ($data === false)
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception($this->convertCharset($this->conpayCharset, $this->charset, $error), 500);
		}

		curl_close($ch);
		return $data;
	}

	/**
	 * @return string
	 */
	private function getViaFileGC()
	{
		$options = array(
			'http'=>array(
				'method'=>"POST",
				'content'=>$this->getQueryData(),
				'header'=>
					"Content-type: application/x-www-form-urlencoded\r\n".
					"Referer: {$_SERVER['HTTP_REFERER']}\r\n"
			)
		);

		$context = stream_context_create($options);
		return file_get_contents($this->serviceUrl, false, $context);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function getQueryData()
	{
		if ($this->merchantId === null) {
			throw new Exception('MerchantId is not set', 500);
		}
		$data = $this->isPostRequest() ? http_build_query($_POST) : $_SERVER['QUERY_STRING'];
		if (strpos($data, 'merchant=') === false) {
			$data .= '&merchant='.$this->merchantId;
		}
		return $this->convertCharset($this->charset, $this->conpayCharset, $data);
	}

	/**
	 * @param string $in
	 * @param string $out
	 * @param string $data
	 * @return string
	 */
	private function convertCharset($in, $out, $data)
	{
		if ($in !== $out && function_exists('iconv')) {
			return iconv($in, $out, $data);
		}
		return $data;
	}
}