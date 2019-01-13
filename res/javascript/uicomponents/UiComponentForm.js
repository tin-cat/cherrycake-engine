(function($){

	$.UiComponentForm = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentForm', base);

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentForm.defaults, options);

			$(base.el).addClass('UiComponentForm');

			if (o.style)
				base.addStyle(o.style);
			
			if (o.additionalCssClasses)
				$(base.el).addClass(o.additionalCssClasses);
			
			if (o.method)
				base.setMethod(o.method);
			
			if (o.url)
				base.setUrl(o.url);
		}

		base.getButtonSend = function() {
			return $('.UiComponentButton', base.el);
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

		base.setMethod = function(method) {
			$(base.el).attr('method', method);
		}

		base.setUrl = function(url) {
			$(base.el).attr('action', url);
		}

		base.submit = function() {
			$(base.el).submit();
			base.setLoading();
		}

		base.setLoading = function() {
			base.getButtonSend().UiComponentButton('setLoading');
		}

		base.init();
	}

	$.UiComponentForm.defaults = {
		style: false,
		method: false,
		url: false,
		items: false
	};

	$.fn.UiComponentForm = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentForm');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentForm(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);