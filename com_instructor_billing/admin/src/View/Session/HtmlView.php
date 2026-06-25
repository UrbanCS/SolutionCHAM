<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Session;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public object $item;
	public array $instructors = [];

	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->instructors = $this->get('Instructors');
		ToolbarHelper::title($this->item->id ? 'Modifier un cours' : 'Créer un cours', 'pencil');

		parent::display($tpl);
	}
}
