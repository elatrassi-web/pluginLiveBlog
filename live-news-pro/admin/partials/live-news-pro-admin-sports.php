<div class="col-xl-5 col-lg-12 mx-auto col-xl-8">
	<div class="card border-0 shadow-sm h-100">
		<div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
			<h4 class="mb-0 fw-bold"><i class="dashicons dashicons-controls-play fs-4 align-middle text-success"></i> Score Sportif</h4>
			<div class="d-flex align-items-center gap-2">
				<select id="sports_period" class="form-select form-select-sm fw-bold w-auto">
					<option value="Avant-match">Avant-match</option>
					<option value="1MT">1ère Mi-temps</option>
					<option value="MT">Mi-temps</option>
					<option value="2MT">2ème Mi-temps</option>
					<option value="Prol.">Prolongations</option>
					<option value="TAB">Tirs au but</option>
					<option value="Fin">Terminé</option>
				</select>
			</div>
		</div>
		<div class="card-body bg-light">

			<div class="row text-center mb-4 align-items-center">
				<div class="col-5">
					<h5 class="fw-bold mb-3">Équipe Domicile</h5>
					<div class="mb-2"><input type="text" id="t1_name" class="form-control text-center fw-bold fs-5" placeholder="Nom équipe"></div>
					<div class="mb-2 d-flex gap-2">
						<input type="color" id="t1_color" class="form-control form-control-color w-25" value="#e2001a" title="Couleur">
						<input type="text" id="t1_logo" class="form-control w-75" placeholder="URL Logo (opt.)">
					</div>
					<div class="d-flex justify-content-center align-items-center gap-3 mt-3">
						<button class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-target="t1_score" data-val="-1">-</button>
						<input type="number" id="t1_score" class="form-control text-center fs-1 fw-bold border-0 bg-transparent w-50" value="0" min="0">
						<button class="btn btn-outline-primary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-target="t1_score" data-val="1">+</button>
					</div>
					<div class="mt-3">
						<button class="btn btn-sm btn-outline-dark fw-bold w-100 sports-quick-insert" data-text="⚽ <strong>BUT !</strong> ">⚽ BUT !</button>
					</div>
				</div>

				<div class="col-2">
					<div class="bg-dark text-white rounded p-2 mb-2">
						<input type="text" id="sports_timer" class="form-control text-center bg-transparent border-0 text-white fs-3 fw-bold p-0" value="00:00" placeholder="00:00">
					</div>
					<button id="sports_timer_toggle" class="btn btn-sm btn-success w-100 fw-bold mb-1" data-status="stopped">▶ Play</button>
					<button id="sports_timer_reset" class="btn btn-sm btn-outline-secondary w-100" style="font-size:10px;">Reset</button>
					<span class="fs-3 fw-bold text-muted d-block mt-3">-</span>
				</div>

				<div class="col-5">
					<h5 class="fw-bold mb-3">Équipe Extérieur</h5>
					<div class="mb-2"><input type="text" id="t2_name" class="form-control text-center fw-bold fs-5" placeholder="Nom équipe"></div>
					<div class="mb-2 d-flex gap-2">
						<input type="color" id="t2_color" class="form-control form-control-color w-25" value="#0056b3" title="Couleur">
						<input type="text" id="t2_logo" class="form-control w-75" placeholder="URL Logo (opt.)">
					</div>
					<div class="d-flex justify-content-center align-items-center gap-3 mt-3">
						<button class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-target="t2_score" data-val="-1">-</button>
						<input type="number" id="t2_score" class="form-control text-center fs-1 fw-bold border-0 bg-transparent w-50" value="0" min="0">
						<button class="btn btn-outline-primary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-target="t2_score" data-val="1">+</button>
					</div>
					<div class="mt-3">
						<button class="btn btn-sm btn-outline-dark fw-bold w-100 sports-quick-insert" data-text="⚽ <strong>BUT !</strong> ">⚽ BUT !</button>
					</div>
				</div>
			</div>

			<div class="p-3 bg-white rounded border mt-4">
				<h6 class="fw-bold mb-2">Actions rapides</h6>
				<div class="d-flex flex-wrap gap-2">
					<button class="btn btn-sm btn-warning sports-quick-insert" data-text="🟨 <strong>Carton Jaune :</strong> ">🟨 Jaune</button>
					<button class="btn btn-sm btn-danger sports-quick-insert" data-text="🟥 <strong>Carton Rouge :</strong> ">🟥 Rouge</button>
					<button class="btn btn-sm btn-outline-secondary sports-quick-insert" data-text="🔄 <strong>Changement :</strong> ">🔄 Remplacement</button>
					<button class="btn btn-sm btn-outline-secondary sports-quick-insert" data-text="🏁 <strong>Coup de sifflet :</strong> ">🏁 Fin/Mi-temps</button>
				</div>
			</div>

			<button type="button" id="live_news_btn_sports_update" class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-3 mt-4">
				🔄 METTRE À JOUR LE SCORE
			</button>

			<div class="mt-5 border-top pt-4">
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