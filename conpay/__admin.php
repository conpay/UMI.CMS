<?php
abstract class __conpay extends baseModuleAdmin
{
	public $cook = '';

	public function config()
	{
		$regedit = regedit::getInstance();

		$params = array();
		$params['merchant']['int:merchant_id'] = $regedit->getVal("//settings/conpay/merchant_id");
		$params['merchant']['string:api_key'] = $regedit->getVal("//settings/conpay/api_key");

		$params['look']['string:tag_name'] = $regedit->getVal("//settings/conpay/tag_name");
		$params['look']['string:tag_class'] = $regedit->getVal("//settings/conpay/tag_class");
		$params['look']['string:button_text'] = $regedit->getVal("//settings/conpay/button_text");

		$params['advanced']['string:img_field'] = $regedit->getVal("//settings/conpay/img_field");

		$mode = (string)getRequest('param0');

		if ($mode == "do")
		{
			$params = $this->expectParams($params);

			$merchant_id = $params['merchant']['int:merchant_id'];
			$api_key = $params['merchant']['string:api_key'];

			$regedit->setVal("//settings/conpay/merchant_id", $merchant_id);
			$regedit->setVal("//settings/conpay/api_key", $api_key);

			$regedit->setVal("//settings/conpay/tag_name", $params['look']['string:tag_name']);
			$regedit->setVal("//settings/conpay/tag_class", $params['look']['string:tag_class']);
			$regedit->setVal("//settings/conpay/button_text", $params['look']['string:button_text']);

			$regedit->setVal("//settings/conpay/img_field", $params['advanced']['string:img_field']);

			$this->chooseRedirect();
		}

		$this->setDataType('settings');
		$this->setActionType('modify');

		$data = $this->prepareData($params, 'settings');

		$this->setData($data);
		return $this->doData();
	}
}