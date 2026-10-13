jQuery(document).ready(function ($) {
  const settingsForm = $("#cbqrcode-settings-form");
  const appearanceForm = $("#cbqrcode-appearance-form");
  const i18n = CBQRCodeAjax.i18n;


  let mediaUploader;

  function initMediaUploader() {
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    mediaUploader = wp.media({
      title: i18n.select_logo,
      button: {
        text: i18n.use_image
      },
      library: {
        type: 'image'
      },
      multiple: false
    });

    mediaUploader.on('select', function() {
      const attachment = mediaUploader.state().get('selection').first().toJSON();
      

      $('#qr-code-logo-id').val(attachment.id);
      $('#qr-code-logo-url').val(attachment.url);
      

      let displayName = attachment.filename || attachment.title || i18n.selected_image;
      if (displayName.length > 25) {
        const lastDotIndex = displayName.lastIndexOf('.');
        const extension = lastDotIndex !== -1 ? displayName.substring(lastDotIndex) : '';
        const nameWithoutExt = lastDotIndex !== -1 ? displayName.substring(0, lastDotIndex) : displayName;
        
        if (nameWithoutExt.length > 15) {
          const start = nameWithoutExt.substring(0, 5);
          const end = nameWithoutExt.substring(nameWithoutExt.length - 6);
          displayName = start + '...' + end + extension;
        }
      }
      

      $('.cbqrcode-selected-image-name').text(displayName);
      

      $('.cbqrcode-remove-media').show();
      

      generateQrCodePreview();
    });

    mediaUploader.open();
  }

  function removeMediaSelection() {

    $('#qr-code-logo-id').val('');
    $('#qr-code-logo-url').val('');
    

    $('.cbqrcode-selected-image-name').text(i18n.no_image);
    

    $('.cbqrcode-remove-media').hide();
    

    generateQrCodePreview();
  }


  $(document).on('click', '.cbqrcode-select-media', function(e) {
    e.preventDefault();
    initMediaUploader();
  });

  $(document).on('click', '.cbqrcode-remove-media', function(e) {
    e.preventDefault();
    removeMediaSelection();
  });

  // Preview requests fire on every input and change, so responses can arrive out of
  // order; only the latest request's response is rendered (issue #13).
  let previewRequest = 0;

  function generateQrCodePreview() {
    const requestId = ++previewRequest;
    const formFields = $("#cbqrcode-appearance-form").serializeArray();
    const filteredFields = formFields.filter(field => field.name !== 'action' && field.name !== 'tab');
    const formData = $.param(filteredFields) +
      "&action=cbqrcode_preview&security=" + CBQRCodeAjax.nonce;

    $.ajax({
      url: CBQRCodeAjax.ajax_url,
      type: "POST",
      data: formData,
      dataType: "json",
      success: function (response) {
        if (requestId !== previewRequest) {
          return;
        }
        if (response.success) {
          $("#cbqrcode-preview").html(response.data.html);
        }
      },
      error: function () {
        if (requestId !== previewRequest) {
          return;
        }
        $("#cbqrcode-preview").html('<p>' + i18n.preview_error + '</p>');
      }
    });
  }

  function ajaxSave(form, tab) {
    const formData =
      form.serialize() +
      "&action=cbqrcode_save_settings" +
      "&tab=" + tab +
      "&security=" +
      CBQRCodeAjax.nonce;
    $(".notice").remove();
    $.ajax({
      url: CBQRCodeAjax.ajax_url,
      type: "POST",
      data: formData,
      dataType: "json",
      success: function (response) {
        const dismissBtn =
          '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + i18n.dismiss + '</span></button>';
        if (response.success) {
          const noticeHtml = '<div class="notice notice-success is-dismissible">' +
            dismissBtn + "<p>" + response.data.message + "</p></div>";
          
          if (form.attr('id') === 'cbqrcode-appearance-form') {
            form.prepend(noticeHtml);
          } else {
            form.before(noticeHtml);
          }
        } else {
          const errors = response.data.errors || [i18n.error];
          let errorHtml = '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
            dismissBtn + "<ul>";
          $.each(errors, function (i, err) {
            errorHtml += "<li>" + err + "</li>";
          });
          errorHtml += "</ul></div>";
          
          if (form.attr('id') === 'cbqrcode-appearance-form') {
            form.prepend(errorHtml);
          } else {
            form.before(errorHtml);
          }
        }
      },
      error: function (xhr) {
        const dismissBtn =
          '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + i18n.dismiss + '</span></button>';
        let errorMsg = i18n.unexpected_error;
        if (xhr.status === 403) {
          errorMsg = i18n.nonce_error;
        }
        
        const errorHtml = '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
          dismissBtn + "<ul><li>" + errorMsg + "</li></ul></div>";
          
        if (form.attr('id') === 'cbqrcode-appearance-form') {
          form.prepend(errorHtml);
        } else {
          form.before(errorHtml);
        }
      },
    });
  }

  function handleCustomUrlVisibility() {
    if ($("input[name='cbqrcode-url-mode']:checked").val() === 'custom') {
      $('#cbqrcode-custom-url').show();
    } else {
      $('#cbqrcode-custom-url').hide();
    }
  }

  $(document).on("click", ".header-submit-btn", function (e) {
    e.preventDefault();
    if ($("#cbqrcode-tab-appearance").is(":visible")) {
      ajaxSave($("#cbqrcode-appearance-form"), 'appearance');
    } else if ($("#cbqrcode-tab-settings").is(":visible")) {
      ajaxSave($("#cbqrcode-settings-form"), 'settings');
    }
  });

  $(document).on("click", ".notice.is-dismissible .notice-dismiss", function () {
    $(this).closest(".notice").fadeOut(200, function () {
      $(this).remove();
    });
  });

  appearanceForm.on("change input", "input, select", function () {
    generateQrCodePreview();
  });

  $(document).on('change', "input[name='cbqrcode-url-mode']", function () {
    handleCustomUrlVisibility();
  });

  generateQrCodePreview();
  handleCustomUrlVisibility();
});
