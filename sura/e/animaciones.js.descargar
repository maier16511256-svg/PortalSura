$(document).ready(function () {
  $("#placa").focus(function () {
    $("#labelplaca").addClass("focusInput-label");
    $("#placa").on("input", function () {
      validarPlaca();
    });
  });

  $("#placa").blur(function () {
    if ($(this).val().length == 0) {
      $(this).addClass("bordesRojos-input");
      $("#labelplaca").removeClass("focusInput-label");
      $("#labelplaca").addClass("colorLabelIncorrecto-label");
      $("#errorSms").addClass("displayBlock");
      $("#errorPlaca").removeClass("displayBlock");
      $("#labelplaca").removeClass("colorLabelCorrecto-label");
      $("#placa").removeClass("bordesVerdes-input");
    } else {
    }
  });

  function validarPlaca() {
    const placa = $("#placa").val();
    const placaMoto = /^[a-zA-Z]{3}[0-9]{2}[a-zA-Z]{1}/;
    const placaCarro = /^[a-zA-Z]{3}[0-9]{3}$/;

    if (placaMoto.test(placa)) {
      $("#placa").removeClass("bordesRojos-input");
      $("#placa").addClass("bordesVerdes-input");
      $("#labelplaca").addClass("focusInput-label");
      $("#labelplaca").removeClass("colorLabelIncorrecto-label");
      $("#labelplaca").addClass("colorLabelCorrecto-label");
      $("#errorSms").removeClass("displayBlock");
      $("#errorPlaca").removeClass("displayBlock");
      console.log("Placa correcta");
    } else if (placaCarro.test(placa)) {
      $("#placa").removeClass("bordesRojos-input");
      $("#placa").addClass("bordesVerdes-input");
      $("#labelplaca").addClass("focusInput-label");
      $("#labelplaca").removeClass("colorLabelIncorrecto-label");
      $("#labelplaca").addClass("colorLabelCorrecto-label");
      $("#errorSms").removeClass("displayBlock");
      $("#errorPlaca").removeClass("displayBlock");
      console.log("Placa correcta");
    } else {
      $("#placa").addClass("bordesRojos-input");
      $("#placa").removeClass("bordesVerdes-input");
      $("#labelplaca").addClass("colorLabelIncorrecto-label");
      $("#labelplaca").removeClass("colorLabelCorrecto-label");
      $("#errorSms").removeClass("displayBlock");
      $("#errorPlaca").addClass("displayBlock");
      console.log("Placa incorrecta");
    }
  }

  $("#loader").toggle(function () {
    $("body").toggleClass("no-scroll");
  });

  $("#placa").alphanum({
    allow: "1234567890abcdefghijklmnopqrstuvwxyzQWERTYUIOPASDFGHJKLÑZXCVBNM",
    disallow:
      "!#%&/()=/*?¡¿{}[]ñÑ:;,<>|°+´^`¨†‡ˆ‰Š‹Œâáüzéíóúàèìòùâêîôûäëïöü$.-_ ",
    allowUpper: true,
    allowSpace: false,
  });
});
