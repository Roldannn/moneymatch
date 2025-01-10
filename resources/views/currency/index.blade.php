<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Divisas</title>
    <!-- Incluir Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        /* Estilo para asegurar que el footer se mantenga en la parte inferior de la página */
        html, body {
            height: 100%;
            margin: 0;
        }
        .wrapper {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
    </style>
</head>
<body class="bg-light">
    <div class="wrapper">
        <div class="content">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <h1 class="text-center mb-4">MoneyMatch - Calculadora Indicadores / Equivalencias (Alpha Version)</h1>

                        <!-- Formulario para la conversión -->
                        <form method="POST" action="{{ route('currency.convert') }}" class="bg-white p-4 rounded shadow-sm">
                            @csrf

                            <!-- Selector de País -->
                            <div class="mb-3">
                                <label for="country" class="form-label">Selecciona un país:</label>
                                <select id="country" name="country" class="form-select" required>
                                    <option value="" disabled selected>Selecciona un país</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">
                                            {{ $currency->currency }} ({{ $currency->equivalence }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Input para el monto -->
                            <div class="mb-3">
                                <label for="amount" class="form-label">Monto:</label>
                                <input type="number" id="amount" name="amount" step="0.01" placeholder="Ingresa el monto" class="form-control" required>
                            </div>

                            <!-- Botón para convertir -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Convertir</button>
                            </div>
                        </form>

                        <!-- Mostrar resultado de la conversión -->
                        @if (session('result'))
                            <div class="alert alert-success mt-3" role="alert">
                                <p>
                                    Moneda: <strong>{{ session('result')['currencyName'] }}</strong><br>
                                    Tasa de conversión: <strong>{{ session('result')['rate'] }}</strong><br>
                                    Monto ingresado: <strong>{{ session('result')['amount'] }}</strong><br>
                                    Resultado: <strong>{{ session('result')['converted'] }}</strong>
                                </p>
                            </div>
                        @endif

                        <!-- Mostrar errores si los hay -->
                        @if ($errors->any())
                            <div class="alert alert-danger mt-3" role="alert">
                                <strong>Error:</strong>
                                <ul>
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
        <footer class="bg-dark text-white text-center py-3">
            <div class="container">
                <p class="mb-0">Para Jocelyn y los aduaneros</p>
                <p>© 2025 MoneyMatch. Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>

    <!-- Incluir Bootstrap JS y Popper.js para funcionalidades avanzadas si es necesario -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>