<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class DateService
{
	public static function formatLocal($value, string $format = 'Y-m-d H:i'): string
	{
		if (!$value) {
			return '';
		}

		try {
			$date = new \DateTimeImmutable((string) $value, new \DateTimeZone('UTC'));
			return $date->setTimezone(self::timezone())->format($format);
		} catch (\Throwable $e) {
			return (string) $value;
		}
	}

	public static function currentWeekBounds(): array
	{
		$now = new \DateTimeImmutable('now', self::timezone());
		$start = $now->modify('monday this week');
		$end = $start->modify('+6 days');

		return [$start->format('Y-m-d'), $end->format('Y-m-d')];
	}

	private static function timezone(): \DateTimeZone
	{
		$configured = (string) ComponentHelper::getParams(AccessService::COMPONENT)->get('display_timezone', 'America/Toronto');

		try {
			return new \DateTimeZone($configured ?: 'America/Toronto');
		} catch (\Throwable $e) {
			// Fall back below if a custom timezone was mistyped in component options.
		}

		$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();

		try {
			return $user->getTimezone();
		} catch (\Throwable $e) {
			return new \DateTimeZone('America/Toronto');
		}
	}
}
