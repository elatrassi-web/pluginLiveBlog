jQuery(document).ready(function($) {
	var nonce = $('#live_news_nonce').val();

	if ($('.dash-stat-row').length > 0) {
		function updateDashboardViews() {
			var postIds = [];
			$('.dash-stat-row').each(function() { postIds.push($(this).data('post-id')); });

			if (postIds.length > 0) {
				$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_get_bulk_viewers', security: nonce, post_ids: postIds }, function(res) {
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
		setInterval(updateDashboardViews, 60000);
	}

	$('.btn-delete-live').on('click', function(e) {
		e.preventDefault();
		var postId = $(this).data('post-id');
		var tr = $(this).closest('tr');

		if (confirm("🚨 Voulez-vous vraiment SUPPRIMER définitivement cet événement ? L'historique et les fichiers seront détruits. Cette action est irréversible !")) {
			var btn = $(this);
			btn.prop('disabled', true).html('⏳...');

			$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_delete_event', security: nonce, post_id: postId }, function(res) {
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
		$.post(liveNewsAdmin.ajaxurl, {
			action: 'live_news_create_new',
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
			$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_get_viewers', security: nonce, post_id: livePostId }, function(res) {
				if(res.success) {
					$('#live-viewer-count').text(res.data.count);
					$('#total-viewer-count').text(res.data.total);
				}
			});
		}
		setInterval(updateViewerCount, 60000);

		$('#live_news_emoji_btn').on('click', function(e) {
			e.preventDefault();
			$('#live_news_emoji_picker').toggle();
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('#live_news_emoji_btn, #live_news_emoji_picker').length) {
				$('#live_news_emoji_picker').hide();
			}
		});

		$('.live-emoji-item').on('click', function(e) {
			e.preventDefault();
			var emoji = $(this).text();
			if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content') && !tinymce.get('live_news_content').isHidden()) {
				tinymce.get('live_news_content').execCommand('mceInsertContent', false, emoji);
			} else {
				var textarea = $('#live_news_content')[0];
				var startPos = textarea.selectionStart;
				var endPos = textarea.selectionEnd;
				textarea.value = textarea.value.substring(0, startPos) + emoji + textarea.value.substring(endPos, textarea.value.length);
				textarea.focus();
			}
			$('#live_news_emoji_picker').hide();
		});

		$('.quick-insert-btn').on('click', function(e) {
			e.preventDefault();
			var textToInsert = $(this).data('text');
			if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content') && !tinymce.get('live_news_content').isHidden()) {
				tinymce.get('live_news_content').execCommand('mceInsertContent', false, textToInsert);
			} else {
				var textarea = $('#live_news_content')[0];
				var startPos = textarea.selectionStart;
				var endPos = textarea.selectionEnd;
				textarea.value = textarea.value.substring(0, startPos) + textToInsert + textarea.value.substring(endPos, textarea.value.length);
				textarea.focus();
			}
		});

		function loadSavedData() {
			$('#loader-data').show();

			// Trigger custom event for Pro plugin to hook into
			$(document).trigger('live_news_load_saved_data', [livePostId, nonce]);

			$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_get_saved_data', security: nonce, post_id: livePostId }, function(res) {
				if(res.success) {
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
					$('#live_news_history_list').html(historyHtml);
				}
				$('#loader-data').hide();
				updateViewerCount();
			});
		}
		loadSavedData();

		// Make it available globally for the pro script
		window.liveNewsLoadSavedData = loadSavedData;

		$('#live_news_btn_purge').on('click', function(e) {
			e.preventDefault();
			var confirm1 = confirm("🚨 ATTENTION : Vous êtes sur le point d'effacer TOUT l'historique des messages, de remettre les scores/votes à zéro, et de réinitialiser le compteur de vues totales.\n\nVoulez-vous vraiment continuer ?");
			if (confirm1) {
				var confirm2 = confirm("⚠️ DERNIER AVERTISSEMENT : Cette action est irréversible. Confirmez-vous la purge du direct ?");
				if (confirm2) {
					var btn = $(this);
					var originalHtml = btn.html();
					btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Nettoyage...').prop('disabled', true);

					$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_purge_data', security: nonce, post_id: livePostId }, function(res) {
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

		$('#live_news_btn_send').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			var editId = $('#live_news_edit_id').val();
			var customTime = $('#live_news_custom_time').val();

			if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content')) { tinymce.get('live_news_content').save(); }
			var content = $('#live_news_content').val();

			if(!content) { alert('Tapez un message !'); return; }

			var originalHtml = btn.html();
			btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...').prop('disabled', true);

			$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_send_message', security: nonce, post_id: livePostId, edit_id: editId, content: content, importance: $('#live_news_importance').val(), custom_time: customTime }, function(res) {
				if(res.success) {
					$('#live_news_content').val('');
					if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content')) { tinymce.get('live_news_content').setContent(''); }
					$('#live_news_edit_id').val('');
					$('#live_news_custom_time').val('');
					$('#live_news_btn_cancel_edit').hide();

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

			$('#live_news_content').val(content);
			if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content')) { tinymce.get('live_news_content').setContent(content); }
			$('#live_news_importance').val(imp);
			$('#live_news_edit_id').val(id);
			$('#live_news_custom_time').val(timeOnly);

			$('#live_news_btn_send').removeClass('btn-danger').addClass('btn-primary').html('💾 SAUVEGARDER');
			$('#live_news_btn_cancel_edit').show();
			$('html, body').animate({ scrollTop: 0 }, 'fast');
		});

		$('#live_news_btn_cancel_edit').on('click', function(e) {
			e.preventDefault();
			$('#live_news_content').val('');
			if (typeof tinymce !== 'undefined' && tinymce.get('live_news_content')) { tinymce.get('live_news_content').setContent(''); }
			$('#live_news_edit_id').val('');
			$('#live_news_custom_time').val('');
			$('#live_news_btn_send').removeClass('btn-primary').addClass('btn-danger').html('🚀 PUBLIER L\'INFO');
			$(this).hide();
		});

		$(document).on('click', '.del-msg', function(e) {
			e.preventDefault();
			if(!confirm('🚨 Supprimer définitivement ce message ?')) return;
			var msgId = $(this).data('id');
			$.post(liveNewsAdmin.ajaxurl, { action: 'live_news_delete_message', security: nonce, post_id: livePostId, msg_id: msgId }, function(res) {
				if(res.success) loadSavedData();
			});
		});
	}
});
