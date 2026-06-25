<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AuditService;
use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class InstructorsModel extends BaseDatabaseModel
{
	public function getItems(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['p.*', 'u.name', 'u.username', 'u.email'])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->order($db->quoteName('u.name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getUsers(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['id', 'name', 'username', 'email'])
			->from($db->quoteName('#__users'))
			->where($db->quoteName('block') . ' = 0')
			->order($db->quoteName('name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function saveProfile(array $data): int
	{
		$db = Factory::getDbo();
		$userId = (int) ($data['user_id'] ?? 0);
		$now = Factory::getDate()->toSql();

		if ($userId <= 0) {
			throw new \RuntimeException('Utilisateur Joomla requis.', 400);
		}

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__instructor_profiles'))
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$id = (int) $db->loadResult();

		$row = (object) [
			'user_id'     => $userId,
			'hourly_rate' => MoneyService::fromCents(MoneyService::toCents($data['hourly_rate'] ?? '0')),
			'phone'       => trim((string) ($data['phone'] ?? '')) ?: null,
			'active'      => (int) (($data['active'] ?? 0) ? 1 : 0),
			'updated_at'  => $now,
		];

		if ($id > 0) {
			$row->id = $id;
			$db->updateObject('#__instructor_profiles', $row, 'id');
		} else {
			$row->created_at = $now;
			$db->insertObject('#__instructor_profiles', $row, 'id');
			$id = (int) $row->id;
		}

		AuditService::log('instructor_profile.save', 'instructor_profile', $id, ['user_id' => $userId]);

		return $id;
	}
}
