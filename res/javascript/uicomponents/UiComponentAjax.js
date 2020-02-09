var ajaxHandler;

function ajaxQuery(url, setup) {
	if (setup.isFileUpload) {
		setup.contentType = false;
		setup.processData = false;
	}

	if (!'contentType' in setup)
		setup.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	
	if (!'processData' in setup)
		setup.processData = true;
	
	ajaxHandler = $.ajax({
		url: url,
		type: (setup && 'type' in setup ? setup['type'] : '<?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultRequestType") ?>'),
		timeout: (setup && 'timeout' in setup ? setup['timeout'] : <?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultTimeout") ?>),
		async: (setup && 'isAsync' in setup ? setup['isAsync'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultIsAsync") ? "true" : "false") ?>),
		cache: (setup && 'isCache' in setup ? setup['isCache'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultIsCache") ? "true" : "false") ?>),
		crossDomain: (setup && 'isCrossDomain' in setup ? setup['isCrossDomain'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("DefaultIsCrossDomain") ? "true" : "false") ?>),
		data: setup ? setup['data'] : false,
		dataType: 'json',
		contentType: setup.contentType,
		processData: setup.processData,
		error: function(jqXHR, textStatus, errorThrown) {
			<?php
				if (IS_DEVEL_ENVIRONMENT) {
					?>	
						$('#UiComponentNotice').UiComponentNotice('open', [
							'<div style="text-align: left;">' + 
								'<div style="margin: 20px;">' +
									'<b>Ajax error:</b> ' + textStatus + (errorThrown && errorThrown != textStatus ? ' (' + errorThrown + ')' : '') + ' <b>Status: </b>' + jqXHR.status +  '<br>' + 
									'<b>Url:</b> ' + url + '<br>' +
								'</div>' + 
								'<pre style="background: rgba(255, 255, 255, .9); color: #000; font-size: 7pt; line-height: 1.3em; font-family: Courier; margin: 20px; padding: 20px; height: 50vh; overflow-y: auto;">' + $("<div>").text(jqXHR.responseText).html() + '</pre>' + 
							'</div>',
							'AjaxResponseError',
							false
						]);
					<?php
				}
				else {
					?>
						console.log('%cAjax error: ' + textStatus + (errorThrown && errorThrown != textStatus ? ' (' + errorThrown + ')' : ''), 'color: #c15');
						console.log('%cResponse:\n' + jqXHR.responseText, 'color: #c15');
						$('#UiComponentNotice').UiComponentNotice('open', ['<?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("ajaxErrorText") ?>', 'ajaxResponseError']);
					<?php
				}
			?>
		},
		success: function(data, textStatus, jqHXR) {
			ajaxResponseTreatMessage(data.code, data.description, data.messageType);
			switch (data.code) {
				case 1: // AJAXRESPONSEJSON_ERROR
					if (setup && setup['onError'])
						setup['onError'](data.data);
					break;

				case 0: // AJAXRESPONSEJSON_SUCCESS
					if (setup && setup['onSuccess'])
						setup['onSuccess'](data.data);
					break;
			}
			if (data.redirectUrl)
				document.location = data.redirectUrl;
		},
		xhr: function() {
			var myXhr = $.ajaxSettings.xhr();
			if (myXhr.upload && setup.onProgress) {
				myXhr.upload.addEventListener('progress', function(event) {
					var percent = 0;
					var position = event.loaded || event.position;
					var total = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
					setup.onProgress(percent, position, total);
				}, false);
			}
			return myXhr;
		}
	});
}

function ajaxResponseTreatMessage(code, description, messageType) {
	if (description == '')
		return;

	if (messageType == 1) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NOTICE
		$('#UiComponentNotice').UiComponentNotice('open', [description, 'styleAjaxResponse'+(code == 0 ? 'Success' : (code == 1 ? 'Error' : null))]);
	}
	else
	if (messageType == 2) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP
		$('#UiComponentPopup').UiComponentPopup('open', [{content: description}, 'styleAjaxResponse'+(code == 0 ? 'Success' : (code == 1 ? 'Error' : null)), false, false, true]);
	}
	else
	if (messageType == 3) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
		$('#UiComponentPopup').UiComponentPopup('open', [{content: description}, 'styleAjaxResponse'+(code == 0 ? 'Success' : (code == 1 ? 'Error' : null))]);
	}
	else
	if (messageType == 4) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_CONSOLE
		console.log('%c' + description, 'color: #c15');
	}
}