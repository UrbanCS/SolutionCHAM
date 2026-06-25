<?php

namespace Cham\Component\InstructorBilling\Site\Service;

defined('_JEXEC') or die;

class SharedServices
{
	public static function load(): void
	{
		$base = JPATH_ADMINISTRATOR . '/components/com_instructor_billing/src/Service/';

		foreach (['AccessService', 'MoneyService', 'AuditService', 'InvoiceService', 'ExportService', 'SageService'] as $service) {
			$file = $base . $service . '.php';

			if (is_file($file)) {
				require_once $file;
			}
		}
	}
}
