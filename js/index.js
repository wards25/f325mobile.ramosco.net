$(document).ready(function () {
  LoadCompanyList();
  ReloadPage();
  LoadLocation();
  LoadNotepadList();
  historyPosition();
});
function addCommas(number) {
  var split = number.toString().split(".");
  if (split[0].length >= 4) {
    split[0] = split[0].replace(/(\d)(?=(\d{3})+$)/g, "$1,");
  }
  return split.join(".");
}
;
function LoadCompanyList() {
  $.ajax({
    url: "companylist.php",
    success: function (response) {
      $(".select-list-company").html(response);
      SelectCompany();
    },
  });
}
function SelectCompany() {
  var companyId = $(".select-list-company").val();
  $.ajax({
    type: "POST",
    url: "loaddetail.php",
    data: { id: companyId },
    success: function (response) {
      var obj = JSON.parse(response);
      $(".input-companyname").val(obj.name);
      $(".input-nickname").val(obj.nickname);
      $(".input-referencecode").val(obj.refcode);
      $(".input-vendorcode").val(obj.vendorcode);
      $(".textarea-address").val(obj.address);
      $(".input-bypass").prop("checked", obj.bypass == "1");
      $(".input-active").prop("checked", obj.active == "1");
    },
  });
}
function UnloadCompany() {
  // document.querySelector(".form-company").reset();
  window.location.href = "dashboard.php";
}
function ReloadPage() {
  setTimeout(function () {
    window.location.reload();
  }, 900000);
}
function UpdateCompany() {
  var id = $(".select-list-company").val();
  var companyname = $(".input-companyname").val();
  var nickname = $(".input-nickname").val();
  var vendorcode = $(".input-vendorcode").val();
  var referencecode = $(".input-referencecode").val();
  var address = $(".textarea-address").val();
  var bypass = $(".input-bypass:checked").val();
  var isActive = $(".input-active:checked").val();

  $.ajax({
    type: "POST",
    url: "updatecompany.php",
    data: {
      id: id,
      name: companyname,
      nickname: nickname,
      vendorcode: vendorcode,
      referencecode: referencecode,
      address: address,
      bypass: bypass,
      active: isActive,
    },
    beforeSend: function () {
      $(".button-update-company").prop("disabled", true).text("Updating...");
    },
    success: function (response) {
      // $(".span-notify-alert").text("Company updated successfully!");
      // $(".span-notify-alert").show();
      // $(".form-company").css("background-Color", "");
      // $(".div-load-data").html("");
      // setTimeout(function () {
      //   $(".span-notify-alert").fadeOut();
      // }, 2000);
      if (response === "success") {
        window.location.reload();
      }
    },
  });

  return false;
}
function LoadLocation() {
  $.ajax({
    url: "listlocation.php",
    success: function (response) {
      $(".tbody-list-location").html(response);
    },
  });
}
function UnloadUser() {
  $("#addUserModal").modal("hide");
}
// function UnloadLocation() {
//   $(".div-load-data").html("");
//   $(".tbl-button-menu-td").css("background-Color", "");
//   $(".div-system-bg").hide();
// }

// function UpdateLocation() {
//   $(".input-location").each(function () {
//     var id = $(this).attr("locid");
//     var value = $(this).val();
//     var active = $(this)
//       .closest("tr")
//       .find(".input-checkbox-active")
//       .is(":checked")
//       ? 1
//       : 0;

//     $.ajax({
//       type: "POST",
//       url: "location/updatelocation.php",
//       data: {
//         id: id,
//         value: value,
//         active: active,
//       },
//       success: function () {
//         $(".span-notify-alert").html("Location updated successfully.").show();
//         $(".div-load-data").html("");
//         $(".span-notify-alert").css("background-color", "");
//         setTimeout(function () {
//           $(".span-notify-alert").fadeOut();
//         }, 2000);
//       },
//       error: function () {
//         alert("Error: Failed to update user.");
//       },
//     });
//   });

//   return false;
// }
// function CheckAdmin() {
//   $.ajax({
//     url: "checkadmin.php",
//     success: function (response) {
//       if (response == "admin") {
//         $(".input-form-field").prop("disabled", false);
//       } else {
//         $(".input-admin").prop("disabled", true);

//         if ($(".input-admin").is(":checked")) {
//           $(".input-form-field").prop("disabled", true);
//         } else {
//           $(".input-form-field").prop("disabled", false);
//         }
//       }
//     },
//   });
// }
function NewUser() {
  var username = $(".input-username").val();
  var password = $(".input-password").val();
  var fname = $(".input-fname").val();
  var isActive = $(".input-active").val();
  var access = $(".input-access").val();
  var admin = access == "1" ? "1" : "0";
  var semiadmin = access == "2" ? "1" : "0";

  // Location checkboxes
  var locs = {};
  for (var i = 1; i <= 10; i++) {
    locs["loc" + i] = $(".input-loc" + i + ":checked").val() || "0";
  }
  var postData = {
    username: username,
    password: password,
    fname: fname,
    admin: admin,
    semiadmin: semiadmin,
    active: isActive,
    ...locs,
  };

  $.ajax({
    type: "POST",
    url: "newaccount.php",
    data: postData,
    success: function () {
      $(".span-notify-alert").html("Location updated successfully.").show();
      $("#addUserModal").modal("hide");
      $(".span-notify-alert").css("background-color", "");
      setTimeout(function () {
        $(".span-notify-alert").fadeOut();
      }, 2000);
    },
    error: function () {
      alert("Error: Failed to add user.");
    },
  });

  return false;
}
function EditUser(id) {
  window.location.href = "account-edit.php?id=" + id;
}
function FileDelete() {
  $.ajax({
    url: "upload/filedelete.php",
    success: function () {
      ScanFile();
      $(".check-file").prop("disabled", true);
      $(".clear-file").prop("disabled", true);
      $(".convert-file").prop("disabled", true);
    },
  });
}
function LoadNotepadList() {
  var selectSearch = $(".select-search").val();
  var searchKeyword = $(".input-search").val();
  var statusCode = $(".select-status").val();
  var company = $(".select-company").val();
  $.ajax({
    type: "POST",
    url: "load-notepad-list.php",
    data: {
      selectsearch: selectSearch,
      search: searchKeyword,
      status: statusCode,
      company: company,
    },
    success: function (response) {
      $(".tbody-list-order").html(response);
      LoadNotepadDetail();
    },
  });
}

function LoadNotepadDetail() {
  $(".tbl-list-order-tr").click(function () {
    var _0x460cf7 = $(this).attr("f325id");
    $.ajax({
      type: "POST",
      url: "load-notepad-detail.php",
      data: {
        id: _0x460cf7,
      },
      success: function (_0x3fc222) {
        $("#order-detail-modal").fadeIn();
        obj = JSON.parse(_0x3fc222);
        $(".input-customer").val(obj.branchname);
        $(".input-company").val(obj.vendorname);
        $(".input-company").attr("vcode", obj.vcode);
        $(".input-issued").val(obj.issuedby);
        $(".input-emaildate").val(obj.emaildate);
        $(".input-prepared").val(obj.preparedby);
        $(".input-ordernumber").val(obj.f325number);
        $(".input-orderdate").val(obj.f325date);
        $(".input-remarks").val(obj.remarks);
        $(".input-status").val(obj.status);
        LoadSKU();
        if ($(".input-status").val() == "OPEN") {
          $(".button-print").html("Print");
          $(".button-reopen").hide();
          $(".input-remarks").prop("disabled", false);
        } else {
          $(".button-print").html("Re-Print");
          $(".button-reopen").show();
          $(".input-remarks").prop("disabled", true);
        }
      },
    });
  });
}
function UnloadNotepadDetail() {
  $("#order-detail-modal").fadeOut();
}
function LoadSKU() {
    const f325number = $(".input-ordernumber").val();
    const vendorCode = $(".input-company").attr("vcode");

    $.ajax({
        type: "POST",
        url: "load-sku.php",
        data: {
            f325number: f325number,
            vcode: vendorCode
        },
        success: function (response) {
            $(".tbl-order-list").html(response);
            Subtotal();
        }
    });
}
function Subtotal() {
  var _0x728b92 = 0x0;
  $(".subtotal-lines").each(function () {
    _0x728b92 += parseFloat($(this).attr("subtotal"));
  });
  $(".input-subtotal").val(addCommas(_0x728b92.toFixed(0x2)));
}
function historyPosition() {
    $(".button-history").on("click", function () {
        const pos = $(this).position();
        $(".div-history").css({
            top: pos.top + $(this).outerHeight(),
            left: pos.left
        });

        const processNumber = $(".input-ordernumber").val();

        $.ajax({
            type: "POST",
            url: "notepad-history.php",
            data: { processnumber: processNumber },

            success: function (response) {
                $(".tbody-history-list").html(response);
                $(".div-history").show();
            }
        });
    });
}
function PrintNotepad() {
  var f325number = $(".input-ordernumber").val();
  window.open("print-notepad-details.php?f325number=" + f325number, "_blank");
}
