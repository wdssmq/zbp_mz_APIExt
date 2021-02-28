$(function () {
  $("#read_pwd").change(function () {
    const pwd = $(this).val();
    $("#read_md5").val(MD5(pwd));
  });

  const fnAjax = (param) => {
    return new Promise((resovle, reject) => {
      // console.log(param);
      $.ajax({
        type: param.method || "get",
        async: param.async || true,
        url: param.url,
        data: param.data || "",
        success: (res) => {
          resovle(res);
        },
        error: (err) => {
          reject(err);
        },
      });
    });
  };

  async function fnAPI(
    api = { mod: "post", act: "get" },
    data = {},
    cb = () => {}
  ) {
    const urlBase = bloghost + "zb_system/api.php";
    const url = `${urlBase}?mod=${api.mod}&act=${api.act}`;
    let method = "GET";
    switch (api.act) {
      case "login":
        method = "POST";
        break;
      default:
        break;
    }
    await fnAjax({
      url,
      data,
      method,
    })
      .then((res) => {
        console.log(res);
        cb(res);
      })
      .catch((err) => {
        console.log(err);
      });
  }

  $(".js-api-test").click(function () {
    const arg_data = (() => {
      return {
        username: $(".username span").text().replace("管理员：", "").trim(),
        password: $("#read_md5").val(),
      };
    })();
    fnAPI({ mod: "member", act: "login" }, arg_data, function (res) {
      $(".js-api-msg").text(`${res.message}，也可直接在控制台查看Token使用`);
    });
  });
});
