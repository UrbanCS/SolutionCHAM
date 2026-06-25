<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);
?>

<div class="ib-admin">
	<div class="ib-band">
		<div>
			<h2>Profils instructeurs</h2>
			<p>Associez un utilisateur Joomla à un taux horaire et activez son accès.</p>
		</div>
	</div>

	<form class="ib-form ib-card" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=instructor.saveProfile'); ?>">
		<div class="ib-form-grid">
			<label>Utilisateur Joomla
				<select name="user_id" required>
					<option value="">Choisir...</option>
					<?php foreach ($this->users as $user) : ?>
						<option value="<?php echo (int) $user->id; ?>"><?php echo htmlspecialchars($user->name . ' (' . $user->username . ')'); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Taux horaire
				<input name="hourly_rate" type="number" step="0.01" min="0" required>
			</label>
			<label>Téléphone
				<input name="phone" type="text" maxlength="40">
			</label>
			<label class="ib-check">
				<input name="active" type="checkbox" value="1" checked> Actif
			</label>
		</div>
		<button class="btn btn-primary" type="submit">Enregistrer le profil</button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>

	<table class="table table-striped">
		<thead><tr><th>Nom</th><th>Courriel</th><th>Taux</th><th>Téléphone</th><th>Statut</th></tr></thead>
		<tbody>
		<?php foreach ($this->items as $item) : ?>
			<tr>
				<td><?php echo htmlspecialchars($item->name); ?></td>
				<td><?php echo htmlspecialchars($item->email); ?></td>
				<td><?php echo htmlspecialchars($item->hourly_rate); ?> $/h</td>
				<td><?php echo htmlspecialchars((string) $item->phone); ?></td>
				<td><?php echo (int) $item->active ? 'Actif' : 'Inactif'; ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$this->items) : ?>
			<tr><td colspan="5">Aucun profil instructeur.</td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
