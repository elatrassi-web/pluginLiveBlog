document.addEventListener("DOMContentLoaded", function() {
	var resultsBoard = document.getElementById('live-news-results-board');
	var candidatesArea = document.getElementById('candidates-render-area');
	var resultsUrl = resultsBoard ? resultsBoard.getAttribute('data-results-url') : null;

	var sportsBoard = document.getElementById('live-news-sports-board');
	var sportsUrl = sportsBoard ? sportsBoard.getAttribute('data-sports-url') : null;

	var lastResultsStr = null;
	var lastSportsStr = null;

	function flashElement(element) {
		if(!element) return;
		element.classList.remove('highlight-update');
		void element.offsetWidth;
		element.classList.add('highlight-update');
	}

	function playAlertSound() {
		if (typeof window.liveNewsPlayAlertSound === 'function') {
			window.liveNewsPlayAlertSound();
		}
	}

	document.addEventListener('liveNewsUpdatePro', function(e) {
		var cacheBuster = e.detail ? e.detail.cacheBuster : ('?t=' + new Date().getTime());

		// 1. VOTES / ELECTIONS UPDATE
		if(resultsUrl && resultsBoard && candidatesArea) {
			fetch(resultsUrl + cacheBuster)
				.then(res => res.text())
				.then(text => { try { return JSON.parse(text); } catch(err) { return null; } })
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
						var titleEl = document.getElementById('live-news-results-title');
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
				}).catch(err => {});
		}

		// 2. SPORTS UPDATE
		if(sportsUrl && sportsBoard) {
			fetch(sportsUrl + cacheBuster)
				.then(res => res.text())
				.then(text => { try { return JSON.parse(text); } catch(err) { return null; } })
				.then(data => {
					if(!data) return;

					var currentSportsStr = JSON.stringify(data);

					var d1 = JSON.parse(currentSportsStr);
					var d2 = lastSportsStr ? JSON.parse(lastSportsStr) : null;

					var isScoreOrPeriodChange = false;
					if (d2) {
						var m1 = d1.matches || [];
						var m2 = d2.matches || [];
						// Fallback check
						if (m1.length === 0 && d1.team1) m1 = [d1];
						if (m2.length === 0 && d2.team1) m2 = [d2];

						if (m1.length !== m2.length) {
							isScoreOrPeriodChange = true;
						} else {
							for(var i=0; i<m1.length; i++) {
								var mm1 = JSON.parse(JSON.stringify(m1[i]));
								var mm2 = JSON.parse(JSON.stringify(m2[i]));
								if(mm1.timer) mm1.timer.value = '';
								if(mm2.timer) mm2.timer.value = '';
								if(JSON.stringify(mm1) !== JSON.stringify(mm2)) {
									isScoreOrPeriodChange = true;
									break;
								}
							}
						}
					}

					if(isScoreOrPeriodChange) {
						playAlertSound();
						flashElement(sportsBoard);
					}

					if(currentSportsStr !== lastSportsStr) {
						lastSportsStr = currentSportsStr;

						var matches = data.matches || [];
						if (matches.length === 0 && data.team1) {
							matches = [data]; // Fallback to old single-match structure
						}

						if(matches.length === 0) {
							sportsBoard.style.display = 'none';
							return;
						}

						sportsBoard.style.display = 'block';

						var html = '';
						matches.forEach(function(match, idx) {
							var t1 = match.team1 || {};
							var t2 = match.team2 || {};
							var timer = match.timer || {};

							var displayTimer = timer.value || '00:00';
							if(timer.status === 'running') {
								var diff = Math.floor(Date.now() / 1000) - timer.last_update;
								var pts = displayTimer.split(':');
								var sec = parseInt(pts[0])*60 + parseInt(pts[1]) + diff;
								var m = Math.floor(sec / 60);
								var s = sec % 60;
								displayTimer = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
							}

							html += '<div class="sports-match-item mb-3">';
							html += '<div class="sports-header">';
							html += '<div class="sports-period">' + (timer.period || '') + '</div>';
							html += '<div class="sports-timer sports-timer-dyn" data-status="'+timer.status+'" data-last="'+timer.last_update+'" data-base="'+(timer.value||'00:00')+'">' + displayTimer + '</div>';
							html += '</div>';

							html += '<div class="sports-body">';
							html += '<div class="sports-team">';
							html += '<div class="sports-team-color" style="background-color:' + (t1.color||'#000') + ';"></div>';
							html += '<div class="sports-team-name">' + (t1.name||'') + '</div>';
							html += '</div>';

							html += '<div class="sports-score">';
							html += '<span>' + (t1.score||0) + '</span><span class="sports-score-divider">-</span><span>' + (t2.score||0) + '</span>';
							html += '</div>';

							html += '<div class="sports-team team-right">';
							html += '<div class="sports-team-name">' + (t2.name||'') + '</div>';
							html += '<div class="sports-team-color" style="background-color:' + (t2.color||'#000') + ';"></div>';
							html += '</div>';
							html += '</div>';

							html += '</div>';
						});

						var renderArea = document.getElementById('sports-matches-render-area');
						if(renderArea) {
							renderArea.innerHTML = html;
						}

						// Handle show_editor toggle styling
						var feedWrapper = document.getElementById('live-news-feed-wrapper');
						var isEditorActive = data.show_editor !== false;
						if (!isEditorActive && feedWrapper) {
							feedWrapper.style.display = 'none';
						} else if (isEditorActive && feedWrapper && feedWrapper.style.display === 'none') {
							// Check if there are messages to display before showing
							var feedHtml = document.getElementById('live-news-feed');
							if(feedHtml && feedHtml.innerHTML.indexOf('Le direct n\'a pas encore commencé') === -1) {
								feedWrapper.style.display = 'block';
							}
						}
					}
				}).catch(err => {});
		}
	});

	// If sports timer is running, update it locally every second
	setInterval(function() {
		var dynTimers = document.querySelectorAll('.sports-timer-dyn');
		dynTimers.forEach(function(el) {
			if(el.getAttribute('data-status') === 'running') {
				var lastUpdate = parseInt(el.getAttribute('data-last'));
				var baseVal = el.getAttribute('data-base');

				var diff = Math.floor(Date.now() / 1000) - lastUpdate;
				var pts = baseVal.split(':');
				var sec = parseInt(pts[0])*60 + parseInt(pts[1]) + diff;
				var m = Math.floor(sec / 60);
				var s = sec % 60;
				el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
			}
		});
	}, 1000);

});
