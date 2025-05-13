<?php
// Iniciar sesión para manejar la autenticación del usuario
session_start();

// Verificar si el usuario ha iniciado sesión, de lo contrario redirigir al inicio de sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: banca-en-linea.php?error=acceso_no_autorizado');
    exit();
}

// ---------------------------------------------
// Conexión a la base de datos
// ---------------------------------------------
$conexion = new mysqli("localhost", "root", "", "bankcloud");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// ---------------------------------------------
// Obtener vouchers de compras
// ---------------------------------------------
$user_id = $_SESSION['user_id'];
$sql_compras = "SELECT detalles, total, fecha FROM vouchers WHERE user_id = ? AND tipo = 'compra' ORDER BY fecha DESC";
$stmt_compras = $conexion->prepare($sql_compras);
$stmt_compras->bind_param('i', $user_id);
$stmt_compras->execute();
$result_compras = $stmt_compras->get_result();
$vouchers_compras = $result_compras->fetch_all(MYSQLI_ASSOC);
$stmt_compras->close();

// ---------------------------------------------
// Obtener vouchers de transacciones
// ---------------------------------------------
$sql_transacciones = "SELECT descripcion, monto, fecha FROM vouchers WHERE user_id = ? AND tipo = 'transaccion' ORDER BY fecha DESC";
$stmt_transacciones = $conexion->prepare($sql_transacciones);
$stmt_transacciones->bind_param('i', $user_id);
$stmt_transacciones->execute();
$result_transacciones = $stmt_transacciones->get_result();
$vouchers_transacciones = $result_transacciones->fetch_all(MYSQLI_ASSOC);
$stmt_transacciones->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - BankCloud</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* ---------------------------------------------
           Estilos generales de la página
        --------------------------------------------- */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333333;
            margin-bottom: 20px;
            font-size: 28px;
        }

        /* ---------------------------------------------
           Estilos de las pestañas
        --------------------------------------------- */
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 25px;
            background-color: #007bff;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .tab-button.active {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .tab-button:hover {
            background-color: #0056b3;
        }

        .tab-content {
            display: none;
            color: #333333;
        }

        .tab-content.active {
            display: block;
        }

        /* ---------------------------------------------
           Estilos de los vouchers
        --------------------------------------------- */
        .voucher {
            font-family: Arial, sans-serif;
            width: 90%;
            margin: 20px auto;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #333333;
        }

        .voucher-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .voucher-header h1 {
            font-size: 20px;
            color: #333333;
            flex: 1;
            margin-right: 10px;
        }

        .voucher-header img {
            max-width: 70px;
            height: auto;
        }

        .voucher-details {
            margin-bottom: 15px;
        }

        .voucher-details p {
            margin: 5px 0;
            font-size: 13px;
            color: #555555;
        }

        .voucher-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .voucher-table th, .voucher-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            color: #333333;
            word-wrap: break-word;
        }

        .voucher-table th {
            background-color: #f4f4f9;
            font-weight: bold;
        }

        .voucher-total {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #333333;
        }

        /* ---------------------------------------------
           Estilos de la tabla de transacciones
        --------------------------------------------- */
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .transaction-table th, .transaction-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dddddd;
            color: #333333;
        }

        .transaction-table th {
            background-color: #007bff;
            color: #ffffff;
            font-weight: bold;
        }

        .transaction-table tr:last-child td {
            border-bottom: none;
        }

        .no-vouchers {
            text-align: center;
            color: #555555;
            font-size: 18px;
        }

        /* ---------------------------------------------
           Estilos del botón "Volver al Inicio"
        --------------------------------------------- */
        .back-button {
            display: inline-block;
            margin: 20px auto;
            padding: 12px 25px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .back-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .print-button {
            display: inline-block;
            margin: 10px 0;
            padding: 10px 20px;
            background-color: #28a745;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .print-button:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
    </style>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // ---------------------------------------------
        // Inicializar animaciones AOS
        // ---------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({
                duration: 800, // Duración de las animaciones
                once: true // Ejecutar animaciones solo una vez
            });

            // Manejo de pestañas
            const tabs = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(content => content.classList.remove('active'));

                    tab.classList.add('active');
                    const target = document.getElementById(tab.getAttribute('data-tab'));
                    if (target) {
                        target.classList.add('active');
                        AOS.refresh(); // Refrescar animaciones al cambiar de pestaña
                    }
                });
            });
        });

        function printVoucher(voucherId) {
            const voucher = document.getElementById(voucherId).cloneNode(true);
            const printButton = voucher.querySelector('.print-button');
            if (printButton) {
                printButton.remove(); // Eliminar el botón de impresión
            }

            // Aplicar estilos temporales para impresión
            voucher.style.boxShadow = 'none';
            voucher.style.borderRadius = '0';

            const originalContent = document.body.innerHTML; // Guardar el contenido original
            document.body.innerHTML = voucher.outerHTML; // Reemplazar el contenido con el voucher
            window.print(); // Imprimir el contenido actual
            document.body.innerHTML = originalContent; // Restaurar el contenido original
        }
    </script>
</head>
<body>
    <header>
        <a href="pagina-destino.php" class="back-button">Volver al Inicio</a>
    </header>
    <div class="container" data-aos="fade-up">
        <h1>Vouchers</h1>
        <div class="tabs" data-aos="fade-up">
            <button class="tab-button active" data-tab="compras-tab">Vouchers de Compras</button>
            <button class="tab-button" data-tab="transacciones-tab">Vouchers de Transacciones</button>
        </div>
        <div id="compras-tab" class="tab-content active">
            <?php if (!empty($vouchers_compras)): ?>
                <?php foreach ($vouchers_compras as $index => $voucher): ?>
                    <div class="voucher" data-aos="fade-up" id="voucher-<?php echo $index; ?>">
                        <div class="voucher-header">
                            <h1>Factura</h1>
                            <img src="bankcloud big logo.png" alt="Logo">
                        </div>
                        <div class="voucher-details">
                            <p><strong>Fecha:</strong> <?php echo $voucher['fecha']; ?></p>
                            <p><strong>Total:</strong> $<?php echo number_format($voucher['total'], 2); ?></p>
                        </div>
                        <table class="voucher-table">
                            <thead>
                                <tr>
                                    <th>Cantidad</th>
                                    <th>Descripción</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (json_decode($voucher['detalles'], true) as $detalle): ?>
                                    <tr>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                                        <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                        <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="voucher-total">Total: $<?php echo number_format($voucher['total'], 2); ?></p>
                        <button class="print-button" onclick="printVoucher('voucher-<?php echo $index; ?>')">Imprimir Factura</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-vouchers" data-aos="fade-up">No hay vouchers de compras disponibles.</p>
            <?php endif; ?>
        </div>
        <div id="transacciones-tab" class="tab-content">
            <?php if (!empty($vouchers_transacciones)): ?>
                <table class="transaction-table" data-aos="fade-up">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers_transacciones as $voucher): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($voucher['descripcion']); ?></td>
                                <td>$<?php echo number_format($voucher['monto'], 2); ?></td>
                                <td><?php echo $voucher['fecha']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-vouchers" data-aos="fade-up">No hay vouchers de transacciones disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
