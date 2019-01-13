(function($){

	$.UiComponentPopup = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentPopup', base);

		var timeoutHandler;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentPopup.defaults, options);
			$(base.el).append('<div class="background"></div>');
			$(base.el).append('<div class="windowWrapper"><div class="window"></div></div>');

			$('> .background', base.el).on('click', function(e) {
				base.close();
				e.preventDefault();
			});
		}

		base.setWindowStyle = function(style, width, height) {
			// Removes all classes starting with "style"
			if($('> .windowWrapper > .window', base.el).attr('class')) {
				windowClasses = $('> .windowWrapper > .window', base.el).attr('class').split(' ');
				for(i=0; i<windowClasses.length; i++) {
					if(windowClasses[i].substr(0, 5) == 'style')
						$('> .windowWrapper > .window', base.el).removeClass(windowClasses[i]);
				};
			}

			if (style)
				$('> .windowWrapper > .window', base.el).addClass(style);

			if(!width)
				$('> .windowWrapper > .window', base.el).css('width', 'initial');
			else
				$('> .windowWrapper > .window', base.el).css('width', width);

			if(!height)
				$('> .windowWrapper > .window', base.el).css('height', 'initial');
			else
				$('> .windowWrapper > .window', base.el).css('height', height);
		}

		base.setContent = function(p) {
			if (typeof p === "object") {
				if (p.content) {
					$('<div/>')
						.addClass('content')
						.html(p.content)
						.appendTo($('> .windowWrapper > .window', base.el));
				}
				if (p.UiComponentButtons) {
					var buttons = $('<div/>')
						.addClass('buttons')
						.appendTo($('> .windowWrapper > .window', base.el));
					for (var p of p.UiComponentButtons) {
						var button = $('<div/>').appendTo(buttons);
						if (p.isCancel)
							p.onClick = function() { base.close(); }
						$(button).UiComponentButton(p);
					}
				}
			} else
				$('> .windowWrapper > .window', base.el).html(p);
		}

		base.resetContent = function() {
			base.setContent('');
		}

		base.close = function() {
			$(base.el).hide();
			base.resetContent();

			if(o.hookEsc)
				$(document).unbind('keyup');

			clearTimeout(timeoutHandler);
		}

		base.open = function(content, style, width, height, isAutoClose, autoCloseDelay) {
			base.setWindowStyle(style, width, height);
			base.setContent(content);
			$(base.el).show();

			if(o.hookEsc)
				$(document).keyup(function(e){
					if(e.keyCode === 27) {
						base.close();
						e.preventDefault();
					}
				});

			if (isAutoClose)
				timeoutHandler = setTimeout(function() {
					base.close();
				}, (autoCloseDelay ? autoCloseDelay : o.autoCloseDelay));
		}

		base.openAjax = function(url, setup, style, width, height, isAutoClose, autoCloseDelay) {
			if (!setup)
				setup = Array;
			setup.success = function(data) {
				base.open(
					data.popupContent,
					(style ? style : data.popupStyle),
					(width ? width : data.popupWidth),
					(height ? height : data.popupHeight),
					(isAutoClose ? isAutoClose : data.isAutoClose),
					(autoCloseDelay ? autoCloseDelay : data.autoCloseDelay)
				);
			}
			ajaxQuery(url, setup);
		}

		base.openUrl = function(url, style, width, height) {
			base.open(
				'<iframe' +
					' width="' + width + '"' +
					' height="' + height + '"' +
					' src="' + url + '"' +
					' frameborder="0"' +
					' seamless' +
				'></iframe>',
				style,
				width,
				height
			);
		}

		base.init();
	}

	$.UiComponentPopup.defaults = {
		autoCloseDelay: 3000,
		hookEsc: true
	};

	$.fn.UiComponentPopup = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentPopup');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentPopup(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);

$(function() {
	$('body').append('<div id="UiComponentPopup"></div>');
	$('#UiComponentPopup').UiComponentPopup();
});

function openRegularPopupUrl(url, width, height) {
	var top = Math.round((screen.height/2) - (height/2));
	var left = Math.round((screen.width/2) - (width/2));

	window.open(url, '_blank', 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left + ', status=no, titlebar=no, toolbar=no, menubar=no');
}