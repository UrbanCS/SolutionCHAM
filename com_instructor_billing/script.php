<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;

/**
 * Small installer helper for cPanel-friendly Joomla installs.
 */
class Com_Instructor_BillingInstallerScript
{
	public function postflight(string $type, InstallerAdapter $parent): bool
	{
		if (!in_array($type, ['install', 'update'], true)) {
			return true;
		}

		$this->ensureInstructorGroup();

		return true;
	}

	private function ensureInstructorGroup(): void
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__usergroups'))
			->where($db->quoteName('title') . ' = ' . $db->quote('Instructeur'));
		$db->setQuery($query);

		if ((int) $db->loadResult() > 0) {
			return;
		}

		$query = $db->getQuery(true)
			->select($db->quoteName(['id', 'lft', 'rgt']))
			->from($db->quoteName('#__usergroups'))
			->where($db->quoteName('title') . ' = ' . $db->quote('Registered'));
		$db->setQuery($query);
		$registered = $db->loadObject();

		if (!$registered) {
			return;
		}

		// Minimal nested-set insert under Registered. Administrators can fine-tune ACL after install.
		$query = $db->getQuery(true)
			->update($db->quoteName('#__usergroups'))
			->set($db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2')
			->where($db->quoteName('rgt') . ' >= ' . (int) $registered->rgt);
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->update($db->quoteName('#__usergroups'))
			->set($db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2')
			->where($db->quoteName('lft') . ' > ' . (int) $registered->rgt);
		$db->setQuery($query)->execute();

		$group = (object) [
			'parent_id' => (int) $registered->id,
			'lft'       => (int) $registered->rgt,
			'rgt'       => (int) $registered->rgt + 1,
			'title'     => 'Instructeur',
		];

		$db->insertObject('#__usergroups', $group);
	}
}
