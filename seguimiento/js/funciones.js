$(document).ready(function () {
  $('#loginForm').submit(function (e) {
    e.preventDefault();

    const datos = {
      usuario: $('#usuario').val().trim(),
      clave: $('#clave').val().trim()
    };

    $.ajax({
      type: 'POST',
      url: 'php/validacion_login.php',
      data: datos,
      dataType: 'json',
      success: function (respuesta) {
        if (respuesta.status === 'ok') {
          alertify.success('Bienvenido');
          setTimeout(() => {
            //window.location.href = 'https://apps.melonesoilterminal.com/compras/seguimiento/index.php'
            window.location.href = 'http://localhost/compras/seguimiento/index.php';
          }, 1000);
        } else {
          alertify.error(respuesta.message);
        }
      },
      error: function () {
        alertify.error('Error de servidor');
      }
    });
  });
});



