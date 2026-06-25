<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);
?>

<div class="ib-admin">
	<div class="ib-card">
		<h2>Intégration Sage</h2>
		<p>Le MVP inclut l’export CSV comptable et une classe SageService prête pour OAuth2 plus tard.</p>
		<ul>
			<li>Activé dans les paramètres: <?php echo $this->sageStatus['enabled'] ? 'Oui' : 'Non'; ?></li>
			<li>Client ID configuré: <?php echo $this->sageStatus['hasClientId'] ? 'Oui' : 'Non'; ?></li>
			<li><?php echo htmlspecialchars($this->sageStatus['message']); ?></li>
		</ul>
		<p>Les clés API ne doivent jamais être codées dans les fichiers. Utilisez les paramètres du composant Joomla.</p>
	</div>
</div>
