<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

class MoneyService
{
	public static function toCents($amount): int
	{
		$normalized = str_replace([' ', ','], ['', '.'], (string) $amount);

		return (int) round(((float) $normalized) * 100);
	}

	public static function fromCents(int $cents): string
	{
		return number_format($cents / 100, 2, '.', '');
	}

	public static function format($amount): string
	{
		$cents = is_int($amount) ? $amount : self::toCents($amount);

		return number_format($cents / 100, 2, ',', ' ') . ' $';
	}

	public static function lineTotalFromMinutes(int $minutes, $hourlyRate): int
	{
		$rateCents = self::toCents($hourlyRate);

		return (int) round(($rateCents * max(0, $minutes)) / 60);
	}

	public static function lineTotalFromHours($quantityHours, $hourlyRate): int
	{
		$hours = max(0, (float) str_replace(',', '.', (string) $quantityHours));

		return (int) round(self::toCents($hourlyRate) * $hours);
	}
}
