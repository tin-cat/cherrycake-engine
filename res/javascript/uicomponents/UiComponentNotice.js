(function($){

	$.UiComponentNotice = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentNotice', base);

		var closeTimeout = false;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentNotice.defaults, options);

			$('> .content', base.el).on('click', function() {
				base.close();
			});
		}

		base.setStyle = function(style) {
			// Removes all classes starting with "style"
			if($('> .content', base.el).attr('class')) {
				classes = $('> .content', base.el).attr('class').split(' ');
				for(i=0; i<classes.length; i++) {
					if(classes[i].substr(0, 5) == 'style')
						$('> .content', base.el).removeClass(classes[i]);
				};
			}

			if (style)
				$('> .content', base.el).addClass('style' + style);
		}

		base.setContent = function(content) {
			$('> .content', base.el).html(content);
		}

		base.resetContent = function() {
			base.setContent('');
		}

		base.getContentHeight = function() {
			return $('> .content', base.el).outerHeight();
		}

		base.open = function(content, style, disappearDelay) {
			base.setStyle(style);
			base.setContent(content);
			clearTimeout(closeTimeout);

			// Place the notice outside the view
			$(base.el).css('top', base.getContentHeight()*-1);
			$(base.el).css('visibility', 'visible');
			$(base.el).stop().animate(
				{
					top: 0
				},
				{
					duration: o.revealDelay,
					easing: o.revealEasing,
					complete: function() {
						if (disappearDelay !== false && (disappearDelay || o.defaultDisappearDelay)) {
							closeTimeout = setTimeout(function() {
								base.close();
							}, (disappearDelay ? disappearDelay : o.defaultDisappearDelay));
						}
					}
				}
			);
		}

		base.openAjax = function(url, setup, style, disappearDelay) {
			if (!setup)
				setup = Array;
			setup.success = function(data) {
				base.open(
					data.noticeContent,
					(style ? style : data.noticeStyle),
					(disappearDelay ? disappearDelay : data.disappearDelay)
				);
			}
			ajaxQuery(url, setup);
		}

		base.close = function(complete) {
			$(base.el).stop().animate(
				{
					top: base.getContentHeight()*-1
				},
				{
					duration: o.hideDelay,
					easing: o.hideEasing,
					complete: function() {
						base.resetContent();
						if (complete)
							complete();
					}
				}
			);
		}

		base.init();
	}

	$.UiComponentNotice.defaults = {
		revealDelay: <?= $e->UiComponentNotice->getConfig("revealDelay") ?>,
		hideDelay: <?= $e->UiComponentNotice->getConfig("hideDelay") ?>,
		revealEasing: '<?= $e->UiComponentNotice->getConfig("revealEasing") ?>',
		hideEasing: '<?= $e->UiComponentNotice->getConfig("hideEasing") ?>',
		defaultDisappearDelay: <?= $e->UiComponentNotice->getConfig("defaultDisappearDelay") ?>
	};

	$.fn.UiComponentNotice = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentNotice');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentNotice(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);

$(function() {
	$('body').append('<div id="UiComponentNotice"><div class="content"></div></div>');
	$('#UiComponentNotice').UiComponentNotice();
});