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