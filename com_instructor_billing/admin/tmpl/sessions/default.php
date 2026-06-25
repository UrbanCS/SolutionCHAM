<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);

$app = Factory::getApplication();
$statusLabels = ['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'];
$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
?>

<div class="ib-admin">
	<div class="ib-band">
		<h2>Cours et trajets</h2>
		<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=session'); ?>">Créer une entrée</a>
	</div>

	<form class="ib-filters" method="get" action="<?php echo Route::_('index.php'); ?>">
		<input type="hidden" name="option" value="com_instructor_billing">
		<input type="hidden" name="view" value="sessions">
		<select name="filter_instructor">
			<option value="">Tous les instructeurs</option>
			<?php foreach ($this->instructors as $instructor) : ?>
				<option value="<?php echo (int) $instructor->user_id; ?>" <?php echo $app->input->getInt('filter_instructor') === (int) $instructor->user_id ? 'selected' : ''; ?>>
					<?php echo htmlspecialchars($instructor->name); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<select name="filter_status">
			<option value="">Tous les statuts</option>
			<?php foreach ($statusLabels as $value => $label) : ?>
				<option value="<?php echo $value; ?>" <?php echo $app->input->getCmd('filter_status') === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="filter_from" value="<?php echo htmlspecialchars($app->input->getString('filter_from', '')); ?>">
		<input type="date" name="filter_to" value="<?php echo htmlspecialchars($app->input->getString('filter_to', '')); ?>">
		<button class="btn btn-secondary" type="submit">Filtrer</button>
	</form>

	<table class="table table-striped">
		<thead><tr><th>Date</th><th>Instructeur</th><th>Élève</th><th>Durée</th><th>Statut</th><th>GPS</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($this->items as $item) : ?>
			<tr>
				<td><?php echo htmlspecialchars($item->start_time); ?></td>
				<td><?php echo htmlspecialchars($item->instructor_name); ?></td>
				<td><?php echo htmlspecialchars((string) $item->student_name); ?></td>
				<td><?php echo $minutesToHours($item->duration_minutes); ?></td>
				<td><span class="ib-pill ib-status-<?php echo htmlspecialchars($item->status); ?>"><?php echo $statusLabels[$item->status] ?? $item->status; ?></span></td>
				<td><?php echo ($item->start_lat && $item->end_lat) ? 'Début/fin' : (($item->start_lat || $item->end_lat) ? 'Partiel' : '-'); ?></td>
				<td class="ib-actions">
					<a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=session&id=' . (int) $item->id); ?>">Modifier</a>
					<?php if ($item->status === 'submitted') : ?>
						<form method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.approve&id=' . (int) $item->id); ?>">
							<button class="btn btn-sm btn-success" type="submit">Approuver</button><?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<form method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.refuse&id=' . (int) $item->id); ?>">
							<button class="btn btn-sm btn-warning" type="submit">Refuser</button><?php echo HTMLHelper::_('form.token'); ?>
						</form>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$this->items) : ?>
			<tr><td colspan="7">Aucune entrée trouvée.</td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
