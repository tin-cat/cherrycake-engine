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

		base.setOnChange = function(levelName) {
			base.getSelect(levelName).on('change', function() {
				o.levels[levelName].value = parseInt(base.getSelectValue(levelName));
				base.getData();
			});
		}

		base.unsetOnChange = function(levelName) {
			base.getSelect(levelName).off('change');
		}

		base.getSelect = function(levelName) {
			return $('select[name=' + levelName + ']', base.el);
		}
		
		base.getSelectValue = function(levelName) {
			return base.getSelect(levelName).val();
		}

		base.setSelectValue = function(levelName, value) {
			base.getSelect(levelName).val(value);
		}

		base.setSelectValues = function(levelValues) {
			$.each(o.levels, function(levelName) {
				base.setSelectValue(levelName, levelValues[levelName].value);
			});
		}

		base.setSelectsToCurrentValues = function() {
			$.each(o.levels, function(levelName) {
				base.setSelectValue(levelName, o.levels[levelName].value);
			});
		}

		base.getData = function(onSuccess) {
			base.setLoading();
			var requestLevels = {};
			$.each(o.levels, function(levelName, levelData) {
				requestLevels[levelName] = o.levels[levelName].value;
			});
			console.log(requestLevels);
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
					base.setSelectsToCurrentValues();
				}
			});
		}

		base.setData = function(data) {
			$.each(o.levels, function(levelName) {
				base.setLevelData(levelName, data[levelName]);
			});
		}

		base.setLevelData = function(levelName, levelData) {
			base.setSelectOptions(levelName, levelData);
		}

		base.setSelectOptions = function(levelName, options) {
			var select = base.getSelect(levelName);
			base.unsetOnChange(levelName);
			$(select).empty();
			$.each(options, function(idx, option) {
				select.append($("<option></option>")
					.attr("value", option.id).text(option.name));
			});
			base.setOnChange(levelName);
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