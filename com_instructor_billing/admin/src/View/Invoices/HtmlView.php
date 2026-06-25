<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Invoices;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $instructors = [];
	public array $defaultPeriod = [];

	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->instructors = $this->get('Instructors');
		$this->defaultPeriod = $this->get('DefaultPeriod');
		ToolbarHelper::title('Factures', 'file');

		parent::display($tpl);
	}
}
