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

					// Ignore timer changes for flashing
					var d1 = JSON.parse(currentSportsStr);
					var d2 = lastSportsStr ? JSON.parse(lastSportsStr) : null;
					if(d1 && d1.timer) d1.timer.value = '';
					if(d2 && d2.timer) d2.timer.value = '';
					var isScoreOrPeriodChange = d2 && JSON.stringify(d1) !== JSON.stringify(d2);

					if(isScoreOrPeriodChange) {
						playAlertSound();
						flashElement(sportsBoard);
					}

					if(currentSportsStr !== lastSportsStr) {
						lastSportsStr = currentSportsStr;

						sportsBoard.style.display = 'block';

						if(data.team1) {
							var el = document.getElementById('sports-board-t1-name'); if(el) el.textContent = data.team1.name;
							el = document.getElementById('sports-board-t1-score'); if(el) el.textContent = data.team1.score;
							el = document.getElementById('sports-board-t1-color'); if(el) el.style.backgroundColor = data.team1.color;
						}

						if(data.team2) {
							var el = document.getElementById('sports-board-t2-name'); if(el) el.textContent = data.team2.name;
							el = document.getElementById('sports-board-t2-score'); if(el) el.textContent = data.team2.score;
							el = document.getElementById('sports-board-t2-color'); if(el) el.style.backgroundColor = data.team2.color;
						}

						if(data.timer) {
							var el = document.getElementById('sports-board-period'); if(el) el.textContent = data.timer.period;
							el = document.getElementById('sports-board-timer');
							if(el) {
								if(data.timer.status === 'running') {
									// In a real app we'd calculate the diff from last_update
									var diff = Math.floor(Date.now() / 1000) - data.timer.last_update;
									var pts = data.timer.value.split(':');
									var sec = parseInt(pts[0])*60 + parseInt(pts[1]) + diff;
									var m = Math.floor(sec / 60);
									var s = sec % 60;
									el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
								} else {
									el.textContent = data.timer.value;
								}
							}
						}
					}
				}).catch(err => {});
		}
	});

	// If sports timer is running, update it locally every second
	setInterval(function() {
		if(!lastSportsStr) return;
		try {
			var data = JSON.parse(lastSportsStr);
			if(data && data.timer && data.timer.status === 'running') {
				var diff = Math.floor(Date.now() / 1000) - data.timer.last_update;
				var pts = data.timer.value.split(':');
				var sec = parseInt(pts[0])*60 + parseInt(pts[1]) + diff;
				var m = Math.floor(sec / 60);
				var s = sec % 60;
				var el = document.getElementById('sports-board-timer');
				if(el) el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
			}
		} catch(e) {}
	}, 1000);

});
