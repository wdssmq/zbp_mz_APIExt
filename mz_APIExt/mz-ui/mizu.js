$(function () {
  // tabs
  function fnToggleTab() {
    if (location.hash !== "") {
      $(".tab-content").hide();
      $(".content-box-tabs a").removeClass("current");
      $(location.hash).show();
      $('a[href="' + location.hash + '"]').addClass("current");
    }
  }
  fnToggleTab();
  $(".content-box-tabs a").click(function () {
    //alert(this.href);
    window.history.pushState(null, null, this.href.replace(/#tab1/, ""));
  });
  // readOnly
  const fnReCover = ($el) => {
    if ($el.data("val")) {
      $el.val($el.data("val"));
    } else {
      $el.data("val", $el.val());
    }
    $el.focus();
    $el.select();
  };
  $(".focus + *").mouseenter(function (i) {
    $(this).focus();
  });
  $(".placeholder").each(function (i) {
    const str = $(this).data("placeholder");
    if (str) {
      const $el = $(this).next("*");
      $el.attr("placeholder", str);
    }
  });
  // 因为是在zblog里选项器用的相邻
  $(".readonly + *")
    .click(function () {
      const _this = this;
      setTimeout(function () {
        _this.select();
      }, 150);
      fnReCover($(this));
    })
    .mouseenter(function () {
      fnReCover($(this));
    })
    .keyup(function () {
      fnReCover($(this));
    })
    .change(function () {
      fnReCover($(this));
    })
    .attr("readonly", 1);
});
