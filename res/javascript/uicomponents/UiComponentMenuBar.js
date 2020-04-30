(function($){

	$.UiComponentMenuBar = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentMenuBar', base);

		base.init = function() {
            base.options = o = $.extend({}, $.UiComponentMenuBar.defaults, options);

            $('.buttonSmallScreenMenu', base.el).on('click', function() {
                base.smallScreenMenuSwitch();
			});
        }

        base.isSmallScreenMenuOpen = function() {
            return $(base.el).hasClass('smallScreenMenuOpen');
        }

        base.smallScreenMenuSwitch = function() {
            if (base.isSmallScreenMenuOpen())
                $(base.el).removeClass('smallScreenMenuOpen');
            else
                $(base.el).addClass('smallScreenMenuOpen');
        }
        
		base.init();
	}

	$.UiComponentMenuBar.defaults = {
        responsiveBreakpoints: {
            thresholdWidthToHideLogo: <?= $e->UiComponentMenuBar->getConfig("responsiveBreakpoints")["thresholdWidthToHideLogo"] ?>,
            thresholdWidthToUseSlidingPanel: <?= $e->UiComponentMenuBar->getConfig("responsiveBreakpoints")["thresholdWidthToHideLogo"] ?>
        }
	};

	$.fn.UiComponentMenuBar = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentMenuBar');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentMenuBar(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);