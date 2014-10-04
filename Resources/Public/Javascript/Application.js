jQuery(document).ready(function($) {
	var viewingClass = $('#viewing-classname').val();
	var timer;
	$('#extensionSelector, #versionSelector').change(function() {
		document.location = $(this).find(':selected').attr('data-url');
		return false;
	});
	function search() {
		var q = $('#viewhelper-filter').val().toLowerCase();
		if (typeof q == 'string') {
			$('.viewhelper-group:not(.root)').each(expandGroup).hide();
			$('.viewhelper-group a').parent().show();
			if (q.length == 0) {
				$('.viewhelper-group').show().not('.root').each(collapseGroup);
				expandGroup($('.viewhelper-group li.active').parents('.viewhelper-group:first'));
			} else if (q.length >= 1) {
				$('.viewhelper-group.root a').each(function() {
					var link = $(this);
					var group = link.parents('.viewhelper-group:first');
					link.parent().hide();
					if (link.attr('id').toLowerCase().indexOf(q) >= 0) {
						expandGroup(group);
						group.show();
						link.parent().show();
					};
				});
			};
		};
	};
	$('#viewhelper-filter').keyup(function() {
		clearInterval(timer);
		timer = setTimeout(search, 100);
	}).tooltip({
		'title': 'Tip: Use TAB for quick access',
		'trigger': 'hover'
	});
	$('.viewhelper-group').not('.root').each(function() {
		var element = $(this);
		var tree = element.find('.viewhelper-group-tree:first');
		var toggle = element.find('.viewhelper-group-toggle:first');
		toggle.find('.folder-icon').removeClass('glyphicon-folder-open').addClass('glyphicon-folder-close');
		toggle.click(function() {
			if (tree.hasClass('open')) {
				collapseGroup(element, 250);
			} else {
				expandGroup(element, 250);
			};
		});
		tree.find('a.file').each(function() {
			var link = $(this);
			var parent = link.parents('.viewhelper-group:first');
			if (link.attr('id') == viewingClass) {
				link.parent().addClass('active');
				expandGroup(element);
			};
		});
	});
	function collapseGroup(element, speed) {
		if (typeof speed == 'undefined') {
			speed = 0;
		};
		if (typeof element.find == 'undefined') {
			var element = $(this);
		};
		var toggle = element.find('.viewhelper-group-toggle:first');
		var tree = element.find('.viewhelper-group-tree:first');
		if (speed > 0) {
			tree.slideUp(speed, function() {
				tree.removeClass('open');
			});
		} else {
			tree.hide();
			tree.removeClass('open');
		};
		toggle.find('.folder-icon').addClass('glyphicon-folder').removeClass('glyphicon-folder-open');
		element.find('.viewhelper-group').each(collapseGroup);
		element.removeClass('open');
	};
	function expandGroup(element, speed) {
		if (typeof speed == 'undefined') {
			speed = 0;
		};
		if (typeof element.find == 'undefined') {
			var element = $(this);
		};
		var toggle = element.find('.viewhelper-group-toggle:first');
		toggle.find('li').show();
		var tree = element.find('.viewhelper-group-tree:first');
		if (speed > 0) {
			tree.slideDown(speed, function() {
				tree.addClass('open');
			});
		} else {
			tree.show();
			tree.addClass('open');
		};
		toggle.find('.folder-icon').addClass('glyphicon-folder-open').removeClass('glyphicon-folder');
		var parentGroups = element.parents('.viewhelper-group').not('.root');
		if (0 < parentGroups.length) {
			expandGroup(parentGroups.first());
		};
		element.addClass('open').show();
	};
});
