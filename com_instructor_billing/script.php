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

		$this->ensureSchema();
		$this->ensureInstructorGroup();

		return true;
	}

	private function ensureSchema(): void
	{
		$this->ensureSageSettingsTable();
		$this->ensureColumn('#__instructor_profiles', 'sage_contact_id', "ALTER TABLE `#__instructor_profiles` ADD `sage_contact_id` varchar(80) NULL DEFAULT NULL AFTER `phone`");
		$this->ensureColumn('#__invoices', 'sage_invoice_id', "ALTER TABLE `#__invoices` ADD `sage_invoice_id` varchar(80) NULL DEFAULT NULL AFTER `status`");
		$this->ensureColumn('#__invoices', 'sage_invoice_number', "ALTER TABLE `#__invoices` ADD `sage_invoice_number` varchar(80) NULL DEFAULT NULL AFTER `sage_invoice_id`");
		$this->ensureColumn('#__invoices', 'sage_synced_at', "ALTER TABLE `#__invoices` ADD `sage_synced_at` datetime NULL DEFAULT NULL AFTER `sage_invoice_number`");
		$this->ensureColumn('#__invoices', 'sage_sync_status', "ALTER TABLE `#__invoices` ADD `sage_sync_status` varchar(30) NULL DEFAULT NULL AFTER `sage_synced_at`");
		$this->ensureColumn('#__invoices', 'sage_sync_error', "ALTER TABLE `#__invoices` ADD `sage_sync_error` text NULL DEFAULT NULL AFTER `sage_sync_status`");
		$this->ensureIndex('#__invoices', 'idx_invoices_sage_status', "ALTER TABLE `#__invoices` ADD KEY `idx_invoices_sage_status` (`sage_sync_status`)");
	}

	private function ensureSageSettingsTable(): void
	{
		$db = Factory::getDbo();
		$db->setQuery(
			"CREATE TABLE IF NOT EXISTS `#__instructor_billing_sage_settings` (
				`id` int unsigned NOT NULL AUTO_INCREMENT,
				`setting_key` varchar(120) NOT NULL,
				`setting_value` text NULL DEFAULT NULL,
				`created_at` datetime NOT NULL,
				`updated_at` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `idx_sage_settings_key` (`setting_key`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
		)->execute();
	}

	private function ensureColumn(string $table, string $column, string $sql): void
	{
		$db = Factory::getDbo();
		$db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote($column));

		if ($db->loadObject()) {
			return;
		}

		$db->setQuery($sql)->execute();
	}

	private function ensureIndex(string $table, string $index, string $sql): void
	{
		$db = Factory::getDbo();
		$db->setQuery('SHOW INDEX FROM ' . $db->quoteName($table) . ' WHERE ' . $db->quoteName('Key_name') . ' = ' . $db->quote($index));

		if ($db->loadObject()) {
			return;
		}

		$db->setQuery($sql)->execute();
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
