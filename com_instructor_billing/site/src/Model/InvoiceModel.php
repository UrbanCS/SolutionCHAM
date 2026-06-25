<?php

namespace Cham\Component\InstructorBilling\Site\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\ExportService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class InvoiceModel extends BaseDatabaseModel
{
	public function getItem(?int $id = null): object
	{
		SharedServices::load();
		$id = $id ?: Factory::getApplication()->input->getInt('id');
		$export = new ExportService();
		$invoice = $export->assertCanExport((int) $id);
		AccessService::denyUnless((int) $invoice->instructor_user_id === (int) AccessService::currentUser()->id || AccessService::isManager());

		return $invoice;
	}

	public function getItems(?int $id = null): array
	{
		SharedServices::load();
		$id = $id ?: Factory::getApplication()->input->getInt('id');

		return (new ExportService())->getInvoiceItems((int) $id);
	}
}
