(function($){

	$.UiComponentUploadImage = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentUploadImage', base);

		var buttonUpload = $('> .uploadButton', base.el);

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentUploadImage.defaults, options);

			$(buttonUpload).on('click', function() {
				base.selectFile();
			});
		}

		base.selectFile = function() {
			ajaxUpload({
				ajaxUrl: o.ajaxUrl,
				onFileSelected: function() {
					console.log('File selected');
				}
			});
		}

		base.init();
	}

	$.UiComponentUploadImage.defaults = {
		ajaxUrl: false
	};

	$.fn.UiComponentUploadImage = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentUploadImage');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentUploadImage(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);