## 3. Cómo lo corrimos con Docker

Es la forma que más usamos mientras desarrollábamos, porque no tuvimos que instalar nada aparte de Docker.

```bash
docker compose up -d --build
docker compose ps   # confirma que flight_web y flight_db digan "Up"
```

- El sitio queda en **http://localhost:8080**
- MySQL queda expuesto en el puerto 3306 (usuario `flight_user`, contraseña `flight_pass`, base `flight_reservation`), por si se quiere revisar con Workbench o DBeaver.
- Si el puerto 3306 ya lo está usando otro MySQL en tu máquina (nos pasó a nosotros), cámbialo en `docker-compose.yml` a `"3307:3306"` — no afecta la comunicación interna entre contenedores.
- `db/init.sql` solo se ejecuta la primera vez que se crea el volumen. Para reiniciar todo desde cero: `docker compose down -v && docker compose up -d --build`.

Para ver qué está pasando si algo no prende:

```bash
docker compose logs -f web
docker compose logs -f db
```

## 4. Cómo lo desplegamos en una máquina virtual (Ubuntu Server, sin Docker)

Además de Docker, también lo dejamos corriendo de forma nativa en una VM con **Ubuntu Server 24.04 LTS** (la armamos en VirtualBox), instalando Apache, PHP y MySQL directo sobre el sistema operativo, para tener el proyecto funcionando en un servidor real y no solo en contenedores.

```bash
# Instalar lo necesario
sudo apt install -y apache2 php libapache2-mod-php php-mysql mysql-server git

# Crear la base de datos y el usuario
sudo mysql -e "CREATE DATABASE flight_reservation;
CREATE USER 'flight_user'@'localhost' IDENTIFIED BY 'flight_pass';
GRANT ALL PRIVILEGES ON flight_reservation.* TO 'flight_user'@'localhost';
FLUSH PRIVILEGES;"

# Traer el proyecto y cargar el esquema
git clone https://github.com/Favelalvara01/RA1-Lumiere.git
sudo mysql flight_reservation < RA1-Lumiere/db/init.sql

# Copiar el sitio a donde Apache lo sirve
sudo cp -r RA1-Lumiere/src/* /var/www/html/lumiere/
```

**Importante:** `php/db.php` por defecto busca la base de datos en un host llamado `db` (así está pensado para Docker). En la VM hay que cambiar esa línea a `localhost`, o definirlo como variable de entorno de Apache para no tener que tocar el código cada vez que se actualiza:

```bash
sudo nano /etc/apache2/envvars
# agregar al final: export DB_HOST=localhost
sudo systemctl restart apache2
```

Con la VM en modo de red "Adaptador puente" (para que tenga su propia IP en la red local), el sitio queda disponible en `http://<ip-de-la-vm>/lumiere/`.

## 5. Sobre Google Cloud

Documentamos y dejamos listos los pasos para desplegarlo también en Google Cloud (Cloud SQL + Compute Engine), pero al final nos quedamos con Docker y la VM de Ubuntu Server como nuestras dos formas de despliegue para esta entrega.

## 6. Cómo usarlo

1. Entra a `index.html` y dale a **"Crear una cuenta"**.
2. Regístrate con usuario, correo y contraseña.
3. Inicia sesión — la sesión se guarda en el navegador.
4. En **Buscar vuelos**, filtra por origen/destino (o déjalo vacío para ver todos) y dale **Reservar** al vuelo que quieras.
5. En **Mis reservas** puedes ver y cancelar tus reservas.

## 7. Seguridad

- Las contraseñas se guardan cifradas con `password_hash` (bcrypt), nunca en texto plano.
- Todas las consultas usan sentencias preparadas de `mysqli` para evitar inyección SQL.
- Antes de confirmar una reserva se valida que el usuario y el vuelo existan y que haya asientos disponibles, todo dentro de una transacción.
