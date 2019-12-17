(function($){

	$.UiComponentUploadImage = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentUploadImage', base);

		var content = $('> .content', base.el);
		var buttonUpload = $('> .uploadButton', content);

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentUploadImage.defaults, options);

			$(buttonUpload).on('click', function() {
				base.selectFile();
			});

			if (o.defaultImageUrl)
				base.setImage(o.defaultImageUrl);
		}

		base.selectFile = function() {
			ajaxUpload({
				accept: 'image/*',
				ajaxUrl: o.ajaxUrl,
				onFileSelected: function() {
					$(buttonUpload).UiComponentButton('setUploading');
				},
				onSuccess: function(data) {
					$(buttonUpload).UiComponentButton('unsetLoadingPercent');
					$(buttonUpload).UiComponentButton('unsetUploading');
					$(buttonUpload).UiComponentButton('unsetWorking');
					base.setImage(data.imageUrl);
				},
				onError: function() {
					$(buttonUpload).UiComponentButton('unsetLoadingPercent');
					$(buttonUpload).UiComponentButton('unsetUploading');
					$(buttonUpload).UiComponentButton('unsetWorking');
				},
				onProgress: function(percent, position, total) {
					$(buttonUpload).UiComponentButton('setLoadingPercent', percent);
					if (percent >= 100)
						$(buttonUpload).UiComponentButton('setWorking');
				}
			});
		}

		base.hideContent = function() {
			$(base.el).addClass("hidden");
		}

		base.showContent = function() {
			$(base.el).removeClass("hidden");
		}

		base.setImage = function(url) {
			url = url + '?' + Math.floor(Math.random() * 1000000);	
			$(base.el).css('background-image', 'url("' + url + '")');
		}

		base.init();
	}

	$.UiComponentUploadImage.defaults = {
		ajaxUrl: false,
		defaultImageUrl: false
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