jQuery(document).ready(function ($) {
  const settingsForm = $("#cbqc-settings-form");
  const appearanceForm = $("#cbqc-appearance-form");

  function generateQrCodePreview() {
    const label = $("#qr-code-label").val() || "Scan Me";
    const labelSize = $("#qr-code-font-size").val() || "16";
    const size = $("#qr-code-size").val() || "150";
    const margin = $("#qr-code-margin").val() || "2";
    const format = "png";
    const colorDark = $("#qr-code-dark").val() || "000000";
    const colorLight = $("#qr-code-light").val() || "ffffff";
    const logourl = $("#qr-code-logo-url").val() || "";
    const logoSize = $("#qr-code-logo-size").val() || "50";

    const qrurl = "https://quickchart.io/qr?";
    const params = {
      text: CBQRCodeAjax.siteUrl,
      size: size,
      margin: margin,
      format: format,
      dark: colorDark,
      light: colorLight,
    };
    if (logourl) {
      params.centerImageUrl = logourl;
      params.centerImageSizeRatio = logoSize / 100;
    }
    const queryString = new URLSearchParams(params).toString();
    const qrCodeUrl = qrurl + queryString;
    const qrCodeImage = `<img src="${qrCodeUrl}" alt="QR Code Preview" style="max-width: 100%; height: auto;">`;
    const previewContainer = $("#cbqc-preview");
    
    const labelHtml = `<div class="cb-qr-label" style="font-size: ${labelSize}px; font-weight:bold;">${label}</div>`;
    previewContainer.html(labelHtml + qrCodeImage);
  }

  // AJAX save for both forms
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
          if (form.attr('id') === 'cbqc-appearance-form') {
            form.prepend(
              '<div class="notice notice-success is-dismissible">' +
                dismissBtn +
                "<p>" +
                response.data.message +
                "</p></div>"
            );
          } else {
            form.before(
              '<div class="notice notice-success is-dismissible">' +
                dismissBtn +
                "<p>" +
                response.data.message +
                "</p></div>"
            );
          }
        } else {
          const errors = response.data.errors || ["An error occurred."];
          let errorHtml =
            '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
            dismissBtn +
            "<ul>";
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
          errorMsg =
            "Security check failed (invalid or expired nonce). Please reload the page and try again.";
        }
        if (form.attr('id') === 'cbqc-appearance-form') {
          form.prepend(
            '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
              dismissBtn +
              "<ul><li>" +
              errorMsg +
              "</li></ul></div>"
          );
        } else {
          form.before(
            '<div class="notice notice-error is-dismissible" style="margin-top:20px;">' +
              dismissBtn +
              "<ul><li>" +
              errorMsg +
              "</li></ul></div>"
          );
        }
      },
    });
  }

  $(document).on("click", ".header-submit-btn", function (e) {
    e.preventDefault();
    if ($("#cbqc-tab-appearance").is(":visible")) {
      ajaxSave($("#cbqc-appearance-form"), 'appearance');
    } else if ($("#cbqc-tab-settings").is(":visible")) {
      ajaxSave($("#cbqc-settings-form"), 'settings');
    }
  });

  $("#cbqc-settings-form, #cbqc-appearance-form").off("submit");

  settingsForm.on("submit", function (e) {
    e.preventDefault();
    ajaxSave(settingsForm);
  });
  appearanceForm.on("submit", function (e) {
    e.preventDefault();
    ajaxSave(appearanceForm);
  });

  $(document).on(
    "click",
    ".notice.is-dismissible .notice-dismiss",
    function () {
      $(this)
        .closest(".notice")
        .fadeOut(200, function () {
          $(this).remove();
        });
    }
  );

  settingsForm.on("change input", "input, select", function () {
    generateQrCodePreview();
  });
  appearanceForm.on("change input", "input, select", function () {
    generateQrCodePreview();
  });

  generateQrCodePreview();

  $(document).on("click", ".cbqc-tabs-nav button[data-tab='appearance']", function () {
    setTimeout(generateQrCodePreview, 10);
  });

  $(document).on('change', "input[name='cbqc-url-mode']", function () {
    if ($(this).val() === 'custom') {
      $('#cbqc-custom-url').show();
    } else {
      $('#cbqc-custom-url').hide();
    }
  });

  if ($("input[name='cbqc-url-mode']:checked").val() === 'custom') {
    $('#cbqc-custom-url').show();
  } else {
    $('#cbqc-custom-url').hide();
  }
});
