(function($){

	$.UiComponentFormInputAjax = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentFormInputAjax', base);

		var input, button;
		var committedValue;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentFormInputAjax.defaults, options);
			input = $(o.inputElementType, base.el);
			button = $('.UiComponentButton', base.el);

			$(button).UiComponentButton({
				onClick: function() {
					base.save();
				}
			});

			committedValue = base.getValue();

			$(input).on('keydown paste', function(e) {
				return base.keydown(e);
			});

			$(input).on('change keyup', function(e) {
				return base.changed(e);
			});

			$(input).focus(function() {
				base.showSaveButton();
			});

			$(input).blur(function() {
				if (!base.isHasChanged())
					base.hideSaveButton();
			});

			if (o.defaultTooltip)
				base.setTooltip(o.defaultTooltip);
		}

		base.getValue = function() {
			return $(input).val();
		}

		base.setValue = function(value) {
			$(input).val(value);
		}

		base.isEmpty = function() {
			return base.getValue().trim() = "";
		}

		base.isHasChanged = function() {
			return committedValue !== base.getValue();
		}

		base.keydown = function(e) {
			if (e.keyCode == 13 && o.saveOnEnter && o.inputElementType != 'textarea') {
				base.save();
				e.preventDefault();
				return false;
			}
			base.showSaveButton();
		}

		base.changed = function(e) {
		}

		base.showSaveButton = function() {
			$(base.el).addClass('showSaveButton');
		}

		base.hideSaveButton = function() {
			$(base.el).removeClass('showSaveButton');
		}

		base.save = function(p) {
			$(button).UiComponentButton('setLoading');
			var data = {};
			data[o.saveAjaxKey] = base.getValue();
			ajaxQuery(o.saveAjaxUrl, {
				data: data,
				onError: function(data) {
					$(button).UiComponentButton('unsetLoading');
					if (o.isShakeOnError)
						base.shake();
					base.setError(data && 'description' in data ? data.description : false);
					if (p && p.onError)
						p.onError();
				},
				onSuccess: function(data) {
					$(button).UiComponentButton('unsetLoading');
					if (typeof data === 'object' && data.values !== null)
						base.setValue(data.values[o.saveAjaxKey]);
					committedValue = base.getValue();
					base.unsetError();
					$(input).blur();
					if (p && p.onSuccess)
						p.onSuccess();
				}
			});
		}

		base.setError = function(description) {
			$(base.el).addClass('error');
			if (description)
				base.setTooltip({
					style: 'styleWarning',
					content: description
				});
		}

		base.unsetError = function() {
			base.removeTooltip();
			$(base.el).removeClass('error');
		}

		base.isError = function() {
			return $(base.el).hasClass('error');
		}

		base.shake = function() {
			animationEffectShake(base.el);
		}

		base.setTooltip = function(data) {
			data = $.extend({}, {
				isOpenOnInit: true,
				isCloseWhenOthersOpen: false,
				position: 'topCenter',
				isTapToPopupOnSmallScreens: true
			}, data);
			$(base.el).UiComponentTooltip(data);
		}

		base.removeTooltip = function() {
			if ($(base.el).data('UiComponentTooltip'))
				$(base.el).UiComponentTooltip('close');
		}

		base.init();
	}

	$.UiComponentFormInputAjax.defaults = {
		inputElementType: 'input',
		saveOnEnter: true,
		saveAjaxUrl: false,
		saveAjaxKey: false,
		isShakeOnError: true,
		defaultTooltip: false
	};

	$.fn.UiComponentFormInputAjax = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentFormInputAjax');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentFormInputAjax(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);