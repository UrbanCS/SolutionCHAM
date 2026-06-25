<?php

namespace Cham\Component\InstructorBilling\Site\View\Sessions;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	public array $items = [];

	public function display($tpl = null)
	{
		$this->items = $this->get('Items');

		parent::display($tpl);
	}
}
