<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Confirmación de Pago Extra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }

        .email-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
        }

        h2 {
            color: #333;
        }

        ul {
            list-style: none;
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
        <h2>¡Tu pago se ha realizado con éxito!</h2>
        <p>Hola {{ $pagoExtra['cliente_nombre'] }},</p>
        <p>Gracias por realizar tu pago extra en nuestra veterinaria. Aquí están los detalles:</p>

        <ul>
            <li><span class="highlight">Compra Nº:</span> {{ $pagoExtra['purchaseNumber'] }}</li>
            <li><span class="highlight">Servicio:</span> {{ $pagoExtra['servicio'] }}</li>
            <li><span class="highlight">Monto:</span> {{ $pagoExtra['monto'] }} {{ $pagoExtra['currency'] }}</li>
            <li><span class="highlight">Tarjeta:</span> {{ $pagoExtra['tarjeta'] }}</li>
            <li><span class="highlight">Marca:</span> {{ $pagoExtra['marca'] }}</li>
        </ul>

        <p><strong>Veterinaria MPL</strong></p>
    </div>
</body>

</html>