$(document).ready(function() {
  LoadCompanyList();
  ReloadPage();
  LoadLocation();
});
function _0xae47() {
  var _0x5495c5 = [
    "Geolocation\x20is\x20not\x20supported\x20by\x20this\x20browser.",
    "getCurrentPosition",
    "attr",
    ".input-latitude",
    "1658358vJyBnN",
    ".div-greeting",
    "ready",
    "latitude",
    "hide",
    "272uIkrqj",
    "2326443fjfHwl",
    "coords",
    "60633TvFvBh",
    "proceedlogin.php",
    "3235dPmxhN",
    ".div-border",
    "company",
    "Sorry,\x20your\x20browser\x20does\x20not\x20support\x20getUserMedia",
    "outerWidth",
    "geolocation",
    "The\x20following\x20error\x20occurred\x20when\x20trying\x20to\x20use\x20getUserMedia:\x20",
    "PERMISSION_DENIED",
    "POSITION_UNAVAILABLE",
    "POST",
    "show",
    "width",
    "520ABFTPu",
    "ajax",
    "getUserMedia",
    "log",
    "1550610zuHKqA",
    "css",
    "animate",
    ".span-greeting",
    "usertype/",
    "longitude",
    "UNKNOWN_ERROR",
    "innerHTML",
    ".input-longitude",
    "355899LYUKQB",
    "click",
    "val",
    "1751660iEAjSh",
    "location",
    "linear",
  ];
  _0xae47 = function () {
    return _0x5495c5;
  };
  return _0xae47();
}
var _0x549e68 = _0x3ac3;
(function (_0x2fc0c0, _0x46c12d) {
  var _0x57b23e = _0x3ac3,
    _0xfbbb93 = _0x2fc0c0();
  while (!![]) {
    try {
      var _0x53c73e =
        (parseInt(_0x57b23e(0xbc)) / 0x1) * (parseInt(_0x57b23e(0x9b)) / 0x2) +
        parseInt(_0x57b23e(0xa8)) / 0x3 +
        -parseInt(_0x57b23e(0xab)) / 0x4 +
        parseInt(_0x57b23e(0x9f)) / 0x5 +
        -parseInt(_0x57b23e(0xb2)) / 0x6 +
        -parseInt(_0x57b23e(0xb8)) / 0x7 +
        (-parseInt(_0x57b23e(0xb7)) / 0x8) * (-parseInt(_0x57b23e(0xba)) / 0x9);
      if (_0x53c73e === _0x46c12d) break;
      else _0xfbbb93["push"](_0xfbbb93["shift"]());
    } catch (_0x194f54) {
      _0xfbbb93["push"](_0xfbbb93["shift"]());
    }
  }
})(_0xae47, 0x6e6a0),
  $(document)[_0x549e68(0xb4)](function () {
    ProceedToLogin(), getLocation(), AccessCamera(), LoopGreeting();
  });
function LoopGreeting() {
  var _0x5c20c7 = _0x549e68,
    _0x35d940 = $(document)[_0x5c20c7(0xc7)](),
    _0x2f9df4 = $(_0x5c20c7(0xa2))[_0x5c20c7(0xc0)]();
  $(_0x5c20c7(0xa2))[_0x5c20c7(0xa0)]({ right: -_0x2f9df4 + "px" }),
    $(".span-greeting")[_0x5c20c7(0xa1)](
      { right: _0x35d940 },
      0x2710,
      _0x5c20c7(0xad),
      function () {
        var _0x38dadf = _0x5c20c7;
        $(_0x38dadf(0xa2))[_0x38dadf(0xa0)]({ right: -_0x2f9df4 + "px" }),
          $(_0x38dadf(0xb3))["hide"](),
          setTimeout(function () {
            var _0x59f2ba = _0x38dadf;
            $(_0x59f2ba(0xb3))[_0x59f2ba(0xc6)](), LoopGreeting();
          }, 0x7d0);
      }
    );
}
function ProceedToLogin() {
  var _0x51a577 = _0x549e68;
  $(".button-company")["on"](_0x51a577(0xa9), function () {
    var _0x1c4271 = _0x51a577,
      _0x55932e = $(this)[_0x1c4271(0xb0)](_0x1c4271(0xbe));
    $[_0x1c4271(0x9c)]({
      type: _0x1c4271(0xc5),
      url: _0x1c4271(0xbb),
      data: { company: _0x55932e },
      success: function () {
        var _0x31bf9 = _0x1c4271;
        window[_0x31bf9(0xac)]["href"] = _0x31bf9(0xa3);
      },
    });
  });
}
function getLocation() {
  var _0x241ee0 = _0x549e68;
  navigator["geolocation"]
    ? navigator[_0x241ee0(0xc1)][_0x241ee0(0xaf)](showPosition, showError)
    : (x[_0x241ee0(0xa6)] = _0x241ee0(0xae));
}
function showPosition(_0x1708b6) {
  var _0x483862 = _0x549e68,
    _0x2c58f5 = _0x1708b6["coords"][_0x483862(0xb5)],
    _0x5c1e89 = _0x1708b6[_0x483862(0xb9)][_0x483862(0xa4)];
  $(".input-longitude")[_0x483862(0xaa)](_0x5c1e89),
    $(_0x483862(0xb1))[_0x483862(0xaa)](_0x2c58f5),
    LocationStatus();
}
function showError(_0xe637f1) {
  var _0x2416f5 = _0x549e68;
  switch (_0xe637f1["code"]) {
    case _0xe637f1[_0x2416f5(0xc3)]:
      LocationStatus();
      break;
    case _0xe637f1[_0x2416f5(0xc4)]:
      LocationStatus();
      break;
    case _0xe637f1["TIMEOUT"]:
      LocationStatus();
      break;
    case _0xe637f1[_0x2416f5(0xa5)]:
      LocationStatus();
      break;
  }
}
function _0x3ac3(_0x323a73, _0x46a07f) {
  var _0xae47bb = _0xae47();
  return (
    (_0x3ac3 = function (_0x3ac309, _0x57c53d) {
      _0x3ac309 = _0x3ac309 - 0x9b;
      var _0x5c6510 = _0xae47bb[_0x3ac309];
      return _0x5c6510;
    }),
    _0x3ac3(_0x323a73, _0x46a07f)
  );
}
function LocationStatus() {
  var _0x542cf4 = _0x549e68,
    _0x451a90 = $(_0x542cf4(0xa7))["val"](),
    _0x2d5982 = $(_0x542cf4(0xb1))[_0x542cf4(0xaa)]();
  _0x2d5982 != "" && _0x451a90 != ""
    ? $(".div-border")[_0x542cf4(0xb6)]()
    : $(_0x542cf4(0xbd))[_0x542cf4(0xc6)]();
}
function AccessCamera() {
  var _0x1179cd = _0x549e68;
  navigator[_0x1179cd(0x9d)]
    ? navigator[_0x1179cd(0x9d)](
        { video: !![] },
        function (_0x5650eb) {},
        function (_0x34b31a) {
          var _0x380bb9 = _0x1179cd;
          console[_0x380bb9(0x9e)](_0x380bb9(0xc2) + _0x34b31a);
        }
      )
    : alert(_0x1179cd(0xbf));
}
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
  window.location.href = 'dashboard.php';
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
    beforeSend: function(){
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
      if(response === 'success'){
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
function UnloadUser(){
  $('#addUserModal').modal('hide');
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