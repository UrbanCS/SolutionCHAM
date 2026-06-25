<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Settings;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public array $sageStatus = [];

	public function display($tpl = null)
	{
		$this->sageStatus = $this->get('SageStatus');
		ToolbarHelper::title('Paramètres Sage', 'cog');

		parent::display($tpl);
	}
}
