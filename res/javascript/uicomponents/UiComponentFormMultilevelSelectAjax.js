(function($){

	$.UiComponentFormMultilevelSelectAjax = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentFormMultilevelSelectAjax', base);

		var input;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentFormMultilevelSelectAjax.defaults, options);
			base.getData();
		}

		base.getSelect = function(levelName) {
			return $('select[name=' + levelName + ']', base.el);
		}
		
		base.getValue = function(levelName) {
			return base.getSelect(levelName).val();
		}

		base.setSelectOptions = function(levelName, options) {
			var select = base.getSelect(levelName);
			$(select).empty();
			$.each(options, function(value, title) {
				select.append($("<option></option>")
					.attr("value", value).text(title));
			});
		}

		base.getData = function() {
			base.setLoading();
			var requestLevels = o.levels;
			$.each(o.levels, function(levelName) {
				requestLevels[levelName] = {
					value: base.getValue(levelName)
				}
			});
			ajaxQuery(o.getDataAjaxUrl, {
				data: {
					levels: JSON.stringify(requestLevels)
				},
				onError: function() {
					base.unsetLoading();
					if (o.isShakeOnError)
						base.shake();
					base.setError();
				},
				onSuccess: function(data) {
					base.setData(data);
					base.unsetLoading();
					base.unsetError();
				}
			});
		}

		base.setData = function(data) {
			console.log(data);
		}

		base.save = function(p) {
			base.setLoading();
			var data = {};
			data[o.saveAjaxKey] = base.getValue();
			ajaxQuery(o.saveAjaxUrl, {
				data: data,
				onError: function() {
					base.unsetLoading();
					if (o.isShakeOnError)
						base.shake();
					base.setError();
					if (p && p.onError)
						p.onError();
				},
				onSuccess: function(data) {
					base.unsetLoading();
					base.unsetError();
					if (p && p.onSuccess)
						p.onSuccess();
				}
			});
		}

		base.setError = function() {
			$(base.el).addClass('error');
		}

		base.unsetError = function() {
			$(base.el).removeClass('error');
		}

		base.isError = function() {
			return $(base.el).hasClass('error');
		}

		base.shake = function() {
			animationEffectShake(base.el);
		}

		base.setLoading = function() {
			$(base.el).addClass('loading');
		}

		base.unsetLoading = function() {
			$(base.el).removeClass('loading');
		}

		base.init();
	}

	$.UiComponentFormMultilevelSelectAjax.defaults = {
		levels: false,
		getDataAjaxUrl: false,
		saveAjaxUrl: false,
		saveAjaxKey: false,
		isShakeOnError: true
	};

	$.fn.UiComponentFormMultilevelSelectAjax = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentFormMultilevelSelectAjax');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentFormMultilevelSelectAjax(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);