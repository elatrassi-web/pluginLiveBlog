<div class="col-xl-5 col-lg-12 <?php if($selected_theme == 'votes') echo 'mx-auto col-xl-8'; ?>">
	<div class="card border-0 shadow-sm h-100">
		<div class="card-header bg-white border-bottom py-3">
			<h4 class="mb-0 fw-bold"><i class="dashicons dashicons-chart-bar fs-4 align-middle text-primary"></i> Tableau des scores</h4>
		</div>
		<div class="card-body bg-light">
			<div class="mb-4 p-3 bg-white rounded border shadow-sm">
				<label class="form-label fw-bold text-secondary">Statut du dépouillement :</label>
				<select id="live_news_results_status" class="form-select form-select-lg">
					<option value="En direct">🔵 En direct (Estimations)</option>
					<option value="Partiels">🟠 Résultats Partiels</option>
					<option value="Définitifs">🟢 Résultats Définitifs</option>
				</select>
			</div>

			<div id="candidates-admin-list" class="mb-3"></div>

			<button type="button" id="btn-add-candidate" class="btn dashed-btn w-100 py-3 mb-4 rounded">
				<i class="dashicons dashicons-plus-alt2 align-middle"></i> Ajouter un candidat
			</button>

			<button type="button" id="live_news_btn_results" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm py-3">
				🔄 METTRE À JOUR LES VOTES
			</button>

			<?php if ( $selected_theme === 'votes' ) : ?>
				<hr class="my-4">
				<button type="button" id="live_news_btn_purge" class="btn btn-outline-danger w-100 fw-bold">🚨 PURGER LE TABLEAU ET LES VUES</button>
			<?php endif; ?>
		</div>
	</div>
</div>