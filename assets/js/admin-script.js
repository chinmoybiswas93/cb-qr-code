jQuery(document).ready(function ($) {
  const settingsForm = $("#cbqc-settings-form");
  const appearanceForm = $("#cbqc-appearance-form");

  function generateQrCodePreview() {
    const formFields = $("#cbqc-appearance-form").serializeArray();
    const filteredFields = formFields.filter(field => field.name !== 'action' && field.name !== 'tab');
    const formData = $.param(filteredFields) +
      "&action=cb_qr_code_preview&security=" + CBQRCodeAjax.nonce;

    $.ajax({
      url: CBQRCodeAjax.ajax_url,
      type: "POST",
      data: formData,
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $("#cbqc-preview").html(response.data.html);
        }
      },
      error: function () {
        $("#cbqc-preview").html('<p>Error generating preview</p>');
      }
    });
  }

  function ajaxSave(form, tab) {
    const formData =
      form.serialize() +
      "&action=cb_qr_code_save_settings" +
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
          '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        if (response.success) {
          const noticeHtml = '<div class="notice notice-success is-dismissible">' +
            dismissBtn + "<p>" + response.data.message + "</p></div>";
          
          if (form.attr('id') === 'cbqc-appearance-form') {
            form.prepend(noticeHtml);
          } else {
            form.before(noticeHtml);
          }
        } else {
          const errors = response.data.errors || ["An error occurred."];
          let errorHtml = '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
            dismissBtn + "<ul>";
          $.each(errors, function (i, err) {
            errorHtml += "<li>" + err + "</li>";
          });
          errorHtml += "</ul></div>";
          
          if (form.attr('id') === 'cbqc-appearance-form') {
            form.prepend(errorHtml);
          } else {
            form.before(errorHtml);
          }
        }
      },
      error: function (xhr) {
        const dismissBtn =
          '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        let errorMsg = "An unexpected error occurred.";
        if (xhr.status === 403) {
          errorMsg = "Security check failed (invalid or expired nonce). Please reload the page and try again.";
        }
        
        const errorHtml = '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
          dismissBtn + "<ul><li>" + errorMsg + "</li></ul></div>";
          
        if (form.attr('id') === 'cbqc-appearance-form') {
          form.prepend(errorHtml);
        } else {
          form.before(errorHtml);
        }
      },
    });
  }

  function handleCustomUrlVisibility() {
    if ($("input[name='cbqc-url-mode']:checked").val() === 'custom') {
      $('#cbqc-custom-url').show();
    } else {
      $('#cbqc-custom-url').hide();
    }
  }

  $(document).on("click", ".header-submit-btn", function (e) {
    e.preventDefault();
    if ($("#cbqc-tab-appearance").is(":visible")) {
      ajaxSave($("#cbqc-appearance-form"), 'appearance');
    } else if ($("#cbqc-tab-settings").is(":visible")) {
      ajaxSave($("#cbqc-settings-form"), 'settings');
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

  $(document).on('change', "input[name='cbqc-url-mode']", function () {
    handleCustomUrlVisibility();
  });

  generateQrCodePreview();
  handleCustomUrlVisibility();
});
