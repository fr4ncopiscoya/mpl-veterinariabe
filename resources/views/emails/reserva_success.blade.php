<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Confirmación de Reserva</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .email-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333333;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            padding: 5px 0;
        }

        .highlight {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h2>¡Tu reserva ha sido confirmada!</h2>
        <p>Hola {{ $reserva['cliente_nombre'] }},</p>
        <p>Gracias por agendar tu cita en nuestra veterinaria. Aquí están los detalles de tu reserva:</p>

        <ul>
            <li><span class="highlight">Compra Nº:</span> {{ $reserva['purchaseNumber'] }}</li>
            <li><span class="highlight">Fecha:</span> {{ $reserva['fecha'] }}</li>
            <li><span class="highlight">Hora:</span> {{ $reserva['hora'] }}</li>
            <li><span class="highlight">Mascota:</span> {{ $reserva['mascota'] }}</li>
            <li><span class="highlight">Servicio:</span> {{ $reserva['servicio'] }}</li>
            <li><span class="highlight">Monto:</span> {{ $reserva['monto'] }} {{ $reserva['currency'] }}</li>
            <li><span class="highlight">Tarjeta:</span> {{ $reserva['tarjeta'] }}</li>
            <li><span class="highlight">Marca:</span> {{ $reserva['marca'] }}</li>
        </ul>

        <p>Por favor, asegúrate de llegar a tiempo para tu cita.</p>
        <p>¡Te esperamos!</p>
        <p><strong>Veterinaria MPL</strong></p>
    </div>
</body>

</html>