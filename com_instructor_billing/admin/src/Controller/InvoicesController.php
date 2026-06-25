<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\InvoiceService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class InvoicesController extends BaseController
{
	public function generateWeekly(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canInvoice());

		$app = Factory::getApplication();
		$data = $app->input->post->getArray();
		$invoiceId = (new InvoiceService())->generateWeeklyInvoice(
			(int) ($data['instructor_user_id'] ?? 0),
			(string) ($data['period_start'] ?? ''),
			(string) ($data['period_end'] ?? ''),
			(int) AccessService::currentUser()->id
		);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_GENERATED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoiceId, false));
	}

	public function createManual(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canInvoice());

		$app = Factory::getApplication();
		$invoiceId = (new InvoiceService())->createManualInvoice(
			$app->input->post->getArray(),
			(int) AccessService::currentUser()->id
		);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_CREATED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoiceId, false));
	}
}
