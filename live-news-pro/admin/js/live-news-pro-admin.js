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
		if ($('#sports-matches-container').length > 0) {
			$.post(ajaxurl, { action: 'live_news_get_sports_data', security: nonce, post_id: postId }, function(res) {
				if(res.success && res.data) {
					var data = res.data;

					if (data.show_editor === false) {
						$('#toggle_sports_editor').prop('checked', false);
						$('#sports-editor-wrapper').hide();
					}

					var matches = data.matches || [];
					if (matches.length > 0) {
						$.each(matches, function(index, match) {
							addMatchBlock(match);
						});
					} else {
						// Fallback if there was old single-match data or empty
						if (data.team1) {
							addMatchBlock(data);
						} else {
							addMatchBlock();
						}
					}
				} else {
					addMatchBlock();
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

	function addMatchBlock(match = {}) {
		var id = Math.random().toString(36).substr(2, 9);
		var t1 = match.team1 || { name: '', score: 0, color: '#e2001a', logo: '' };
		var t2 = match.team2 || { name: '', score: 0, color: '#0056b3', logo: '' };
		var timer = match.timer || { value: '00:00', period: 'Avant-match', status: 'stopped' };

		var html = `
		<div class="match-block bg-white shadow-sm" data-id="`+id+`">
			<a href="#" class="remove-match" title="Supprimer ce match">✖️</a>

			<div class="d-flex align-items-center justify-content-between mb-3">
				<h6 class="text-muted fw-bold mb-0">Détails du Match</h6>
				<select class="form-select form-select-sm fw-bold w-auto sports_period">
					<option value="Avant-match" `+(timer.period==='Avant-match'?'selected':'')+`>Avant-match</option>
					<option value="1MT" `+(timer.period==='1MT'?'selected':'')+`>1ère Mi-temps</option>
					<option value="MT" `+(timer.period==='MT'?'selected':'')+`>Mi-temps</option>
					<option value="2MT" `+(timer.period==='2MT'?'selected':'')+`>2ème Mi-temps</option>
					<option value="Prol." `+(timer.period==='Prol.'?'selected':'')+`>Prolongations</option>
					<option value="TAB" `+(timer.period==='TAB'?'selected':'')+`>Tirs au but</option>
					<option value="Fin" `+(timer.period==='Fin'?'selected':'')+`>Terminé</option>
				</select>
			</div>

			<div class="row text-center mb-3 align-items-center">
				<div class="col-5">
					<div class="mb-2"><input type="text" class="t1_name form-control text-center fw-bold fs-5" placeholder="Nom domicile" value="`+t1.name+`"></div>
					<div class="mb-2 d-flex gap-2">
						<input type="color" class="t1_color form-control form-control-color w-25" value="`+t1.color+`" title="Couleur">
						<input type="text" class="t1_logo form-control w-75" placeholder="URL Logo (opt.)" value="`+t1.logo+`">
					</div>
					<div class="d-flex justify-content-center align-items-center gap-3 mt-3">
						<button class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-dir="-1">-</button>
						<input type="number" class="t1_score form-control text-center fs-2 fw-bold border-0 bg-transparent w-50" value="`+t1.score+`" min="0">
						<button class="btn btn-outline-primary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-dir="1">+</button>
					</div>
				</div>

				<div class="col-2">
					<div class="bg-dark text-white rounded p-2 mb-2">
						<input type="text" class="sports_timer form-control text-center bg-transparent border-0 text-white fs-4 fw-bold p-0" value="`+timer.value+`" placeholder="00:00" data-seconds="`+parseTime(timer.value)+`" data-status="stopped">
					</div>
					<button class="btn btn-sm btn-success w-100 fw-bold mb-1 sports_timer_toggle">▶ Play</button>
					<button class="btn btn-sm btn-outline-secondary w-100 sports_timer_reset" style="font-size:10px;">Reset</button>
				</div>

				<div class="col-5">
					<div class="mb-2"><input type="text" class="t2_name form-control text-center fw-bold fs-5" placeholder="Nom extérieur" value="`+t2.name+`"></div>
					<div class="mb-2 d-flex gap-2">
						<input type="color" class="t2_color form-control form-control-color w-25" value="`+t2.color+`" title="Couleur">
						<input type="text" class="t2_logo form-control w-75" placeholder="URL Logo (opt.)" value="`+t2.logo+`">
					</div>
					<div class="d-flex justify-content-center align-items-center gap-3 mt-3">
						<button class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-dir="-1">-</button>
						<input type="number" class="t2_score form-control text-center fs-2 fw-bold border-0 bg-transparent w-50" value="`+t2.score+`" min="0">
						<button class="btn btn-outline-primary btn-sm rounded-circle px-2 py-1 fs-5 fw-bold btn-score" data-dir="1">+</button>
					</div>
				</div>
			</div>
		</div>`;
		$('#sports-matches-container').append(html);
	}

	$('#btn-add-sports-match').on('click', function(e){ e.preventDefault(); addMatchBlock(); });
	$(document).on('click', '.remove-match', function(e){ e.preventDefault(); $(this).closest('.match-block').remove(); });

	$('#toggle_sports_editor').on('change', function() {
		if($(this).is(':checked')) { $('#sports-editor-wrapper').slideDown(); }
		else { $('#sports-editor-wrapper').slideUp(); }
	});

	$(document).on('click', '.btn-score', function(e) {
		e.preventDefault();
		var target = $(this).siblings('input[type="number"]');
		var val = parseInt($(this).data('dir'));
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

	// Simple timer logic for admin UI (multiple timers)
	var timerIntervals = {};

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

	$(document).on('change', '.sports_timer', function() {
		$(this).data('seconds', parseTime($(this).val()));
	});

	$(document).on('click', '.sports_timer_toggle', function(e) {
		e.preventDefault();
		var btn = $(this);
		var matchBlock = btn.closest('.match-block');
		var matchId = matchBlock.data('id');
		var input = matchBlock.find('.sports_timer');

		var status = input.data('status');

		if(status === 'stopped') {
			input.data('status', 'running');
			btn.removeClass('btn-success').addClass('btn-warning').html('⏸ Pause');
			input.data('seconds', parseTime(input.val()));

			timerIntervals[matchId] = setInterval(function() {
				var sec = parseInt(input.data('seconds')) + 1;
				input.data('seconds', sec);
				input.val(formatTime(sec));
			}, 1000);
		} else {
			input.data('status', 'stopped');
			btn.removeClass('btn-warning').addClass('btn-success').html('▶ Play');
			clearInterval(timerIntervals[matchId]);
		}
	});

	$(document).on('click', '.sports_timer_reset', function(e) {
		e.preventDefault();
		var matchBlock = $(this).closest('.match-block');
		var matchId = matchBlock.data('id');
		var input = matchBlock.find('.sports_timer');
		var toggleBtn = matchBlock.find('.sports_timer_toggle');

		clearInterval(timerIntervals[matchId]);
		input.data('status', 'stopped');
		input.data('seconds', 0);
		input.val('00:00');
		toggleBtn.removeClass('btn-warning').addClass('btn-success').html('▶ Play');
	});

	$('#live_news_btn_sports_update').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var nonce = $('#live_news_nonce').val();
		var livePostId = $('#live_post_id').val();

		var matches = [];
		$('.match-block').each(function() {
			var b = $(this);
			matches.push({
				team1_name: b.find('.t1_name').val(),
				team1_score: b.find('.t1_score').val(),
				team1_color: b.find('.t1_color').val(),
				team1_logo: b.find('.t1_logo').val(),
				team2_name: b.find('.t2_name').val(),
				team2_score: b.find('.t2_score').val(),
				team2_color: b.find('.t2_color').val(),
				team2_logo: b.find('.t2_logo').val(),
				timer_status: b.find('.sports_timer').data('status'),
				timer_value: b.find('.sports_timer').val(),
				period: b.find('.sports_period').val()
			});
		});

		btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> MAJ Scores...').prop('disabled', true);

		var data = {
			action: 'live_news_update_sports',
			security: nonce,
			post_id: livePostId,
			show_editor: $('#toggle_sports_editor').is(':checked'),
			matches: matches
		};

		$.post(ajaxurl, data, function(res) {
			if(res.success) {
				btn.removeClass('btn-success').addClass('btn-primary').html('✅ SCORES MIS À JOUR !');
				setTimeout(function(){ btn.removeClass('btn-primary').addClass('btn-success').html('🔄 METTRE À JOUR LES SCORES'); }, 2000);
			}
			btn.prop('disabled', false);
		});
	});

});
