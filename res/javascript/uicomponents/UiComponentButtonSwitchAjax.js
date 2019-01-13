(function($){

	$.UiComponentButtonSwitchAjax = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentButtonSwitchAjax', base);

		var currentState;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentButtonSwitchAjax.defaults, options);
			$(base.el).UiComponentButton();
			currentState = o.defaultState;
			base.setState(o.defaultState);
		}

		base.setState = function(state) {
			base.unSetState(state == 1 ? 0 : 1);

			if (!o.states[state])
				return;

			$(base.el).UiComponentButton('setTitle', o.states[state]['title']);

			$(base.el).UiComponentButton('setIcon', o.states[state]['icon']);

			if (o.states[state]['style'])
				$(base.el).UiComponentButton('setStyle', o.states[state]['style']);

			$(base.el).UiComponentButton('setOnClick', function() {
				$(base.el).UiComponentButton('setLoading');
				ajaxQuery(o.states[state]['ajaxQueryUrl'], {
					onSuccess: function(data) {
						currentState = state;
						$(base.el).UiComponentButton('unsetLoading');
						base.switchState();
					},
					onError: function() {
						$(base.el).UiComponentButton('unsetLoading');
					}
				});
			});
		}

		base.unSetState = function(state) {
			if (o.states[state]['icon'])
				$(base.el).UiComponentButton('setIcon', o.states[state]['icon']);

			if (o.states[state]['style'])
				$(base.el).UiComponentButton('removeStyle', o.states[state]['style']);
		}

		base.switchState = function() {
			base.setState(currentState == 1 ? 0 : 1);
		}

		base.init();
	}

	$.UiComponentButtonSwitchAjax.defaults = {
		defaultState: null,
		states: false
	};

	$.fn.UiComponentButtonSwitchAjax = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentButtonSwitchAjax');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentButtonSwitchAjax(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);