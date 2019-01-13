(function($){

	$.UiComponentFormText = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

        base.$el.data('UiComponentFormText', base);
        
        var title;
        var textarea;

        var ajaxOnChangeTimeout;
        var ajaxOnChangeIsLoading;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentFormText.defaults, options);

			$(base.el).addClass('UiComponentFormText');

			if (o.style)
                base.addStyle(o.style);
            
            if (o.title)
                base.setTitle(o.title);
            
            base.addTextarea();
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
        
        base.setTitle = function(titleContent) {
            $(title).remove();
            title = $('<div class=\"title\"></div>').appendTo(base.el);
			$(title).html(titleContent);
        }

        base.addTextarea = function() {
            textarea = $('<textarea></textarea>').appendTo(base.el);
            if (o.name) $(textarea).attr('name', o.name);
            if (o.columns) $(textarea).attr('cols', o.columns);
            if (o.rows) $(textarea).attr('rows', o.rows);
            if (o.placeHolder) $(textarea).attr('placeholder', o.placeHolder);
            if (o.isDisabled) $(textarea).prop('disabled', true);
            if (o.isAutofocus) $(textarea).prop('autofocus', true);
            $(textarea).attr('autocomplete', o.isAutocomplete ? 'on' : 'off');
            $(textarea).attr('autocorrect', o.isAutocorrect ? 'on' : 'off');
            $(textarea).attr('spellcheck', o.isSpellCheck ? 'on' : 'off');
            $(textarea).on('change keyup', function() { base.onChange(); });
            if (o.value) base.setValue(o.value);
        }

        base.setValue = function(value) {
            $(input).value(value);
        }

        base.getValue = function() {
            return $(textarea).val();
        }

        base.onChange = function() {
            if (typeof o.onChange === "function")
				o.onChange();
			else
			if (o.onChange != null)
                eval(o.onChange);
            
            if (o.isAjaxOnChange) {
                if (!ajaxOnChangeIsLoading) {
                    if (ajaxOnChangeTimeout)
                        clearTimeout(ajaxOnChangeTimeout);
                    ajaxOnChangeTimeout = setTimeout(function() {
                        base.ajaxSend();
                    }, o.ajaxOnChangeDelay);
                }
            }
        }

        base.ajaxSend = function() {
            var data = {};
			data['text'] = base.getValue();
			ajaxQuery(o.ajaxSaveUrl, {
				data: data,
				onError: function() {
					base.setError();
				},
				onSuccess: function(data) {
                    base.unsetError();
				}
			});
        }

        base.setError = function() {
			base.addStyle('error');
		}

		base.unsetError = function() {
			base.removeStyle('error');
		}

		base.init();
	}

	$.UiComponentFormText.defaults = {
        style: false,
        name: false,
        value: false,
        columns: false,
        rows: false,
        placeHolder: false,
        isDisabled: false,
        isAutoComplete: false,
        isAutoFocus: false,
        isAutocorrect: false,
        isSpellCheck: false,
        onChange: false,
        title: false,
        isAjaxOnChange: false,
        ajaxOnChangeDelay: 500,
        ajaxSaveUrl: false
	},

	$.fn.UiComponentFormText = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentFormText');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentFormText(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);