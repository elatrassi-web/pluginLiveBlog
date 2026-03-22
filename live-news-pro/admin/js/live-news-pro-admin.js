jQuery(document).ready(function($) {
	// Attends que liveNewsProAdmin.ajaxurl et liveNewsAdmin.ajaxurl (du core) soient dispos
	var ajaxurl = typeof liveNewsProAdmin !== 'undefined' ? liveNewsProAdmin.ajaxurl : (typeof liveNewsAdmin !== 'undefined' ? liveNewsAdmin.ajaxurl : '');
	if(!ajaxurl) return;

	// Écoute l'événement de chargement des données depuis le Core
	$(document).on('live_news_load_saved_data', function(e, postId, nonce) {

		// 1. GESTION DES VOTES / ÉLECTIONS
		if ($('#live_news_results_status').length > 0) {
			$('#candidates-admin-list').empty();

			$.post(ajaxurl, { action: 'live_news_get_saved_data', security: nonce, post_id: postId }, function(res) {
				if(res.success && res.data.results_data) {
					$('#live_news_results_status').val(res.data.results_data.status || 'En direct');
					var cands = res.data.results_data.candidates || [];
					if(cands.length > 0) {
						$.each(cands, function(index, cand) { addCandidateBlock(cand.name, cand.votes, cand.color, cand.photo); });
					} else {
						addCandidateBlock(); addCandidateBlock();
					}
				}
			});
		}

		// 2. GESTION DU SPORT
		if ($('#sports_timer').length > 0) {
			$.post(ajaxurl, { action: 'live_news_get_sports_data', security: nonce, post_id: postId }, function(res) {
				if(res.success && res.data) {
					var data = res.data;
					if(data.team1) {
						$('#t1_name').val(data.team1.name || '');
						$('#t1_score').val(data.team1.score || 0);
						$('#t1_color').val(data.team1.color || '#e2001a');
						$('#t1_logo').val(data.team1.logo || '');
					}
					if(data.team2) {
						$('#t2_name').val(data.team2.name || '');
						$('#t2_score').val(data.team2.score || 0);
						$('#t2_color').val(data.team2.color || '#0056b3');
						$('#t2_logo').val(data.team2.logo || '');
					}
					if(data.timer) {
						$('#sports_timer').val(data.timer.value || '00:00');
						timerSeconds = parseTime(data.timer.value || '00:00');
						timerStatus = data.timer.status || 'stopped';
						if(timerStatus === 'running') {
							// For admin UI, just show it as stopped to let admin restart it or we can auto-start
							timerStatus = 'stopped';
							$('#sports_timer_toggle').removeClass('btn-warning').addClass('btn-success').html('▶ Play');
						} else {
							$('#sports_timer_toggle').removeClass('btn-warning').addClass('btn-success').html('▶ Play');
						}

						if(data.timer.period) {
							$('#sports_period').val(data.timer.period);
						}
					}
				}
			});
		}
	});

	function addCandidateBlock(name = '', votes = '', color = '#1e73be', photo = '') {
		var html = `
		<div class="candidate-block bg-white shadow-sm">
			<a href="#" class="remove-candidate" title="Supprimer">✖️</a>
			<h6 class="text-muted fw-bold mb-3">Profil Candidat</h6>
			<div class="mb-2">
				<input type="text" class="cand-name form-control fw-bold" placeholder="Nom du candidat" value="`+name+`">
			</div>
			<div class="input-group mb-2 shadow-sm">
				<span class="input-group-text bg-light">🗳️</span>
				<input type="number" class="cand-votes form-control" placeholder="Voix" value="`+votes+`">
				<input type="color" class="cand-color form-control form-control-color" value="`+color+`" title="Couleur de la barre">
			</div>
			<div class="input-group input-group-sm">
				<span class="input-group-text bg-light">📸 URL Photo</span>
				<input type="text" class="cand-photo form-control" placeholder="https://..." value="`+photo+`">
			</div>
		</div>`;
		$('#candidates-admin-list').append(html);
	}

	$('#btn-add-candidate').on('click', function(e){ e.preventDefault(); addCandidateBlock(); });
	$(document).on('click', '.remove-candidate', function(e){ e.preventDefault(); $(this).closest('.candidate-block').remove(); });

	$('#live_news_btn_results').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var nonce = $('#live_news_nonce').val();
		var livePostId = $('#live_post_id').val();

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

		var status = $('#live_news_results_status').val();

		btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculs...').prop('disabled', true);
		$.post(ajaxurl, { action: 'live_news_update_results', security: nonce, post_id: livePostId, candidates: candidates, results_status: status }, function(res) {
			if(res.success) {
				btn.removeClass('btn-primary').addClass('btn-success').html('✅ VOTES MIS À JOUR !');
				setTimeout(function(){ btn.removeClass('btn-success').addClass('btn-primary').html('🔄 METTRE À JOUR LES VOTES'); }, 2000);
			}
			btn.prop('disabled', false);
		});
	});

	// --- SPORTS LOGIC ---
	$('.btn-score').on('click', function(e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var val = parseInt($(this).data('val'));
		var current = parseInt(target.val()) || 0;
		var next = current + val;
		if(next < 0) next = 0;
		target.val(next);
	});

	$('.sports-quick-insert').on('click', function(e) {
		e.preventDefault();
		var textToInsert = $(this).data('text');
		var textarea = $('#live_news_content')[0];
		if(!textarea) return;
		var startPos = textarea.selectionStart;
		var endPos = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, startPos) + textToInsert + textarea.value.substring(endPos, textarea.value.length);
		textarea.focus();
	});

	// Simple timer logic for admin UI
	var timerInterval;
	var timerSeconds = 0;
	var timerStatus = 'stopped';

	function formatTime(sec) {
		var m = Math.floor(sec / 60);
		var s = sec % 60;
		return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
	}

	function parseTime(str) {
		var pts = str.split(':');
		if(pts.length === 2) return parseInt(pts[0])*60 + parseInt(pts[1]);
		return 0;
	}

	$('#sports_timer').on('change', function() {
		timerSeconds = parseTime($(this).val());
	});

	$('#sports_timer_toggle').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		if(timerStatus === 'stopped') {
			timerStatus = 'running';
			btn.removeClass('btn-success').addClass('btn-warning').html('⏸ Pause');
			timerSeconds = parseTime($('#sports_timer').val());
			timerInterval = setInterval(function() {
				timerSeconds++;
				$('#sports_timer').val(formatTime(timerSeconds));
			}, 1000);
		} else {
			timerStatus = 'stopped';
			btn.removeClass('btn-warning').addClass('btn-success').html('▶ Play');
			clearInterval(timerInterval);
		}
	});

	$('#sports_timer_reset').on('click', function(e) {
		e.preventDefault();
		clearInterval(timerInterval);
		timerStatus = 'stopped';
		timerSeconds = 0;
		$('#sports_timer').val('00:00');
		$('#sports_timer_toggle').removeClass('btn-warning').addClass('btn-success').html('▶ Play');
	});

	$('#live_news_btn_sports_update').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var nonce = $('#live_news_nonce').val();
		var livePostId = $('#live_post_id').val();

		btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> MAJ Score...').prop('disabled', true);

		var data = {
			action: 'live_news_update_sports',
			security: nonce,
			post_id: livePostId,

			team1_name: $('#t1_name').val(),
			team1_score: $('#t1_score').val(),
			team1_color: $('#t1_color').val(),
			team1_logo: $('#t1_logo').val(),

			team2_name: $('#t2_name').val(),
			team2_score: $('#t2_score').val(),
			team2_color: $('#t2_color').val(),
			team2_logo: $('#t2_logo').val(),

			timer_status: timerStatus,
			timer_value: $('#sports_timer').val(),
			period: $('#sports_period').val()
		};

		$.post(ajaxurl, data, function(res) {
			if(res.success) {
				btn.removeClass('btn-success').addClass('btn-primary').html('✅ SCORE MIS À JOUR !');
				setTimeout(function(){ btn.removeClass('btn-primary').addClass('btn-success').html('🔄 METTRE À JOUR LE SCORE'); }, 2000);
			}
			btn.prop('disabled', false);
		});
	});

});
