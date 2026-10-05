# Referencia histórica del flujo de costos

Este diagrama ilustra FIFO y no todos los métodos vigentes. El código admite también promedio, estándar y LIFO; consultar [Inventario](docs/inventario.md) y [mapa del proyecto](docs/memoria/PROYECTO.md). Las fechas deben provenir de la operación correspondiente, no asumirse siempre como `now()`.

COMPRA (Entrada)
    ↓
Crear Capa de Costo
    ├── cantidad_original = cantidad
    ├── cantidad_disponible = cantidad
    ├── costo_unitario = precio_compra
    └── fecha = now()

VENTA (Salida)
    ↓
Buscar Capas Disponibles (FIFO)
    ├── Ordenar por fecha (más antiguas primero)
    ├── Tomar de las capas más antiguas
    ├── Reducir cantidad_disponible
    └── Calcular costo_total

AJUSTE DE STOCK
    ↓
    ├── Ajuste Positivo → Nueva capa
    └── Ajuste Negativo → Consumir capas FIFO