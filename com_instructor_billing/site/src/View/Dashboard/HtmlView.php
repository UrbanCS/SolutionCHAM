<?php

namespace Cham\Component\InstructorBilling\Site\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	public ?object $activeSession = null;
	public array $recentSessions = [];
	public object $weeklySummary;
	public array $invoices = [];

	public function display($tpl = null)
	{
		$this->activeSession = $this->get('ActiveSession');
		$this->recentSessions = $this->get('RecentSessions');
		$this->weeklySummary = $this->get('WeeklySummary');
		$this->invoices = $this->get('Invoices');

		parent::display($tpl);
	}
}
