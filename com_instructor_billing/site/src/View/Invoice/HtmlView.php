<?php

namespace Cham\Component\InstructorBilling\Site\View\Invoice;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	public object $item;
	public array $items = [];

	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->items = $this->get('Items');

		parent::display($tpl);
	}
}
