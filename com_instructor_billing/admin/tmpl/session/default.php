<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);

$item = $this->item;
$formatInput = static function ($value) {
	return $value ? str_replace(' ', 'T', substr($value, 0, 16)) : '';
};
?>

<div class="ib-admin">
	<form class="ib-form ib-card" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.save'); ?>">
		<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
		<div class="ib-form-grid">
			<label>Instructeur
				<select name="instructor_user_id" required>
					<option value="">Choisir...</option>
					<?php foreach ($this->instructors as $instructor) : ?>
						<option value="<?php echo (int) $instructor->user_id; ?>" <?php echo (int) $item->instructor_user_id === (int) $instructor->user_id ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars($instructor->name); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Élève/client
				<input name="student_name" value="<?php echo htmlspecialchars((string) $item->student_name); ?>">
			</label>
			<label>Début
				<input name="start_time" type="datetime-local" value="<?php echo htmlspecialchars($formatInput($item->start_time)); ?>" required>
			</label>
			<label>Fin
				<input name="end_time" type="datetime-local" value="<?php echo htmlspecialchars($formatInput($item->end_time)); ?>">
			</label>
			<label>Statut
				<select name="status">
					<?php foreach (['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'] as $value => $label) : ?>
						<option value="<?php echo $value; ?>" <?php echo $item->status === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Latitude départ
				<input name="start_lat" value="<?php echo htmlspecialchars((string) $item->start_lat); ?>">
			</label>
			<label>Longitude départ
				<input name="start_lng" value="<?php echo htmlspecialchars((string) $item->start_lng); ?>">
			</label>
			<label>Latitude fin
				<input name="end_lat" value="<?php echo htmlspecialchars((string) $item->end_lat); ?>">
			</label>
			<label>Longitude fin
				<input name="end_lng" value="<?php echo htmlspecialchars((string) $item->end_lng); ?>">
			</label>
		</div>
		<label>Notes
			<textarea name="notes" rows="4"><?php echo htmlspecialchars((string) $item->notes); ?></textarea>
		</label>
		<div class="ib-actions">
			<button class="btn btn-primary" type="submit">Enregistrer</button>
			<a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=sessions'); ?>">Retour</a>
		</div>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php if ($item->id) : ?>
		<form class="ib-actions" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.delete&id=' . (int) $item->id); ?>">
			<button class="btn btn-danger" type="submit">Supprimer</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
