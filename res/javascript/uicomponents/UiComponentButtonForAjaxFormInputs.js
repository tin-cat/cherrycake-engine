/**
 * Performs a save operation in multiple UiComponentFormInputAjax elements or similar, and runs a callback if all of them were saved without errors, or another callback if some of them had errors.
 * @param Array p A hash array with the following possible keys:
 * * elements: An array of the jQuery objects representing each UiComponentFormInputAjax or similar
 * * onAllSucceed: Callback to run when all elements have been saved successfully
 * * onSomeErrors: Callback to run when at least one element hasn't been saved successfully
 */
function UiComponentMultipleFormInputAjaxSaveCallback(p) {
	var successCount = errorsCount = 0;

	var saveResult = function(isSuccess) {
		if (isSuccess)
			successCount ++;
		else
			errorsCount ++;
		if (successCount == p.elements.length && p.onAllSucceed) {
			p.onAllSucceed();
		}
		else
		if (successCount + errorsCount == p.elements.length && p.onSomeErrors)
			p.onSomeErrors();
	}

	$(p.elements).each(function(idx, element) {
		$(element).UiComponentFormInputAjax('save', {
			onError: function() { saveResult(false); },
			onSuccess: function() { saveResult(true); }
		});
	});
}

/**
 * Performs a save operation in multiple UiComponentFormInputAjax elements or similar, and then performs a given ajax query if all of them were saved without errors, or another ajax query if some of them had errors.
 * Just like UiComponentMultipleFormInputAjaxSaveResultTrigger, but it performs an ajax query instead of calling callbacks.
 * @param Array p A hash array with the following possible keys:
 * * elements: An array of the jQuery objects representing each UiComponentFormInputAjax or similar
 * * ajaxQueryUrlOnAllSucceed: The ajax url to query when all the elements have been saved successfully.
 * * ajaxQueryUrlOnSomeErrors: The ajax url to query when at least one element hasn't been saved successfully.
 * * button: If the request is being originated from an UiComponentButton, its object button.
 */
function UiComponentMultipleFormInputAjaxSaveAjax(p) {
	if (p.button)
		p.button.UiComponentButton('setLoading');
	UiComponentMultipleFormInputAjaxSaveCallback({
		elements: p.elements,
		onAllSucceed: function() {
			if (p.ajaxQueryUrlOnAllSucceed) {
				ajaxQuery(
					p.ajaxQueryUrlOnAllSucceed,
					{
						onSuccess: function() {
							if (p.button)
								p.button.UiComponentButton('unsetLoading');
						},
						onError: function() {
							if (p.button)
								p.button.UiComponentButton('unsetLoading');
						}
					}
				);
			}
			else {
				if (p.button)
					p.button.UiComponentButton('unsetLoading');
			}
		},
		onSomeErrors: function() {
			if (p.ajaxQueryUrlOnSomeErrors) {
				ajaxQuery(
					p.ajaxQueryUrlOnSomeErrors,
					{
						onSuccess: function() {
							if (p.button)
								p.button.UiComponentButton('unsetLoading');
						},
						onError: function() {
							if (p.button)
								p.button.UiComponentButton('unsetLoading');
						}
					}
				);
			}
			else {
				if (p.button)
					p.button.UiComponentButton('unsetLoading');
			}
		}
	});
}