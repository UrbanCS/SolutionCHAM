<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Invoice;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public object $item;
	public array $items = [];

	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->items = $this->get('Items');
		ToolbarHelper::title('Facture ' . $this->item->invoice_number, 'file');

		parent::display($tpl);
	}
}
