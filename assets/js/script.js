jQuery(document).ready(function ($) {
  $(".cb-qr-code").on("click", function () {
    const $qr = $(this);
    const qrText = $qr.data('url');
    if (qrText) {
      navigator.clipboard.writeText(qrText).then(function () {
        $qr.addClass("copied");
        setTimeout(function () {
          $qr.removeClass("copied");
        }, 350);
      });
    }
  });
});