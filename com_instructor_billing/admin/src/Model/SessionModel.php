<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AuditService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SessionModel extends BaseDatabaseModel
{
	public function getItem(?int $id = null): object
	{
		$id = $id ?: Factory::getApplication()->input->getInt('id');

		if ($id <= 0) {
			return (object) [
				'id'                 => 0,
				'instructor_user_id' => 0,
				'student_name'       => '',
				'start_time'         => '',
				'end_time'           => '',
				'duration_minutes'   => 0,
				'start_lat'          => null,
				'start_lng'          => null,
				'end_lat'            => null,
				'end_lng'            => null,
				'notes'              => '',
				'status'             => 'submitted',
			];
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['s.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__driving_sessions', 's'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.instructor_user_id'))
			->where($db->quoteName('s.id') . ' = ' . (int) $id);
		$db->setQuery($query);
		$item = $db->loadObject();

		if (!$item) {
			throw new \RuntimeException('Cours introuvable.', 404);
		}

		return $item;
	}

	public function save(array $data): int
	{
		$db = Factory::getDbo();
		$id = (int) ($data['id'] ?? 0);
		$start = $this->normalizeDateTime((string) ($data['start_time'] ?? ''));
		$end = $this->normalizeDateTime((string) ($data['end_time'] ?? ''));
		$duration = $this->calculateDuration($start, $end, (int) ($data['duration_minutes'] ?? 0));
		$now = Factory::getDate()->toSql();
		$status = (string) ($data['status'] ?? 'submitted');

		if (!in_array($status, ['draft', 'submitted', 'approved', 'refused'], true)) {
			$status = 'submitted';
		}

		$row = (object) [
			'instructor_user_id' => (int) ($data['instructor_user_id'] ?? 0),
			'student_name'       => trim((string) ($data['student_name'] ?? '')) ?: null,
			'start_time'         => $start,
			'end_time'           => $end ?: null,
			'duration_minutes'   => $duration,
			'start_lat'          => $this->coordinate($data['start_lat'] ?? null),
			'start_lng'          => $this->coordinate($data['start_lng'] ?? null),
			'end_lat'            => $this->coordinate($data['end_lat'] ?? null),
			'end_lng'            => $this->coordinate($data['end_lng'] ?? null),
			'notes'              => trim((string) ($data['notes'] ?? '')) ?: null,
			'status'             => $status,
			'updated_at'         => $now,
		];

		if ($row->instructor_user_id <= 0 || !$row->start_time) {
			throw new \RuntimeException('Instructeur et heure de début requis.', 400);
		}

		if ($status === 'approved') {
			$row->approved_by = (int) Factory::getApplication()->getIdentity()->id;
			$row->approved_at = $now;
		}

		if ($id > 0) {
			$row->id = $id;
			$db->updateObject('#__driving_sessions', $row, 'id');
		} else {
			$row->created_at = $now;
			$db->insertObject('#__driving_sessions', $row, 'id');
			$id = (int) $row->id;
		}

		AuditService::log('session.save', 'driving_session', $id, ['status' => $status]);

		return $id;
	}

	public function setStatus(int $id, string $status): void
	{
		if ($id <= 0 || !in_array($status, ['approved', 'refused'], true)) {
			throw new \RuntimeException('Statut invalide.', 400);
		}

		$db = Factory::getDbo();
		$now = Factory::getDate()->toSql();
		$row = (object) [
			'id'         => $id,
			'status'     => $status,
			'updated_at' => $now,
		];

		if ($status === 'approved') {
			$row->approved_by = (int) Factory::getApplication()->getIdentity()->id;
			$row->approved_at = $now;
		} else {
			$row->approved_by = null;
			$row->approved_at = null;
		}

		$db->updateObject('#__driving_sessions', $row, 'id');
		AuditService::log('session.' . $status, 'driving_session', $id);
	}

	public function delete(int $id): void
	{
		if ($id <= 0) {
			return;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('id') . ' = ' . (int) $id);
		$db->setQuery($query)->execute();
		AuditService::log('session.delete', 'driving_session', $id);
	}

	public function getInstructors(): array
	{
		return (new SessionsModel())->getInstructors();
	}

	private function normalizeDateTime(string $value): string
	{
		if ($value === '') {
			return '';
		}

		return str_replace('T', ' ', substr($value, 0, 16)) . ':00';
	}

	private function calculateDuration(string $start, ?string $end, int $fallback): int
	{
		if ($start && $end) {
			$startDate = new \DateTimeImmutable($start);
			$endDate = new \DateTimeImmutable($end);

			return max(0, (int) ceil(($endDate->getTimestamp() - $startDate->getTimestamp()) / 60));
		}

		return max(0, $fallback);
	}

	private function coordinate($value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}

		return number_format((float) $value, 7, '.', '');
	}
}
