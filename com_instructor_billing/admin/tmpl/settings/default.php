<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);
$status = $this->sageStatus;
?>

<div class="ib-admin">
	<div class="ib-card">
		<h2>Intégration Sage</h2>
		<p>Connectez Sage Business Cloud Accounting pour pousser les factures hebdomadaires vers Sage.</p>
		<ul>
			<li>Activé dans les paramètres: <?php echo $status['enabled'] ? 'Oui' : 'Non'; ?></li>
			<li>Client ID configuré: <?php echo $status['hasClientId'] ? 'Oui' : 'Non'; ?></li>
			<li>Client secret configuré: <?php echo $status['hasClientSecret'] ? 'Oui' : 'Non'; ?></li>
			<li>OAuth2 connecté: <?php echo $status['connected'] ? 'Oui' : 'Non'; ?></li>
			<li>Compte de grand livre configuré: <?php echo $status['hasLedger'] ? 'Oui' : 'Non'; ?></li>
			<li>Business ID: <?php echo htmlspecialchars($status['businessId'] ?: 'Non défini'); ?></li>
			<li><?php echo htmlspecialchars($status['message']); ?></li>
		</ul>
		<p><strong>Redirect URI à configurer dans Sage:</strong><br><code><?php echo htmlspecialchars($status['redirectUri']); ?></code></p>
		<div class="ib-actions">
			<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&task=sage.connect&' . Session::getFormToken() . '=1'); ?>">Connecter Sage</a>
			<form method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=sage.disconnect'); ?>">
				<button class="btn btn-secondary" type="submit">Déconnecter Sage</button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>
		<p>Les clés API ne doivent jamais être codées dans les fichiers. Configurez Client ID, secret, compte de grand livre, type de document et Business ID dans les paramètres du composant Joomla.</p>
		<?php if (!empty($status['businesses'])) : ?>
			<h3>Entreprises Sage détectées</h3>
			<table class="table table-striped">
				<thead><tr><th>Nom</th><th>ID</th></tr></thead>
				<tbody>
				<?php foreach ($status['businesses'] as $business) : ?>
					<tr>
						<td><?php echo htmlspecialchars((string) ($business['name'] ?? $business['displayed_as'] ?? 'Entreprise Sage')); ?></td>
						<td><code><?php echo htmlspecialchars((string) ($business['id'] ?? '')); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
