<?php

namespace Cham\Component\InstructorBilling\Site\View\Management;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	public array $pendingSessions = [];
	public array $invoiceCandidates = [];
	public array $recentInvoices = [];
	public array $instructorProfiles = [];
	public array $users = [];
	public array $period = [];
	public bool $canManageRates = false;

	public function display($tpl = null)
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canApprove() || AccessService::canInvoice());

		$this->canManageRates = AccessService::canInvoice();
		$this->period = $this->get('Period');
		$this->instructorProfiles = $this->canManageRates ? $this->get('InstructorProfiles') : [];
		$this->users = $this->canManageRates ? $this->get('Users') : [];
		$this->pendingSessions = AccessService::canApprove() ? $this->get('PendingSessions') : [];
		$this->invoiceCandidates = AccessService::canInvoice() ? $this->get('InvoiceCandidates') : [];
		$this->recentInvoices = AccessService::canInvoice() ? $this->get('RecentInvoices') : [];

		parent::display($tpl);
	}
}
