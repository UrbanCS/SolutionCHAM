<?php

namespace Cham\Component\InstructorBilling\Site\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\ExportService;
use Cham\Component\InstructorBilling\Administrator\Service\InvoiceService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

class InvoiceController extends BaseController
{
	public function generateSelf(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::isInstructor());
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$userId = (int) AccessService::currentUser()->id;
		$data = $app->input->post->getArray();
		$invoiceId = (new InvoiceService())->generateInstructorWeeklyInvoice(
			$userId,
			(string) ($data['period_start'] ?? ''),
			(string) ($data['period_end'] ?? '')
		);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SELF_INVOICE_GENERATED'));
		$this->redirectBack('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoiceId);
	}

	public function csv(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::isInstructor());

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$export = new ExportService();
		$invoice = $export->assertCanExport($id);
		$csv = $export->buildInvoiceCsv($id);
		$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number) . '.csv';

		$app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
		$app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
		echo $csv;
		$app->close();
	}

	public function syncSage(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		(new \Cham\Component\InstructorBilling\Administrator\Service\SageService())->createInvoice($id);
		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SAGE_INVOICE_SYNCED'));
		$this->redirectBack('index.php?option=com_instructor_billing&view=invoice&id=' . $id);
	}

	private function redirectBack(string $fallback): void
	{
		$app = Factory::getApplication();
		$encoded = $app->input->post->getString('return', '');
		$return = $encoded !== '' ? base64_decode($encoded, true) : false;

		if (is_string($return) && $return !== '' && Uri::isInternal($return)) {
			$this->setRedirect($return);

			return;
		}

		$this->setRedirect(Route::_($fallback, false));
	}
}
