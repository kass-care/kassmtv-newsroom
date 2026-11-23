/* KASS-mtv theme: menu, search, date, reading bar, video, sharing, reads. */
( function () {
	var data = window.kassmtv || {};

	function onClick( selector, handler ) {
		document.addEventListener( 'click', function ( event ) {
			var target = event.target.closest( selector );

			if ( target ) {
				handler( target, event );
			}
		} );
	}

	/* ---------- Phone menu ---------- */
	var toggle = document.querySelector( '.k-menu-toggle' );
	var nav = document.getElementById( 'k-nav' );

	function setMenu( open ) {
		if ( ! toggle || ! nav ) {
			return;
		}

		nav.classList.toggle( 'is-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		toggle.setAttribute( 'aria-label', open ? 'Close menu' : 'Open menu' );
	}

	if ( toggle && nav ) {
		toggle.addEventListener( 'click', function () {
			setSearch( false );
			setMenu( ! nav.classList.contains( 'is-open' ) );
		} );

		nav.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( 'a' ) ) {
				setMenu( false );
			}
		} );
	}

	/* ---------- Search panel ---------- */
	var searchToggle = document.querySelector( '.k-search-toggle' );
	var searchPanel = document.getElementById( 'k-search-panel' );

	function setSearch( open ) {
		if ( ! searchToggle || ! searchPanel ) {
			return;
		}

		searchPanel.hidden = ! open;
		searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( open ) {
			var input = searchPanel.querySelector( 'input[type="search"]' );

			if ( input ) {
				input.focus();
			}
		}
	}

	if ( searchToggle && searchPanel ) {
		searchToggle.addEventListener( 'click', function () {
			setMenu( false );
			setSearch( searchPanel.hidden );
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			setMenu( false );
			setSearch( false );
		}
	} );

	/* ---------- Today's date (pages may come from the cache) ---------- */
	var dateEl = document.querySelector( '[data-k-date]' );

	if ( dateEl && window.Intl ) {
		try {
			dateEl.textContent = new Intl.DateTimeFormat( 'en-GB', {
				weekday: 'long',
				day: 'numeric',
				month: 'long',
				year: 'numeric',
			} ).format( new Date() );
		} catch ( error ) {}
	}

	/* ---------- Reading progress bar ---------- */
	var bar = document.querySelector( '.k-progress span' );
	var story = document.querySelector( '.k-story-main' ) || document.querySelector( '.k-prose' );

	if ( bar && story ) {
		var ticking = false;

		var update = function () {
			var rect = story.getBoundingClientRect();
			var total = rect.height - window.innerHeight * 0.6;
			var done = Math.min( 1, Math.max( 0, ( -rect.top + window.innerHeight * 0.2 ) / Math.max( 1, total ) ) );

			bar.style.transform = 'scaleX(' + done + ')';
			ticking = false;
		};

		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}, { passive: true } );

		update();
	}

	/* ---------- Video: play in place, pick from the list ---------- */
	function playVideo( button, id ) {
		var frame = document.createElement( 'iframe' );

		frame.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) + '?autoplay=1&rel=0';
		frame.title = 'KASS-mtv video';
		frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
		frame.allowFullscreen = true;
		frame.className = 'k-player-frame';

		button.replaceWith( frame );
	}

	onClick( '[data-k-play]', function ( button ) {
		playVideo( button, button.getAttribute( 'data-k-play' ) );
	} );

	onClick( '[data-k-video]', function ( item ) {
		var box = item.closest( '[data-k-videos]' );

		if ( ! box ) {
			return;
		}

		var id = item.getAttribute( 'data-k-video' );
		var player = box.querySelector( '.k-player' );
		var title = box.querySelector( '[data-k-player-title]' );
		var current = player.querySelector( '.k-player-frame, [data-k-play]' );
		var button = document.createElement( 'button' );

		button.type = 'button';
		button.className = 'k-player-button';
		button.setAttribute( 'data-k-play', id );
		button.innerHTML = '<span class="k-play" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>';

		if ( current ) {
			current.replaceWith( button );
		}

		box.querySelectorAll( '[data-k-video]' ).forEach( function ( other ) {
			other.classList.toggle( 'is-active', other === item );
		} );

		if ( title ) {
			title.textContent = item.getAttribute( 'data-k-title' ) || '';
		}

		playVideo( button, id );
	} );

	/* ---------- Copy link ---------- */
	onClick( '[data-k-copy]', function ( button ) {
		var url = button.getAttribute( 'data-k-copy' );
		var label = button.querySelector( '[data-k-copy-label]' );

		var done = function () {
			if ( label ) {
				label.textContent = 'Copied ✓';
				window.setTimeout( function () {
					label.textContent = 'Copy link';
				}, 2000 );
			}
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( done, function () {} );
		} else {
			window.prompt( 'Copy this link:', url );
		}
	} );

	/* ---------- Count one read per story per day (for "Most read") ---------- */
	if ( data.postId && data.viewUrl && window.fetch ) {
		var key = 'kassmtv-read-' + data.postId + '-' + new Date().toISOString().slice( 0, 10 );
		var seen = false;

		try {
			seen = window.localStorage.getItem( key ) === '1';
		} catch ( error ) {}

		if ( ! seen ) {
			window.setTimeout( function () {
				fetch( data.viewUrl + data.postId, { method: 'POST', keepalive: true, credentials: 'omit' } ).catch( function () {} );

				try {
					window.localStorage.setItem( key, '1' );
				} catch ( error ) {}
			}, 4000 );
		}
	}
}() );

/* ---------- Newsletter and story-tip forms: send without leaving the page ---------- */
( function () {
	var forms = document.querySelectorAll( '[data-k-form]' );

	if ( ! forms.length || ! window.fetch || ! window.FormData ) {
		return;
	}

	forms.forEach( function ( form ) {
		var time = form.querySelector( '[data-k-time]' );
		var msg = form.querySelector( '[data-k-msg]' );
		var button = form.querySelector( '[type="submit"]' );

		if ( time ) {
			time.value = Math.floor( Date.now() / 1000 );
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var data = new FormData( form );
			data.append( 'kass_ajax', '1' );

			if ( button ) {
				button.disabled = true;
			}

			if ( msg ) {
				msg.className = 'k-form-msg';
				msg.textContent = 'Sending…';
			}

			fetch( form.action, { method: 'POST', body: data, credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json().catch( function () {
						return { ok: false, message: 'Something went wrong. Please try again.' };
					} );
				} )
				.then( function ( result ) {
					if ( msg ) {
						msg.className = 'k-form-msg ' + ( result.ok ? 'is-ok' : 'is-error' );
						msg.textContent = result.message || '';
					}

					if ( result.ok ) {
						form.reset();

						if ( time ) {
							time.value = Math.floor( Date.now() / 1000 );
						}
					}
				} )
				.catch( function () {
					if ( msg ) {
						msg.className = 'k-form-msg is-error';
						msg.textContent = 'No connection. Please try again.';
					}
				} )
				.then( function () {
					if ( button ) {
						button.disabled = false;
					}
				} );
		} );
	} );
}() );

/* ---------- Ads: show one paid ad per space at random, count what is seen ---------- */
( function () {
	var spaces = document.querySelectorAll( '[data-k-ads]' );

	if ( ! spaces.length ) {
		return;
	}

	var cfg = window.kassmtvAds || {};
	var today = new Date();
	var pad = function ( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	};
	var todayStr = today.getFullYear() + '-' + pad( today.getMonth() + 1 ) + '-' + pad( today.getDate() );
	var counted = {};

	function countView( id ) {
		if ( ! id || counted[ id ] || ! cfg.viewUrl || ! window.fetch ) {
			return;
		}

		counted[ id ] = true;
		fetch( cfg.viewUrl + id, { method: 'POST', keepalive: true, credentials: 'omit' } ).catch( function () {} );
	}

	spaces.forEach( function ( space ) {
		var items = Array.prototype.slice.call( space.querySelectorAll( '[data-k-ad]' ) ).filter( function ( item ) {
			var end = item.getAttribute( 'data-k-ad-end' );
			var ok = ! end || end >= todayStr;

			if ( ! ok ) {
				item.remove();
			}

			return ok;
		} );

		if ( ! items.length ) {
			space.remove();
			return;
		}

		var pick = items[ Math.floor( Math.random() * items.length ) ];

		items.forEach( function ( item ) {
			item.hidden = item !== pick;
		} );

		var id = pick.getAttribute( 'data-k-ad' );

		// Count a view only when at least half the ad is on screen.
		if ( 'IntersectionObserver' in window ) {
			var seen = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						countView( id );
						seen.disconnect();
					}
				} );
			}, { threshold: 0.5 } );

			seen.observe( pick );
		} else {
			countView( id );
		}
	} );
}() );
