jQuery(document).ready(function ($) {
  $(".cb-qr-code").on("click", function () {
    const $qr = $(this);
    const img = $qr.find("img");
    const src = img.attr("src");
    const urlParams = new URLSearchParams(src.split("?")[1]);
    const qrText = urlParams.get("text");
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