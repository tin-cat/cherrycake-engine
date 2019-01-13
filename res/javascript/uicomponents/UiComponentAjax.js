var ajaxHandler;

function ajaxQuery(url, setup) {
	ajaxHandler = $.ajax({
		url: url,
		type: (setup && 'type' in setup ? setup['type'] : '<?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultRequestType") ?>'),
		timeout: (setup && 'timeout' in setup ? setup['timeout'] : <?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultTimeout") ?>),
		async: (setup && 'isAsync' in setup ? setup['isAsync'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultIsAsync") ? "true" : "false") ?>),
		cache: (setup && 'isCache' in setup ? setup['isCache'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("defaultIsCache") ? "true" : "false") ?>),
		crossDomain: (setup && 'isCrossDomain' in setup ? setup['isCrossDomain'] : <?= ($e->Ui->uiComponents["UiComponentAjax"]->getConfig("DefaultIsCrossDomain") ? "true" : "false") ?>),
		data: setup ? setup['data'] : false,
		dataType: 'json',
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('%cAjax error: ' + textStatus + (errorThrown && errorThrown != textStatus ? ' (' + errorThrown + ')' : ''), 'color: #c15');
			console.log('%cResponse:\n' + jqXHR.responseText, 'color: #c15');
			$('#UiComponentNotice').UiComponentNotice('open', ['<?= $e->Ui->uiComponents["UiComponentAjax"]->getConfig("ajaxErrorText") ?>', 'ajaxResponseError']);
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
		$('#UiComponentPopup').UiComponentPopup('open', [description, 'styleAjaxResponse'+(code == 0 ? 'Success' : (code == 1 ? 'Error' : null)), false, false, true]);
	}
	else
	if (messageType == 3) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
		$('#UiComponentPopup').UiComponentPopup('open', [description, 'styleAjaxResponse'+(code == 0 ? 'Success' : (code == 1 ? 'Error' : null))]);
	}
	else
	if (messageType == 4) { // AJAXRESPONSEJSON_UI_MESSAGE_TYPE_CONSOLE
		console.log('%c' + description, 'color: #c15');
	}
}