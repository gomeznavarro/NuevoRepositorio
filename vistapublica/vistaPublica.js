// JavaScript Document

    $(document).ready(function(){
		$(".leer_menos").hide();
		$('.articulo_empresa').hide();
		$(".leer_mas").click(function(){
			$('.articulo_empresa_cortado').hide();
			$('.articulo_empresa').show();
			$('.leer_mas').hide();
			$('.leer_menos').show();
		});
		$('.leer_menos').click(function(){
			$('.articulo_empresa_cortado').show();
			$('.articulo_empresa').hide();
			$('.leer_mas').show();
			$('.leer_menos').hide();
		});

    });

    //validacion de los campos del formulario de inscripcion de candidatos
    $(document).ready(function(){
		//document.write(mensajenuevo);
        validarCampos()

    });

    var jVal;
    var error1 = true;
    var error2 = true;
    var error3 = true;
	var idRemitente = 0;
    var idCodMens = 0;
    var codRedSocial = 0;
    var nivelComunicacion = 0;
    var estado = "";
    var fuentes ='0000';


    function validarCampos() {
	jVal = {
		'nombre' : function() {

			/*$('body').append('<div id="nombreInfo" class="info"></div>');*/

			var nombreInfo = $('#nombreInfo');
			var ele = $('#nombre');
			/*var pos = ele.offset();
			nombreInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
			*/

			//var patt = /^[a-zA-Z\u00f1\u00d1\u00e9\u00ed\u00f3\u00fa\u00e1\u00c1\u00c9\u00cd\u00d3\u00da\u00fc\s\-\'\.\,]+$/;
			var patt = /^\D/;
			
			if( ele.val() == null || ele.val().length < 2 ||  /^\s+$/.test(ele.val())  ) {

					jVal.errors = true;
                                        error1 = true;
					//nombreInfo.removeClass('correct').addClass('error').html("Por favor, escriba su nombre. Ha de tener al menos dos caracteres").show();
					nombreInfo.removeClass('correct').addClass('error').html(nombreObligatorio).show();
					ele.removeClass('normal').addClass('wrong');
					
			}
			else if( ele.val().length >100 ) {

					jVal.errors = true;
                                        error1 = true;
					//nombreInfo.removeClass('correct').addClass('error').html("El nombre es demasiado largo.").show();
					nombreInfo.removeClass('correct').addClass('error').html(nombreLargo).show();
					ele.removeClass('normal').addClass('wrong');
			}
			else if( !patt.test(ele.val()) ) {

					jVal.errors = true;
                                        error1 = true;
					//nombreInfo.removeClass('correct').addClass('error').html("El nombre no puede contener números").show();
					nombreInfo.removeClass('correct').addClass('error').html(nombreNumeros).show();
					ele.removeClass('normal').addClass('wrong');
			}
			else {
					nombreInfo.removeClass('error').addClass('correct').html('&radic;').show();
					ele.removeClass('wrong').addClass('normal');
                                        error1 = false;

			}
		},

		'email' : function() {

			/*$('body').append('<div id="emailInfo" class="info"></div>');*/

			var emailInfo = $('#emailInfo');
			var ele = $('#email');
			var pos = ele.offset();

		/*	emailInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
			*/

			var email = ele.val();
			var email = email.replace(/^\s*|\s*$/g,"");
			var patt = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

			if(!patt.test(email) || email=='') {
					jVal.errors = true;
                    error2 = true;
					//emailInfo.removeClass('correct').addClass('error').html('Introduzca un email válido').show();
					emailInfo.removeClass('correct').addClass('error').html(emailValido).show();
					ele.removeClass('normal').addClass('wrong');
			}
			else if( ele.val().length >100 ) {

					jVal.errors = true;
                    error2 = true;
					//emailInfo.removeClass('correct').addClass('error').html('El email es demasiado largo').show();
					emailInfo.removeClass('correct').addClass('error').html(emailLargo).show();
					ele.removeClass('normal').addClass('wrong');
			}


			else{

			$.ajax({
                    type: "POST",
                    url: "index.php?r=publicaciones/obtenerSoloRemitenteAjax&email="+email+"&nombre="+$('#nombre').val()+""
            }).done(function(r) {
                    var arrR=r.split('_');
                    var existeRemitente=arrR[0];
                    idRemitente=arrR[1];

                    if(existeRemitente==0){
                    	emailInfo.removeClass('error').addClass('correct').html('&radic;').show();
						ele.removeClass('wrong').addClass('normal');
						error2 = false;
                    }
                    else{

					$.ajax({
                    type: "POST",
                    url: "index.php?r=publicaciones/encontrarFuentesAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+""
            }).done(function(r) {

					fuente1=r;
						if(fuente1[0]==1){
						$('#boton_inscripcion1').removeClass('envio1').addClass('envio1_gris');
						$('#boton_inscripcion1').prop('title', '');
						}
						if(fuente1[0]==0){
						$('#boton_inscripcion1').removeClass('envio1_gris').addClass('envio1');
						}
						if(fuente1[1]==1){
						$('#boton_inscripcion2').removeClass('envio2').addClass('envio2_gris');
						$('#boton_inscripcion2').prop('title', ''); 						
						}
						if(fuente1[1]==0){
						$('#boton_inscripcion2').removeClass('envio2_gris').addClass('envio2');											
						}
						if(fuente1[2]==1){
						$('#boton_inscripcion3').removeClass('envio3').addClass('envio3_gris');
						$('#boton_inscripcion3').prop('title', '');						
						}
						if(fuente1[2]==0){
						$('#boton_inscripcion3').removeClass('envio3_gris').addClass('envio3');
						}
						if(fuente1[3]==1){
						$('#boton_inscripcion4').removeClass('envio4').addClass('envio4_gris');
						$('#boton_inscripcion4').prop('title', ''); 					
						}
						if(fuente1[3]==0){
						$('#boton_inscripcion4').removeClass('envio4_gris').addClass('envio4');
						}
						if(fuente1[0]==0 && fuente1[1]==0 && fuente1[2]==0 && fuente1[3]==0)
						{
							emailInfo.removeClass('error').addClass('correct').html('&radic;').show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
						}
						else if(fuente1[0]=='1' && fuente1[1]=='1' && fuente1[2]=='1' && fuente1[3]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Ya está inscrito con todas las opciones. Solo podrá enviar mensajes').show();
							emailInfo.removeClass('error').addClass('correct').html(yaInscrito).show();

							ele.removeClass('wrong').addClass('normal');
						 	error2 = false;
						}
						else if(fuente1[0]=='1' && fuente1[1]=='1' && fuente1[2]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Solo podrá inscribirse con twitter').show();
							emailInfo.removeClass('error').addClass('correct').html(soloTwitter).show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
						}

						else if(fuente1[1]=='1' && fuente1[2]=='1' && fuente1[3]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Solo podrá enviar un cv').show();
							emailInfo.removeClass('error').addClass('correct').html(soloCv).show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
						}
						else if(fuente1[0]=='1' && fuente1[2]=='1' && fuente1[3]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Solo podrá inscribirse con linkedin').show();
							emailInfo.removeClass('error').addClass('correct').html(soloLinkedIn).show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
						}
						else if(fuente1[0]=='1' && fuente1[1]=='1' && fuente1[3]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Solo podrá inscribirse con facebook').show();
							emailInfo.removeClass('error').addClass('correct').html(soloFacebook).show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
						}

						else if(fuente1[0]=='1' || fuente1[1]=='1' || fuente1[2]=='1' || fuente1[3]=='1')
						{
							//emailInfo.removeClass('error').addClass('correct').html('Ya está inscrito con algunas opciones; no podrá inscribirse de nuevo con ellas').show();
							emailInfo.removeClass('error').addClass('correct').html(inscritoAlgunas).show();
							ele.removeClass('wrong').addClass('normal');
							error2 = false;
							nivel_comunic=1;

						}
					});
				} //fin else existeRemitente!=0

				}); //fin ajax done tras obtemerremitente insertar remitente

				} //fin else

			},

		'descripcion' : function() {

			/*$('body').append('<div id="descripcionInfo" class="info"></div>');*/

			var descripcionInfo = $('#descripcionInfo');
			var ele = $('#descripcion');
			var pos = ele.offset();

			/*descripcionInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});*/

			//var patt = /^[a-zA-Z0-9\u00f1\u00d1\u00e9\u00ed\u00f3\u00fa\u00e1\u00c1\u00c9\u00cd\u00d3\u00da\u00fc\@\s\-\'\.\,\;\:\¡\!\¿\?\(\)\/\"\€]+$/;
			////var patt=/[\*\$]/; EN CASO DE CAMBIO QUITAR EL ! del patt.test abajo

			if(ele.val().length > 500 ) {

				jVal.errors = true;
                error3 = true;
				//descripcionInfo.removeClass('correct').addClass('error').html('Se permiten sólo 500 caracteres').show();
				descripcionInfo.removeClass('correct').addClass('error').html(mensajeLargo).show();
				ele.removeClass('normal').addClass('wrong');
			}
			else if(ele.val() == null || ele.val().length < 1 || /^\s+$/.test(ele.val()) ) {

				jVal.errors = true;
                error3 = true;
				//descripcionInfo.removeClass('correct').addClass('error').html('No ha escrito nada...').show();
				descripcionInfo.removeClass('correct').addClass('error').html(sinMensaje).show();
				ele.removeClass('normal').addClass('wrong');
			}
                       /* else if(!patt.test(ele.val())) {

				jVal.errors = true;
                error3 = true;
				descripcionInfo.removeClass('correct').addClass('error').html('Hay caracteres no permitidos').show();
				ele.removeClass('normal').addClass('wrong');
			}*/

			else {
				descripcionInfo.removeClass('error').addClass('correct').html('&radic;').show();
				ele.removeClass('wrong').addClass('normal');
                error3 = false;
			}
		}
	};

	$('#nombre').change(jVal.nombre);
	$('#email').change(jVal.email);
	$('#descripcion').keyup(jVal.descripcion);
    }

    //Inscripciones y mensajes
	/*
	Función inscripcionConCV:
	
	Comprueba si el candidato ya existe, con obtenerRemitenteAjax, que:
		
		Si no existe, devuelve 0_idCandidato:		
			activa JavaScript guardaMensaje()
		Si existe, devuelve 1_idCandidato:
			va al controlador encontrarFuentesAjax, devuelve la fuente y activa JavaScript guardaMensajeUpdate() si fuente[0]!=1
	
	*/

    function inscripcionConCV() {
		//document.write(nivel_comunic);
        if(!error1 && !error2 && !error3) {

            bloqueaPantallaSinTimeout ();
            // Enviamos la petición AJAX
            $.ajax({
                    type: "POST",
                    url: "index.php?r=publicaciones/obtenerRemitenteAjax&email="+$('#email').val()+"&nombre="+$('#nombre').val()+"&idPublicacion="+idPublicacion+""
            }).done(function(r) {
                    var arrR=r.split('_');
                    var existeRemitente=arrR[0];
                    idRemitente=arrR[1];

                    if(existeRemitente==0){
						idCodMens=1;
						nivelComunicacion=2;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes='0000';
						//La pantalla se desbloquea en la siguiente llamada
						guardaMensaje();
						$('#registro').css({display:'none'});
						$('#infoCV').css({display:'block'});
						$('#cv').css({display:'block'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
						
					  }
                    else{

						$.ajax({
						type: "POST",
						url: "index.php?r=publicaciones/encontrarFuentesAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+""
				}).done(function(r) {

						fuente1=r;
							if (fuente1=='0000'){
								idCodMens=1;
								nivelComunicacion=2;
								//Estado 1 -> pendiente
								estado = 1;
								fuentes='0000';
								//La pantalla se desbloquea en la siguiente llamada
								guardaMensajeUpdate();
								$('#registro').css({display:'none'});
								$('#infoCV').css({display:'block'});
								$('#cv').css({display:'block'});
								$('#nombreInfo').css({display:'none'});
								$('#emailInfo').css({display:'none'});
								$('#descripcionInfo').css({display:'none'});
							}

							else if(fuente1[0]=='1')
							{
								$('body').append('<div id="emailInfo" class="info"></div>');
								var ele = $('#email');
								var pos = ele.offset();
								var emailInfo = $('#emailInfo');
								emailInfo.css({
									top: pos.top-30,
									left: pos.left+ele.width()-120
								});
								//emailInfo.removeClass('correct').addClass('error').html('Ya está inscrito con CV').show();
								emailInfo.removeClass('correct').addClass('error').html(yaCv).show();
								ele.removeClass('normal').addClass('wrong');
			                	desbloqueaPantalla ();

							}

							else{
								if(fuente1[1]==1 || fuente1[2]==1 || fuente1[3]==1)
								nivelComunicacion=1
								else
								nivelComunicacion=2;
								fuente1='0'+fuente1[1]+fuente1[2]+fuente1[3]
								idCodMens=1;								
								//Estado 1 -> pendiente
								estado = 1;
								fuentes=fuente1;
								//La pantalla se desbloquea en la siguiente llamada
								guardaMensajeUpdate();
								$('#registro').css({display:'none'});
								$('#infoCV').css({display:'block'});
								$('#cv').css({display:'block'});

								$('#nombreInfo').css({display:'none'});
								$('#emailInfo').css({display:'none'});
								$('#descripcionInfo').css({display:'none'});

							}
							
						});
					}
            	});				
        	}
               if(error1) {
             $('body').append('<div id="nombreInfo" class="info"></div>');
        	var ele = $('#nombre');
			var pos = ele.offset();
			var nombreInfo = $('#nombreInfo');
			nombreInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            //nombreInfo.removeClass('correct').addClass('error').html("El nombre ha de tener al menos dos caracteres").show();
			nombreInfo.removeClass('correct').addClass('error').html(nombreObligatorio).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error2) {
            $('body').append('<div id="emailInfo" class="info"></div>');
			var ele = $('#email');
			var pos = ele.offset();
			var emailInfo = $('#emailInfo');
            emailInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
			//emailInfo.removeClass('correct').addClass('error').html('Introduzca un email válido').show();
            emailInfo.removeClass('correct').addClass('error').html(emailValido).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error3) {
            $('body').append('<div id="descripcionInfo" class="info"></div>');
			var ele = $('#descripcion');
			var pos = ele.offset();
			var descripcionInfo = $('#descripcionInfo');
            descripcionInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            //descripcionInfo.removeClass('correct').addClass('error').html('No ha escrito mucho...').show();
			if($('#descripcion').val().length > 500 ) {
            descripcionInfo.removeClass('correct').addClass('error').html(mensajeLargo).show();
			}
			else{
            descripcionInfo.removeClass('correct').addClass('error').html(sinMensaje).show();
			}
			ele.removeClass('normal').addClass('wrong');
        }
    }

    function inscripcionConRedSocial(codRed) {
        if(!error1 && !error2 && !error3) {

            bloqueaPantalla ();
            // Enviamos la petición AJAX
            $.ajax({
                    type: "POST",
                    url: "index.php?r=publicaciones/obtenerRemitenteAjax&email="+$('#email').val()+"&nombre="+$('#nombre').val()+"&idPublicacion="+idPublicacion+""
            }).done(function(r) {
		var arrR=r.split('_');
		var existeRemitente=arrR[0];
		idRemitente=arrR[1];
		codRedSocial=codRed;

		//si no existe aún el remitente
		if(existeRemitente==0){
		    idCodMens=1;
		    nivelComunicacion=1;
		    //Estado 1 -> pendiente
		    estado = 1;
		    fuentes='0000';

		    guardaMensajeRed();
		    $('#registro').css({display:'none'});
		    $('#nombreInfo').css({display:'none'});
		    $('#emailInfo').css({display:'none'});
		    $('#descripcionInfo').css({display:'none'});
		}
		// si ya existe el remitente
		else
		{
		    $.ajax({
		    type: "POST",
		    url: "index.php?r=publicaciones/encontrarFuentesAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+""
		}).done(function(r) {

				fuente1=r;

				if (codRedSocial==2) { //inscripcion con LinkedIn

					// si hasta ahora sólo había enviado mensajes
					if (fuente1=='0000'){
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes='0000';
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
					}
					//si ya está inscrito con LinkedIn, dará error
					else if(fuente1[1]=='1'){

						$('body').append('<div id="emailInfo" class="info"></div>');
						var ele = $('#email');
						var pos = ele.offset();
						var emailInfo = $('#emailInfo');
						emailInfo.css({
							top: pos.top-30,
							left: pos.left+ele.width()-120
						});
						//emailInfo.removeClass('correct').addClass('error').html('Ya está inscrito con LinkedIn').show();
						emailInfo.removeClass('correct').addClass('error').html(yaLinkedIn).show();
						ele.removeClass('normal').addClass('wrong');
				desbloqueaPantalla ();

					}
					//si estaba inscrito con cv u otras redes, mantiene sus dígitos y graba un 1 en las posición [1]
					else{

						fuente1=fuente1[0]+'0'+fuente1[2]+fuente1[3]
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes=fuente1;
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});

					}
				}
				else if (codRedSocial==3) { //inscripcion con Facebook

					// si hasta ahora sólo había enviado mensajes
					if (fuente1=='0000'){
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes='0000';
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
					}
					//si ya está inscrito con Facebook, dará error
					else if(fuente1[2]=='1'){

						$('body').append('<div id="emailInfo" class="info"></div>');
						var ele = $('#email');
						var pos = ele.offset();
						var emailInfo = $('#emailInfo');
						emailInfo.css({
							top: pos.top-30,
							left: pos.left+ele.width()-120
						});
						//emailInfo.removeClass('correct').addClass('error').html('Ya está inscrito con Facebook').show();
						emailInfo.removeClass('correct').addClass('error').html(yaFacebook).show();
						ele.removeClass('normal').addClass('wrong');
				desbloqueaPantalla ();

					}
					//si estaba inscrito con cv u otras redes, mantiene sus dígitos y graba un 1 en las posición [2]
					else{

						fuente1=fuente1[0]+fuente1[1]+'0'+fuente1[3]
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes=fuente1;
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
					}
				}
				else if (codRedSocial==4) { //inscripcion con Twitter

					// si hasta ahora sólo había enviado mensajes
					if (fuente1=='0000'){
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes='0000';
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
					}
					//si ya está inscrito con Twitter, dará error
					else if(fuente1[3]=='1'){

						$('body').append('<div id="emailInfo" class="info"></div>');
						var ele = $('#email');
						var pos = ele.offset();
						var emailInfo = $('#emailInfo');
						emailInfo.css({
							top: pos.top-30,
							left: pos.left+ele.width()-120
						});
						emailInfo.removeClass('correct').addClass('error').html(yaTwitter).show();
						ele.removeClass('normal').addClass('wrong');
				desbloqueaPantalla ();

					}
					//si estaba inscrito con cv u otras redes, mantiene sus dígitos y graba un 1 en las posición [3]
					else{

						fuente1=fuente1[0]+fuente1[1]+fuente1[2]+'0';
						idCodMens=1;
						nivelComunicacion=1;
						//Estado 1 -> pendiente
						estado = 1;
						fuentes=fuente1;
						guardaMensajeUpdate();
						$('#registro').css({display:'none'});
						$('#nombreInfo').css({display:'none'});
						$('#emailInfo').css({display:'none'});
						$('#descripcionInfo').css({display:'none'});
					}
				}
			});
		}

            });

		}

		if(error1) {
             $('body').append('<div id="nombreInfo" class="info"></div>');
        	var ele = $('#nombre');
			var pos = ele.offset();
			var nombreInfo = $('#nombreInfo');
			nombreInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            nombreInfo.removeClass('correct').addClass('error').html(nombreObligatorio).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error2) {
            $('body').append('<div id="emailInfo" class="info"></div>');
			var ele = $('#email');
			var pos = ele.offset();
			var emailInfo = $('#emailInfo');
            emailInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            emailInfo.removeClass('correct').addClass('error').html(emailValido).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error3) {
            $('body').append('<div id="descripcionInfo" class="info"></div>');
			var ele = $('#descripcion');
			var pos = ele.offset();
			var descripcionInfo = $('#descripcionInfo');
            descripcionInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
			if($('#descripcion').val().length > 500 ) {
            descripcionInfo.removeClass('correct').addClass('error').html(mensajeLargo).show();
			}
			else{
            descripcionInfo.removeClass('correct').addClass('error').html(sinMensaje).show();
			}
			ele.removeClass('normal').addClass('wrong');
        }
    }


    function soloMensaje() {

        if(!error1 && !error2 && !error3) {

            bloqueaPantalla ();
            // Enviamos la petición AJAX

			$.ajax({
                    type: "POST",
                    url: "index.php?r=publicaciones/obtenerRemitenteAjax&email="+$('#email').val()+"&nombre="+$('#nombre').val()+"&idPublicacion="+idPublicacion+""
            }).done(function(r) {
                    var arrR=r.split('_');
                    var existeRemitente=arrR[0];
                    idRemitente=arrR[1];

                    if(existeRemitente==0){

						idCodMens=2;
                    	nivelComunicacion=2;
						fuentes='0000';
						estado=1;
					 	guardaMensajeInscripcion();
                    }
                    else{
						idCodMens=2;
						
						guardaMensajeSolo();
					}

                    $('#registro').css({display:'none'});
                    $('#nombreInfo').css({display:'none'});
                    $('#emailInfo').css({display:'none'});
                    $('#descripcionInfo').css({display:'none'});
 				});
        }

        if(error1) {
             $('body').append('<div id="nombreInfo" class="info"></div>');
        	var ele = $('#nombre');
			var pos = ele.offset();
			var nombreInfo = $('#nombreInfo');
			nombreInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            nombreInfo.removeClass('correct').addClass('error').html(nombreObligatorio).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error2) {
            $('body').append('<div id="emailInfo" class="info"></div>');
			var ele = $('#email');
			var pos = ele.offset();
			var emailInfo = $('#emailInfo');
            emailInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
            emailInfo.removeClass('correct').addClass('error').html(emailValido).show();
			ele.removeClass('normal').addClass('wrong');
        }
        if(error3) {
            $('body').append('<div id="descripcionInfo" class="info"></div>');
			var ele = $('#descripcion');
			var pos = ele.offset();
			var descripcionInfo = $('#descripcionInfo');
            descripcionInfo.css({
				top: pos.top-30,
				left: pos.left+ele.width()-120
			});
			if($('#descripcion').val().length > 500 ) {
            descripcionInfo.removeClass('correct').addClass('error').html(mensajeLargo).show();
			}
			else{
            descripcionInfo.removeClass('correct').addClass('error').html(sinMensaje).show();
			}
			ele.removeClass('normal').addClass('wrong');
        }
    }

    function guardaMensaje() {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=mensaje/nuevoAjax",
				//data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&idRecipiente=0&descripcion="+$('#descripcion').val()+""
				data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&sendEmail=1&idRecipiente=0&descripcion="+$('#descripcion').val()+""
        }).done(function(idMensaje) {
               guardaInscripcion(idMensaje, 0);
			   document.getElementById('cv').id_mensaje.value=idMensaje;


        });
    }

	function guardaMensajeRed() {
        // Enviamos la petición AJAX
        $.ajax({
			type: "POST",
			url: "index.php?r=mensaje/nuevoAjax",
			data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&sendEmail=1&idRecipiente=0&descripcion="+$('#descripcion').val()+""
        }).done(function(idMensaje) {
		guardaInscripcionRed(idMensaje);
        });
    }

	function guardaMensajeInscripcion() {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=mensaje/nuevoAjax",
				data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&sendEmail=1&idRecipiente=0&descripcion="+$('#descripcion').val()+""
        }).done(function(idMensaje) {
               guardaInscripcion(idMensaje, 1);

        });
    }
	/*function borrar(idRemitente) {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=publicacion/borrarRemitenteAjax&idRemitente="+idRemitente,
        }).done(function(r) {

               window.location.href="index.php?r=publicaciones/vistaPublica&id="+idPublicacion;
        });
    }*/
	function guardaMensajeUpdate() {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=mensaje/nuevoAjax",
				data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&sendEmail=1&idRecipiente=0&descripcion="+$('#descripcion').val()+""
        }).done(function(idMensaje) {
               guardaInscripcionUpdate(idMensaje);
			   document.getElementById('cv').id_mensaje.value=idMensaje;

        });
    }

	function guardaMensajeSolo() {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=mensaje/nuevoAjax",
				data:"idCodMens="+idCodMens+"&idPublicacion="+idPublicacion+"&idRemitente="+idRemitente+"&sendEmail=1&idRecipiente=0&descripcion="+$('#descripcion').val()+""
				 }).done(function(idMensaje) {

                //La pantalla venia bloqueada de la peticion anterior
                desbloqueaPantalla ();
				window.location.href="index.php?r=publicaciones/vistaPublica&id="+idPublicacion+"&confirmado=2&idRemitente="+idRemitente+"&idMensaje="+idMensaje;
        });
    }

    function subirCv() {
        document.getElementById('cv').id_publicacion.value=idPublicacion;
        document.getElementById('cv').id_candidato.value=idRemitente;
		idMensaje=document.getElementById('cv').id_mensaje.value
        document.getElementById('cv').method="post";
        document.getElementById('cv').action="index.php?r=remitente/subirCvAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+"&idMensaje="+idMensaje;
        document.getElementById('cv').submit();
	$('#nombreInfo').css({display:'none'});
	$('#emailInfo').css({display:'none'});
	$('#descripcionInfo').css({display:'none'});
    }
    
    //Importante para que al subir el cv si ha habifdo error, conserve el id_candidato y el id_publicacion
    function subirCv2() {		

        idPublicacion=document.getElementById('cv2').id_publicacion.value;
        idRemitente=document.getElementById('cv2').id_candidato.value;
        document.getElementById('cv2').method="post";
        document.getElementById('cv2').action="index.php?r=remitente/subirCvAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion;
        document.getElementById('cv2').submit();
    }
	
    function guardaInscripcion(idMensaje, redirecciona) {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=inscripcion/nuevoAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+"&nivel="+nivelComunicacion+"&fuentes="+fuentes+"&estado="+estado+"&idMensaje="+idMensaje+"&idProcedencia="+idProcedencia+""
        }).done(function(r) {
            //La pantalla venia bloqueada de la peticion anterior
            desbloqueaPantalla ();
	    if(redirecciona) {
		window.location.href="index.php?r=publicaciones/vistaPublica&id="+idPublicacion+"&confirmado=2&idRemitente="+idRemitente+"&idMensaje="+idMensaje;
	    }
        });
    }

    function guardaInscripcionRed(idMensaje) {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=inscripcion/nuevoAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+"&nivel="+nivelComunicacion+"&fuentes="+fuentes+"&estado="+estado+"&idMensaje="+idMensaje+"&idProcedencia="+idProcedencia+""
        }).done(function(r) {
        	redirigirARedSocial(idMensaje);
        });
    }

    function redirigirARedSocial(idMensaje)
    {
	var urlRetorno = urlencode(encode64("index.php?r=publicaciones/vistaPublica&id="+idPublicacion+"&idRemitente="+idRemitente+"&idMensaje="+idMensaje+'&idRed='+codRedSocial));
	window.location.href = 'index.php?r=network/vincularCandidato&id='+codRedSocial+'&candidato='+idRemitente+'&urlRetorno='+urlRetorno;
    }

    function guardaInscripcionUpdate(idMensaje) {
        // Enviamos la petición AJAX
        $.ajax({
                type: "POST",
                url: "index.php?r=inscripcion/updateAjax&idCandidato="+idRemitente+"&idPublicacion="+idPublicacion+"&nivel="+nivelComunicacion+"&fuentes="+fuentes+"&estado="+estado+"&idMensaje="+idMensaje+"&idProcedencia="+idProcedencia+""
        }).done(function(r) {
                if (codRedSocial != undefined && codRedSocial != null && codRedSocial != '') {
            	    redirigirARedSocial(idMensaje);
            	} else {
            		//La pantalla venia bloqueada de la peticion anterior
                	desbloqueaPantalla();
            	}
        });
    }

	