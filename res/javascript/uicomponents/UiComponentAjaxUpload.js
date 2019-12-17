/*
 * Opens a file upload dialog and treats the upload according to the passed setup.
 * Must be called from a user activation even (a click, for example), otherwise it might not work on most browsers as a security measure.
*/
function ajaxUpload(setup) {
	var inputElement = document.createElement("input");
	inputElement.type = "file";
	if (setup) {
		if (setup.accept)
			inputElement.accept = setup.accept;
	}
	inputElement.addEventListener("change", function() {
		var formData = new FormData();
		formData.append("file", inputElement.files[0], inputElement.files[0].name);
		ajaxQuery(
			setup.ajaxUrl,
			{
				isFileUpload: true,
				data: formData,
				timeout: setup.timeout ? setup.timeout : <?= $e->Ui->uiComponents["UiComponentAjaxUpload"]->getConfig("timeout") ?>,
				onSuccess: function(data) {
					if (setup.onSuccess)
						setup.onSuccess(data);
				},
				onError: function() {
					if (setup.onError)
						setup.onError();
				},
				onProgress: setup.onProgress
			}
		);
		if (setup && setup.onFileSelected)
			setup.onFileSelected();
	});
	inputElement.dispatchEvent(new MouseEvent("click"));
}

/*
Progress bar

Upload.prototype.doUpload = function () {
    var that = this;
    var formData = new FormData();

    // add assoc key values, this will be posts values
    formData.append("file", this.file, this.getName());
    formData.append("upload_file", true);

    $.ajax({
        type: "POST",
        url: "script",
        xhr: function () {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
                myXhr.upload.addEventListener('progress', that.progressHandling, false);
            }
            return myXhr;
        },
        success: function (data) {
            // your callback here
        },
        error: function (error) {
            // handle error
        },
        async: true,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        timeout: 60000
    });
};

Upload.prototype.progressHandling = function (event) {
    var percent = 0;
    var position = event.loaded || event.position;
    var total = event.total;
    var progress_bar_id = "#progress-wrp";
    if (event.lengthComputable) {
        percent = Math.ceil(position / total * 100);
    }
    // update progressbars classes so it fits your code
    $(progress_bar_id + " .progress-bar").css("width", +percent + "%");
    $(progress_bar_id + " .status").text(percent + "%");
};
*/