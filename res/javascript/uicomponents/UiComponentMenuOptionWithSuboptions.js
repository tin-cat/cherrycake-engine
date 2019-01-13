(function($){

	$.UiComponentMenuOptionWithSuboptions = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentMenuOptionWithSuboptions', base);

		base.init = function() {
            base.options = o = $.extend({}, $.UiComponentMenuOptionWithSuboptions.defaults, options);

            $('.UiComponentMenuOption', base.el).on('click', function() {
                base.switch();
			});
        }

        base.isOpen = function() {
            return $(base.el).hasClass('open');
        }

        base.switch = function() {
            if (base.isOpen())
                $(base.el).removeClass('open');
            else
                $(base.el).addClass('open');
        }
        
		base.init();
	}

	$.UiComponentMenuOptionWithSuboptions.defaults = {
	};

	$.fn.UiComponentMenuOptionWithSuboptions = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentMenuOptionWithSuboptions');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentMenuOptionWithSuboptions(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);