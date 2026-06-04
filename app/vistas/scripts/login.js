$("#frmAcceso").on('submit', function(e)
{
	e.preventDefault();
	logina=$("#logina").val();
	clavea=$("#clavea").val();
	empresa=$("#empresa").val().trim();

	// Autodetectar el slug del subdominio si el campo está vacío.
	// corp.atiende.localhost -> "corp" | atiende.localhost -> "" (cuenta principal)
	if (empresa === '') {
		var parts = window.location.hostname.split('.');
		if (parts.length > 1 && ['atiende','www','localhost'].indexOf(parts[0]) === -1) {
			empresa = parts[0];
		}
	}

	$.post("../ajax/usuario.php?op=verificar", {"logina":logina, "clavea":clavea, "empresa":empresa},
        function(data)
        {
            if (data.trim()!='null')
            {
                $(location).attr("href","escritorio.php");
                //bootbox.alert(data);
            }else{                
               // $(location).attr("href","login.php");
                //bootbox.alert("Usuario y/o Password incorrectos");
                Swal.fire({
                    icon: 'error',
                    text: 'Usuario y/o contraseña incorrectos'
                });

            }
         });
});