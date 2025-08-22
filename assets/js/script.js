jQuery(document).ready(function ($) {
  $(".cbqrcode-qr").on("click", function () {
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