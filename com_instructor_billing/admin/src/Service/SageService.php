<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

class SageService
{
	public function createInvoice(int $invoiceId): array
	{
		return [
			'ready'      => false,
			'invoiceId'  => $invoiceId,
			'message'    => 'Sage n’est pas encore connecté. Utiliser l’export CSV comptable pour le MVP.',
		];
	}

	public function syncCustomer(int $instructorId): array
	{
		return [
			'ready'        => false,
			'instructorId' => $instructorId,
			'message'      => 'Synchronisation client Sage préparée, OAuth2 à connecter ultérieurement.',
		];
	}

	public function testConnection(): array
	{
		$params = ComponentHelper::getParams(AccessService::COMPONENT);

		return [
			'ready'       => false,
			'enabled'     => (bool) $params->get('sage_enabled', 0),
			'hasClientId' => trim((string) $params->get('sage_client_id', '')) !== '',
			'message'     => 'Configuration enregistrable, connexion API non activée dans le MVP.',
		];
	}
}
