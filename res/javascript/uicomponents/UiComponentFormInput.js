(function($){

	$.UiComponentFormInput = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

        base.$el.data('UiComponentFormInput', base);
        
        var title;
        var input;

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentFormInput.defaults, options);

			$(base.el).addClass('UiComponentFormInput');

			if (o.style)
                base.addStyle(o.style);
            
            if (o.type == 'hidden')
                base.addStyle('hidden');
            
            if (o.title)
                base.setTitle(o.title);
            
            if (o.type)
                base.addInput();
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

        base.addInput = function() {
            input = $('<input />').appendTo(base.el);
            $(input).attr('type', o.type);
            if (o.name) $(input).attr('name', o.name);
            if (o.value) base.setValue(o.value);
            if (o.size) $(input).attr('size', o.size);
            if (o.maxLength) $(input).attr('maxLength', o.maxLength);
            if (o.isDisabled) $(input).prop('disabled', true);
            if (o.isAutofocus) $(input).prop('autofocus', true);
            if (o.placeHolder) $(input).attr('placeholder', o.placeHolder);
            $(input).attr('autocomplete', o.isAutocomplete ? 'on' : 'off');
            $(input).attr('autocapitalize', o.isAutocapitalize ? 'on' : 'off');
            $(input).attr('autocorrect', o.isAutocorrect ? 'on' : 'off');
            $(input).attr('spellcheck', o.isSpellCheck ? 'on' : 'off');
            $(input).on('change', function() { base.onChange(); });
            $(input).on('keyup', function(event) { base.onKeyUp(event); });
        }

        base.setValue = function(value) {
            $(input).attr('value', value);
        }

        base.getValue = function() {
            return $(input).val();
        }

        base.onChange = function() {
            if (typeof o.onChange === "function")
				o.onChange();
			else
			if (o.onChange != null)
				eval(o.onChange);
        }

        base.onKeyUp = function(event) {
            switch (event.which) {
                case 13:
                    base.onKeyEnter();
                    break;
            }
        }

        base.onKeyEnter = function() {
            if (o.isSubmitOnEnter)
                base.submitForm();
        }

        base.submitForm = function() {
            $(base.el).closest('.UiComponentForm').UiComponentForm('submit');
        }

		base.init();
	}

	$.UiComponentFormInput.defaults = {
        type: false,
        style: false,
        name: false,
        value: false,
        size: false,
        maxLength: 255,
        placeHolder: false,
        isDisabled: false,
        isAutoFocus: false,
        isAutoComplete: true,
        isAutocapitalize: false,
        isAutocorrect: false,
        isSpellCheck: false,
        onChange: false,
        title: false,
        isSubmitOnEnter: false
	},

	$.fn.UiComponentFormInput = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentFormInput');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentFormInput(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);