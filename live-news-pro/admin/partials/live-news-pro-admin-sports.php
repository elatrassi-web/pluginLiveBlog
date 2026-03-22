<div class="col-xl-5 col-lg-12 mx-auto col-xl-8">
	<div class="card border-0 shadow-sm h-100">
		<div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
			<h4 class="mb-0 fw-bold"><i class="dashicons dashicons-controls-play fs-4 align-middle text-success"></i> Multiplex / Scores Sportifs</h4>
			<div class="form-check form-switch mt-1">
				<input class="form-check-input" type="checkbox" role="switch" id="toggle_sports_editor" checked>
				<label class="form-check-label fw-bold" for="toggle_sports_editor">Activer Rédaction du Live</label>
			</div>
		</div>
		<div class="card-body bg-light">

			<div id="sports-matches-container">
				<!-- Matches will be added here dynamically -->
			</div>

			<button type="button" id="btn-add-sports-match" class="btn dashed-btn w-100 py-3 mb-4 rounded">
				<i class="dashicons dashicons-plus-alt2 align-middle"></i> Ajouter un match
			</button>

			<div class="p-3 bg-white rounded border mt-4 mb-4">
				<h6 class="fw-bold mb-2">Actions rapides</h6>
				<div class="d-flex flex-wrap gap-2">
					<button class="btn btn-sm btn-warning sports-quick-insert" data-text="🟨 <strong>Carton Jaune :</strong> ">🟨 Jaune</button>
					<button class="btn btn-sm btn-danger sports-quick-insert" data-text="🟥 <strong>Carton Rouge :</strong> ">🟥 Rouge</button>
					<button class="btn btn-sm btn-outline-secondary sports-quick-insert" data-text="🔄 <strong>Changement :</strong> ">🔄 Remplacement</button>
					<button class="btn btn-sm btn-outline-secondary sports-quick-insert" data-text="🏁 <strong>Coup de sifflet :</strong> ">🏁 Fin/Mi-temps</button>
				</div>
			</div>

			<button type="button" id="live_news_btn_sports_update" class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-3 mt-4">
				🔄 METTRE À JOUR LES SCORES
			</button>

			<div id="sports-editor-wrapper" class="mt-5 border-top pt-4">
				<h4 class="mb-3 fw-bold"><i class="dashicons dashicons-edit fs-4 align-middle text-primary"></i> Rédaction du Live</h4>
				<input type="hidden" id="live_news_edit_id" value="">
				<div class="mb-3">
					<textarea id="live_news_content" class="form-control" rows="4" placeholder="Tapez votre commentaire de match ici..."></textarea>
				</div>
				<div class="row g-3 mb-3">
					<div class="col-md-6">
						<select id="live_news_importance" class="form-select form-select-sm">
							<option value="normal">⚪ Action de jeu (Normal)</option>
							<option value="important">🔵 Occasion (Important)</option>
							<option value="alerte">🔴 BUT / ROUGE (ALERTE)</option>
						</select>
					</div>
					<div class="col-md-6">
						<div class="input-group input-group-sm">
							<span class="input-group-text">Min.</span>
							<input type="text" id="live_news_custom_time" class="form-control" placeholder="Ex: 45' ou 90+2'">
						</div>
					</div>
				</div>
				<div class="d-flex gap-2">
					<button type="button" id="live_news_btn_send" class="btn btn-primary w-100 fw-bold">🚀 PUBLIER COMMENTAIRE</button>
					<button type="button" id="live_news_btn_cancel_edit" class="btn btn-outline-dark" style="display:none;">Annuler</button>
				</div>

				<div id="live_news_history_list" class="list-group shadow-sm mt-4" style="max-height: 400px; overflow-y: auto;"></div>
				<button type="button" id="live_news_btn_purge" class="btn btn-outline-danger btn-sm fw-bold w-100 mt-2">🚨 PURGER LE MATCH</button>
			</div>

		</div>
	</div>
</div>