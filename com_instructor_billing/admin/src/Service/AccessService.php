<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

class AccessService
{
	public const COMPONENT = 'com_instructor_billing';

	public static function currentUser(): User
	{
		return Factory::getApplication()->getIdentity() ?: Factory::getUser();
	}

	public static function isManager(?User $user = null): bool
	{
		$user = $user ?: self::currentUser();

		return !$user->guest
			&& ($user->authorise('core.admin', self::COMPONENT) || $user->authorise('core.manage', self::COMPONENT));
	}

	public static function canApprove(?User $user = null): bool
	{
		$user = $user ?: self::currentUser();

		return self::isManager($user) || $user->authorise('billing.approve', self::COMPONENT);
	}

	public static function canInvoice(?User $user = null): bool
	{
		$user = $user ?: self::currentUser();

		return self::isManager($user) || $user->authorise('billing.invoice', self::COMPONENT);
	}

	public static function isInstructor(?User $user = null): bool
	{
		$user = $user ?: self::currentUser();

		return !$user->guest
			&& (self::isManager($user) || $user->authorise('billing.instructor', self::COMPONENT) || self::hasActiveProfile((int) $user->id));
	}

	public static function canAccessInstructor(int $instructorUserId, ?User $user = null): bool
	{
		$user = $user ?: self::currentUser();

		return self::isManager($user) || ((int) $user->id === $instructorUserId && self::isInstructor($user));
	}

	public static function hasActiveProfile(int $userId): bool
	{
		if ($userId <= 0) {
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__instructor_profiles'))
			->where($db->quoteName('user_id') . ' = ' . (int) $userId)
			->where($db->quoteName('active') . ' = 1');
		$db->setQuery($query);

		return (int) $db->loadResult() > 0;
	}

	public static function denyUnless(bool $allowed, string $message = 'JERROR_ALERTNOAUTHOR'): void
	{
		if (!$allowed) {
			throw new \RuntimeException($message, 403);
		}
	}
}
