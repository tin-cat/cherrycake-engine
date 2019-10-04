(function($){

	$.UiComponentIcon = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentIcon', base);

		var savedIcon;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentIcon.defaults, options);
			if (o.name)
				base.setIcon(o.name);
			if (o.variant)
				base.setVariant(o.variant);
		}

		base.change = function(from, to) {
			$(base.el).removeClass(from).addClass(to);
		}

		base.setIcon = function(iconName) {
			base.clearIcon();
			$(base.el).addClass('UiComponentIcon ' + iconName);
		}

		base.setVariant = function(variant) {
			$(base.el).addClass(variant);
		}

		base.saveIcon = function() {
			savedIcon = $(base.el).prop('className');
		}

		base.restoreIcon = function() {
			if (savedIcon) {
				base.setIcon(savedIcon);
				savedIcon = false;
			}
		}

		base.clearIcon = function() {
			$(base.el).prop('className').split(' ').forEach(function(className) {
				if (className == 'UiComponentIcon')
					return;
				$(base.el).removeClass(className);

			});
		}

		base.init();
	}

	$.UiComponentIcon.defaults = {
		name: false,
		variant: false
	};

	$.fn.UiComponentIcon = function(options, params, param2) {
		return this.each(function(){
			var me = $(this).data('UiComponentIcon');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentIcon(this, options);
			else
				eval('me.'+options)(params, param2);
		});
	}

})(jQuery);