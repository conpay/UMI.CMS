<?php
class conpay extends def_module
{
	public function __construct()
	{
		parent::__construct();

		if (cmsController::getInstance()->getCurrentMode() == "admin")
		{
			$this->__loadLib("__admin.php");
			$this->__implement("__conpay");
		}
		else
		{
			$this->__loadLib("__custom.php");
			$this->__implement("__custom_conpay");
		}

		$this->__loadLib("ConpayProxyModel.php");
	}

	/**
	 * Вставляет в шаблон JavaScript-инициализацию системы ConPay
	 * @return String HTML код с JavaScript инструкциями
	 */
	public function getJScript()
	{
		$regedit = regedit::getInstance();
		$merchant_id = $regedit->getVal("//settings/conpay/merchant_id");

		$button_attr = array();
		$button_attr['tagName'] = $regedit->getVal("//settings/conpay/tag_name");
		$button_attr['className'] = $regedit->getVal("//settings/conpay/tag_class");
		$button_attr['text'] = $regedit->getVal("//settings/conpay/button_text");

		$custom_vars = $this->getCustomVars();

		$out = '<script src="http://www.conpay.ru/public/js/credits/btn.1.5.proxy.min.js" type="text/javascript"></script>';
		$out .= '<script type="text/javascript">
					try{window.conpay.init("/conpay/proxy/", '.json_encode($button_attr).', '.json_encode($custom_vars).');} catch(e){}
				</script>';

		return $out;
	}

	/**
	 * Метод для проксирования запросов к Conpay
	 */
	public function proxy()
	{
		$regedit = regedit::getInstance();
		$merchant_id = $regedit->getVal("//settings/conpay/merchant_id");

		$proxy = new ConpayProxyModel;
		$proxy->setMerchantId($merchant_id);
		echo $proxy->sendRequest();

		exit();
	}

	/**
	 * Вставить кнопку "Купить в кредит" для корзины
	 * @param String $container_id = '' HTML-атрибут id контейнера, в который будет помещена кнопка
	 * @return String HTML код с JavaScript инструкциями
	 */
	public function insertButtonForBasket($container_id = '')
	{
		$regedit = regedit::getInstance();
		$image_field_name = $regedit->getVal("//settings/conpay/img_field");

		$order = __emarket_purchasing::getBasketOrder();
		$orderItems = $order->getItems();
		$items = array();

		foreach ($orderItems as $orderItem)
		{
			$orderItemId = $orderItem->getId();

			$name = $orderItem->getName();
			$amount = $orderItem->getAmount();

			$plainPriceOriginal = $orderItem->getItemPrice();

			$status = order::getCodeByStatus($order->getOrderStatus());
			if (!$status || $status == 'basket')
			{
				$element = $orderItem->getItemElement();
			}
			else
			{
				$symlink = $orderItem->getObject()->item_link;
				if (is_array($symlink) && sizeof($symlink))
				{
					list($item) = $symlink;
					$element = $item;
				}
				else {
					$element = null;
				}
			}

			$link = "http://".$_SERVER['HTTP_HOST'].'/';
			if ($element instanceof iUmiHierarchyElement) {
				$link = $element->link;
			}

			$item_descr = array();
			$item_descr['name'] = $name;
			$item_descr['url'] = $link;
			$item_descr['quantity'] = $amount;
			$item_descr['price'] = $plainPriceOriginal;

			if ($image_field_name && $element instanceof iUmiHierarchyElement)
			{
				if ($img = $element->getValue($image_field_name))
				{
					$img = $img->getFilePath(true);
					$item_descr['image'] = "http://".$_SERVER['HTTP_HOST'].'/'.substr($img, 2);
				}
			}

			$items[] = $item_descr;
		}

		$order_actual_summ = $order->getActualPrice();

		return '<script type="text/javascript">try{ window.conpay.addButton("'.$this->getChecksumm($order_actual_summ).'", "'.$container_id.'", '.json_encode($items).');} catch(e){}</script>';
	}

	/**
	 * Вставить кнопку "Купить в кредит" для конкретного товара
	 * @param Integer $item_id айди элемента каталога
	 * @param Integer $quantity = 1 количество экземпляров данного товара
	 * @param String $container_id = '' HTML-атрибут id контейнера, в который будет помещена кнопка
	 * @param String $name_prefix = '' префикс, который будет добавлен к имени, исползуется если в имени нет информации о разделе или бренде
	 * @param Boolean $ignore_discount = true флаг игнорирования скидок
	 * @return String HTML код с JavaScript инструкциями
	 */
	public function insertButton($item_id, $quantity = 1, $container_id = '', $name_prefix = '', $ignore_discount = true)
	{
		$regedit = regedit::getInstance();
		$element = umiHierarchy::getInstance()->getElement($item_id);
		if (!$element) {
			return;
		}

		$emarket = cmsController::getInstance()->getModule('emarket');
		$price = $emarket->getPrice($element, $ignore_discount);

		$item_descr = array();
		$item_descr['name'] = $name_prefix.$element->name;
		$item_descr['url'] = $element->link;
		$item_descr['quantity'] = $quantity;
		$item_descr['price'] = $quantity * $price;

		$image_field_name = $regedit->getVal("//settings/conpay/img_field");
		if ($image_field_name)
		{
			if ($img = $element->getValue($image_field_name))
			{
				$img = $img->getFilePath(true);
				$item_descr['image'] = "http://".$_SERVER['HTTP_HOST'].'/'.substr($img, 2);
			}
		}

		return '<script type="text/javascript"> try{ window.conpay.addButton("'.$this->getChecksumm($item_descr['price']).'", "'.$container_id.'", '.json_encode($item_descr).');} catch(e){} </script>';
	}

	/**
	 * Генерирует контрольную сумму транзакции
	 * @param Integer $summ сумма транзакции
	 * @return String контрольная сумма
	 */
	protected function getChecksumm($summ)
	{
		$regedit = regedit::getInstance();
		$merchant_id = $regedit->getVal("//settings/conpay/merchant_id");
		$api_key = $regedit->getVal("//settings/conpay/api_key");

		$custom_vars = $this->getCustomVars();

		if (sizeof($custom_vars) > 1)
		{
			return md5(sprintf('%s!%d!%s!%s!%s!%s!%s!%s', $api_key, $summ, $merchant_id
				, $custom_vars['user_type']
				, $custom_vars['user_login']
				, $custom_vars['user_name']
				, $custom_vars['user_email']
				, $custom_vars['user_id']));
		}
		else {
			return md5(sprintf('%s!%d!%s!%s', $api_key, $summ, $merchant_id, $custom_vars['user_type']));
		}
	}

	/**
	 * Определяет дополнительные переменные для идентификации пользователя
	 * @return Array информация о покупателе
	 */
	protected function getCustomVars()
	{
		$permissions = permissionsCollection::getInstance();
		$user_id = $permissions->getUserId();
		$guest_id = $permissions->getGuestId();

		$custom_vars = array();
		if ($user_id == $guest_id) {
			$custom_vars['user_type'] = "Guest";
		}
		else
		{
			$custom_vars['user_type'] = "Registred user";

			if ($user = umiObjectsCollection::getInstance()->getObject($user_id))
			{
				$custom_vars['user_login'] = $user->name;
				$custom_vars['user_name'] = $user->lname.' '.$user->fname;
				$custom_vars['user_email'] = $user->getValue('e-mail');
				$custom_vars['user_id'] = $user->id;
			}
		}

		return $custom_vars;
	}
}