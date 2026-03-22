<div class="wrap bs-admin-wrap">
	<div class="mb-3">
		<a href="?page=live-news-dashboard" class="text-decoration-none fw-bold text-secondary">← Retour au tableau de bord</a>
	</div>

	<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
		<div class="d-flex align-items-center">
			<span class="fs-1 me-3">🔴</span>
			<div>
				<h2 class="mb-0 fw-bold"><?php echo esc_html( $post_title ); ?></h2>
				<div class="mt-1 d-flex align-items-center gap-2">
					<span class="badge bg-secondary text-uppercase">Thème : <?php echo esc_html( $selected_theme ); ?></span>
					<code class="bg-white border px-2 py-1 rounded text-danger fw-bold shadow-sm" style="user-select:all;">[live_news id="<?php echo esc_attr( $post_id ); ?>"]</code>
				</div>
			</div>
		</div>

		<div class="bg-dark text-white px-3 py-2 rounded-pill shadow-sm d-inline-flex align-items-center border border-secondary flex-nowrap" title="Statistiques d'audience">
			<div class="d-flex align-items-center pe-3 border-end border-secondary text-nowrap">
				<span class="live-indicator me-2"></span>
				<span class="fw-bold fs-4 text-danger" id="live-viewer-count">0</span>
				<span class="fw-normal fs-6 text-light ms-2 text-uppercase d-none d-md-inline" style="letter-spacing: 1px;">En direct</span>
			</div>
			<div class="d-flex align-items-center ps-3 text-nowrap">
				<span class="fs-5 me-2">👁️</span>
				<span class="fw-bold fs-4 text-info" id="total-viewer-count">0</span>
				<span class="fw-normal fs-6 text-light ms-2 text-uppercase d-none d-md-inline" style="letter-spacing: 1px;">Vues totales</span>
			</div>
		</div>
	</div>

	<input type="hidden" id="live_post_id" value="<?php echo esc_attr( $post_id ); ?>">
	<span id="loader-data" style="display:none;"></span>

	<div class="row g-4">
		<?php if ( $selected_theme !== 'votes' && $selected_theme !== 'sports' ) : ?>
			<div class="col-xl-7 col-lg-12">
				<div class="card border-0 shadow-sm h-100">
					<div class="card-header bg-white border-bottom py-3">
						<h4 class="mb-0 fw-bold"><i class="dashicons dashicons-edit fs-4 align-middle text-primary"></i> Rédaction du Flash Info</h4>
					</div>
					<div class="card-body">
						<input type="hidden" id="live_news_edit_id" value="">

						<div class="p-3 bg-light rounded border mb-4 d-flex flex-wrap gap-2 align-items-center">
							<span class="badge bg-primary text-uppercase px-2 py-2"><i class="dashicons dashicons-lightning"></i> Raccourcis</span>
							<div class="position-relative d-inline-block">
								<button type="button" class="btn btn-outline-dark btn-sm fw-bold" id="live_news_emoji_btn">😀 Emojis ▾</button>
								<div id="live_news_emoji_picker" class="emoji-panel">
									<div class="d-flex flex-wrap gap-1">
										<?php
										$emojis = ['🔴','🟢','🔵','🟡','⚠️','🚨','🔥','⏳','📊','📉','📈','🎙️','🗣️','📣','🗳️','✅','❌','🛑','🏆','🥇','👏','📌','📍','📸','🎥','📺','🗞️','💡','👉','👇'];
										foreach ( $emojis as $e ) { echo '<button type="button" class="btn btn-light btn-sm live-emoji-item fs-5 p-1" style="width:36px; height:36px;">'.$e.'</button>'; }
										?>
									</div>
								</div>
							</div>
							<div class="vr mx-1"></div>
							<button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="🔴 <strong>ALERTE INFO :</strong> ">🔴 Urgent</button>
							<button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="🎙️ <strong>Prise de parole :</strong> ">🎙️ Déclaration</button>
							<?php if ( $selected_theme === 'elections' ) : ?>
								<button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="📊 <strong>Nouveaux résultats :</strong> ">📊 Résultats</button>
							<?php endif; ?>
						</div>

						<div class="mb-4">
							<?php wp_editor( '', 'live_news_content', array( 'media_buttons' => true, 'textarea_rows' => 8, 'tinymce' => array( 'paste_as_text' => true ) ) ); ?>
						</div>

						<div class="row g-3 mb-4 p-3 bg-light rounded border">
							<div class="col-md-6">
								<label class="form-label fw-bold text-secondary">Niveau d'alerte :</label>
								<select id="live_news_importance" class="form-select">
									<option value="normal">⚪ Normal</option>
									<option value="important">🔵 Important</option>
									<option value="alerte">🔴 ALERTE URGENTE</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label fw-bold text-secondary">Heure forcée (Optionnel) :</label>
								<input type="time" id="live_news_custom_time" class="form-control" title="Laissez vide pour l'heure actuelle">
							</div>
						</div>

						<div class="d-flex gap-2">
							<button type="button" id="live_news_btn_send" class="btn btn-danger btn-lg flex-grow-1 fw-bold shadow-sm py-3">🚀 PUBLIER L'INFO</button>
							<button type="button" id="live_news_btn_cancel_edit" class="btn btn-outline-dark btn-lg" style="display:none;">Annuler</button>
						</div>

						<hr class="my-5">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h5 class="fw-bold mb-0 text-secondary"><i class="dashicons dashicons-backup align-middle"></i> Historique des publications</h5>
							<button type="button" id="live_news_btn_purge" class="btn btn-outline-danger btn-sm fw-bold">🚨 PURGER LE DIRECT</button>
						</div>

						<div id="live_news_history_list" class="list-group shadow-sm" style="max-height: 500px; overflow-y: auto;"></div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php do_action( 'live_news_pro_editor_sidebar', $selected_theme ); ?>
	</div>
</div>