// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

"use strict";

/* l10n */
if (document.getElementById('jsonLang')) {
	var BTlang = JSON.parse(document.getElementById('jsonLang').textContent);
}


Date.prototype.ymdhis = function() {
	var y = this.getFullYear();
	var m = ("00" + (this.getMonth() + 1)).slice(-2); // 0-11
	var d = ("00" + (this.getDate())).slice(-2);
	//var h = ("00" + (this.getHours())).slice(-2);
	//var i = ("00" + (this.getMinutes())).slice(-2);
	//var s = ("00" + (this.getSeconds())).slice(-2);

	return "".y + m + d;
}

/* date from YYYYMMDDHHIISS format */
Date.dateFromYMDHIS = function(d) {
 	var d = new Date(d.toString().substr(0, 4), d.toString().substr(4, 2) - 1, d.toString().substr(6, 2), d.toString().substr(8, 2), d.toString().substr(10, 2), d.toString().substr(12, 2));
	return d;
}

/*
	menu icons : onclick.
*/

// close already open menus, but not the current menu
function closeOpenMenus(target) {
	// close already open menus, but not the current menu
	var openMenu = document.querySelectorAll('#top > .visible');
	for (var i=0, len=openMenu.length ; i<len ; i++) {
		if (!openMenu[i].contains(target)) openMenu[i].classList.remove('visible');
	}
}

// add "click" listeners on the list of menus
['nav', 'nav-acc', 'notif-icon'].forEach(function(elem) {
	document.getElementById(elem).addEventListener('click', function(e) {
		closeOpenMenus(e.target);
		var menu = document.getElementById(elem);
		if (this === (e.target)) menu.classList.toggle('visible');
		window.addEventListener('click', function(e) {
			var openMenu = document.querySelectorAll('#top > .visible');
			// no open menus: abord
			if (!openMenu.length) return;
			// open menus ? close them.
			else closeOpenMenus(null);
		}, {once: true});

		e.stopPropagation();
	});
});


/*
	cancel button on forms.
*/
function goToUrl(pagecible) {
	window.location = pagecible;
}

/*
	On article or comment writing: insert a BBCode Tag or a Unicode char.
*/

function insertTag(e, startTag, endTag) {
	var seekField = e;
	while (!seekField.classList.contains('formatbut')) {
		seekField = seekField.parentNode;
	}
	while (!seekField.tagName || seekField.tagName != 'TEXTAREA') {
		seekField = seekField.nextSibling;
	}

	var field = seekField;
	var scroll = field.scrollTop;
	field.focus();
	var startSelection   = field.value.substring(0, field.selectionStart);
	var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
	var endSelection     = field.value.substring(field.selectionEnd);
	if (currentSelection == "") { currentSelection = "TEXT"; }
	field.value = startSelection + startTag + currentSelection + endTag + endSelection;
	field.focus();
	field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
	field.scrollTop = scroll;
}

function insertChar(e, ch) {
	var seekField = e;
	while (!seekField.classList.contains('formatbut')) {
		seekField = seekField.parentNode;
	}
	while (!seekField.tagName || seekField.tagName != 'TEXTAREA') {
		seekField = seekField.nextSibling;
	}

	var field = seekField;

	var scroll = field.scrollTop;
	field.focus();

	var bef_cur = field.value.substring(0, field.selectionStart);
	var aft_cur = field.value.substring(field.selectionEnd);
	field.value = bef_cur + ch + aft_cur;
	field.focus();
	field.setSelectionRange(bef_cur.length + ch.toString.length +1, bef_cur.length + ch.toString.length +1);
	field.scrollTop = scroll;
}

/*
	Used in file upload: converts bytes to kB, MB, GB…
*/
function humanFileSize(bytes) {
	var e = Math.log(bytes)/Math.log(1e3)|0,
	nb = (e, bytes/Math.pow(1e3,e)).toFixed(1),
	unit = (e ? 'KMGTPEZY'[--e] : '') + 'B';
	return nb + ' ' + unit
}



/*
	in page maintenance : switch visibility of forms.
*/

function switch_form(activeForm) {
	var form_export = document.getElementById('form_export');
	var form_import = document.getElementById('form_import');
	var form_optimi = document.getElementById('form_optimi');
	form_export.style.display = form_import.style.display = form_optimi.style.display = 'none';
	document.getElementById(activeForm).style.display = 'block';
}

function switch_export_type(activeForm) {
	var e_zip = document.getElementById('e_zip');
	e_zip.style.display = 'none';
	document.getElementById(activeForm).style.display = 'block';
}

function hide_forms(blocs) {
	var radios = document.getElementsByName(blocs);
	var e_zip = document.getElementById('e_zip');
	var checked = false;
	for (var i = 0, length = radios.length; i < length; i++) {
		if (!radios[i].checked) {
			var cont = document.getElementById('e_'+radios[i].value);
			while (cont.firstChild) {cont.removeChild(cont.firstChild);}
		}
	}
}




/**************************************************************************************************************************************
	*********        ****          ****
	***********    ********      ********
	***     ***  ***      ***  ***      ***
 	***     ***  ***           ***
	**********    **********    **********
	********      **********    **********
	***  ***              ***           ***
	***   ***    ***      ***  ***      ***
	***    ***     ********      ********
	***     ***      ****          ****

	RSS PAGE HANDLING
**************************************************************************************************************************************/

// animation loading (also used in images wall/slideshow)
function loading_animation(onoff) {
	var notifNode = document.getElementById('counter');
	if (onoff == 'on') {
		notifNode.style.display = 'inline-block';
	}
	else {
		notifNode.style.display = 'none';
	}
	return false;
}

function RssReader() {
	var _this = this;

	// hasUpdated flag
	this.hasUpdated = false;

	/***********************************
	** Some properties & misc actions
	*/
	// init JSON List
	//var theJSON = JSON.parse(document.getElementById('json_rss').textContent);
	//this.feedList = theJSON.posts;
	//this.siteList = theJSON.sites;
	this.feedList = new Array();
	this.siteList = new Array();

	// init local "mark as read" buffer
	this.readQueue = {"count": "0", "urlList": []};

	// get some DOM elements
	this.notifNode = document.getElementById('message-return');
	this.domPage = document.getElementById('page');
	this.postsList = document.getElementById('post-list');
	this.feedsList = document.getElementById('feed-list');

	// get edit-popup template
	this.editFeedPopupTemplate = document.getElementById('popup-wrapper').parentNode.removeChild(document.getElementById('popup-wrapper'));
	this.editFeedPopupTemplate.removeAttribute('hidden');

	// get post/folder/site templates
	this.postTemplate = this.postsList.removeChild(this.postsList.firstElementChild);                 this.postTemplate.removeAttribute('hidden');
	this.siteTemplate = this.feedsList.removeChild(this.feedsList.querySelector('.feed-site'));       this.siteTemplate.removeAttribute('hidden');
	this.folderTemplate = this.feedsList.removeChild(this.feedsList.querySelector('.feed-folder'));   this.folderTemplate.removeAttribute('hidden');

	// init the « open-all » toogle-button.
	this.openAllButton = document.getElementById('openallitemsbutton');
	this.openAllButton.addEventListener('click', function(){ _this.openAll(); });

	// init the « list-all / list-favs / filst-today » events
	this.feedsList.querySelectorAll('.special > ul > li').forEach(function(li) {
		li.addEventListener('click', function(e) { _this.sortElements(e); });
	});

	// init the « hide feed-list » button
	document.getElementById('hide-side-nav').addEventListener('click', function(){ _this.feedsList.classList.toggle('hidden-list'); });

	// init the « mark as read » button.
	document.getElementById('markasread').addEventListener('click', function(){ _this.markAsRead(); });

	// init the « refresh all » button event
	document.getElementById('refreshFeeds').addEventListener('click', function(e){ _this.refreshAllFeeds(e); });

	// init the « reload JSON » button event
	document.getElementById('reloadFeeds').addEventListener('click', function(e){ _this.reloadJsonData(e); });

	// init the « delete old » button
	document.getElementById('deleteOld').addEventListener('click', function(){ _this.deleteOldFeeds(); });

	// init the « add new feed » button
	document.getElementById('fab').addEventListener('click', function(){ _this.addNewFeed(); });


	// Global Page listeners
	// onkeydown : detect "open next/previous" action with keyboard
	window.addEventListener('keydown', function(e) {
		_this.kbActionHandle(e);
	});

	// beforeunload : to send a "mark as read" request before unloading the page
	window.addEventListener("beforeunload", function() {
		if (_this.readQueue.urlList.length == 0) return true;
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-read', 'postlist');
		formData.append('mark-as-read-data', JSON.stringify(_this.readQueue.urlList));
		navigator.sendBeacon('rss.ajax.php', formData);
	});

	// if the tab is hidden (or if the browser is hidden (on mobile): send a "mark as read" request)
	document.addEventListener("visibilitychange", function() {
		if (document.visibilityState == 'hidden' && _this.readQueue.urlList.length !== 0) {
			_this.markAsReadXHR('postlist', JSON.stringify(_this.readQueue.urlList));
		}
	});

	// allows a "popup close" when the user goes back 1 time in history (esp. on Android)
	window.addEventListener("popstate", function(e) {
		_this.closePopup();
	});

	// built page
	window.addEventListener("load", function() {
		_this.reloadJsonData();
		//_this.rebuiltPostsTree();
		//_this.rebuiltSitesTree();
	});



	/***********************************
	** The HTML builder methods
	*/

	// builts the whole list of posts.
	this.rebuiltPostsTree = function() {
		// empties the actual list
		while (this.postsList.firstChild) {
			 this.postsList.removeChild(this.postsList.firstChild);
		}
		var countPosts = this.feedList.length;
		if (0 === countPosts) return false;

		var liList = document.createDocumentFragment();

		var DateTimeFormat = new Intl.DateTimeFormat('UTC', {year: "numeric", weekday: "short", month: "short", day: "numeric", hour: "numeric", minute: "numeric"}); //时间格式化接口

		// populates the new list
		this.feedList.forEach(function(item) {

			var li = _this.postTemplate.cloneNode(true);

			li.id = 'i_'+item.id;
			li.setAttribute('data-id', item.id);
			li.setAttribute('data-folder', item.folder);
			li.setAttribute('data-datetime', item.datetime);
			li.setAttribute('data-sitehash', item.feedhash);
			li.setAttribute('data-is-fav', item.fav);
			if (0 === item.statut) { li.classList.add('read'); }
			li.querySelector('.post-head > .site').textContent = item.sitename;
			li.querySelector('.post-head > .folder').textContent = item.folder;
			li.querySelector('.post-head > .post-title').href = item.link;
			li.querySelector('.post-head > .post-title').title = item.title;
			li.querySelector('.post-head > .post-title').textContent = item.title;
			li.querySelector('.post-head > .share > .lien-share').href = 'links.php?url='+encodeURIComponent(item.link);
			li.querySelector('.post-head > .share > .lien-open').href = item.link;
			li.querySelector('.post-head > .share > .lien-mail').href = 'mailto:?&subject='+ encodeURIComponent(item.title) + '&body=' + encodeURIComponent(item.link);
			li.querySelector('.post-head > .date').textContent = DateTimeFormat.format(Date.dateFromYMDHIS(item.datetime));

			li.querySelector('.post-head > .post-title').addEventListener('click', function(e){ if(!_this.openThisItem(item)) e.preventDefault(); } );
			li.querySelector('.post-head > .lien-fav').addEventListener('click', function(e){ _this.markAsFav(item); e.preventDefault(); } );
			
			li.querySelector('.rss-item-content').appendChild(document.createComment(item.content));
			liList.appendChild(li);
		});

		this.postsList.appendChild(liList);

		// displays the number of items (local counter)
		var count = document.querySelector('#post-counter');
		count.textContent = countPosts;

		return false;
	}

	// builts the whole list of sites
	this.rebuiltSitesTree = function() {
		// remove existing entries (if any)
		this.feedsList.querySelectorAll(':scope > li:not(.special)').forEach(function (li) {
			li.parentNode.removeChild(li);
		});

		var ulList = document.createDocumentFragment();

		// populates the new list
		this.siteList.forEach(function(item) {

			var li = _this.siteTemplate.cloneNode(true);
			li.style.backgroundImage = "url(favatar.php?w=favicon&q="+((new URL(item.link)).hostname)+')';
			li.setAttribute('data-nbrun', item.nbrun);
			li.setAttribute('data-feed-hash', item.id);
			if (0 !== item.iserror) { li.classList.add('feed-error'); }
			li.appendChild(document.createTextNode(item.title));

			li.addEventListener('click', function(e) { _this.sortElements(e); });
			li.querySelector(':scope > button').addEventListener('click', function(e) { e.stopPropagation(); _this.showFeedEditPopup(item); });

			if ("" !== item.folder) {
				// check if folder UL already exists
				var folderUl = ulList.querySelector('li[data-folder="'+item.folder+'"]');
				if (!folderUl) {
					// if not create it
					var folderUl = _this.folderTemplate.cloneNode(true);
					folderUl.addEventListener('click', function(e) { _this.sortElements(e); });
					folderUl.querySelector('.unfold').addEventListener('click', function(e) { 
						e.stopPropagation();
						this.parentNode.classList.toggle('open');
					 } ) ;

					folderUl.setAttribute('data-folder', item.folder);
					folderUl.setAttribute('data-nbrun', 0);
					folderUl.insertBefore(document.createTextNode(item.folder), folderUl.firstElementChild);

					var beforeNode = ulList.firstChild;

					// place new folder such as forders get sorted.
					while (beforeNode && beforeNode.classList.contains('feed-folder')) {
						if (beforeNode.getAttribute('data-folder') < item.folder) {
							beforeNode = beforeNode.nextElementSibling;
						} else break;
					}

					ulList.insertBefore(folderUl, beforeNode);

				}
				// if exists, append site to folder
				folderUl.querySelector('ul').appendChild(li);

				folderUl.setAttribute('data-nbrun', parseInt(folderUl.getAttribute('data-nbrun'), 10)+parseInt(item.nbrun, 10));

			}
			// else, append to normal list
			else {
				ulList.appendChild(li);
			}
		});
		this.feedsList.appendChild(ulList);
	
		return false;
	}

	/************************************
	** Methos to handle popup
	*/

	// show the "edit feed" popup
	this.showFeedEditPopup = function (item) {

		// new popup
		var popupWrapper = this.editFeedPopupTemplate.cloneNode(true);
		popupWrapper.querySelector('.popup-edit-feed').id = 'popup';
		var popup = popupWrapper.querySelector('#popup');
		popup.removeAttribute('hidden');

		// this allows closing the popup with the "back" button (espacially on Android)
		if (history.state === null) history.pushState({'popupOpen': true}, null, window.location.pathname + '#popup');

		popupWrapper.addEventListener('click', function(e) {
			// clic is on wrapper (back drop) but not the popup
			if (e.target == this) {
				_this.closePopup();
			}
		});

		document.body.classList.add('noscroll');

		popup.querySelector('.feed-content-error').textContent = item.iserror || '';
		popup.querySelector('.feed-content-lastpost > time').textContent = Date.dateFromYMDHIS(item.time).toLocaleDateString('UTC', {weekday: "long", year: "numeric", month: "long", day: "numeric", hour: "numeric", minute: "numeric"});
		popup.querySelector('.feed-content input[name="feed-url"]').value = item.link;
		popup.querySelector('.feed-content input[name="feed-url"]').style.backgroundImage = "url(../../favatar.php?w=favicon&q="+((new URL(item.link)).hostname)+')';
		popup.querySelector('.feed-content input[name="feed-title"]').value = item.title;
		popup.querySelector('.feed-content input[name="feed-folder"]').value = item.folder;

		popup.querySelector('.popup-title > .button-cancel').addEventListener('click', function() {
			_this.closePopup();
		});

		popup.querySelector('.feed-footer > .button-submit').addEventListener('click', function() {
			_this.saveEditFeed(item);
			_this.closePopup();
		});

		popup.querySelector('.feed-footer > .button-delete').addEventListener('click', function() {
			if (!window.confirm(BTlang.questionSupprFlux)) { return false; }
			_this.deleteFeed(item);
			_this.closePopup();
		});

		this.domPage.appendChild(popupWrapper);
	}


	// close actual popup
	this.closePopup = function() {
		var popupWrapper = document.getElementById('popup-wrapper');
		if (popupWrapper) popupWrapper.parentNode.removeChild(popupWrapper);
		document.body.classList.remove('noscroll');
	}

	/***********************************
	** Methods to "open" elements (all, one, next…)
	*/
	// open ALL the items
	this.openAll = function() {
		var posts = this.postsList.querySelectorAll('li:not([hidden])');

		if (!this.openAllButton.classList.contains('unfold')) {
			posts.forEach(function(post) {
				post.classList.add('open-post');
				var content = post.querySelector('.rss-item-content');
				if (content.childNodes[0] && content.childNodes[0].nodeType == 8) {
					content.innerHTML = content.childNodes[0].data;
				}
			});
			this.openAllButton.classList.add('unfold');
		} else {
			posts.forEach(function(post) {
				post.classList.remove('open-post');
			});
			this.openAllButton.classList.remove('unfold');
		}
		return false;
	}

	// open clicked item
	this.openThisItem = function(item) {
		var post = this.postsList.querySelector('li[data-id="'+item.id+'"]');
		if (post.classList.contains('open-post')) { return true; }

		// close previously opened posts
		this.postsList.querySelectorAll('.open-post').forEach(function(post) {
			post.classList.remove('open-post');
		});		
		this.openAllButton.classList.remove('unfold');

		// opens this post
		post.classList.add('open-post');

		// unveil the content
		var content = post.querySelector('.rss-item-content');
		if (content.childNodes[0].nodeType == 8) {
			content.innerHTML = content.childNodes[0].data;
		}

		// jump to post (anchor + 120px)
		var rect = post.getBoundingClientRect();
		var isVisible = ( (rect.top < 144) || (rect.bottom > window.innerHeight) ) ? false : true ;
		if (!isVisible) {
			window.location.hash = post.id;
			window.scrollBy(0, -144);
		}

		// mark as read in DOM and saves for mark as read in DB
		if (!post.classList.contains('read')) {
			this.markAsReadPost(item);
			post.classList.add('read');
		}
		return false;
	}

	// handle keyboard actions
	this.kbActionHandle = function(e) {
		// down
		if (e.keyCode == '40' && e.ctrlKey) {
			e.preventDefault();

			// first post to open
			var toOpenPost = this.postsList.querySelector('li.open-post ~ li:not([hidden])');
			// ... or first post if none are open
			if (!toOpenPost) { var toOpenPost = this.postsList.querySelector('li:not([hidden])'); }
			// ... or return if no post in list
			if (!toOpenPost) return false;

			// find item
			var item = this.feedList.find(function(i) {
				return (i.id == toOpenPost.dataset.id);
			});

			this.openThisItem(item);
		}
		// up
		if (e.keyCode == '38' && e.ctrlKey) {
			e.preventDefault();
			// actually open post
			var theOpenPost = this.postsList.querySelector('li.open-post');
			// ... or return if no open post yet
			if (!theOpenPost) return false;
			// finds the previous non-hidden post
			while (theOpenPost.previousSibling && theOpenPost.previousSibling.hasAttribute('hidden')) {
				theOpenPost = theOpenPost.previousSibling;
			}

			if (theOpenPost.previousSibling) {
				toOpenPost = theOpenPost.previousSibling;

				// find item
				var item = this.feedList.find(function(i) {
					return (i.id == toOpenPost.dataset.id);
				});

				this.openThisItem(item);
			}
		}
	}


	/***********************************
	** Method to "sort" elements (by site, folder, favs…)
	*/

	this.sortElements = function (e) {
		// prevent a clic on a "site" to go to a parent "folder"
		e.stopPropagation();

		// sort all feeds
		if (e.target.classList.contains('all-feeds')) {
			this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
				post.classList.remove('open-post');
				post.removeAttribute('hidden');
			});
		}

		// sort by site
		else if (e.target.classList.contains('feed-site')) {
			var theSite = e.target.getAttribute('data-feed-hash');
			this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
				post.classList.remove('open-post');
				if (post.getAttribute('data-sitehash') === theSite) {
					post.removeAttribute('hidden');
				} else {
					post.setAttribute('hidden', '');
				}
			});
		}

		// sort by folder
		else if (e.target.classList.contains('feed-folder')) {
			var theFolder = e.target.getAttribute('data-folder');
			this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
				post.classList.remove('open-post');
				if (post.getAttribute('data-folder') === theFolder) {
					post.removeAttribute('hidden');
				} else {
					post.setAttribute('hidden', '');
				}
			});
		}

		// sort favs
		else if (e.target.classList.contains('fav-feeds')) {
			this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
				post.classList.remove('open-post');
				if (post.getAttribute('data-is-fav') == 1) {
					post.removeAttribute('hidden');
				} else {
					post.setAttribute('hidden', '');
				}
			});
		}

		// sort today
		else if (e.target.classList.contains('today-feeds')) {

			this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
				post.classList.remove('open-post');
				var d = new Date();
				var ymd000 = '' + d.getFullYear() + ('0' + (d.getMonth()+1)).slice(-2) + ('0' + d.getDate()).slice(-2) + '000000';

				if (post.getAttribute('data-datetime') >= ymd000) {
					post.removeAttribute('hidden');
				} else {
					post.setAttribute('hidden', '');
				}
			});
		}

		if (this.feedsList.querySelector('.active-site')) {
			this.feedsList.querySelector('.active-site').classList.remove('active-site');
		}

		e.target.classList.add('active-site');
		window.location.hash = '';
		this.openAllButton.classList.remove('unfold');
		this.feedsList.classList.remove('hidden-list'); // on mobile: hide the sites 
	}


	/***********************************
	** Methods to "mark as read" item in the local list and on screen
	*/
	this.markAsRead = function() {
		var markWhat = document.querySelector('.active-site');

		// Mark ALL as read.
		if (markWhat.classList.contains('all-feeds')) {
			// for "all" feeds, ask confirmation
			if (!confirm("Tous les éléments seront marqués comme lus ?")) { // TODO : $lang
				return false;
			}
			// send XHR
			if (!this.markAsReadXHR('all', 'all')) return false;

			// mark items as read in list
			this.feedList.forEach(function(item) {
				item.statut = 0;
			});

		}

		// Mark one FOLDER as read
		else if (markWhat.classList.contains('feed-folder')) {
			var folder = markWhat.dataset.folder;

			// send XHR
			if (!this.markAsReadXHR('folder', folder)) return false;

			// mark 0 for that folder
			markWhat.dataset.nbrun = 0;

			// mark 0 for the sites in that folder
			markWhat.querySelectorAll('li.feed-site:not([data-nbrun="0"])').forEach(function(site) {
				site.dataset.nbrun = 0;
			});

			// mark items as "read" in list
			for (var i = 0, len = this.feedList.length ; i < len ; i++) {
				if (this.feedList[i].folder == folder) this.feedList[i].statut = 0;
			}
		}

		// else… mark one SITE as read
		else if (markWhat.classList.contains('feed-site')) {
			var siteHash = markWhat.dataset.feedHash;

			// send XHR
			if (!this.markAsReadXHR('site', siteHash)) return false;

			// if site is in a folder, update amount of unread for that folder too
			var parentFolder = markWhat.parentNode.parentNode;
			if (parentFolder.dataset.folder) {
				parentFolder.dataset.nbrun -= markWhat.dataset.nbrun;
			}

			// mark 0 for that sites
			markWhat.dataset.nbrun = 0;

			// mark items as "read" in list
			for (var i = 0, len = this.feedList.length ; i < len ; i++) {
				if (this.feedList[i].feedhash == siteHash) this.feedList[i].statut = 0;
			}

		}

		// mark items as "read" on screen
		this.postsList.querySelectorAll('#post-list > li:not([hidden])').forEach(function(post) {
			post.classList.add('read');
		});

	}

	// This is called when a post is opened (for the first time)
	this.markAsReadPost = function(item) {
		// add thePost to local read posts buffer
		this.readQueue.urlList.push(item.id);
		// if 10 items in queue, send XHR request and reset list to zero.
		if (this.readQueue.urlList.length >= 10) {
			var list = this.readQueue.urlList;
			this.markAsReadXHR('postlist', JSON.stringify(list));
			this.readQueue.urlList = [];
		}

		// mark as read in list
		item.statut = 0;

		// decrement site "unread"
		this.feedsList.querySelector('li[data-feed-hash="'+item.feedhash+'"]').dataset.nbrun -= 1;

		// decrement folder (if any)
		if (item.folder !== "") {
			this.feedsList.querySelector('li[data-folder="'+item.folder+'"]').dataset.nbrun -= 1;
		}
	}


	/***********************************
	** Methods to init and send the XHR request
	*/
	// Mark as read by user input.
	this.markAsReadXHR = function(marType, marWhat) {
		loading_animation('on');
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php', true);

		// onload
		xhr.onload = function() {
			loading_animation('off');
		};

		// onerror
		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-read', marType);
		formData.append('mark-as-read-data', marWhat);
		xhr.send(formData);

		return true;
	}

	// Mark a post a favorite
	this.markAsFav = function(item) {
		loading_animation('on');

		item.fav = 1 - item.fav;
		this.postsList.querySelector('li[data-id="'+item.id+'"]').dataset.isFav = item.fav;

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		// onload
		xhr.onload = function() {
			var resp = this.responseText;
			loading_animation('off');
		};

		// onerror
		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-fav', 1);
		formData.append('url', item.id);
		xhr.send(formData);
	}


	// This requests the server to download the feeds and send the new ones to browser
	// This call is long, also it updates gradually on screen.
	this.refreshAllFeeds = function(e) {
		var _refreshButton = e.target;
		// if refresh ongoing : abbord !
		if (_refreshButton.dataset.refreshOngoing == 1) {
			return false;
		} else {
			_refreshButton.dataset.refreshOngoing = 1;
		}
		// else refresh
		loading_animation('on');

		// prepare XMLHttpRequest
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		// Counts the feeds that have been updated already and displays it like « 10/42 feeds »
		var glLength = 0;
		xhr.onprogress = function() {
			if (glLength != this.responseText.length) {
				var posSpace = (this.responseText.substr(0, this.responseText.length-1)).lastIndexOf(" ");
				_this.notifNode.textContent = this.responseText.substr(posSpace);
				glLength = this.responseText.length;
			}
		}

		// when finished : displays amount of items gotten.
		xhr.onload = function(e) {
			var resp = this.responseText;

			// grep new feeds
			var newJson = JSON.parse(resp.substr(resp.indexOf("Success")+7))
			var newFeeds = newJson.posts;
			this.siteList = newJson.sites

			// update status
			_this.notifNode.textContent = newFeeds.length+' 条订阅更新'; // TODO $[lang]

			// if not empty, add items to list
			if (0 != newFeeds.length) {
				for (var i = 0, len = newFeeds.length ; i < len ; i++) {
					_this.feedList.unshift(newFeeds[len-1-i]); // "len-1-i" for reverse order
				}

				// rebuilt Ul-Li to display the new elements.
				_this.rebuiltPostsTree();
				_this.rebuiltSitesTree();

				// hide all items but the recently added ones
				_this.postsList.querySelectorAll('#post-list > li').forEach(function(post) {
					post.classList.remove('open-post');
					post.setAttribute('hidden', '');

					var item = newFeeds.find(function(i) {
						return (i.id === post.getAttribute('data-id'));
					});
					if (item) post.removeAttribute('hidden');

				});
			}

			_refreshButton.dataset.refreshOngoing = 0;
			loading_animation('off');
		};

		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			_refreshButton.dataset.refreshOngoing = 0;
			loading_animation('off');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('refresh_all', 1);
		xhr.send(formData);
	}

	// This requests the server to send it the latest feeds it has in DB
	this.reloadJsonData = function(e) {
		loading_animation('on');

		if (this.readQueue.urlList.length !== 0) {
			this.markAsReadXHR('postlist', JSON.stringify(this.readQueue.urlList));
		}

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');
		var formData = new FormData();
		formData.append('token', token);
		formData.append('get_initial_data', 1);

		// when finished : builts wall of objects
		xhr.onload = function() {
			var resp = this.responseText;
			resp = (JSON.parse(this.responseText.substr(this.responseText.indexOf("Success")+7)))
			_this.feedList = resp.posts;
			_this.siteList = resp.sites;

			_this.rebuiltPostsTree();
			_this.rebuiltSitesTree();
			loading_animation('off');
		};

		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};

		xhr.send(formData);
	}

	// Method to delete old feeds from DB
	this.deleteOldFeeds = function() {
		if (!confirm("Les vieilles entrées seront supprimées ?")) {
			return false;
		}

		loading_animation('on');
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				_this.notifNode.textContent = BTlang.confirmFeedClean;
			}
			loading_animation('off');
		};
		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('delete_old', 1);
		xhr.send(formData);
	}

	// Method to add a new feed (promt for URL and send to server)
	this.addNewFeed = function() {
		var newLink = window.prompt(BTlang.rssJsAlertNewLink, '');
		// if empty string : stops here
		if (!newLink) return false;
		// ask folder
		var newFolder = window.prompt(BTlang.rssJsAlertNewLinkFolder, '');

		loading_animation('on');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		xhr.onload = function(e) {
			var resp = this.responseText;
			// if error : stops
			if (resp.indexOf("Success") !== -1) {
				_this.notifNode.textContent = '订阅已添加';
			} else {
				_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +resp;
			}
			loading_animation('off');
		};

		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('add-feed', newLink);
		formData.append('add-feed-folder', newFolder);
		xhr.send(formData);
	}

	this.saveEditFeed = function(item) {
		var popup = document.getElementById('popup');
		loading_animation('on');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		var toSaveFeed = new Object();
		toSaveFeed.id = item.id;
		toSaveFeed.folder = popup.querySelector('.feed-content input[name="feed-folder"]').value
		toSaveFeed.title = popup.querySelector('.feed-content input[name="feed-title"]').value
		toSaveFeed.link = popup.querySelector('.feed-content input[name="feed-url"]').value
		toSaveFeed.action = 'edited'

		// make a string out of it
		var feedDataText = JSON.stringify(toSaveFeed);

		xhr.onload = function(e) {
			var resp = this.responseText;
			if (resp.indexOf("Success") != -1) {
				_this.notifNode.textContent = 'FLux édité.';
			} else {
				_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +resp;
			}
			loading_animation('off');

			// update info in list
			var oldFolder = item.folder;
			var oldTitle = item.title;
			item.folder = popup.querySelector('.feed-content input[name="feed-folder"]').value;
			item.title = popup.querySelector('.feed-content input[name="feed-title"]').value;
			item.link =  popup.querySelector('.feed-content input[name="feed-url"]').value;

			// if item has been edited, rebuilt sites/post trees
			if (oldFolder !== item.folder || oldTitle !== item.title) {
				_this.rebuiltSitesTree();
				// todo : change sitename / folder on posts (if changed)
				//_this.rebuiltPostsTree();
			}

			loading_animation('off');
		};

		xhr.onerror = function(e) {
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
			loading_animation('off');
		};
		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('edit-feed-list', feedDataText);
		xhr.send(formData);
	}

	this.deleteFeed = function(item) {
		var popup = document.getElementById('popup');
		loading_animation('on');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'rss.ajax.php');

		var toSaveFeed = new Object();
		toSaveFeed.id = item.id;
		toSaveFeed.action = 'delete'

		// make a string out of it
		var feedDataText = JSON.stringify(toSaveFeed);

		xhr.onload = function(e) {
			var resp = this.responseText;
			// if error : stops
			if (resp.indexOf("Success") == 0) {
				_this.notifNode.textContent = 'FLux supprimé.';
			} else {
				_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +resp;
				loading_animation('off');
			}
	
			// delete feed in list
			_this.siteList.splice(_this.siteList.indexOf(item), 1);

			// delete feed on screen
			var li = _this.feedsList.querySelector('li.feed-site[data-feed-hash="' + item.id + '"]');
			if (li.parentNode.parentNode.dataset.folder) { li.parentNode.parentNode.dataset.nbrun -= item.nbrun; }
			li.parentNode.removeChild(li);

			_this.postsList.querySelectorAll('#post-list > li[data-sitehash="'+item.id+'"]').forEach(function(post) {
				post.parentNode.removeChild(post);
			});
			// todo  :remove posts from list object (and then rebuilt the li.list). On rebuilst, test for "current active site"
			loading_animation('off');
		};

		xhr.onerror = function(e) {
			loading_animation('off');
			// adding notif
			_this.notifNode.textContent = 'Error ' + e.target.status + ' ' +this.responseText;
		};
		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('edit-feed-list', feedDataText);
		xhr.send(formData);
	}

	/**********************
	* Registers service worker (for offline capability)
	*/
	//if ('serviceWorker' in navigator) {
	//	navigator.serviceWorker
	//		.register('service-worker.js')
	//		.then(function() { console.log('Service Worker Registered'); });
	//}
};


