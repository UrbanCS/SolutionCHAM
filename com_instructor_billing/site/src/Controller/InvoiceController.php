<?php

namespace Cham\Component\InstructorBilling\Site\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\ExportService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class InvoiceController extends BaseController
{
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
}
