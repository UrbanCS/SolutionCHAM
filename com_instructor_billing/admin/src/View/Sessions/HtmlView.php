<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Sessions;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $instructors = [];

	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->instructors = $this->get('Instructors');
		ToolbarHelper::title('Cours et trajets', 'list');

		parent::display($tpl);
	}
}
