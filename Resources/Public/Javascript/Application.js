jQuery(document).ready(function($) {
	var viewingClass = $('#viewing-classname').val();
	var touched = false;
	$('#extensionSelector, #versionSelector').change(function() {
		document.location = $(this).find(':selected').attr('data-url');
		return false;
	});
	$('#viewhelper-filter').keyup(function() {
		var q = $(this).val();
		if (typeof q == 'string') {
		if (q.length == 0 && touched) {
			$('.viewhelper-group-tree').hide();
			$('.viewhelper-group').show();
			$('.viewhelper-group a').each(function() {
				if ($(this).name == activeLink.name) {
					expandGroup($(this));
				};
				$(this).parents('li:first').show();
			});
			$('.viewhelper-group').each(function() {
				if ($(this).find('li.active').length == 0) {
					collapseGroup($(this));
				};
			});
		} else if (q.length >= 1) {
			touched = true;
			$('.viewhelper-group').each(function() {
				var group = $(this);
				var toggle = group.find('.viewhelper-group-toggle:first');
				var groupTree = group.find('.viewhelper-group-tree:first');
				groupTree.find('a').each(function() {
					var link = $(this);
					if (link.attr('id').indexOf(q) >= 0) {
						expandGroup(group);
						link.parents('li:first').show();
					} else {
						link.parents('li:first').hide();
					};
				});
				if (group.find('li:visible').length == 0) {
					collapseGroup(group);
					group.hide();
				};
			});
		};
	};
	}).blur(function() {
		if ($(this).val().length < 1) {
			touched = false;
		};
	}).tooltip({
		'title': 'Tip: Use TAB for quick access',
		'trigger': 'hover'
	});
	$('.viewhelper-group').each(function() {
		var element = $(this);
		var tree = element.find('.viewhelper-group-tree:first');
		var toggle = element.find('.viewhelper-group-toggle:first');
		toggle.find('.folder-icon').removeClass('icon-folder-open').addClass('icon-folder-close');
		toggle.click(function() {
			if (tree.hasClass('open')) {
				collapseGroup(element, 250);
			} else {
				expandGroup(element, 250);
			};
		});
		tree.find('> li > a').each(function() {
			var link = $(this);
			var parent = link.parents('.viewhelper-group:first');
			if (link.attr('id') == viewingClass) {
				link.parents('li:first').addClass('active');
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
		var tree = element.find('.viewhelper-group-tree:first');
		var toggle = element.find('.viewhelper-group-toggle:first');
		toggle.find('.folder-icon').addClass('icon-folder-close').removeClass('icon-folder-open');
		if (speed > 0) {
			toggle.animate({'color': '#333'}, speed);
			tree.slideUp(speed, function() {
				tree.removeClass('open');
			});
		} else {
			toggle.css({'color': '#33'});
			tree.hide();
		};
		element.find('.viewhelper-group').each(collapseGroup);
	};
	function expandGroup(element, speed) {
		if (typeof speed == 'undefined') {
			speed = 0;
		};
		if (typeof element.find == 'undefined') {
			var element = $(this);
		};
		var tree = element.find('.viewhelper-group-tree:first');
		var toggle = element.find('.viewhelper-group-toggle:first');
		toggle.find('.folder-icon').addClass('icon-folder-open').removeClass('icon-folder-close');
		if (speed > 0) {
			toggle.animate({'color': '#08C'}, speed);
			tree.slideDown(speed, function() {
				tree.addClass('open');
			});
		} else {
			toggle.css({'color': '#08C'});
			tree.show();
		};
		element.parents('.viewhelper-group:not(.root)').each(expandGroup);
	};
});
