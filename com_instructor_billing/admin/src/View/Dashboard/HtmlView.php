<?php

namespace Cham\Component\InstructorBilling\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public array $summary = [];
	public array $pending = [];
	public array $audit = [];
	public array $week = [];

	public function display($tpl = null)
	{
		$this->summary = $this->get('WeekSummary');
		$this->pending = $this->get('PendingSessions');
		$this->audit = $this->get('AuditLogs');
		$this->week = $this->get('CurrentWeek');
		ToolbarHelper::title('Facturation instructeurs', 'clock');

		parent::display($tpl);
	}
}
