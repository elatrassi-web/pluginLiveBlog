<div class="wrap bs-admin-wrap">
	<div class="d-flex align-items-center justify-content-between mb-4">
		<h1 class="mb-0 fw-bold"><span class="fs-1 me-2 align-middle">🔴</span> Tableau de bord : Vos Directs</h1>
		<a href="?page=live-news-create" class="btn btn-primary btn-lg fw-bold shadow-sm">➕ Créer un nouveau Direct</a>
	</div>

	<div class="card border-0 shadow-sm">
		<div class="card-body p-0">
			<table class="table table-hover align-middle mb-0 bg-white">
				<thead class="table-light">
				<tr>
					<th class="py-3 px-4">Titre de l'événement & Shortcode</th>
					<th class="py-3">Thème</th>
					<th class="py-3">Vues (Live / Total)</th>
					<th class="py-3 text-end px-4">Actions</th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $lives ) ) : ?>
					<tr><td colspan="4" class="text-center py-5 text-muted fst-italic">Aucun direct créé pour le moment. Cliquez sur "Créer un nouveau Direct" pour commencer.</td></tr>
				<?php else : foreach ( $lives as $p ) :
					$theme = get_post_meta( $p->ID, '_live_news_theme', true );
					$total_views = (int) get_post_meta( $p->ID, 'live_news_total_views', true );

					$viewers = get_transient( 'live_news_viewers_' . $p->ID );
					$live_views = 0;
					if ( is_array( $viewers ) ) {
						$now = time();
						foreach ( $viewers as $id => $time ) { if ( $now - $time <= 45 ) { $live_views++; } }
					}

					$theme_badge = 'bg-secondary';
					$theme_name  = 'Inconnu';

					// Allows PRO to filter theme names and badges
					$theme_data = apply_filters( 'live_news_theme_data', array( 'badge' => $theme_badge, 'name' => $theme_name ), $theme );

					if ( $theme == 'info' ) { $theme_data['badge'] = 'bg-info text-dark'; $theme_data['name'] = '📰 Info'; }

					$theme_badge = $theme_data['badge'];
					$theme_name  = $theme_data['name'];
					?>
					<tr class="dash-stat-row" data-post-id="<?php echo $p->ID; ?>">
						<td class="py-3 px-4">
							<strong class="fs-5 d-block text-dark"><?php echo esc_html( $p->post_title ); ?></strong>
							<code class="bg-light border px-2 py-1 rounded mt-2 d-inline-block text-danger fw-bold shadow-sm" style="user-select: all; cursor: pointer;" title="Double-cliquez pour copier">[live_news id="<?php echo $p->ID; ?>"]</code>
						</td>
						<td class="py-3"><span class="badge <?php echo esc_attr($theme_badge); ?> fs-6"><?php echo esc_html($theme_name); ?></span></td>
						<td class="py-3">
							<span class="badge bg-dark rounded-pill py-2 px-3 shadow-sm border border-secondary">
								<span class="text-danger fw-bold">
									<span id="dash-indicator-<?php echo $p->ID; ?>" class="<?php echo ( $live_views > 0 ) ? 'live-indicator me-1' : ''; ?>"></span>
									<span id="dash-live-<?php echo $p->ID; ?>"><?php echo esc_html($live_views); ?></span>
								</span>
								<span class="text-muted mx-1">/</span>
								<span class="text-info fw-bold" id="dash-total-<?php echo $p->ID; ?>">👁️ <?php echo esc_html($total_views); ?></span>
							</span>
						</td>
						<td class="py-3 text-end px-4">
							<a href="?page=live-news-dashboard&action=edit&post_id=<?php echo $p->ID; ?>" class="btn btn-sm btn-primary fw-bold shadow-sm px-3 py-2 me-1">Gérer</a>
							<button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm px-3 py-2 btn-delete-live" data-post-id="<?php echo $p->ID; ?>">🗑️ Supprimer</button>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>