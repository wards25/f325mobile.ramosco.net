$(document).ready(function () {
  LoadCompanyList();
  ReloadPage();
  LoadLocation();
});
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
