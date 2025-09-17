<?php
// Aquí puedes agregar lógica PHP para el sistema de reservas
// Por ejemplo:
// session_start();
// Obtener datos de la cancha desde la base de datos
// Verificar disponibilidad de horarios
// $cancha_id = $_GET['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cancha - CanchApp</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .court-image {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .time-slot {
            height: 80px;
            border-radius: 8px;
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .time-slot.disponible {
            background-color: #28a745;
        }
        
        .time-slot.disponible:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .time-slot.parcial {
            background-color: #ffc107;
            color: #000;
        }
        
        .time-slot.ocupado {
            background-color: #dc3545;
            cursor: not-allowed;
        }
        
        .time-slot.pasado {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .time-slot.selected {
            background-color: #007bff !important;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        
        .day-selector {
            border-radius: 8px;
            padding: 8px 12px;
            margin: 0 2px;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 60px;
        }
        
        .day-selector.active {
            background-color: #007bff;
            color: white;
        }
        
        .day-selector:not(.active) {
            background-color: #f8f9fa;
            color: #495057;
        }
        
        .day-selector:hover {
            transform: translateY(-2px);
        }
        
        .date-picker-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .reservation-summary {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .confirm-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .confirm-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="container-fluid text-light p-2">
        <!-- Navbar -->
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg">
                    <a class="navbar-brand me-auto" href="#">Logo</a>
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel">
                        <div class="offcanvas-header">
                            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Logo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="index.php">Home</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="gestion.php">Gestión</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 active" href="buscador.php">Reservar</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="acerca-de.php">Acerca de</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="inicioses.php" class="login-button">Login</a>
                    <button class="navbar-toggler pe-0" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Botón Volver -->
        <div class="row mt-3">
            <div class="col-12">
                <button class="btn btn-outline-secondary" onclick="history.back()">
                    ← Volver al buscador
                </button>
            </div>
        </div>

        <!-- Imagen de la Cancha -->
        <div class="row mt-4">
            <div class="col-12">
                <img src="image/cancha.jpg" 
                     class="img-fluid w-100 court-image" 
                     alt="Club Atlético Boca Juniors"
                     id="courtImage">
            </div>
        </div>

        <!-- Información de la Cancha -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="text-center">
                    <h2 class="text-primary mb-3" id="courtTitle">Club Atlético Boca Juniors</h2>
                    <p class="text-info mb-2">📍 <span id="courtAddress">Alsina 1244, Buenos Aires</span></p>
                    <p class="lead mb-4" id="courtDescription">
                        Cancha de pádel profesional con excelentes instalaciones. Contamos con vestuarios, 
                        estacionamiento y un ambiente familiar perfecto para disfrutar del deporte. 
                        Superficie de césped sintético de última generación e iluminación LED para partidos nocturnos.
                    </p>
                </div>
            </div>
        </div>

        <!-- Sistema de Reservas -->
        <div class="row mt-4">
            <div class="col-12">
                <h3 class="text-center mb-4">Haz clic para reservar</h3>
                
                <!-- Selector de Fecha -->
                <div class="date-picker-container text-center" style="background-image: url('image/padel-fondo.jpg'); background-size: cover; background-position: center;">
                    <h5 class="mb-3">Selecciona el día</h5>
                    <p class="text-light mb-3">Mostrando: <span id="currentDate">14/09/2025</span></p>
                    
                    <div class="d-flex justify-content-center flex-wrap mb-3" id="daySelector">
                        <!-- Los días se generan dinámicamente -->
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <input type="date" id="datePicker" class="form-control" style="max-width: 200px;">
                        <button class="btn btn-primary" onclick="updateAvailability()">Ver fecha</button>
                    </div>
                </div>

                <!-- Leyenda -->
                <div class="legend justify-content-center">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #28a745;"></div>
                        <span>Disponible (4 espacios libres)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ffc107;"></div>
                        <span>Parcialmente ocupado (puedes unirte)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #dc3545;"></div>
                        <span>Cancha completa (4/4 ocupado)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6c757d;"></div>
                        <span>Hora pasada</span>
                    </div>
                </div>

                <!-- Horarios Disponibles -->
                <div class="row g-3" id="timeSlots">
                    <!-- Los horarios se generan dinámicamente -->
                </div>

                <!-- Resumen de Reserva -->
                <div class="reservation-summary bg-light p-3 text-dark" id="reservationSummary">
                    <h5 class="text-primary mb-3">📋 Confirmar Reserva</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Cancha:</strong> <span id="summaryCourtName"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Fecha:</strong> <span id="summaryDate"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Horario:</strong> <span id="summaryTime"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Precio:</strong> <span id="summaryPrice">$2,800</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <button class="confirm-btn" onclick="proceedToBooking()">
                            Continuar con la Reserva
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-5">
            <div class="row p-5 bg-secondary text-white">
                <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
                    <h3 class="mb-2">CanchApp</h3>
                    <p>Tu sitio de confianza para reservar y gestionar canchas de pádel.</p>
                </div>
                <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
                    <h5 class="mb-2">Enlaces</h5>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Inicio</a>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Sobre Nosotros</a>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Servicios</a>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Contacto</a>
                </div>
                <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
                    <h5 class="mb-2">Contacto</h5>
                    <p class="mb-1">Email: info@canchapp.com</p>
                    <p class="mb-1">Tel: +54 11 1234-5678</p>
                    <p class="mb-1">Dirección: Av. Pádel 123, Buenos Aires</p>
                </div>
                <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
                    <h5 class="mb-2">Síguenos</h5>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Instagram</a>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Facebook</a>
                    <a href="#" class="d-block text-white text-decoration-none mb-1">Twitter</a>
                </div>
            </div>
            <div class="row bg-dark text-white text-center py-2">
                <div class="col-12">
                    <small>&copy; 2024 CanchApp. Todos los derechos reservados.</small>
                </div>
            </div>
        </footer>
        <!-- Fin Footer -->
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Datos de ejemplo para horarios
        const timeSlots = [
            { time: '08:00', status: 'disponible', available: 4 },
            { time: '09:00', status: 'disponible', available: 4 },
            { time: '10:00', status: 'disponible', available: 4 },
            { time: '11:00', status: 'disponible', available: 4 },
            { time: '12:00', status: 'disponible', available: 4 },
            { time: '13:00', status: 'disponible', available: 4 },
            { time: '14:00', status: 'disponible', available: 4 },
            { time: '15:00', status: 'parcial', available: 2 },
            { time: '16:00', status: 'disponible', available: 4 },
            { time: '17:00', status: 'disponible', available: 4 },
            { time: '18:00', status: 'disponible', available: 4 },
            { time: '19:00', status: 'disponible', available: 4 },
            { time: '20:00', status: 'disponible', available: 4 },
            { time: '21:00', status: 'disponible', available: 4 },
            { time: '22:00', status: 'disponible', available: 4 }
        ];

        let selectedTimeSlot = null;

        // Inicializar la página
        document.addEventListener('DOMContentLoaded', function() {
            initializePage();
            generateDaySelector();
            generateTimeSlots();
        });

        function initializePage() {
            const today = new Date();
            const datePicker = document.getElementById('datePicker');
            
            if (datePicker) {
                datePicker.min = today.toISOString().split('T')[0];
                datePicker.value = today.toISOString().split('T')[0];
            }
        }

        function generateDaySelector() {
            const daySelector = document.getElementById('daySelector');
            const today = new Date();
            
            const days = ['Sáb', 'Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
            let html = '';
            
            for (let i = 0; i < 7; i++) {
                const date = new Date(today);
                date.setDate(today.getDate() + i);
                
                const dayName = days[(date.getDay() + 6) % 7]; // Ajustar para que Lun = 0
                const dayNum = date.getDate().toString().padStart(2, '0');
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                
                const isActive = i === 0 ? 'active' : '';
                
                html += `
                    <button class="day-selector ${isActive}" onclick="selectDay(this, '${date.toISOString().split('T')[0]}')">
                        ${dayName}<br>${dayNum}/${month}
                    </button>
                `;
            }
            
            daySelector.innerHTML = html;
        }

        function selectDay(button, dateString) {
            // Remover clase active de todos los botones
            document.querySelectorAll('.day-selector').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Agregar clase active al botón seleccionado
            button.classList.add('active');
            
            // Actualizar fecha en el picker y en el texto
            const datePicker = document.getElementById('datePicker');
            const currentDate = document.getElementById('currentDate');
            
            if (datePicker) datePicker.value = dateString;
            if (currentDate) {
                const date = new Date(dateString + 'T00:00:00');
                currentDate.textContent = date.toLocaleDateString('es-AR');
            }
            
            // Actualizar disponibilidad
            updateAvailability();
        }

        function generateTimeSlots() {
            const timeSlotsContainer = document.getElementById('timeSlots');
            
            const html = timeSlots.map(slot => {
                let statusClass = slot.status;
                let statusText = '';
                
                switch(slot.status) {
                    case 'disponible':
                        statusText = `Disponible<br>${slot.available} espacios libres`;
                        break;
                    case 'parcial':
                        statusText = `Parcialmente ocupado<br>${slot.available}/4 espacios ocupados`;
                        break;
                    case 'ocupado':
                        statusText = `Cancha completa<br>4/4 ocupado`;
                        break;
                    case 'pasado':
                        statusText = `Hora pasada`;
                        break;
                }
                
                return `
                    <div class="col-md-6 col-lg-4">
                        <div class="time-slot ${statusClass}" onclick="selectTimeSlot('${slot.time}', this, '${slot.status}')">
                            <div class="fs-5 fw-bold">${slot.time}</div>
                            <div class="small">${statusText}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            timeSlotsContainer.innerHTML = html;
        }

        function selectTimeSlot(time, element, status) {
            if (status === 'ocupado' || status === 'pasado') {
                return; // No permitir selección
            }
            
            // Remover selección previa
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Seleccionar el nuevo slot
            element.classList.add('selected');
            selectedTimeSlot = time;
            
            // Mostrar resumen de reserva
            showReservationSummary(time);
        }

        function showReservationSummary(time) {
            const summary = document.getElementById('reservationSummary');
            const courtName = document.getElementById('courtTitle').textContent;
            const currentDate = document.getElementById('currentDate').textContent;
            
            // Llenar datos del resumen
            document.getElementById('summaryCourtName').textContent = courtName;
            document.getElementById('summaryDate').textContent = currentDate;
            document.getElementById('summaryTime').textContent = time + ':00 - ' + (parseInt(time) + 1) + ':30';
            
            // Mostrar el resumen
            summary.style.display = 'block';
            
            // Scroll suave al resumen
            summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function updateAvailability() {
            const datePicker = document.getElementById('datePicker');
            const currentDate = document.getElementById('currentDate');
            
            if (datePicker.value) {
                const date = new Date(datePicker.value + 'T00:00:00');
                currentDate.textContent = date.toLocaleDateString('es-AR');
                
                // Aquí podrías hacer una llamada a la API para obtener disponibilidad real
                // Por ahora solo regeneramos los slots
                generateTimeSlots();
                
                // Ocultar resumen si estaba visible
                document.getElementById('reservationSummary').style.display = 'none';
                selectedTimeSlot = null;
            }
        }

        function proceedToBooking() {
            if (!selectedTimeSlot) {
                alert('Por favor selecciona un horario');
                return;
            }
            
            // Aquí redirigiría a la página de datos del usuario o procesamiento de pago
            alert(`Redirigiendo a completar reserva para las ${selectedTimeSlot}:00`);
            
            // En una aplicación real:
            // window.location.href = `checkout.php?time=${selectedTimeSlot}&date=${datePicker.value}`;
        }
    </script>
</body>

</html>