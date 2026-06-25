<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Instructors;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $users = [];

	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->users = $this->get('Users');
		ToolbarHelper::title('Instructeurs', 'users');

		parent::display($tpl);
	}
}
