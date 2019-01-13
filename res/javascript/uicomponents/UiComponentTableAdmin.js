(function($){

	$.UiComponentTableAdmin = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentTableAdmin', base);

		base.init = function() {
            base.options = o = $.extend({}, $.UiComponentTableAdmin.defaults, options);
            $(base.el).addClass('UiComponentTableAdmin');
            $(base.el).UiComponentTable({
                title: o.title,
                style: o.style,
                additionalCssClasses: o.additionalCssClasses,
                columns: o.columns
            });
            base.getRows();
        }

        base.getRows = function() {
            $(base.el).UiComponentTable('setLoading');
            ajaxQuery(o.ajaxUrls.getRows, {
                data: {
                    mapName: o.mapName
                },
                onError: function() {
                    $(base.el).UiComponentTable('unsetLoading');
                },
				onSuccess: function(data) {
                    $(base.el).UiComponentTable('addRows', data.rows);
                    $(base.el).UiComponentTable('unsetLoading');
				}
			});
        }

		base.init();
	}

	$.UiComponentTableAdmin.defaults = {
        mapName: false,
        ajaxUrls: {
            getRows: false
        }
	};

	$.fn.UiComponentTableAdmin = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentTableAdmin');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentTableAdmin(this, options);
			else
				eval('me.'+options+'.apply(me.'+options+', params)');
		});
	}

})(jQuery);