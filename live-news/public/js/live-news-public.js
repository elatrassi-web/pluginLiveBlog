document.addEventListener("DOMContentLoaded", function() {
	var feedContainer = document.getElementById('live-news-feed');
	var feedWrapper = document.getElementById('live-news-feed-wrapper');
	var floatingBtn = document.getElementById('live-news-floating-btn');
	var filterToggle = document.getElementById('live-filter-toggle');
	var soundToggleBtn = document.getElementById('live-sound-toggle');

	var isSoundEnabled = localStorage.getItem('live_news_sound_pref') === 'true';
	var notificationSound = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
	notificationSound.volume = 0.5;

	var ajaxUrl = typeof liveNewsPublic !== 'undefined' ? liveNewsPublic.ajaxurl : '';
	var livePostId = feedContainer ? feedContainer.getAttribute('data-post-id') : 0;

	var viewerId = localStorage.getItem('live_news_vid');
	if(!viewerId) {
		viewerId = Math.random().toString(36).substr(2, 9);
		localStorage.setItem('live_news_vid', viewerId);
	}

	function sendLivePing() {
		if(livePostId > 0 && ajaxUrl) {
			var formData = new FormData();
			formData.append('action', 'live_news_ping');
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
				localStorage.setItem('live_news_sound_pref', 'true');
				notificationSound.play().catch(function(e) { console.log("Son bloqué par le navigateur."); });
			} else {
				this.innerHTML = '<i class="fa fa-bell-slash-o"></i>';
				this.classList.remove('active');
				localStorage.setItem('live_news_sound_pref', 'false');
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

	// Trigger custom event for Pro plugin to hook into
	var liveNewsPlayAlertSound = playAlertSound;
	window.liveNewsPlayAlertSound = liveNewsPlayAlertSound;

	var feedUrl = feedContainer ? feedContainer.getAttribute('data-json-url') : null;
	var baseUrl = window.location.href.split('#')[0];
	var lastFeedStr = null;
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

		// Let pro version handle its own updates
		document.dispatchEvent(new CustomEvent('liveNewsUpdatePro', { detail: { cacheBuster: cacheBuster } }));

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
							var shareText = encodeURIComponent("🔴 Live News : " + cleanText);
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

						if(feedWrapper) {
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
