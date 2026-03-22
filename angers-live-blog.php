<?php
/*
Plugin Name: Angers Live Blog Pro
Description: Système de Live Blogging Premium avec Tableau de bord dynamique, Shortcodes, Thèmes et Statistiques.
Version: 4.2 Pro
Author: Mohamed El Atrassi
*/

if (!defined('ABSPATH')) exit; // Sécurité

/* ==========================================================================
 * 1. INSTALLATION ET CRÉATION DE LA BASE DE DONNÉES / CPT
 * ========================================================================== */
register_activation_hook(__FILE__, 'angers_live_activate');

function angers_live_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        content text NOT NULL,
        importance varchar(50) DEFAULT 'normal' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $upload_dir = wp_upload_dir();
    $live_dir = $upload_dir['basedir'] . '/live-blogs';
    if (!file_exists($live_dir)) { wp_mkdir_p($live_dir); }
}

add_action('init', 'angers_live_register_cpt');
function angers_live_register_cpt() {
    register_post_type('angers_live_data', array(
        'label' => 'Live Blogs',
        'public' => false,
        'show_ui' => false,
        'supports' => array('title')
    ));
}

/* ==========================================================================
 * 2. ARCHITECTURE DES MENUS & ROUTAGE (UI BOOTSTRAP)
 * ========================================================================== */
add_action('admin_menu', 'angers_live_admin_menu');

function angers_live_admin_menu() {
    add_menu_page('Live Blog Pro', '🔴 Live Blog', 'edit_posts', 'angers-live-dashboard', 'angers_live_dashboard_router', 'dashicons-megaphone', 4);
    add_submenu_page('angers-live-dashboard', 'Tous les directs', 'Tableau de bord', 'edit_posts', 'angers-live-dashboard', 'angers_live_dashboard_router');
    add_submenu_page('angers-live-dashboard', 'Créer un Direct', 'Créer un Direct', 'edit_posts', 'angers-live-create', 'angers_live_create_page');
    add_submenu_page('angers-live-dashboard', 'Aide & Docs', 'Aide & Docs', 'edit_posts', 'angers-live-intro', 'angers_live_intro_page');
}

add_action('admin_head', 'angers_live_admin_styles');
function angers_live_admin_styles() {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'angers-live') !== false) {
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
        echo '<style>
            .bs-admin-wrap, .bs-admin-wrap * { box-sizing: border-box !important; }
            .notice.e-notice.e-notice--dismissible.e-notice--extended, div#setting-error-tgmpa, .ajdg-notification.notice, .update-nag, .notice.is-dismissible { display: none !important; }
            .bs-admin-wrap { max-width: 1400px; margin-top: 20px; font-family: system-ui, -apple-system, sans-serif; }
            .card { max-width: 100% !important; }
            .theme-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border: 2px solid transparent; }
            .theme-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; border-color: #0d6efd; }
            .theme-card.disabled { opacity: 0.7; cursor: not-allowed; border-color: transparent !important; transform: none !important; }
            @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }
            .live-indicator { width: 12px; height: 12px; background-color: #dc3545; border-radius: 50%; display: inline-block; animation: pulse-red 2s infinite; flex-shrink: 0; }
        </style>';
    }
}

function angers_live_dashboard_router() {
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    if ($action === 'edit' && isset($_GET['post_id'])) {
        angers_live_render_editor(intval($_GET['post_id']));
    } else {
        angers_live_render_dashboard();
    }
}

// ---------------------------------------------------------
// 2.1 VUE : LE TABLEAU DE BORD (Historique des Lives)
// ---------------------------------------------------------
function angers_live_render_dashboard() {
    wp_nonce_field('angers_live_nonce_action', 'angers_live_nonce');
    $lives = get_posts(array(
        'post_type' => array('angers_live_data', 'page', 'post'),
        'meta_key' => '_angers_live_theme',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    ?>
    <div class="wrap bs-admin-wrap">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="mb-0 fw-bold"><span class="fs-1 me-2 align-middle">🔴</span> Tableau de bord : Vos Directs</h1>
            <a href="?page=angers-live-create" class="btn btn-primary btn-lg fw-bold shadow-sm">➕ Créer un nouveau Direct</a>
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
                    <?php if(empty($lives)) : ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted fst-italic">Aucun direct créé pour le moment. Cliquez sur "Créer un nouveau Direct" pour commencer.</td></tr>
                    <?php else: foreach($lives as $p) :
                        $theme = get_post_meta($p->ID, '_angers_live_theme', true);
                        $total_views = (int) get_post_meta($p->ID, 'angers_live_total_views', true);

                        $viewers = get_transient('angers_live_viewers_' . $p->ID);
                        $live_views = 0;
                        if (is_array($viewers)) {
                            $now = time();
                            foreach ($viewers as $id => $time) { if ($now - $time <= 45) { $live_views++; } }
                        }

                        $theme_badge = 'bg-secondary';
                        $theme_name = 'Inconnu';
                        if($theme == 'info') { $theme_badge = 'bg-info text-dark'; $theme_name = '📰 Info'; }
                        if($theme == 'elections') { $theme_badge = 'bg-primary'; $theme_name = '🗳️ Info+Votes'; }
                        if($theme == 'votes') { $theme_badge = 'bg-warning text-dark'; $theme_name = '📊 Sondage'; }
                        ?>
                        <tr class="dash-stat-row" data-post-id="<?php echo $p->ID; ?>">
                            <td class="py-3 px-4">
                                <strong class="fs-5 d-block text-dark"><?php echo esc_html($p->post_title); ?></strong>
                                <code class="bg-light border px-2 py-1 rounded mt-2 d-inline-block text-danger fw-bold shadow-sm" style="user-select: all; cursor: pointer;" title="Double-cliquez pour copier">[angers_live_blog id="<?php echo $p->ID; ?>"]</code>
                            </td>
                            <td class="py-3"><span class="badge <?php echo $theme_badge; ?> fs-6"><?php echo $theme_name; ?></span></td>
                            <td class="py-3">
                                <span class="badge bg-dark rounded-pill py-2 px-3 shadow-sm border border-secondary">
                                    <span class="text-danger fw-bold">
                                        <span id="dash-indicator-<?php echo $p->ID; ?>" class="<?php echo ($live_views > 0) ? 'live-indicator me-1' : ''; ?>"></span>
                                        <span id="dash-live-<?php echo $p->ID; ?>"><?php echo $live_views; ?></span>
                                    </span>
                                    <span class="text-muted mx-1">/</span>
                                    <span class="text-info fw-bold" id="dash-total-<?php echo $p->ID; ?>">👁️ <?php echo $total_views; ?></span>
                                </span>
                            </td>
                            <td class="py-3 text-end px-4">
                                <a href="?page=angers-live-dashboard&action=edit&post_id=<?php echo $p->ID; ?>" class="btn btn-sm btn-primary fw-bold shadow-sm px-3 py-2 me-1">Gérer</a>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm px-3 py-2 btn-delete-live" data-post-id="<?php echo $p->ID; ?>">🗑️ Supprimer</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------
// 2.2 VUE : CRÉER UN DIRECT (Thèmes & Modal)
// ---------------------------------------------------------
function angers_live_create_page() {
    wp_nonce_field('angers_live_nonce_action', 'angers_live_nonce');
    ?>
    <div class="wrap bs-admin-wrap">
        <div class="mb-3">
            <a href="?page=angers-live-dashboard" class="text-decoration-none fw-bold text-secondary">← Retour au tableau de bord</a>
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

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm theme-card" data-theme="elections">
                    <div class="card-body text-center p-4">
                        <span style="font-size: 50px;">🗳️</span>
                        <h4 class="fw-bold mt-3">Info + Votes</h4>
                        <p class="text-muted small mb-4">Le flux d'actualité combiné avec un tableau de scores interactif pour les candidats.</p>
                        <button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm theme-card" data-theme="votes">
                    <div class="card-body text-center p-4">
                        <span style="font-size: 50px;">📊</span>
                        <h4 class="fw-bold mt-3">Sondage</h4>
                        <p class="text-muted small mb-4">Affichage uniquement du tableau des résultats pour une soirée électorale pure.</p>
                        <button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm theme-card disabled">
                    <div class="card-body text-center p-4">
                        <span style="font-size: 50px;">⚽</span>
                        <h4 class="fw-bold mt-3 text-muted">Score Sportif</h4>
                        <p class="text-muted small mb-4">Chronomètre en direct, affichage des équipes et alertes buts/cartons.</p>
                        <button class="btn btn-secondary w-100 fw-bold" disabled>⏳ Bientôt disponible</button>
                    </div>
                </div>
            </div>
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
                                <input type="text" id="create_live_title" class="form-control form-control-lg shadow-sm" placeholder="Ex: Manifestation Angers..." required>
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
    <?php
}

// ---------------------------------------------------------
// 2.3 VUE : LA SALLE DE RÉDACTION (ÉDITEUR)
// ---------------------------------------------------------
function angers_live_render_editor($post_id) {
    wp_nonce_field('angers_live_nonce_action', 'angers_live_nonce');
    $selected_theme = get_post_meta($post_id, '_angers_live_theme', true);
    $post_title = get_the_title($post_id);
    ?>
    <style>
        .bs-admin-wrap select, .bs-admin-wrap input[type="text"], .bs-admin-wrap input[type="number"], .bs-admin-wrap input[type="time"] { border-radius: 6px; border: 1px solid #dee2e6; }
        .bs-admin-wrap .wp-editor-wrap { border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; }
        .emoji-panel { display:none; position:absolute; top:100%; left:0; background:#fff; border:1px solid #dee2e6; padding:15px; width:260px; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.1); z-index:1000; }
        .dashed-btn { border: 2px dashed #0d6efd !important; background: transparent; color: #0d6efd; font-weight: bold; }
        .dashed-btn:hover { background: #f8f9fa; }
    </style>
    <div class="wrap bs-admin-wrap">
        <div class="mb-3">
            <a href="?page=angers-live-dashboard" class="text-decoration-none fw-bold text-secondary">← Retour au tableau de bord</a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <span class="fs-1 me-3">🔴</span>
                <div>
                    <h2 class="mb-0 fw-bold"><?php echo esc_html($post_title); ?></h2>
                    <div class="mt-1 d-flex align-items-center gap-2">
                        <span class="badge bg-secondary text-uppercase">Thème : <?php echo esc_html($selected_theme); ?></span>
                        <code class="bg-white border px-2 py-1 rounded text-danger fw-bold shadow-sm" style="user-select:all;">[angers_live_blog id="<?php echo $post_id; ?>"]</code>
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

        <input type="hidden" id="live_post_id" value="<?php echo $post_id; ?>">
        <span id="loader-data" style="display:none;"></span>

        <div class="row g-4">
            <?php if ($selected_theme !== 'votes') : ?>
                <div class="col-xl-7 col-lg-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="mb-0 fw-bold"><i class="dashicons dashicons-edit fs-4 align-middle text-primary"></i> Rédaction du Flash Info</h4>
                        </div>
                        <div class="card-body">
                            <input type="hidden" id="angers_live_edit_id" value="">

                            <div class="p-3 bg-light rounded border mb-4 d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-primary text-uppercase px-2 py-2"><i class="dashicons dashicons-lightning"></i> Raccourcis</span>
                                <div class="position-relative d-inline-block">
                                    <button type="button" class="btn btn-outline-dark btn-sm fw-bold" id="angers_live_emoji_btn">😀 Emojis ▾</button>
                                    <div id="angers_live_emoji_picker" class="emoji-panel">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php
                                            $emojis = ['🔴','🟢','🔵','🟡','⚠️','🚨','🔥','⏳','📊','📉','📈','🎙️','🗣️','📣','🗳️','✅','❌','🛑','🏆','🥇','👏','📌','📍','📸','🎥','📺','🗞️','💡','👉','👇'];
                                            foreach($emojis as $e) { echo '<button type="button" class="btn btn-light btn-sm live-emoji-item fs-5 p-1" style="width:36px; height:36px;">'.$e.'</button>'; }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="vr mx-1"></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="🔴 <strong>ALERTE INFO :</strong> ">🔴 Urgent</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="🎙️ <strong>Prise de parole :</strong> ">🎙️ Déclaration</button>
                                <?php if ($selected_theme === 'elections') : ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm quick-insert-btn bg-white" data-text="📊 <strong>Nouveaux résultats :</strong> ">📊 Résultats</button>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <?php wp_editor('', 'angers_live_content', array('media_buttons' => true, 'textarea_rows' => 8, 'tinymce' => array('paste_as_text' => true))); ?>
                            </div>

                            <div class="row g-3 mb-4 p-3 bg-light rounded border">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Niveau d'alerte :</label>
                                    <select id="angers_live_importance" class="form-select">
                                        <option value="normal">⚪ Normal</option>
                                        <option value="important">🔵 Important</option>
                                        <option value="alerte">🔴 ALERTE URGENTE</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Heure forcée (Optionnel) :</label>
                                    <input type="time" id="angers_live_custom_time" class="form-control" title="Laissez vide pour l'heure actuelle">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" id="angers_live_btn_send" class="btn btn-danger btn-lg flex-grow-1 fw-bold shadow-sm py-3">🚀 PUBLIER L'INFO</button>
                                <button type="button" id="angers_live_btn_cancel_edit" class="btn btn-outline-dark btn-lg" style="display:none;">Annuler</button>
                            </div>

                            <hr class="my-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0 text-secondary"><i class="dashicons dashicons-backup align-middle"></i> Historique des publications</h5>
                                <button type="button" id="angers_live_btn_purge" class="btn btn-outline-danger btn-sm fw-bold">🚨 PURGER LE DIRECT</button>
                            </div>

                            <div id="angers_live_history_list" class="list-group shadow-sm" style="max-height: 500px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($selected_theme !== 'info') : ?>
                <div class="col-xl-5 col-lg-12 <?php if($selected_theme == 'votes') echo 'mx-auto col-xl-8'; ?>">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h4 class="mb-0 fw-bold"><i class="dashicons dashicons-chart-bar fs-4 align-middle text-primary"></i> Tableau des scores</h4>
                        </div>
                        <div class="card-body bg-light">
                            <div class="mb-4 p-3 bg-white rounded border shadow-sm">
                                <label class="form-label fw-bold text-secondary">Statut du dépouillement :</label>
                                <select id="angers_live_results_status" class="form-select form-select-lg">
                                    <option value="En direct">🔵 En direct (Estimations)</option>
                                    <option value="Partiels">🟠 Résultats Partiels</option>
                                    <option value="Définitifs">🟢 Résultats Définitifs</option>
                                </select>
                            </div>

                            <div id="candidates-admin-list" class="mb-3"></div>

                            <button type="button" id="btn-add-candidate" class="btn dashed-btn w-100 py-3 mb-4 rounded">
                                <i class="dashicons dashicons-plus-alt2 align-middle"></i> Ajouter un candidat
                            </button>

                            <button type="button" id="angers_live_btn_results" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm py-3">
                                🔄 METTRE À JOUR LES VOTES
                            </button>

                            <?php if ($selected_theme === 'votes') : ?>
                                <hr class="my-4">
                                <button type="button" id="angers_live_btn_purge" class="btn btn-outline-danger w-100 fw-bold">🚨 PURGER LE TABLEAU ET LES VUES</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------
// PAGE 3 : INTRODUCTION & DOCS (Version Angers Info)
// ---------------------------------------------------------
function angers_live_intro_page() {
    ?>
    <div class="wrap bs-admin-wrap" style="max-width: 1100px; margin-top: 30px;">
        <div class="text-center mb-5">
            <span style="font-size: 60px;">🔴</span>
            <h1 class="fw-bold mt-2" style="font-size: 36px;">Espace Live - Angers Info</h1>
        </div>
        <div class="card border-0 shadow-sm p-4">
            <h4 class="fw-bold text-primary mb-3">📍 Chère rédaction : Comment intégrer un Live ?</h4>
            <p class="fs-5">L'outil a été pensé pour vous faire gagner du temps sur le terrain et vous offrir une liberté éditoriale totale :</p>
            <ul class="fs-5 text-muted" style="line-height: 1.8; list-style: none; padding-left: 0;">
                <li class="mb-2"><strong>1.</strong> Allez dans l'onglet <strong>"Créer un Direct"</strong> pour ouvrir votre événement et choisissez le Thème adapté à votre couverture (Élections, Info en continu...).</li>
                <li class="mb-2"><strong>2.</strong> Le système va générer un code unique pour cet événement, par exemple : <code class="bg-light px-2 border text-danger rounded">[angers_live_blog id="15"]</code></li>
                <li class="mb-2"><strong>3.</strong> <strong>Copiez ce code</strong> depuis votre Tableau de bord.</li>
                <li class="mb-2"><strong>4.</strong> <strong>Collez ce code absolument où vous voulez sur le site !</strong> Dans un nouvel article classique, au cœur d'une ancienne publication, ou juste après votre chapô... Le fil en direct s'affichera exactement à l'endroit du code pour les lecteurs d'Angers Info !</li>
            </ul>
        </div>
    </div>
    <?php
}

/* ==========================================================================
 * 3. TRAITEMENTS AJAX (Création, Données, Suppression & Stats)
 * ========================================================================== */

add_action('wp_ajax_angers_live_create_new', 'angers_live_create_new_ajax');
function angers_live_create_new_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $title = sanitize_text_field($_POST['title']);
    $theme = sanitize_text_field($_POST['theme']);

    $post_id = wp_insert_post(array('post_title' => $title, 'post_status' => 'publish', 'post_type' => 'angers_live_data'));

    if(!is_wp_error($post_id)) {
        update_post_meta($post_id, '_angers_live_theme', $theme);
        update_post_meta($post_id, 'angers_live_total_views', 0);
        wp_send_json_success(array('redirect' => admin_url('admin.php?page=angers-live-dashboard&action=edit&post_id=' . $post_id)));
    } else { wp_send_json_error(); }
}

add_action('wp_ajax_angers_live_delete_event', 'angers_live_delete_event_ajax');
function angers_live_delete_event_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $post_id = intval($_POST['post_id']);
    if ($post_id > 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'angers_live_updates';

        $wpdb->delete($table_name, array('post_id' => $post_id));
        delete_transient('angers_live_viewers_' . $post_id);
        delete_option('angers_live_results_' . $post_id);
        wp_delete_post($post_id, true);

        $dir = wp_upload_dir();
        @unlink($dir['basedir'] . '/live-blogs/live-' . $post_id . '.json');
        @unlink($dir['basedir'] . '/live-blogs/results-' . $post_id . '.json');

        wp_send_json_success();
    }
    wp_send_json_error();
}

add_action('wp_ajax_angers_live_get_bulk_viewers', 'angers_live_get_bulk_viewers_ajax');
function angers_live_get_bulk_viewers_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
    $results = array();
    foreach ($post_ids as $post_id) {
        if ($post_id > 0) {
            $total = (int) get_post_meta($post_id, 'angers_live_total_views', true);
            $viewers = get_transient('angers_live_viewers_' . $post_id);
            $count = 0;
            if (is_array($viewers)) {
                $now = time();
                foreach ($viewers as $id => $time) { if ($now - $time > 45) { unset($viewers[$id]); } else { $count++; } }
            }
            $results[$post_id] = array('count' => $count, 'total' => $total);
        }
    }
    wp_send_json_success($results);
}

add_action('wp_ajax_nopriv_angers_live_ping', 'angers_live_ping_ajax');
add_action('wp_ajax_angers_live_ping', 'angers_live_ping_ajax');
add_action('wp_ajax_angers_live_get_viewers', 'angers_live_get_viewers_ajax');

function angers_live_ping_ajax() {
    $post_id = intval($_POST['post_id']);
    $vid = isset($_POST['vid']) ? sanitize_text_field($_POST['vid']) : '';
    if ($post_id > 0 && !empty($vid)) {
        $transient_key = 'angers_live_viewers_' . $post_id;
        $viewers = get_transient($transient_key);
        if (!is_array($viewers)) { $viewers = array(); }

        if (!isset($viewers[$vid])) {
            $total_views = (int) get_post_meta($post_id, 'angers_live_total_views', true);
            update_post_meta($post_id, 'angers_live_total_views', $total_views + 1);
        }
        $now = time();
        $viewers[$vid] = $now;
        foreach ($viewers as $id => $timestamp) { if ($now - $timestamp > 45) { unset($viewers[$id]); } }
        set_transient($transient_key, $viewers, 60);
    }
    wp_send_json_success();
}

function angers_live_get_viewers_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $post_id = intval($_POST['post_id']);
    $count = 0; $total = 0;
    if ($post_id > 0) {
        $total = (int) get_post_meta($post_id, 'angers_live_total_views', true);
        $viewers = get_transient('angers_live_viewers_' . $post_id);
        if (is_array($viewers)) {
            $now = time();
            foreach ($viewers as $id => $time) { if ($now - $time > 45) { unset($viewers[$id]); } }
            $count = count($viewers);
        }
    }
    wp_send_json_success(array('count' => $count, 'total' => $total));
}

add_action('wp_ajax_angers_live_send_message', 'angers_live_handle_ajax');
add_action('wp_ajax_angers_live_delete_message', 'angers_live_delete_message_ajax');
add_action('wp_ajax_angers_live_get_saved_data', 'angers_live_get_saved_data_ajax');
add_action('wp_ajax_angers_live_update_results', 'angers_live_handle_results_ajax');
add_action('wp_ajax_angers_live_purge_data', 'angers_live_purge_data_ajax');

function angers_live_purge_data_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';
    $post_id = intval($_POST['post_id']);
    if ($post_id > 0) {
        $wpdb->delete($table_name, array('post_id' => $post_id));
        update_post_meta($post_id, 'angers_live_total_views', 0);
        delete_transient('angers_live_viewers_' . $post_id);

        $empty_results = array('status' => 'En direct', 'candidates' => array());
        update_option('angers_live_results_' . $post_id, $empty_results);

        angers_live_generate_json($post_id);
        $dir = wp_upload_dir();
        file_put_contents($dir['basedir'] . '/live-blogs/results-' . $post_id . '.json', wp_json_encode($empty_results));
        wp_send_json_success();
    }
    wp_send_json_error();
}

function angers_live_handle_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';

    $post_id = intval($_POST['post_id']);
    $edit_id = intval($_POST['edit_id']);
    $content = wp_kses_post(stripslashes($_POST['content']));
    $importance = sanitize_text_field($_POST['importance']);
    $custom_time = isset($_POST['custom_time']) ? sanitize_text_field($_POST['custom_time']) : '';

    if (empty($post_id) || empty($content)) { wp_send_json_error('Données manquantes.'); }
    $gmt_plus_one_timestamp = time() + 3600;

    if ($edit_id > 0) {
        $update_data = array('content' => $content, 'importance' => $importance);
        if (!empty($custom_time) && preg_match('/^\d{2}:\d{2}$/', $custom_time)) {
            $existing_time = $wpdb->get_var($wpdb->prepare("SELECT time FROM $table_name WHERE id = %d", $edit_id));
            $date_part = $existing_time ? substr($existing_time, 0, 10) : gmdate('Y-m-d', $gmt_plus_one_timestamp);
            $update_data['time'] = $date_part . ' ' . $custom_time . ':00';
        }
        $wpdb->update($table_name, $update_data, array('id' => $edit_id));
    } else {
        $time_to_save = gmdate('Y-m-d H:i:s', $gmt_plus_one_timestamp);
        if (!empty($custom_time) && preg_match('/^\d{2}:\d{2}$/', $custom_time)) {
            $time_to_save = gmdate('Y-m-d', $gmt_plus_one_timestamp) . ' ' . $custom_time . ':00';
        }
        $wpdb->insert($table_name, array('post_id' => $post_id, 'time' => $time_to_save, 'content' => $content, 'importance' => $importance));
    }
    angers_live_generate_json($post_id);
    wp_send_json_success();
}

function angers_live_delete_message_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';
    $msg_id = intval($_POST['msg_id']);
    $post_id = intval($_POST['post_id']);

    $wpdb->delete($table_name, array('id' => $msg_id));
    angers_live_generate_json($post_id);
    wp_send_json_success();
}

function angers_live_get_saved_data_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $post_id = intval($_POST['post_id']);
    if (empty($post_id)) { wp_send_json_error(); }

    $saved_data = get_option('angers_live_results_' . $post_id, array('status' => 'En direct', 'candidates' => array()));
    if (!isset($saved_data['status'])) { $saved_data = array('status' => 'En direct', 'candidates' => $saved_data); }

    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';
    $history = $wpdb->get_results($wpdb->prepare("SELECT id, time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id));

    wp_send_json_success(array('results_data' => $saved_data, 'history' => $history));
}

function angers_live_handle_results_ajax() {
    check_ajax_referer('angers_live_nonce_action', 'security');
    $post_id = intval($_POST['post_id']);

    $status = isset($_POST['results_status']) ? sanitize_text_field($_POST['results_status']) : 'En direct';
    $candidates = isset($_POST['candidates']) ? $_POST['candidates'] : array();

    $data_to_save = array('status' => $status, 'candidates' => $candidates);
    update_option('angers_live_results_' . $post_id, $data_to_save);

    $dir = wp_upload_dir();
    file_put_contents($dir['basedir'] . '/live-blogs/results-' . $post_id . '.json', wp_json_encode($data_to_save));
    wp_send_json_success();
}

function angers_live_generate_json($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'angers_live_updates';
    $messages = $wpdb->get_results($wpdb->prepare("SELECT id, time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id));

    foreach ($messages as $msg) {
        $content = $msg->content;
        $content = preg_replace('/<a[^>]+href="(https?:\/\/(www\.)?(twitter|x)\.com[^"]+)"[^>]*>.*?<\/a>/i', '$1', $content);
        $content = str_replace('https://x.com/', 'https://twitter.com/', $content);
        $content = preg_replace('/(?:<p>)?(https?:\/\/(www\.)?twitter\.com\/[a-zA-Z0-9_]+\/status\/[0-9]+(?:\?[^\s<]+)?)(?:<\/p>)?/i', '<blockquote class="twitter-tweet"><div class="tweet-loading">⏳ Chargement du tweet en cours...</div><a href="$1"></a></blockquote>', $content);
        $content = wpautop($content);
        $msg->content = $content;
    }

    $dir = wp_upload_dir();
    $json_data = wp_json_encode($messages);
    if (!$json_data) { $json_data = '[]'; }
    file_put_contents($dir['basedir'] . '/live-blogs/live-' . $post_id . '.json', $json_data);
}

/* ==========================================================================
 * 4. JAVASCRIPT ADMINISTRATION (UI BOOTSTRAP)
 * ========================================================================== */
add_action('admin_footer', 'angers_live_admin_script');

function angers_live_admin_script() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'angers-live') === false) return;
    ?>
    <script>
        jQuery(document).ready(function($) {
            var nonce = $('#angers_live_nonce').val();

            if ($('.dash-stat-row').length > 0) {
                function updateDashboardViews() {
                    var postIds = [];
                    $('.dash-stat-row').each(function() { postIds.push($(this).data('post-id')); });

                    if (postIds.length > 0) {
                        $.post(ajaxurl, { action: 'angers_live_get_bulk_viewers', security: nonce, post_ids: postIds }, function(res) {
                            if(res.success) {
                                $.each(res.data, function(pid, stats) {
                                    $('#dash-live-' + pid).text(stats.count);
                                    $('#dash-total-' + pid).html('👁️ ' + stats.total);
                                    if(stats.count > 0) {
                                        $('#dash-indicator-' + pid).addClass('live-indicator me-1');
                                    } else {
                                        $('#dash-indicator-' + pid).removeClass('live-indicator me-1');
                                    }
                                });
                            }
                        });
                    }
                }
                // ✅ PASSÉ À 60 SECONDES (Soulage ton navigateur quand tu es sur le tableau de bord)
                setInterval(updateDashboardViews, 60000);
            }

            $('.btn-delete-live').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                var tr = $(this).closest('tr');

                if (confirm("🚨 Voulez-vous vraiment SUPPRIMER définitivement cet événement ? L'historique et les fichiers seront détruits. Cette action est irréversible !")) {
                    var btn = $(this);
                    btn.prop('disabled', true).html('⏳...');

                    $.post(ajaxurl, { action: 'angers_live_delete_event', security: nonce, post_id: postId }, function(res) {
                        if (res.success) {
                            tr.fadeOut(400, function() { $(this).remove(); });
                        } else {
                            alert("❌ Erreur lors de la suppression.");
                            btn.prop('disabled', false).html('🗑️ Supprimer');
                        }
                    });
                }
            });

            $('.theme-card:not(.disabled)').on('click', function(e) {
                e.preventDefault();
                var theme = $(this).data('theme');
                $('#create_live_theme').val(theme);
                var myModal = new bootstrap.Modal(document.getElementById('createLiveModal'));
                myModal.show();
            });

            $('#form-create-live').on('submit', function(e) {
                e.preventDefault();
                var btn = $('#btn-submit-create');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Création...');
                $.post(ajaxurl, {
                    action: 'angers_live_create_new',
                    security: nonce,
                    title: $('#create_live_title').val(),
                    theme: $('#create_live_theme').val()
                }, function(res) {
                    if(res.success) { window.location.href = res.data.redirect; }
                    else { alert('Erreur lors de la création.'); btn.prop('disabled', false).text('Créer l\'Espace Live 🚀'); }
                });
            });

            var livePostId = $('#live_post_id').val();

            if (livePostId) {
                function updateViewerCount() {
                    $.post(ajaxurl, { action: 'angers_live_get_viewers', security: nonce, post_id: livePostId }, function(res) {
                        if(res.success) {
                            $('#live-viewer-count').text(res.data.count);
                            $('#total-viewer-count').text(res.data.total);
                        }
                    });
                }
                // ✅ PASSÉ À 60 SECONDES (Soulage le serveur quand tu rédiges un article)
                setInterval(updateViewerCount, 60000);

                $('#angers_live_emoji_btn').on('click', function(e) {
                    e.preventDefault();
                    $('#angers_live_emoji_picker').toggle();
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#angers_live_emoji_btn, #angers_live_emoji_picker').length) {
                        $('#angers_live_emoji_picker').hide();
                    }
                });

                $('.live-emoji-item').on('click', function(e) {
                    e.preventDefault();
                    var emoji = $(this).text();
                    if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content') && !tinymce.get('angers_live_content').isHidden()) {
                        tinymce.get('angers_live_content').execCommand('mceInsertContent', false, emoji);
                    } else {
                        var textarea = $('#angers_live_content')[0];
                        var startPos = textarea.selectionStart;
                        var endPos = textarea.selectionEnd;
                        textarea.value = textarea.value.substring(0, startPos) + emoji + textarea.value.substring(endPos, textarea.value.length);
                        textarea.focus();
                    }
                    $('#angers_live_emoji_picker').hide();
                });

                $('.quick-insert-btn').on('click', function(e) {
                    e.preventDefault();
                    var textToInsert = $(this).data('text');
                    if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content') && !tinymce.get('angers_live_content').isHidden()) {
                        tinymce.get('angers_live_content').execCommand('mceInsertContent', false, textToInsert);
                    } else {
                        var textarea = $('#angers_live_content')[0];
                        var startPos = textarea.selectionStart;
                        var endPos = textarea.selectionEnd;
                        textarea.value = textarea.value.substring(0, startPos) + textToInsert + textarea.value.substring(endPos, textarea.value.length);
                        textarea.focus();
                    }
                });

                function addCandidateBlock(name = '', votes = '', color = '#1e73be', photo = '') {
                    var html = `
                    <div class="candidate-block card border-0 shadow-sm mb-3">
                        <div class="card-body p-3 position-relative">
                            <a href="#" class="remove-candidate position-absolute top-0 end-0 mt-3 me-3 text-danger text-decoration-none fs-5" title="Supprimer">✖️</a>
                            <h6 class="card-subtitle mb-3 text-muted fw-bold">Profil Candidat</h6>
                            <div class="mb-2">
                                <input type="text" class="cand-name form-control fw-bold" placeholder="Nom du candidat" value="`+name+`">
                            </div>
                            <div class="input-group mb-2 shadow-sm">
                                <span class="input-group-text bg-white">🗳️</span>
                                <input type="number" class="cand-votes form-control" placeholder="Voix" value="`+votes+`">
                                <input type="color" class="cand-color form-control form-control-color" value="`+color+`" title="Couleur de la barre">
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">📸 URL Photo</span>
                                <input type="text" class="cand-photo form-control" placeholder="https://..." value="`+photo+`">
                            </div>
                        </div>
                    </div>`;
                    $('#candidates-admin-list').append(html);
                }

                $('#btn-add-candidate').on('click', function(e){ e.preventDefault(); addCandidateBlock(); });
                $(document).on('click', '.remove-candidate', function(e){ e.preventDefault(); $(this).closest('.candidate-block').remove(); });

                function loadSavedData() {
                    $('#loader-data').show();
                    $('#candidates-admin-list').empty();

                    $.post(ajaxurl, { action: 'angers_live_get_saved_data', security: nonce, post_id: livePostId }, function(res) {
                        if(res.success) {
                            if(res.data.results_data) {
                                $('#angers_live_results_status').val(res.data.results_data.status || 'En direct');
                                var cands = res.data.results_data.candidates || [];
                                if(cands.length > 0) {
                                    $.each(cands, function(index, cand) { addCandidateBlock(cand.name, cand.votes, cand.color, cand.photo); });
                                } else {
                                    addCandidateBlock(); addCandidateBlock();
                                }
                            }

                            var historyHtml = '';
                            if(res.data.history && res.data.history.length > 0) {
                                $.each(res.data.history, function(index, msg) {
                                    var badgeClass = 'bg-secondary';
                                    if(msg.importance === 'alerte') badgeClass = 'bg-danger';
                                    if(msg.importance === 'important') badgeClass = 'bg-primary';

                                    historyHtml += '<div class="list-group-item list-group-item-action p-3 mb-2 border rounded shadow-sm">';
                                    historyHtml += '<div class="d-flex w-100 justify-content-between align-items-center mb-2">';
                                    historyHtml += '<h6 class="mb-0 fw-bold">' + msg.time + ' <span class="badge ' + badgeClass + ' ms-2 text-uppercase">' + msg.importance + '</span></h6>';
                                    historyHtml += '<div><a href="#" class="edit-msg btn btn-sm btn-outline-primary py-0 me-2" data-id="'+msg.id+'" data-time="'+msg.time+'" data-content="'+encodeURIComponent(msg.content)+'" data-imp="'+msg.importance+'">✏️ Éditer</a>';
                                    historyHtml += '<a href="#" class="del-msg btn btn-sm btn-outline-danger py-0" data-id="'+msg.id+'">🗑️</a></div>';
                                    historyHtml += '</div><p class="mb-0 text-dark" style="font-size:14px; line-height:1.5;">' + msg.content + '</p></div>';
                                });
                            } else {
                                historyHtml = '<div class="list-group-item text-muted fst-italic py-4 text-center">Aucun message publié.</div>';
                            }
                            $('#angers_live_history_list').html(historyHtml);
                        }
                        $('#loader-data').hide();
                        updateViewerCount();
                    });
                }
                loadSavedData();

                $('#angers_live_btn_purge').on('click', function(e) {
                    e.preventDefault();
                    var confirm1 = confirm("🚨 ATTENTION : Vous êtes sur le point d'effacer TOUT l'historique des messages, de remettre les candidats à zéro, et de réinitialiser le compteur de vues totales.\n\nVoulez-vous vraiment continuer ?");
                    if (confirm1) {
                        var confirm2 = confirm("⚠️ DERNIER AVERTISSEMENT : Cette action est irréversible. Confirmez-vous la purge du direct ?");
                        if (confirm2) {
                            var btn = $(this);
                            var originalHtml = btn.html();
                            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Nettoyage...').prop('disabled', true);

                            $.post(ajaxurl, { action: 'angers_live_purge_data', security: nonce, post_id: livePostId }, function(res) {
                                if(res.success) {
                                    alert("✅ Le direct a été intégralement purgé. Les compteurs sont à zéro.");
                                    loadSavedData();
                                } else {
                                    alert("❌ Une erreur est survenue lors de la purge.");
                                }
                                btn.html(originalHtml).prop('disabled', false);
                            });
                        }
                    }
                });

                $('#angers_live_btn_send').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var editId = $('#angers_live_edit_id').val();
                    var customTime = $('#angers_live_custom_time').val();

                    if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content')) { tinymce.get('angers_live_content').save(); }
                    var content = $('#angers_live_content').val();

                    if(!content) { alert('Tapez un message !'); return; }

                    var originalHtml = btn.html();
                    btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...').prop('disabled', true);

                    $.post(ajaxurl, { action: 'angers_live_send_message', security: nonce, post_id: livePostId, edit_id: editId, content: content, importance: $('#angers_live_importance').val(), custom_time: customTime }, function(res) {
                        if(res.success) {
                            $('#angers_live_content').val('');
                            if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content')) { tinymce.get('angers_live_content').setContent(''); }
                            $('#angers_live_edit_id').val('');
                            $('#angers_live_custom_time').val('');
                            $('#angers_live_btn_cancel_edit').hide();

                            btn.removeClass('btn-danger btn-primary').addClass('btn-success').html('✅ PUBLIÉ !');
                            setTimeout(function(){ btn.removeClass('btn-success').addClass('btn-danger').html('🚀 PUBLIER L\'INFO'); }, 2000);
                            loadSavedData();
                        }
                        btn.prop('disabled', false);
                    });
                });

                $(document).on('click', '.edit-msg', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    var content = decodeURIComponent($(this).data('content'));
                    var imp = $(this).data('imp');
                    var timeRaw = $(this).data('time');
                    var timeOnly = timeRaw ? timeRaw.split(' ')[1].substring(0, 5) : '';

                    $('#angers_live_content').val(content);
                    if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content')) { tinymce.get('angers_live_content').setContent(content); }
                    $('#angers_live_importance').val(imp);
                    $('#angers_live_edit_id').val(id);
                    $('#angers_live_custom_time').val(timeOnly);

                    $('#angers_live_btn_send').removeClass('btn-danger').addClass('btn-primary').html('💾 SAUVEGARDER');
                    $('#angers_live_btn_cancel_edit').show();
                    $('html, body').animate({ scrollTop: 0 }, 'fast');
                });

                $('#angers_live_btn_cancel_edit').on('click', function(e) {
                    e.preventDefault();
                    $('#angers_live_content').val('');
                    if (typeof tinymce !== 'undefined' && tinymce.get('angers_live_content')) { tinymce.get('angers_live_content').setContent(''); }
                    $('#angers_live_edit_id').val('');
                    $('#angers_live_custom_time').val('');
                    $('#angers_live_btn_send').removeClass('btn-primary').addClass('btn-danger').html('🚀 PUBLIER L\'INFO');
                    $(this).hide();
                });

                $(document).on('click', '.del-msg', function(e) {
                    e.preventDefault();
                    if(!confirm('🚨 Supprimer définitivement ce message ?')) return;
                    var msgId = $(this).data('id');
                    $.post(ajaxurl, { action: 'angers_live_delete_message', security: nonce, post_id: livePostId, msg_id: msgId }, function(res) {
                        if(res.success) loadSavedData();
                    });
                });

                $('#angers_live_btn_results').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);

                    var candidates = [];
                    $('.candidate-block').each(function() {
                        var name = $(this).find('.cand-name').val();
                        if(name.trim() !== '') {
                            candidates.push({
                                name: name,
                                votes: parseInt($(this).find('.cand-votes').val()) || 0,
                                color: $(this).find('.cand-color').val(),
                                photo: $(this).find('.cand-photo').val()
                            });
                        }
                    });

                    var status = $('#angers_live_results_status').val();

                    btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculs...').prop('disabled', true);
                    $.post(ajaxurl, { action: 'angers_live_update_results', security: nonce, post_id: livePostId, candidates: candidates, results_status: status }, function(res) {
                        if(res.success) {
                            btn.removeClass('btn-primary').addClass('btn-success').html('✅ VOTES MIS À JOUR !');
                            setTimeout(function(){ btn.removeClass('btn-success').addClass('btn-primary').html('🔄 METTRE À JOUR LES VOTES'); }, 2000);
                        }
                        btn.prop('disabled', false);
                    });
                });
            }
        });
    </script>
    <?php
}

/* ==========================================================================
 * 5. FRONT-END : SHORTCODE CÔTÉ VISITEURS
 * ========================================================================== */
add_shortcode('angers_live_blog', 'angers_live_shortcode');

function angers_live_shortcode($atts) {
    global $post, $wpdb;

    $atts = shortcode_atts(array('id' => 0), $atts, 'angers_live_blog');
    $post_id = intval($atts['id']);
    if ($post_id === 0) { $post_id = isset($post->ID) ? $post->ID : 0; }

    $upload_dir = wp_upload_dir();
    $feed_url = $upload_dir['baseurl'] . '/live-blogs/live-' . $post_id . '.json';
    $results_url = $upload_dir['baseurl'] . '/live-blogs/results-' . $post_id . '.json';

    $is_amp = false;
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) { $is_amp = true; }
    if (function_exists('amp_is_request') && amp_is_request()) { $is_amp = true; }

    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <?php if (!$is_amp) : ?>
        <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
    <?php endif; ?>

    <div class="angers-live-wrapper">

        <?php
        $saved_results = get_option('angers_live_results_' . $post_id, array('status' => 'En direct', 'candidates' => array()));
        $amp_cands = isset($saved_results['candidates']) ? $saved_results['candidates'] : (is_array($saved_results) ? $saved_results : array());
        $amp_status = isset($saved_results['status']) ? $saved_results['status'] : 'En direct';
        $show_results_amp = ($is_amp && !empty($amp_cands));
        ?>

        <div id="angers-live-results-board" data-results-url="<?php echo esc_url($results_url); ?>" style="<?php echo $show_results_amp ? 'display:block;' : 'display:none;'; ?>">
            <div class="results-header" id="angers-results-title">📊 RÉSULTATS DES VOTES <?php echo $show_results_amp ? '(' . esc_html($amp_status) . ')' : ''; ?></div>
            <div id="candidates-render-area" class="results-body">
                <?php
                if ($show_results_amp) {
                    $total_votes = 0;
                    foreach($amp_cands as $c) { $total_votes += intval($c['votes']); }
                    usort($amp_cands, function($a, $b) { return $b['votes'] - $a['votes']; });

                    foreach($amp_cands as $cand) {
                        $percent = $total_votes > 0 ? number_format(($cand['votes'] / $total_votes) * 100, 2) : 0;
                        $photoHtml = !empty($cand['photo']) ? '<img src="'.esc_url($cand['photo']).'" class="candidate-photo">' : '<div class="candidate-photo"></div>';
                        echo '<div class="candidate-row">' . $photoHtml . '<div class="candidate-data">';
                        echo '<div class="candidate-stats"><span>' . esc_html($cand['name']) . ' <span class="candidate-votes-text">(' . esc_html($cand['votes']) . ' voix)</span></span><span>' . $percent . '%</span></div>';
                        echo '<div class="progress-bar-bg"><div class="progress-bar-fill" style="width:'.$percent.'%; background-color:'.esc_attr($cand['color']).';"></div></div>';
                        echo '</div></div>';
                    }
                }
                ?>
            </div>
        </div>

        <div id="angers-live-feed-wrapper" class="angers-live-container">
            <div class="angers-live-header">
                LE FIL INFO EN DIRECT <?php if(!$is_amp) echo '<span class="live-blinking-dot"></span>'; ?>

                <?php if(!$is_amp) : ?>
                    <div style="margin-left: auto;">
                        <span id="live-sound-toggle" class="sound-toggle-btn" title="Activer les alertes sonores"><i class="fa fa-bell-slash-o"></i></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if(!$is_amp) : ?>
                <div class="live-filter-bar">
                    <span class="filter-label"><i class="fa fa-bolt" style="color:#ffb300; margin-right:5px;"></i> Temps forts uniquement</span>
                    <label class="live-toggle-switch">
                        <input type="checkbox" id="live-filter-toggle">
                        <span class="live-slider"></span>
                    </label>
                </div>

                <div id="angers-live-floating-btn" class="floating-new-msg-btn">
                    <span class="floating-btn-dot"></span> ⬆ Nouveaux messages
                </div>
            <?php endif; ?>

            <div id="angers-live-feed" data-json-url="<?php echo esc_url($feed_url); ?>">
                <?php if ($is_amp) :
                    $table_name = $wpdb->prefix . 'angers_live_updates';
                    $messages = $wpdb->get_results($wpdb->prepare("SELECT time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id));

                    if (empty($messages)) {
                        echo '<p style="padding:20px; text-align:center; color:#999; font-style:italic;">Le direct n\'a pas encore commencé...</p>';
                    } else {
                        foreach ($messages as $msg) {
                            $time_parts = explode(' ', $msg->time);
                            $hour_parts = explode(':', $time_parts[1]);
                            $time_str = $hour_parts[0] . 'h' . $hour_parts[1];

                            $content = preg_replace('/<a[^>]+href="(https?:\/\/(www\.)?(twitter|x)\.com[^"]+)"[^>]*>.*?<\/a>/i', '$1', $msg->content);
                            $content = preg_replace('/(?:<p>)?(https?:\/\/(www\.)?(twitter|x)\.com\/[a-zA-Z0-9_]+\/status\/[0-9]+(?:\?[^\s<]+)?)(?:<\/p>)?/i', '<a href="$1" target="_blank" style="display:block; padding:12px; background:#f4f8fb; border:1px solid #b1d2eb; border-radius:8px; color:#1da1f2; text-align:center; text-decoration:none; font-weight:bold;">👉 Voir le Tweet sur X</a>', $content);
                            $content = wpautop($content);

                            echo '<div class="live-post live-importance-' . esc_attr($msg->importance) . '">';
                            echo '<div class="timeline-circle"></div>';
                            echo '<div class="live-time-wrapper"><span class="live-time">' . $time_str . '</span></div>';
                            echo '<div class="live-content">' . $content . '</div>';
                            echo '</div>';
                        }
                    }
                else : ?>
                    <div class="skeleton-post"><div class="skeleton-circle"></div><div class="skeleton-time"></div><div class="skeleton-line"></div></div>
                    <div class="skeleton-post"><div class="skeleton-circle"></div><div class="skeleton-time"></div><div class="skeleton-line"></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .angers-live-wrapper * { box-sizing: border-box; }
        .angers-live-wrapper a { text-decoration: none !important; border: none !important; box-shadow: none !important; background: transparent !important; }
        .angers-live-wrapper { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 850px; margin: 0 auto; }
        #angers-live-results-board { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; border: 1px solid #eaeaea; transition: background-color 0.5s;}
        .results-header { background: #004a99; color: #fff; padding: 15px 20px; font-weight: bold; font-size: 18px; text-transform: uppercase; }
        .results-body { padding: 20px; }
        .candidate-row { display: flex; align-items: center; margin-bottom: 15px; }
        .candidate-row:last-child { margin-bottom: 0; }
        .candidate-photo { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 15px; background: #eee; border: 2px solid #ddd;}
        .candidate-data { flex-grow: 1; }
        .candidate-stats { display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 5px; font-size: 15px; color: #333;}
        .candidate-votes-text { font-size: 12px; color: #777; font-weight: normal; }
        .progress-bar-bg { background: #e9ecef; height: 12px; border-radius: 6px; overflow: hidden; width: 100%; }
        .progress-bar-fill { height: 100%; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 6px;}
        .angers-live-container { position: relative; background: #fff; border-radius: 8px; border: 1px solid #eaeaea; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; transition: background-color 0.5s; overflow: hidden; }
        .angers-live-header { color: #111; padding: 15px 20px; font-weight: 900; font-size: 20px; text-transform: uppercase; display: flex; align-items: center; border-bottom: 1px solid #eee; }
        .live-blinking-dot { width: 12px; height: 12px; background: #E2001A; border-radius: 50%; margin-left: 10px; animation: blink 1.5s infinite ease-in-out; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.2; } 100% { opacity: 1; } }
        .sound-toggle-btn { cursor: pointer; color: #888; font-size: 22px; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; }
        .sound-toggle-btn:hover { background: #eaeaea; color: #333; }
        .sound-toggle-btn.active { color: #E2001A; background: #ffebee; }
        .sound-toggle-btn.active:hover { background: #ffcdd2; }
        .live-filter-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background-color: #fcfcfc; border-bottom: 1px solid #eee; }
        .filter-label { font-size: 14px; font-weight: 700; color: #555; display: flex; align-items: center; }
        .live-toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin: 0; }
        .live-toggle-switch input { opacity: 0; width: 0; height: 0; }
        .live-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .live-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .live-toggle-switch input:checked + .live-slider { background-color: #0056b3; }
        .live-toggle-switch input:checked + .live-slider:before { transform: translateX(20px); }
        #angers-live-feed.show-only-important .live-importance-normal { display: none !important; }
        .floating-new-msg-btn { display: none; position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background-color: #ffffff; color: #111; padding: 12px 24px; border-radius: 50px; font-weight: 800; font-size: 14px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); border: 1px solid #eaeaea; cursor: pointer; z-index: 9999; align-items: center; gap: 10px; animation: slideDownBtn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); transition: all 0.2s ease; }
        .floating-new-msg-btn:hover { transform: translateX(-50%) translateY(-3px); box-shadow: 0 12px 40px rgba(0,0,0,0.2); color: #E2001A; }
        .floating-btn-dot { width: 10px; height: 10px; background-color: #E2001A; border-radius: 50%; display: inline-block; animation: blink 1.5s infinite ease-in-out; }
        @keyframes slideDownBtn { from { top: -50px; opacity: 0; } to { top: 80px; opacity: 1; } }
        #angers-live-feed { position: relative; padding: 30px 20px 20px 80px; isolation: isolate; }
        #angers-live-feed::before { content: ''; position: absolute; top: 30px; bottom: 20px; left: 45px; width: 4px; background: #dcdcdc; z-index: -1; }
        .live-post { position: relative; margin-bottom: 40px; min-height: 60px; animation: slideDown 0.5s ease-out forwards; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
        .timeline-circle { position: absolute; left: -50px; top: -2px; width: 30px; height: 30px; border-radius: 50%; border: 6px solid white; background-color: #a00000; z-index: 10; background-clip: padding-box; }
        .live-post:target { scroll-margin-top: 120px; animation: highlightSharedPost 6s ease-out forwards; }
        @keyframes highlightSharedPost { 0% { background-color: #fff9c4; border-radius: 8px; box-shadow: 0 0 15px rgba(226, 0, 26, 0.3); padding-right: 15px; } 100% { background-color: transparent; border-radius: 0; box-shadow: none; padding-right: 0; } }
        .skeleton-post { position: relative; margin-bottom: 40px; min-height: 60px; }
        .skeleton-circle { position: absolute; left: -50px; top: -2px; width: 30px; height: 30px; border-radius: 50%; border: 6px solid white; z-index: 10; background-clip: padding-box; background-color: #eaeaea; animation: skeleton-bg-pulse 1.5s infinite ease-in-out; }
        .skeleton-time { width: 100px; height: 18px; background-color: #eaeaea; border-radius: 4px; margin-bottom: 12px; animation: skeleton-bg-pulse 1.5s infinite ease-in-out; }
        .skeleton-line { width: 100%; height: 14px; background-color: #eaeaea; border-radius: 4px; margin-bottom: 8px; animation: skeleton-bg-pulse 1.5s infinite ease-in-out; }
        @keyframes skeleton-bg-pulse { 0% { background-color: #eaeaea; } 50% { background-color: #f8f8f8; } 100% { background-color: #eaeaea; } }
        .live-time-wrapper { display: flex; align-items: baseline; margin-bottom: 5px; }
        .live-time { font-size: 18px; font-weight: 800; margin-right: 8px; }
        .live-relative-time { font-size: 13px; color: #888; font-style: italic; }
        .live-content { font-size: 16px; line-height: 1.5; color: #222; }
        .live-content img { display:block; max-width: 100%; border-radius: 8px; margin-top: 15px; }
        .live-content iframe, .live-content .twitter-tweet { max-width: 100% !important; margin: 15px auto !important; }
        .tweet-loading { padding: 15px; text-align: center; background: #f4f8fb; border: 1px dashed #b1d2eb; border-radius: 8px; color: #1da1f2; font-style: italic; font-size: 14px; margin: 15px 0; }
        .live-importance-alerte .timeline-circle { background-color: #b30000; }
        .live-importance-alerte .live-time { color: #b30000; }
        .live-importance-important .timeline-circle { background-color: #0056b3; }
        .live-importance-important .live-time { color: #0056b3; }
        .live-importance-normal .timeline-circle { background-color: #777; }
        .live-importance-normal .live-time { color: #777; }
        @keyframes flashUpdate { 0% { background-color: #fffac2; } 100% { background-color: #ffffff; } }
        .highlight-update { animation: flashUpdate 2s ease-out; }
        .live-share-buttons { margin-top: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;}
        .live-share-buttons span { font-size: 12px; color: #888; text-transform: uppercase; font-weight: bold; margin-right: 5px; }
        .live-share-btn i { font-size: 26px !important; color: #666 !important; transition: transform 0.2s, color 0.2s !important; border: none !important; background: none !important; padding: 0 !important; margin: 0 !important; }
        .live-share-btn:hover i { transform: scale(1.15); }
        .btn-wa:hover i { color: #25D366 !important; }
        .btn-fb:hover i { color: #1877F2 !important; }
        .btn-tw:hover i { color: #000000 !important; }
        .btn-tg:hover i { color: #0088cc !important; }
        .btn-in:hover i { color: #0A66C2 !important; }
        .btn-em:hover i { color: #EA4335 !important; }
    </style>

    <?php if (!$is_amp) : ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var feedContainer = document.getElementById('angers-live-feed');
                var feedWrapper = document.getElementById('angers-live-feed-wrapper');
                var resultsBoard = document.getElementById('angers-live-results-board');
                var candidatesArea = document.getElementById('candidates-render-area');
                var floatingBtn = document.getElementById('angers-live-floating-btn');
                var filterToggle = document.getElementById('live-filter-toggle');
                var soundToggleBtn = document.getElementById('live-sound-toggle');

                var isSoundEnabled = localStorage.getItem('angers_live_sound_pref') === 'true';
                var notificationSound = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                notificationSound.volume = 0.5;

                var ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
                var livePostId = "<?php echo $post_id; ?>";

                var viewerId = localStorage.getItem('angers_live_vid');
                if(!viewerId) {
                    viewerId = Math.random().toString(36).substr(2, 9);
                    localStorage.setItem('angers_live_vid', viewerId);
                }

                function sendLivePing() {
                    if(livePostId > 0) {
                        var formData = new FormData();
                        formData.append('action', 'angers_live_ping');
                        formData.append('post_id', livePostId);
                        formData.append('vid', viewerId);
                        fetch(ajaxUrl, { method: 'POST', body: formData }).catch(e => {});
                    }
                }
                sendLivePing();
                setInterval(sendLivePing, 60000);

                if(soundToggleBtn) {
                    if(isSoundEnabled) {
                        soundToggleBtn.innerHTML = '<i class="fa fa-bell"></i>';
                        soundToggleBtn.classList.add('active');
                    }

                    soundToggleBtn.addEventListener('click', function() {
                        isSoundEnabled = !isSoundEnabled;
                        if(isSoundEnabled) {
                            this.innerHTML = '<i class="fa fa-bell"></i>';
                            this.classList.add('active');
                            localStorage.setItem('angers_live_sound_pref', 'true');
                            notificationSound.play().catch(function(e) { console.log("Son bloqué par le navigateur."); });
                        } else {
                            this.innerHTML = '<i class="fa fa-bell-slash-o"></i>';
                            this.classList.remove('active');
                            localStorage.setItem('angers_live_sound_pref', 'false');
                        }
                    });
                }

                function playAlertSound() {
                    if (!isSoundEnabled) return;
                    var snd = notificationSound.cloneNode();
                    snd.volume = 0.5;
                    var playPromise = snd.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(function(e) {});
                    }
                }

                var feedUrl = feedContainer ? feedContainer.getAttribute('data-json-url') : null;
                var resultsUrl = resultsBoard ? resultsBoard.getAttribute('data-results-url') : null;
                var baseUrl = window.location.href.split('#')[0];
                var lastFeedStr = null;
                var lastResultsStr = null;
                var isFirstLoad = true;
                var pendingHtml = '';

                function flashElement(element) {
                    if(!element) return;
                    element.classList.remove('highlight-update');
                    void element.offsetWidth;
                    element.classList.add('highlight-update');
                }

                function getRelativeTime(mysqlTime) {
                    var tParts = mysqlTime.split(/[- :]/);
                    var postDate = new Date(tParts[0], tParts[1]-1, tParts[2], tParts[3], tParts[4], tParts[5]);
                    var seconds = Math.floor((new Date() - postDate) / 1000);
                    if (seconds < 60) return "À l'instant";
                    var minutes = Math.floor(seconds / 60);
                    if (minutes < 60) return "Il y a " + minutes + " min";
                    var hours = Math.floor(minutes / 60);
                    if (hours < 24) return "Il y a " + hours + " h";
                    return "";
                }

                if(filterToggle && feedContainer) {
                    filterToggle.addEventListener('change', function() {
                        if(this.checked) { feedContainer.classList.add('show-only-important'); }
                        else { feedContainer.classList.remove('show-only-important'); }
                    });
                }

                if(floatingBtn && feedContainer && feedWrapper) {
                    floatingBtn.addEventListener('click', function() {
                        feedContainer.innerHTML = pendingHtml;
                        floatingBtn.style.display = 'none';
                        pendingHtml = '';
                        if (window.twttr && window.twttr.widgets) { window.twttr.widgets.load(feedContainer); }
                        window.scrollTo({ top: feedWrapper.offsetTop - 50, behavior: 'smooth' });
                        flashElement(feedWrapper);
                    });
                }

                function updateAll() {
                    var cacheBuster = '?t=' + new Date().getTime();

                    if(resultsUrl && resultsBoard && candidatesArea) {
                        fetch(resultsUrl + cacheBuster)
                            .then(res => res.text())
                            .then(text => {
                                try { return JSON.parse(text); } catch(e) { return null; }
                            })
                            .then(data => {
                                if(!data) return;

                                var currentResStr = JSON.stringify(data);
                                if(lastResultsStr !== null && currentResStr !== lastResultsStr) {
                                    playAlertSound();
                                    flashElement(resultsBoard);
                                }

                                if(currentResStr !== lastResultsStr) {
                                    lastResultsStr = currentResStr;
                                    var statusText = "En direct";
                                    var cands = [];
                                    if (Array.isArray(data)) { cands = data; }
                                    else if (data.candidates) { cands = data.candidates; statusText = data.status || "En direct"; }

                                    if (cands.length === 0) {
                                        resultsBoard.style.display = 'none';
                                        candidatesArea.innerHTML = '';
                                        return;
                                    }

                                    resultsBoard.style.display = 'block';
                                    var titleEl = document.getElementById('angers-results-title');
                                    if(titleEl) { titleEl.textContent = '📊 RÉSULTATS DES VOTES (' + statusText + ')'; }

                                    var totalVotes = 0;
                                    cands.forEach(c => totalVotes += parseInt(c.votes));
                                    var html = '';
                                    cands.sort((a,b) => b.votes - a.votes).forEach(function(cand) {
                                        var percent = totalVotes > 0 ? ((cand.votes / totalVotes) * 100).toFixed(2) : 0;
                                        var photoHtml = cand.photo ? '<img src="'+cand.photo+'" class="candidate-photo">' : '<div class="candidate-photo"></div>';
                                        html += '<div class="candidate-row">' + photoHtml + '<div class="candidate-data">';
                                        html += '<div class="candidate-stats"><span>' + cand.name + ' <span class="candidate-votes-text">(' + cand.votes + ' voix)</span></span><span>' + percent + '%</span></div>';
                                        html += '<div class="progress-bar-bg"><div class="progress-bar-fill" style="width:'+percent+'%; background-color:'+cand.color+';"></div></div>';
                                        html += '</div></div>';
                                    });
                                    candidatesArea.innerHTML = html;
                                }
                            }).catch(e => {});
                    }

                    if(feedUrl && feedContainer) {
                        fetch(feedUrl + cacheBuster)
                            .then(res => res.text())
                            .then(text => { try { var parsed = JSON.parse(text); return Array.isArray(parsed) ? parsed : []; } catch(e) { return []; } })
                            .then(data => {
                                var currentFeedStr = JSON.stringify(data);
                                if (currentFeedStr === lastFeedStr) return;

                                var html = '';
                                if (data.length === 0) {
                                    html = '<p style="padding:20px; text-align:center; color:#999; font-style:italic;">Le direct n\'a pas encore commencé...</p>';
                                } else {
                                    data.forEach(function(post) {
                                        var tParts = post.time.split(/[- :]/);
                                        var d = new Date(tParts[0], tParts[1]-1, tParts[2], tParts[3], tParts[4], tParts[5]);
                                        var timeStr = ('0'+d.getHours()).slice(-2) + 'h' + ('0'+d.getMinutes()).slice(-2);
                                        var relativeTime = getRelativeTime(post.time);

                                        var cleanText = post.content.replace(/<[^>]*>?/gm, '').substring(0, 120) + '...';
                                        var shareText = encodeURIComponent("🔴 Angers Info : " + cleanText);
                                        var encodedPostUrl = encodeURIComponent(baseUrl + '#msg-' + post.id);

                                        var shareHtml = '<div class="live-share-buttons">';
                                        shareHtml += '<a href="https://api.whatsapp.com/send?text='+shareText+'%20'+encodedPostUrl+'" target="_blank" class="live-share-btn btn-wa"><i class="fa fa-whatsapp"></i></a>';
                                        shareHtml += '<a href="https://www.facebook.com/sharer/sharer.php?u='+encodedPostUrl+'" target="_blank" class="live-share-btn btn-fb"><i class="fa fa-facebook"></i></a>';
                                        shareHtml += '<a href="https://twitter.com/intent/tweet?text='+shareText+'&url='+encodedPostUrl+'" target="_blank" class="live-share-btn btn-tw"><i class="fa fa-twitter"></i></a>';
                                        shareHtml += '</div>';

                                        html += '<div class="live-post live-importance-' + post.importance + '" id="msg-'+post.id+'">';
                                        html += '<div class="timeline-circle"></div>';
                                        html += '<div class="live-time-wrapper"><span class="live-time">' + timeStr + '</span> <span class="live-relative-time">' + relativeTime + '</span></div>';
                                        html += '<div class="live-content">' + post.content + '</div>' + shareHtml + '</div>';
                                    });

                                    if(resultsBoard && resultsBoard.style.display === 'block' && data.length === 0 && feedWrapper) {
                                        feedWrapper.style.display = 'none';
                                    } else if(feedWrapper) {
                                        feedWrapper.style.display = 'block';
                                    }
                                }

                                if (isFirstLoad) {
                                    lastFeedStr = currentFeedStr;
                                    setTimeout(function() {
                                        feedContainer.innerHTML = html;
                                        if (window.twttr && window.twttr.widgets) { window.twttr.widgets.load(feedContainer); }
                                        if (window.location.hash) {
                                            setTimeout(function() {
                                                var t = document.querySelector(window.location.hash);
                                                if(t) { t.scrollIntoView({ behavior: 'smooth' }); }
                                            }, 100);
                                        }
                                    }, 800);
                                    isFirstLoad = false;
                                } else {
                                    var isScrolled = feedWrapper ? feedWrapper.getBoundingClientRect().top < -100 : false;
                                    if (isScrolled && floatingBtn) {
                                        lastFeedStr = currentFeedStr;
                                        pendingHtml = html;
                                        floatingBtn.style.display = 'flex';
                                    } else {
                                        lastFeedStr = currentFeedStr;
                                        feedContainer.innerHTML = html;
                                        if (window.twttr && window.twttr.widgets) { window.twttr.widgets.load(feedContainer); }

                                        playAlertSound();

                                        if(feedWrapper) flashElement(feedWrapper);
                                    }
                                }
                            });
                    }
                }

                updateAll();
                setInterval(updateAll, 60000);
            });
        </script>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}
// Désactiver la redirection automatique pour les articles qui ont un Live Blog
add_action('template_redirect', function() {
    if (is_single()) {
        global $post;
        // Si l'article contient ton shortcode, on interdit la redirection vers la date
        if (has_shortcode($post->post_content, 'angers_live_blog')) {
            remove_action('template_redirect', 'redirect_canonical');
        }
    }
}, 1);

// Empêcher WordPress de "deviner" l'URL (Redirect Guess)
add_filter('do_redirect_guess_arg_name', '__return_false');