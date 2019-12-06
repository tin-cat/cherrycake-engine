(function($){

	$.UiComponentTable = function(el, options) {
		var base = this, o;
		base.el = el;
		base.$el = $(el);

		base.$el.data('UiComponentTable', base);

		var columnAlignmentsCssClasses = {
			left: 'alignLeft',
			center: 'alignCenter',
			right: 'alignRight'
		};

		base.init = function() {
			base.options = o = $.extend({}, $.UiComponentTable.defaults, options);
			$(base.el).addClass('UiComponentTable');
			$('<table></table>').appendTo(base.el);
			base.innerTable = $('> table', base.el);
			if (o.style)
				$(base.el).addClass(o.style);
			if (o.additionalCssClasses)
				$(base.el).addClass(o.additionalCssClasses);
			if (o.columns)
				base.setColumns(o.columns);
			if (o.title)
				base.setTitle(o.title);
			$('<tbody></tbody>').appendTo(base.innerTable);
		}

		base.setTitle = function(title) {
			$('> thead.title', base.innerTable).remove();
			$('<thead class=title><tr><th colspan=' + (Object.keys(o.columns).length) + '>' + title + '</th></tr></thead>').prependTo(base.innerTable);
		}

		base.setColumns = function(columns) {
			$('> thead.columns', base.innerTable).remove();
			$('<thead class=columns><tr></tr></thead>').prependTo(base.innerTable);
			for (var key in columns) {
				var column = columns[key];
				$('<th></th>')
					.html(column.title)
					.addClass(columnAlignmentsCssClasses[column.align])
					.appendTo($('> thead > tr', base.innerTable));
			}
		}

		base.addRows = function(rows) {
            for (var idx in rows)
                base.addRow(idx, rows[idx]);
        }

        base.addRow = function(idx, row) {
			var rowElement = $('<tr></tr>')
				.attr('data-id', row['id'])
				.appendTo($('> tbody', base.innerTable));
			
			for (var key in o.columns) {
				var column = o.columns[key];
				var type = null;
				var html = null;
				if (typeof row[key]  === "object") {
					type = row[key].type;
					html = row[key].html;
				}
				else
					html = row[key];
				if (row[key]) {

					if (type == 'buttons') {
						html =
							'<div class="reveal">' +
								html +
							'</div>' +
							'<div class="UiComponentButton small transparent revealButton">' +
								'<div class="UiComponentIcon more"></div>' +
							'</div>';
					}

					$('<td></td>')
						.addClass(columnAlignmentsCssClasses[column.align])
						.addClass(type)
						.html(html)
						.appendTo(rowElement);
				}
				else
					$('<td></td>')
						.appendTo(rowElement);
			}
		}
		
		base.setLoading = function() {
			$(base.el).addClass('loading');
		}

		base.unsetLoading = function() {
			$(base.el).removeClass('loading');
		}

		base.init();
	}

	$.UiComponentTable.defaults = {
		style: false,
		additionalCssClasses: false,
		title: false,
		columns: false
	};

	$.fn.UiComponentTable = function(options, params) {
		return this.each(function(){
			var me = $(this).data('UiComponentTable');
			if ((typeof(options)).match('object|undefined'))
				new $.UiComponentTable(this, options);
			else
				eval('me.'+options)(params);
		});
	}

})(jQuery);