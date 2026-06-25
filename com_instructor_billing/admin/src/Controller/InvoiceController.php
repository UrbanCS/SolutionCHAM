<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\ExportService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class InvoiceController extends BaseController
{
	public function updateStatus(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canInvoice());

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$status = $app->input->getCmd('status', 'draft');
		$this->getModel('Invoice')->updateStatus($id, $status);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_STATUS_UPDATED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $id, false));
	}

	public function saveItems(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canInvoice());

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$this->getModel('Invoice')->saveItems($id, $app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_ITEMS_UPDATED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $id, false));
	}

	public function delete(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canInvoice());

		$app = Factory::getApplication();
		$this->getModel('Invoice')->delete($app->input->getInt('id'));

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_DELETED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoices', false));
	}

	public function csv(): void
	{
		AccessService::denyUnless(AccessService::canInvoice());

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
}
