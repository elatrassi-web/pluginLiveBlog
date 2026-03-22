<div class="wrap bs-admin-wrap">
	<div class="mb-3">
		<a href="?page=live-news-dashboard" class="text-decoration-none fw-bold text-secondary">← Retour au tableau de bord</a>
	</div>

	<div class="text-center mb-5">
		<h1 class="mb-2 fw-bold" style="font-size: 32px;">Créer un nouvel Espace Live</h1>
		<p class="text-muted fs-5">Choisissez le format d'affichage que vous souhaitez générer.</p>
	</div>

	<div class="row g-4 justify-content-center" style="max-width: 1200px; margin: 0 auto;">

		<div class="col-md-6 col-lg-3">
			<div class="card h-100 border-0 shadow-sm theme-card" data-theme="info">
				<div class="card-body text-center p-4">
					<span style="font-size: 50px;">📰</span>
					<h4 class="fw-bold mt-3">Live Info</h4>
					<p class="text-muted small mb-4">Le format classique pour suivre une crise, un événement ou une manifestation en temps réel.</p>
					<button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
				</div>
			</div>
		</div>

		<?php if ( has_action( 'live_news_pro_add_themes' ) ) : ?>
			<?php do_action('live_news_pro_add_themes'); ?>
		<?php else : ?>
			<div class="col-md-6 col-lg-3">
				<div class="card h-100 border-0 shadow-sm theme-card disabled" style="cursor: default;">
					<div class="card-body text-center p-4 position-relative">
						<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 fw-bold">🔒 PRO</span>
						<span style="font-size: 50px; opacity: 0.5;">🗳️</span>
						<h4 class="fw-bold mt-3 text-muted">Info + Votes</h4>
						<p class="text-muted small mb-4">Le flux d'actualité combiné avec un tableau de scores interactif.</p>
						<button class="btn btn-outline-secondary w-100 fw-bold" disabled>Générer ce direct</button>
					</div>
				</div>
			</div>

			<div class="col-md-6 col-lg-3">
				<div class="card h-100 border-0 shadow-sm theme-card disabled" style="cursor: default;">
					<div class="card-body text-center p-4 position-relative">
						<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 fw-bold">🔒 PRO</span>
						<span style="font-size: 50px; opacity: 0.5;">📊</span>
						<h4 class="fw-bold mt-3 text-muted">Sondage</h4>
						<p class="text-muted small mb-4">Affichage uniquement du tableau des résultats pour une soirée électorale pure.</p>
						<button class="btn btn-outline-secondary w-100 fw-bold" disabled>Générer ce direct</button>
					</div>
				</div>
			</div>

			<div class="col-md-6 col-lg-3">
				<div class="card h-100 border-0 shadow-sm theme-card disabled" style="cursor: default;">
					<div class="card-body text-center p-4 position-relative">
						<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 fw-bold">🔒 PRO</span>
						<span style="font-size: 50px; opacity: 0.5;">⚽</span>
						<h4 class="fw-bold mt-3 text-muted">Score Sportif</h4>
						<p class="text-muted small mb-4">Chronomètre en direct, affichage des équipes et alertes buts/cartons.</p>
						<button class="btn btn-outline-secondary w-100 fw-bold" disabled>Générer ce direct</button>
					</div>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<div class="modal fade" id="createLiveModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg">
				<div class="modal-header bg-primary text-white">
					<h5 class="modal-title fw-bold">📝 Nommer votre direct</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form id="form-create-live">
					<div class="modal-body p-4 bg-light">
						<input type="hidden" id="create_live_theme" value="">

						<div class="mb-3">
							<label class="form-label fw-bold">Nom de l'événement</label>
							<input type="text" id="create_live_title" class="form-control form-control-lg shadow-sm" placeholder="Ex: Manifestation..." required>
							<div class="form-text mt-2">Ce nom vous permettra de retrouver votre direct dans le tableau de bord pour récupérer son shortcode.</div>
						</div>
					</div>
					<div class="modal-footer bg-white">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
						<button type="submit" id="btn-submit-create" class="btn btn-primary fw-bold px-4">Créer l'Espace Live 🚀</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>