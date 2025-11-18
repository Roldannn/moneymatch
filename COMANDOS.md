# Comandos de MoneyMatch

## Actualizar Equivalencias de Monedas

El comando `currency:update-equivalences` permite actualizar las equivalencias de monedas desde el sitio web de aduana.cl de manera eficiente y flexible.

### Uso Básico

```bash
# Actualizar solo el año actual (2025) - Más rápido
php artisan currency:update-equivalences --current-only

# Actualizar todos los datos disponibles
php artisan currency:update-equivalences --all

# Actualizar un año específico
php artisan currency:update-equivalences --year=2024

# Actualizar un rango de años
php artisan currency:update-equivalences --from-year=2020 --to-year=2024

# Sin opciones: actualiza solo el año actual por defecto
php artisan currency:update-equivalences
```

### Opciones Disponibles

- `--current-only`: Actualiza solo el año actual (2025). Es la opción más rápida y recomendada para actualizaciones regulares.
- `--year=YEAR`: Actualiza un año específico (ej: `--year=2024`)
- `--from-year=YEAR --to-year=YEAR`: Actualiza un rango de años (ej: `--from-year=2020 --to-year=2024`)
- `--all`: Actualiza todos los datos disponibles (2004-2025). Puede tardar varios minutos.

### Ejemplos de Uso

#### Actualización Rápida (Recomendado para uso diario)
```bash
php artisan currency:update-equivalences --current-only
```
Actualiza solo los meses disponibles de 2025. Tarda aproximadamente 1-2 minutos.

#### Actualizar un Año Específico
```bash
php artisan currency:update-equivalences --year=2024
```
Útil cuando necesitas actualizar datos de un año específico.

#### Actualizar Últimos 3 Años
```bash
php artisan currency:update-equivalences --from-year=2022 --to-year=2024
```
Actualiza un rango específico de años sin tener que actualizar todo.

#### Actualización Completa
```bash
php artisan currency:update-equivalences --all
```
Actualiza todos los datos desde 2004 hasta 2025. Puede tardar 10-15 minutos.

### Notas Importantes

1. **Antes de ejecutar**: Asegúrate de haber ejecutado primero el `CurrencySeeder`:
   ```bash
   php artisan db:seed --class=CurrencySeeder
   ```

2. **Tiempo de ejecución**:
   - `--current-only`: ~1-2 minutos
   - `--year=YEAR`: ~1-2 minutos por año
   - `--all`: ~10-15 minutos

3. **Datos actualizados**: El comando usa `updateOrCreate`, por lo que actualiza equivalencias existentes y crea nuevas si no existen.

4. **Parseo mejorado**: El comando utiliza el mismo sistema de parseo mejorado que maneja correctamente formatos europeos (1.027,7500 → 1027.75).

### Ver Ayuda

```bash
php artisan currency:update-equivalences --help
```

