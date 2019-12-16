(function($){

	$.UiComponentButton = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentButton', base);

		var title = $('> .title', base.el);
		var badge = $('> .badge', base.el);
		var icon = $('> .UiComponentIcon', base.el);

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentButton.defaults, options);

			$(base.el).addClass('UiComponentButton');

			if (o.style)
				base.addStyle(o.style);

			if (o.additionalCssClasses)
				base.addStyle(o.additionalCssClasses);

			if (o.title)
				base.setTitle(o.title);
			
			if (o.tooltip)
				base.setTooltip(o.tooltip);
			
			if (o.badge)
				base.setBadge(o.badge);

			if (o.iconName)
				base.setIcon(o.iconName, o.iconVariant, o.iconIsLeft);

			if (o.isTransparent)
				base.addStyle('transparent');
			
			if (o.isInactive)
				base.setInactive();

			if (o.isCentered)
				$(base.el).wrap('<div class="UiComponentCenteringWrapper' + (o.isIsolated ? ' isolated' : '') + '"></div>');
			
			if (o.isIsolated)
				if (!o.isCentered)
					$(base.el).addStyle('isolated');

			$(base.el).on('utap', function() {
				base.click();
			});
		}

		base.setTitle = function(newTitle) {
			$(title).remove();
			$(base.el).append('<div class=title>' + newTitle + '</div>');
			title = $('> .title', base.el);
		}

		base.setTooltip = function(tooltip) {
			$(base.el).attr('title', tooltip);
		}

		base.setBadge = function(newBadge, style) {
			$(badge).remove();
			$(base.el).append('<div class="badge' + (style ? ' ' + style : '') + '">' + newBadge + '</div>');
			badge = $('> .badge', base.el);
		}

		base.removeBadge = function() {
			$(badge).fadeOut();
		}

		base.setLoadingPercent = function(percent) {
			if (percent >= 100) {
				$('> .loadingPercent', base.el).fadeOut();
				base.removeBadge();
				return;
			}
			base.setBadge(percent, 'centered');
			if (!$('> .loadingPercent', base.el).length)
				$(base.el).append('<div class=loadingPercent></div>');
			$('> .loadingPercent', base.el).css('height', percent + '%');
		}

		base.unsetLoadingPercent = function() {
			base.removeBadge();
			$('> .loadingPercent', base.el).remove();
		}

		base.setIcon = function(iconName, iconVariant = false, isLeft = true) {
			$(icon).remove()

			if (!iconName)
				return;

			if (!iconVariant)
				iconVariant = o.iconVariant;

			if (isLeft)
				$(base.el).prepend('<div class=\"UiComponentIcon ' + iconName + ' ' + iconVariant + '\"></div>');
			else
				$(base.el).append('<div class=\"UiComponentIcon ' + iconName + ' ' + iconVariant + '\"></div>');
			icon = $('> .UiComponentIcon', base.el);
		}

		base.removeIcon = function() {
			if (!icon)
				return;
			$(icon).remove();
		}

		base.addStyle = function(style) {
			$(base.el).addClass(style);
		}

		base.removeStyle = function(style) {
			$(base.el).removeClass(style);
		}

		base.clearStyle = function() {
			$(base.el).removeClass().addClass('UiComponentButton');
		}

		base.setLoading = function() {
			base.unsetAll();
			base.addStyle('loading');
			if (icon)
				$(icon).addClass('loading');
		}

		base.unsetLoading = function() {
			base.removeStyle('loading');
			if (icon)
				$(icon).removeClass('loading');
		}

		base.setUploading = function() {
			base.unsetAll();
			base.addStyle('uploading');
			if (icon)
				$(icon).addClass('uploading');
		}

		base.unsetUploading = function() {
			base.removeStyle('uploading');
			if (icon)
				$(icon).removeClass('uploading');
		}

		base.setWorking = function() {
			base.unsetAll();
			base.addStyle('working');
			if (icon)
				$(icon).addClass('working');
		}

		base.unsetWorking = function() {
			base.removeStyle('working');
			if (icon)
				$(icon).removeClass('working');
		}

		base.unsetAll = function() {
			base.unsetLoading();
			base.unsetUploading();
			base.unsetWorking();
		};

		base.setInactive = function() {
			base.addStyle('inactive');
		}

		base.unsetInactive = function() {
			base.removeStyle('inactive');
		}

		base.click = function(isOverrideConfirmationMessage) {
			if (o.confirmationMessage && !isOverrideConfirmationMessage) {
				$('#UiComponentPopup').UiComponentPopup('open', [
					{
						content: o.confirmationMessage,
						UiComponentButtons: [
							{
								title: 'Ok',
								iconName: 'ok',
								iconVariant: 'white',
								onClick: function() { $('#UiComponentPopup').UiComponentPopup('close'); base.click(true); }
							},
							{
								isCancel: true,
								iconName: 'cancel',
								iconVariant: 'white'
							}
						]
					},
					'styleQuestion'
				]);
				return;
			}

			if (o.href) {
				if (o.isNewWindow)
					window.open(o.href);
				else
				if (o.target)
					window.open(o.href, o.target);
				else
					document.location = o.href;
			}
			else
			if (o.ajaxUrl) {
				base.setLoading();
				ajaxQuery(
					o.ajaxUrl,
					{
						data: o.ajaxData,
						onSuccess: function() {
							if (o.ajaxOnSuccess)
								o.ajaxOnSuccess(base.el);
							base.unsetLoading();
						},
						onError: function() {
							if (o.ajaxOnError)
								o.ajaxOnError(base.el);
							base.unsetLoading();
						}
					}
				);
			}
			else
			if (typeof o.onClick === "function")
				o.onClick(base.el);
			else
			if (o.onClick != null)
				eval(o.onClick);
		}

		base.setOnClick = function(onClick) {
			o.onClick = onClick;
		}

		base.done = function() {
			base.unsetLoading();
		}

		base.init();
	}

	$.UiComponentButton.defaults = {
		style: false,
		additionalCssClasses: false,
		title: false,
		tooltip: false,
		badge: false,
		iconName: false,
		iconIsLeft: true,
		iconVariant: 'white',
		isTransparent: false,
		isInactive: false,
		onClick: false,
		href: false,
		target: false,
		ajaxUrl: false,
		ajaxData: false,
		ajaxOnSuccess: false,
		ajaxOnError: false,
		isNewWindow: false,
		isCentered: false,
		isIsolated: false,
		confirmationMessage: false
	};

	$.fn.UiComponentButton = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentButton');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentButton(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);