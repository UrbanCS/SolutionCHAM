<?php

namespace Cham\Component\InstructorBilling\Site\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\AuditService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SessionModel extends BaseDatabaseModel
{
	public function start(array $data): int
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();

		if ($this->getActiveSession($userId)) {
			throw new \RuntimeException('Un cours est déjà en cours.', 400);
		}

		$now = Factory::getDate()->toSql();
		$row = (object) [
			'instructor_user_id' => $userId,
			'student_name'       => trim((string) ($data['student_name'] ?? '')) ?: null,
			'start_time'         => $now,
			'end_time'           => null,
			'duration_minutes'   => 0,
			'start_lat'          => $this->coordinate($data['start_lat'] ?? null),
			'start_lng'          => $this->coordinate($data['start_lng'] ?? null),
			'end_lat'            => null,
			'end_lng'            => null,
			'notes'              => trim((string) ($data['notes'] ?? '')) ?: null,
			'status'             => 'draft',
			'approved_by'        => null,
			'approved_at'        => null,
			'created_at'         => $now,
			'updated_at'         => $now,
		];

		$db->insertObject('#__driving_sessions', $row, 'id');
		$this->insertGpsPoint((int) $row->id, $row->start_lat, $row->start_lng, $now);
		AuditService::log('session.start', 'driving_session', (int) $row->id, [], $userId);

		return (int) $row->id;
	}

	public function stop(int $userId, array $data): void
	{
		SharedServices::load();
		$db = Factory::getDbo();
		$session = $this->getActiveSession($userId);

		if (!$session) {
			throw new \RuntimeException('Aucun cours actif à terminer.', 404);
		}

		$now = Factory::getDate()->toSql();
		$duration = max(0, (int) ceil((strtotime($now) - strtotime($session->start_time)) / 60));
		$endLat = $this->coordinate($data['end_lat'] ?? null);
		$endLng = $this->coordinate($data['end_lng'] ?? null);

		$row = (object) [
			'id'               => (int) $session->id,
			'student_name'     => trim((string) ($data['student_name'] ?? $session->student_name)) ?: null,
			'end_time'         => $now,
			'duration_minutes' => $duration,
			'end_lat'          => $endLat,
			'end_lng'          => $endLng,
			'notes'            => trim((string) ($data['notes'] ?? $session->notes)) ?: null,
			'status'           => 'submitted',
			'updated_at'       => $now,
		];

		$db->updateObject('#__driving_sessions', $row, 'id');
		$this->insertGpsPoint((int) $session->id, $endLat, $endLng, $now);
		AuditService::log('session.stop', 'driving_session', (int) $session->id, ['duration_minutes' => $duration], $userId);
	}

	public function saveManual(array $data): int
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();
		$start = $this->normalizeDateTime((string) ($data['start_time'] ?? ''));
		$end = $this->normalizeDateTime((string) ($data['end_time'] ?? ''));

		if (!$start || !$end) {
			throw new \RuntimeException('Les heures de début et de fin sont requises.', 400);
		}

		$duration = max(0, (int) ceil((strtotime($end) - strtotime($start)) / 60));
		$now = Factory::getDate()->toSql();
		$row = (object) [
			'instructor_user_id' => $userId,
			'student_name'       => trim((string) ($data['student_name'] ?? '')) ?: null,
			'start_time'         => $start,
			'end_time'           => $end,
			'duration_minutes'   => $duration,
			'start_lat'          => $this->coordinate($data['start_lat'] ?? null),
			'start_lng'          => $this->coordinate($data['start_lng'] ?? null),
			'end_lat'            => $this->coordinate($data['end_lat'] ?? null),
			'end_lng'            => $this->coordinate($data['end_lng'] ?? null),
			'notes'              => trim((string) ($data['notes'] ?? '')) ?: null,
			'status'             => 'submitted',
			'approved_by'        => null,
			'approved_at'        => null,
			'created_at'         => $now,
			'updated_at'         => $now,
		];

		$db->insertObject('#__driving_sessions', $row, 'id');
		$this->insertGpsPoint((int) $row->id, $row->start_lat, $row->start_lng, $start);
		$this->insertGpsPoint((int) $row->id, $row->end_lat, $row->end_lng, $end);
		AuditService::log('session.manual', 'driving_session', (int) $row->id, ['duration_minutes' => $duration], $userId);

		return (int) $row->id;
	}

	public function submitWeek(int $userId): int
	{
		SharedServices::load();
		$start = new \DateTimeImmutable('monday this week');
		$end = $start->modify('+6 days');
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->update($db->quoteName('#__driving_sessions'))
			->set($db->quoteName('status') . ' = ' . $db->quote('submitted'))
			->set($db->quoteName('updated_at') . ' = ' . $db->quote(Factory::getDate()->toSql()))
			->where($db->quoteName('instructor_user_id') . ' = ' . (int) $userId)
			->where($db->quoteName('status') . ' = ' . $db->quote('draft'))
			->where($db->quoteName('end_time') . ' IS NOT NULL')
			->where($db->quoteName('start_time') . ' BETWEEN ' . $db->quote($start->format('Y-m-d') . ' 00:00:00') . ' AND ' . $db->quote($end->format('Y-m-d') . ' 23:59:59'));
		$db->setQuery($query)->execute();
		$count = $db->getAffectedRows();
		AuditService::log('week.submit', 'driving_session', null, ['count' => $count], $userId);

		return $count;
	}

	private function getActiveSession(int $userId): ?object
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('instructor_user_id') . ' = ' . (int) $userId)
			->where($db->quoteName('end_time') . ' IS NULL')
			->where($db->quoteName('status') . ' = ' . $db->quote('draft'))
			->order($db->quoteName('start_time') . ' DESC');
		$db->setQuery($query, 0, 1);

		return $db->loadObject() ?: null;
	}

	private function normalizeDateTime(string $value): string
	{
		if ($value === '') {
			return '';
		}

		return str_replace('T', ' ', substr($value, 0, 16)) . ':00';
	}

	private function coordinate($value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}

		return number_format((float) $value, 7, '.', '');
	}

	private function insertGpsPoint(int $sessionId, ?string $lat, ?string $lng, string $recordedAt): void
	{
		if ($sessionId <= 0 || $lat === null || $lng === null) {
			return;
		}

		$row = (object) [
			'session_id'  => $sessionId,
			'lat'         => $lat,
			'lng'         => $lng,
			'recorded_at' => $recordedAt,
		];

		Factory::getDbo()->insertObject('#__gps_points', $row);
	}
}
