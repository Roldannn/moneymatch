<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyMatch - Calculadora de Divisas</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1e293b;
            --light-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1e293b;
            line-height: 1.6;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
            padding: 2rem 1rem;
        }

        /* Header */
        .app-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--card-shadow);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }

        .app-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .app-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* Card Styles */
        .main-card {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--card-shadow-hover);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .main-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .form-select,
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .form-select:hover,
        .form-control:hover {
            border-color: #cbd5e1;
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Result Card */
        .result-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .result-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .result-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(5px);
        }

        .result-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .result-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .result-main {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
            text-align: center;
        }

        .result-main-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.2;
        }

        .result-main-label {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Error Alert */
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 1.25rem;
            color: #991b1b;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
        }

        .alert-danger strong {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* Footer */
        footer {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer-content {
            text-align: center;
        }

        .footer-heart {
            color: #ef4444;
            animation: heartbeat 1.5s ease-in-out infinite;
        }

        @keyframes heartbeat {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .app-title {
                font-size: 1.5rem;
            }

            .main-card {
                padding: 1.5rem;
            }

            .result-main-value {
                font-size: 1.5rem;
            }
        }

        /* Input Group Enhancements */
        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .input-group-icon .form-select,
        .input-group-icon .form-control {
            padding-left: 2.75rem;
        }

        /* Badge for period */
        .period-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <header class="app-header">
            <div class="container">
                <div class="text-center">
                    <h1 class="app-title">
                        <i class="bi bi-currency-exchange"></i> MoneyMatch
                    </h1>
                    <p class="app-subtitle">
                        Calculadora de Indicadores / Equivalencias
                    </p>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <!-- Main Form Card -->
                        <div class="main-card">
                            <form method="POST" action="{{ route('currency.convert') }}" id="conversionForm">
                                @csrf

                                <!-- País -->
                                <div class="mb-4">
                                    <label for="country" class="form-label">
                                        <i class="bi bi-globe"></i>
                                        País y Moneda
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-flag"></i>
                                        <select id="country" name="country" class="form-select" required>
                                            <option value="" disabled selected>Selecciona un país</option>
                                            @foreach ($currencies as $currency)
                                                <option value="{{ $currency->id }}" {{ old('country') == $currency->id ? 'selected' : '' }}>
                                                    {{ $currency->country }} - {{ $currency->currency }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Año y Mes en fila -->
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="year" class="form-label">
                                            <i class="bi bi-calendar-year"></i>
                                            Año
                                        </label>
                                        <div class="input-group-icon">
                                            <i class="bi bi-calendar3"></i>
                                            <select id="year" name="year" class="form-select" required>
                                                <option value="" disabled selected>Selecciona un año</option>
                                                @foreach ($years as $year)
                                                    <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>
                                                        {{ $year }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="month" class="form-label">
                                            <i class="bi bi-calendar-month"></i>
                                            Mes
                                        </label>
                                        <div class="input-group-icon">
                                            <i class="bi bi-calendar"></i>
                                            <select id="month" name="month" class="form-select" required>
                                                <option value="" disabled selected>Primero selecciona un año</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monto -->
                                <div class="mb-4">
                                    <label for="amount" class="form-label">
                                        <i class="bi bi-cash-coin"></i>
                                        Monto a Convertir
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="bi bi-currency-dollar"></i>
                                        <input
                                            type="number"
                                            id="amount"
                                            name="amount"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="form-control"
                                            value="{{ old('amount') }}"
                                            required
                                        >
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="bi bi-info-circle"></i> Ingresa el monto en la moneda seleccionada (puedes usar coma o punto como separador decimal)
                                    </small>
                                </div>

                                <!-- Botón de Conversión -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="bi bi-calculator"></i> Convertir
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Resultado -->
                        @if (session('result'))
                            <div class="result-card">
                                <div class="result-header">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Resultado de la Conversión</span>
                                </div>

                                <div class="result-item">
                                    <span class="result-label">
                                        <i class="bi bi-globe"></i> Moneda
                                    </span>
                                    <span class="result-value">{{ session('result')['currencyName'] }}</span>
                                </div>

                                <div class="result-item">
                                    <span class="result-label">
                                        <i class="bi bi-calendar-event"></i> Período
                                    </span>
                                    <span class="result-value">
                                        <span class="period-badge">
                                            <i class="bi bi-calendar"></i>
                                            {{ session('result')['month'] }} {{ session('result')['year'] }}
                                        </span>
                                    </span>
                                </div>

                                <div class="result-item">
                                    <span class="result-label">
                                        <i class="bi bi-graph-up"></i> Tasa de Conversión
                                    </span>
                                    <span class="result-value">{{ number_format(session('result')['rate'], 6) }}</span>
                                </div>

                                <div class="result-item">
                                    <span class="result-label">
                                        <i class="bi bi-arrow-down-circle"></i> Monto Ingresado
                                    </span>
                                    <span class="result-value">{{ number_format(session('result')['amount'], 2) }}</span>
                                </div>

                                <div class="result-main">
                                    <div class="result-main-label">
                                        <i class="bi bi-arrow-right-circle"></i> Equivalente en Dólares (USD)
                                    </div>
                                    <div class="result-main-value">
                                        ${{ number_format(session('result')['converted'], 10, '.', ',') }} USD
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Errores -->
                        @if ($errors->any())
                            <div class="alert alert-danger mt-3" role="alert">
                                <strong>
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Error de Validación
                                </strong>
                                <ul class="mt-2 mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <div class="container">
                <div class="footer-content">
                    <p class="mb-2">
                        Hecho con <i class="bi bi-heart-fill footer-heart"></i> para Jocelyn y los aduaneros
                    </p>
                    <p class="mb-0 text-muted" style="color: rgba(255, 255, 255, 0.7);">
                        © 2025 MoneyMatch. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

    <script>
        const monthsByYear = @json($monthsByYear);
        const monthNames = @json($monthNames);
        const yearSelect = document.getElementById('year');
        const monthSelect = document.getElementById('month');
        const form = document.getElementById('conversionForm');
        const submitBtn = document.getElementById('submitBtn');
        const amountInput = document.getElementById('amount');

        /**
         * Actualiza los meses disponibles cuando se selecciona un año
         */
        yearSelect.addEventListener('change', function() {
            const selectedYear = parseInt(this.value);
            monthSelect.innerHTML = '<option value="" disabled selected>Selecciona un mes</option>';

            if (selectedYear && monthsByYear[selectedYear]) {
                monthsByYear[selectedYear].forEach(function(monthNum) {
                    const option = document.createElement('option');
                    option.value = monthNum;
                    option.textContent = monthNames[monthNum];
                    monthSelect.appendChild(option);
                });
            }
        });

        @if(old('year'))
            yearSelect.value = {{ old('year') }};
            yearSelect.dispatchEvent(new Event('change'));
            @if(old('month'))
                setTimeout(function() {
                    monthSelect.value = {{ old('month') }};
                }, 100);
            @endif
        @endif

        /**
         * Muestra estado de carga en el botón al enviar el formulario
         */
        form.addEventListener('submit', function() {
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });

        /**
         * Formatea el input de monto permitiendo comas y puntos como separadores decimales
         */
        amountInput.addEventListener('input', function(e) {
            const input = e.target;
            const cursorPosition = input.selectionStart;
            let value = input.value;
            const originalValue = value;

            value = value.replace(/,/g, '.');
            value = value.replace(/[^\d.]/g, '');

            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            if (value !== originalValue) {
                const commaCount = (originalValue.substring(0, cursorPosition).match(/,/g) || []).length;
                const newCursorPosition = cursorPosition - commaCount + (value.substring(0, cursorPosition - commaCount).match(/\./g) || []).length;

                input.value = value;

                setTimeout(function() {
                    input.setSelectionRange(newCursorPosition, newCursorPosition);
                }, 0);
            }
        });

        /**
         * Maneja el evento keydown para permitir comas y puntos como separadores decimales
         */
        amountInput.addEventListener('keydown', function(e) {
            const input = e.target;
            const value = input.value;

            if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }

            if (e.keyCode === 190 || e.keyCode === 188 || e.keyCode === 110) {
                if (value.indexOf('.') !== -1 || value.indexOf(',') !== -1) {
                    e.preventDefault();
                }
                return;
            }

            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        @if(session('result'))
            setTimeout(function() {
                document.querySelector('.result-card').scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, 100);
        @endif
    </script>
</body>
</html>
