/* global MD5:false */

$(function() {
  $("#read_pwd").change(function() {
    const pwd = $(this).val();
    $("#read_md5").val(MD5(pwd));
  });

  const lsObj = {
    lsName: "mz_APIExt",
    data: {},
    set: (key, value) => {
      lsObj.data[key] = value;
      localStorage.setItem(lsObj.lsName, JSON.stringify(lsObj.data));
    },
    get: (key, def = "") => {
      if (lsObj.data[key]) {
        return lsObj.data[key];
      } else {
        return def;
      }
    },
    load: () => {
      const data = localStorage.getItem(lsObj.lsName);
      if (data) {
        lsObj.data = JSON.parse(data);
      }
    },
  };
  lsObj.load();

  $("#read_savedate").val(lsObj.get("read_savedate", 30));

  const fnAjax = (param) => {
    return new Promise((resovle, reject) => {
      // console.log(param);
      $.ajax({
        type: param.method || "get",
        async: param.async || true,
        url: param.url,
        data: param.data || "",
        headers: param.headers || {},
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
    cb = () => { },
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

  $(".js-api-test").click(function() {
    const arg_data = (() => {
      let savedate = parseInt($("#read_savedate").val());
      // 判断是否为数字
      if (Number.isNaN(savedate) || savedate <= 0) {
        savedate = 30;
        $("#read_savedate").val(savedate);
      }
      if (savedate > 365) {
        alert("有效期不能超过 365 天");
        return false;
      }
      lsObj.set("read_savedate", savedate);
      return {
        username: $(".username span").text().replace("管理员：", "").trim(),
        password: $("#read_md5").val(),
        savedate: savedate,
      };
    })();
    fnAPI({ mod: "member", act: "login" }, arg_data, function(res) {
      $(".js-api-msg").text(`${res.message}，也可直接在控制台查看 Token 使用`);
    });
  });
});
