var chunks = function(array, size) {
    var results = [];
    while (array.length) {
        results.push(array.splice(0, size));
    }
    return results;
};

function sendByChunk(contacts,webSocket,text,modal,subtotal,total) {

    contacts.forEach((contact,key) => {
        subtotal = subtotal +1;

        //setTimeout(function(){
            const texto= `${text}`;
            const mensaje = {
                type: "_msg_externo",
                empresa: globalNombreEmpresa,
                cmd: "chat",
                msg: {
                    to: contact,
                    custom_uid: contact + String(Math.random() * 999),
                    body: {
                        text: texto,
                    }
                }
            };
            webSocket.send(JSON.stringify(mensaje));
            console.log("SEND: "+JSON.stringify(mensaje) );
            modal.find("p").html(`${subtotal.toString()}/${total}`);

       // }, 10000 );

    });
    return subtotal;
}


function wsSendMsjMassive(hostname, port, endpoint, response,modal) {
    modal.modal('show');

    var webSocketURL = hostname + endpoint;

    //console.log("openWSConnection::Connecting to: " + webSocketURL);
    var total = response.contacts.length.toString();
    var contactsBatch = chunks(response.contacts,10);
    try {
        var webSocket = new WebSocket(webSocketURL);
        webSocket.onopen = function(openEvent) {
            //console.log("WebSocket OPEN: " + JSON.stringify(openEvent, null, 4));
            const text = response.menssages.mensaje;
            modal.find("p").html(`0/${total}`);
            var subtotal = 0;

            contactsBatch.forEach( function (contactsBatch,key) {
                setTimeout(function(){
                    modal.find("p").html(`${subtotal.toString()}/${total}`);
                    subtotal = sendByChunk(contactsBatch,webSocket,text,modal,subtotal,total);
                    if (subtotal === parseInt(total)) {
                        // execute last item logic
                        setTimeout(function(){
                            webSocket.close();
                            modal.modal('hide');

                        }, 20000);
                        // webSocket.close()
                    }
                }, 10000 * key);
            });
        };

        webSocket.onclose = function(closeEvent) {
            //console.log("WebSocket CLOSE: " + JSON.stringify(closeEvent, null, 4));
            modal.modal('hide');
            //console.log(" Mensajes fueron enviados ...");
        };

        webSocket.onerror = function(errorEvent) {
            //console.log("WebSocket ERROR: " + JSON.stringify(errorEvent, null, 4));
        };

        webSocket.onmessage = function(messageEvent) {
            var wsMsg = messageEvent.data;
            //console.log("WebSocket MESSAGE: " + wsMsg);
        };
    } catch (exception) {
        console.error(exception);
    }
}
