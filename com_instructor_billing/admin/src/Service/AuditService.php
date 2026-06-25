<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class AuditService
{
	public static function log(string $action, string $entityType, ?int $entityId = null, array $details = [], ?int $userId = null): void
	{
		try {
			$db = Factory::getDbo();
			$now = Factory::getDate()->toSql();
			$userId = $userId ?? (int) AccessService::currentUser()->id;

			$row = (object) [
				'user_id'     => $userId ?: null,
				'action'      => substr($action, 0, 80),
				'entity_type' => substr($entityType, 0, 80),
				'entity_id'   => $entityId,
				'details'     => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
				'created_at'  => $now,
			];

			$db->insertObject('#__billing_audit_logs', $row);
		} catch (\Throwable $e) {
			// Audit logging must not block the instructor workflow on shared hosting.
		}
	}
}
