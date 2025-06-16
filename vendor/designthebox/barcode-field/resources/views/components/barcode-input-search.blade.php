<div id="qr-reader" class="w-full h-60 border border-gray-300 rounded-md"></div>

<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const qrContainer = document.getElementById("qr-reader");

        if (!qrContainer) return;

        const qrReader = new Html5Qrcode("qr-reader");

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                // Usa la cámara trasera si está disponible
                const cameraId = devices.find(device => device.label.toLowerCase().includes("back"))?.id || devices[0].id;

                qrReader.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        console.log("QR leído:", decodedText);
                        // Puedes redirigir o llenar un campo input, por ejemplo
                        alert("Código QR leído: " + decodedText);
                        qrReader.stop(); // Detener la cámara después de leer
                    },
                    errorMessage => {
                        // Error de lectura, se puede ignorar o mostrar
                        console.warn("Error al leer:", errorMessage);
                    }
                ).catch(err => {
                    alert("No se pudo iniciar la cámara: " + err);
                });
            }
        }).catch(err => {
            alert("No se pudo acceder a la cámara: " + err);
        });
    });
</script>
