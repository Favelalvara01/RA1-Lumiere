# SkyReserva — Sistema de Reservas de Vuelos (Arquitectura SOA)

Sistema de gestión de reservas de vuelos construido con **arquitectura orientada
a servicios (SOA)**: cuatro servicios web independientes en PHP, una base de
datos MySQL y un frontend en HTML/CSS/JavaScript, todo desplegable con Docker
o en máquinas virtuales / Google Cloud.

Este proyecto parte del código de ejemplo de la materia
(https://github.com/cristianj87/apwsoa) y fue completado y adaptado para
cumplir con los requisitos de la Unidad 1.

## 1. Descripción del proyecto y arquitectura

El sistema está formado por 4 servicios web desacoplados que se comunican con
el frontend mediante peticiones HTTP (fetch) mandando y recibiendo JSON:

| Servicio                  | Archivo                    | Responsabilidad                                        |
|----------------------------|-----------------------------|---------------------------------------------------------|
| Autenticación              | `php/auth.php`              | Registro e inicio de sesión de usuarios                |
| Búsqueda de vuelos         | `php/search_flights.php`    | Consulta de vuelos disponibles por origen/destino        |
| Reserva de vuelos          | `php/reserve_flight.php`    | Crea una reserva y descuenta el asiento disponible       |
| Gestión de reservas        | `php/manage_reservations.php` | Lista y cancela las reservas del usuario en sesión      |

Cada servicio se conecta a MySQL de forma independiente a través de
`php/db.php` (usando `mysqli` con sentencias preparadas para evitar inyección
SQL), por lo que en un entorno productivo cada uno podría desplegarse incluso
en contenedores/instancias separadas sin cambiar el contrato de la API.

El frontend (`index.html`, `register.html`, `login.html`, `search.html`,
`reservations.html`) es estático y usa `js/scripts.js` para invocar los
servicios vía `fetch`, además de `localStorage` para recordar la sesión del
usuario en el navegador (el user_id que se manda a los servicios).

### Modelo de datos (MySQL)

- **Users**: `user_id, username, password (hash bcrypt), email, created_at`
- **Flights**: `flight_id, airline, origin, destination, departure_date, return_date, price, seats_available`
- **Reservations**: `reservation_id, user_id (FK), flight_id (FK), status, reservation_date`

El script `db/init.sql` crea las tres tablas y precarga varios vuelos de
ejemplo para poder probar la búsqueda sin capturar datos a mano.

## 2. Estructura del repositorio

```
.
├── docker-compose.yml
├── Dockerfile
├── db/
│   └── init.sql
└── src/                      # Document root que se sirve con Apache
    ├── index.html
    ├── register.html
    ├── login.html
    ├── search.html
    ├── reservations.html
    ├── css/styles.css
    ├── js/scripts.js
    └── php/
        ├── db.php
        ├── auth.php
        ├── search_flights.php
        ├── reserve_flight.php
        └── manage_reservations.php
```

## 3. Despliegue con Docker (recomendado para desarrollo/demo)

Requisitos: Docker y Docker Compose instalados.

```bash
# 1. Clonar/copiar este proyecto y entrar a la carpeta
cd sky-reserva

# 2. Levantar los contenedores (Apache+PHP y MySQL)
docker compose up -d --build

# 3. Verificar que ambos servicios estén arriba
docker compose ps
```

- El sitio queda disponible en **http://localhost:8080**
- MySQL queda expuesto en el puerto 3306 (usuario `flight_user`,
  contraseña `flight_pass`, base `flight_reservation`) por si se quiere
  inspeccionar con un cliente como MySQL Workbench o DBeaver.
- El script `db/init.sql` se ejecuta automáticamente **solo la primera vez**
  que se crea el volumen de datos (`db_data`). Si se necesita reiniciar la
  base desde cero: `docker compose down -v && docker compose up -d --build`.

Para ver logs en caso de error:
```bash
docker compose logs -f web
docker compose logs -f db
```

## 4. Despliegue en máquina virtual (sin Docker)

1. Instalar Apache, PHP 8+ con la extensión `mysqli`, y MySQL 8.
2. Copiar el contenido de `src/` a `/var/www/html`.
3. Crear la base de datos y cargar el esquema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE flight_reservation;"
   mysql -u root -p flight_reservation < db/init.sql
   mysql -u root -p -e "CREATE USER 'flight_user'@'%' IDENTIFIED BY 'flight_pass'; GRANT ALL ON flight_reservation.* TO 'flight_user'@'%'; FLUSH PRIVILEGES;"
   ```
4. Definir las variables de entorno que usa `php/db.php` (o editar
   directamente los valores por defecto en ese archivo):
   `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
5. Reiniciar Apache y abrir la IP/dominio de la VM en el navegador.

## 5. Despliegue en Google Cloud

1. **Google Cloud SQL (MySQL)**
   - Crear una instancia de Cloud SQL para MySQL 8.
   - Crear la base `flight_reservation` y ejecutar `db/init.sql` (puede
     importarse desde Cloud Shell o con `mysql` apuntando a la IP pública/
     conexión de Cloud SQL Proxy).
   - Crear el usuario de aplicación y anotar host, usuario y contraseña.
2. **Google Compute Engine** (o App Engine flexible)
   - Crear una VM con Apache + PHP (o usar la imagen de contenedor de este
     proyecto: `docker compose up -d --build` funciona igual dentro de la VM).
   - Configurar las variables de entorno `DB_HOST` (IP de Cloud SQL o el
     socket de conexión), `DB_USER`, `DB_PASS`, `DB_NAME`.
   - Abrir el puerto 80/8080 en el firewall de la VM.
3. **Google Cloud Storage** (opcional)
   - Puede usarse para servir los archivos estáticos (`css/`, `js/`) si se
     desea separar el frontend del backend PHP.

## 6. Instrucciones de uso

1. Entrar a `index.html` y dar clic en **"Crear una cuenta"**.
2. Registrarse con usuario, correo y contraseña (`register.html` →
   `auth.php`, acción `register`).
3. Iniciar sesión (`login.html` → `auth.php`, acción `login`). La sesión se
   guarda en el navegador.
4. En **Buscar vuelos** (`search.html`), filtrar por origen/destino (o dejar
   vacío para ver todos) y presionar **Reservar** en el vuelo deseado
   (`search_flights.php` y `reserve_flight.php`).
5. En **Mis reservas** (`reservations.html`) se listan las reservas del
   usuario y se pueden cancelar (`manage_reservations.php`, acciones `list`
   y `cancel`).

## 7. Notas de seguridad implementadas

- Contraseñas almacenadas con `password_hash` (bcrypt), nunca en texto plano.
- Todas las consultas SQL usan sentencias preparadas (`mysqli::prepare`) para
  evitar inyección SQL.
- La reserva de vuelos valida existencia de usuario/vuelo y disponibilidad de
  asientos dentro de una transacción antes de confirmar.
