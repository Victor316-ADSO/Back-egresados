# 🚀 Guía de Despliegue - API Egresados CURN

## 📋 Requisitos del Servidor

### Requisitos Mínimos
- PHP >= 7.4 (Recomendado: PHP 8.0+)
- MySQL/MariaDB >= 5.7
- Apache con mod_rewrite habilitado
- Composer instalado
- Extensiones PHP requeridas:
  - pdo_mysql
  - json
  - mbstring
  - openssl

### Verificar PHP
```bash
php -v
php -m | grep pdo_mysql
php -m | grep json
```

## 📦 Preparación del Proyecto

### 1. Clonar/Subir el Proyecto
```bash
# Si usas Git
git clone <tu-repo> /var/www/html/back_egresados

# O sube los archivos vía FTP/SFTP
```

### 2. Instalar Dependencias
```bash
cd /var/www/html/back_egresados
composer install --no-dev --optimize-autoloader
```

### 3. Configurar Variables de Entorno
```bash
cp .env.example .env  # Si tienes un ejemplo
nano .env
```

Contenido del `.env`:
```env
# Base de datos
DB_HOST=localhost
DB_NAME=nombre_bd_produccion
DB_USER=usuario_bd
DB_PASS=password_seguro

# JWT Secret (Genera uno seguro)
JWT_SECRET=clave_super_secreta_aleatoria_min_32_caracteres
```

### 4. Generar JWT Secret Seguro
```bash
# En PHP
php -r "echo bin2hex(random_bytes(32));"

# En Linux
openssl rand -base64 32
```

### 5. Configurar Permisos
```bash
# Dar permisos al servidor web
chown -R www-data:www-data /var/www/html/back_egresados
chmod -R 755 /var/www/html/back_egresados

# Proteger .env
chmod 600 .env
```

## 🌐 Configuración de Apache

### Opción 1: Virtual Host (Recomendado)

Crear archivo: `/etc/apache2/sites-available/api-egresados.conf`

```apache
<VirtualHost *:80>
    ServerName api.egresados.tudominio.com
    DocumentRoot /var/www/html/back_egresados/public

    <Directory /var/www/html/back_egresados/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/api-egresados-error.log
    CustomLog ${APACHE_LOG_DIR}/api-egresados-access.log combined
</VirtualHost>
```

Activar el sitio:
```bash
a2ensite api-egresados.conf
a2enmod rewrite
systemctl reload apache2
```

### Opción 2: Subdirectorio

Si está en un subdirectorio como `/back_egresados`, no necesitas cambiar nada. Los `.htaccess` lo manejan.

Acceso: `http://tudominio.com/back_egresados/`

## 🔒 Seguridad en Producción

### 1. Configurar HTTPS
```bash
# Con Let's Encrypt (Certbot)
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d api.egresados.tudominio.com
```

### 2. Actualizar CORS
Editar `src/App/App.php`:
```php
// Cambiar de:
->withHeader('Access-Control-Allow-Origin','*')

// A:
->withHeader('Access-Control-Allow-Origin','https://tudominio.com')
```

### 3. Desactivar Errores Visibles
Editar `src/App/App.php`:
```php
// Cambiar:
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// A:
$errorMiddleware = $app->addErrorMiddleware(false, false, false);
```

### 4. Proteger Archivos Sensibles
Agregar a `.htaccess` en raíz:
```apache
# Proteger archivos sensibles
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "composer\.(json|lock)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 5. Configurar Firewall
```bash
# UFW (Ubuntu)
sudo ufw allow 'Apache Full'
sudo ufw enable
```

## 🗄️ Configuración de Base de Datos

### 1. Crear Base de Datos
```sql
CREATE DATABASE nombre_bd_produccion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Crear Usuario
```sql
CREATE USER 'usuario_bd'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON nombre_bd_produccion.* TO 'usuario_bd'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Importar Tablas
```bash
mysql -u usuario_bd -p nombre_bd_produccion < backup.sql
```

## ✅ Checklist de Despliegue

### Pre-Despliegue
- [ ] Composer instalado en servidor
- [ ] PHP >= 7.4 con extensiones requeridas
- [ ] Apache con mod_rewrite habilitado
- [ ] Base de datos creada y accesible
- [ ] Usuario de BD con permisos correctos

### Durante Despliegue
- [ ] Archivos subidos al servidor
- [ ] `composer install` ejecutado
- [ ] `.env` configurado con datos correctos
- [ ] JWT_SECRET generado (seguro)
- [ ] Permisos configurados (755 dirs, 644 files)
- [ ] .env protegido (chmod 600)

### Post-Despliegue
- [ ] Virtual Host configurado (o .htaccess funcionando)
- [ ] Apache reiniciado
- [ ] HTTPS configurado (SSL)
- [ ] CORS configurado para producción
- [ ] Error display desactivado
- [ ] Logs configurados
- [ ] Firewall configurado

### Testing en Producción
- [ ] GET / - Página de inicio funciona
- [ ] GET /api/test - Test endpoint responde
- [ ] POST /api/auth/login - Login funciona
- [ ] GET /api/programas - Datos de BD se obtienen
- [ ] Verificar logs de errores de Apache

## 🔍 Troubleshooting

### Error 500 - Internal Server Error
```bash
# Revisar logs
tail -f /var/log/apache2/error.log

# Verificar permisos
ls -la /var/www/html/back_egresados
```

### Rutas no funcionan (404)
```bash
# Verificar mod_rewrite
a2enmod rewrite
systemctl restart apache2

# Verificar .htaccess
cat /var/www/html/back_egresados/.htaccess
cat /var/www/html/back_egresados/public/.htaccess
```

### Error de conexión a BD
```bash
# Probar conexión
mysql -u usuario_bd -p -h localhost nombre_bd_produccion

# Verificar .env
cat .env
```

### Composer no encuentra clases
```bash
# Regenerar autoload
composer dump-autoload -o
```

## 📊 Monitoreo

### Logs a Revisar
```bash
# Logs de Apache
tail -f /var/log/apache2/api-egresados-error.log

# Logs de PHP
tail -f /var/log/php/error.log

# Logs de MySQL
tail -f /var/log/mysql/error.log
```

### Herramientas Recomendadas
- **Monitoring:** New Relic, DataDog
- **Logs:** Loggly, Papertrail
- **Uptime:** UptimeRobot, Pingdom
- **Performance:** Blackfire, Xdebug

## 🔄 Actualización del Proyecto

```bash
# 1. Hacer backup
cp -r /var/www/html/back_egresados /var/backups/back_egresados-$(date +%F)

# 2. Actualizar código
git pull origin main
# O subir nuevos archivos

# 3. Actualizar dependencias
composer install --no-dev --optimize-autoloader

# 4. Limpiar caché (si aplica)
# composer dump-autoload -o

# 5. Verificar funcionamiento
curl http://localhost/back_egresados/api/test
```

## 📱 Extras

### Configurar Rate Limiting
Instalar: `composer require middlewares/rate-limit`

### Configurar Logs
```php
// En src/App/App.php
$container->set('logger', function() {
    $logger = new \Monolog\Logger('app');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../../logs/app.log'));
    return $logger;
});
```

### Backup Automático
```bash
# Agregar a crontab
0 2 * * * /usr/local/bin/backup-api.sh
```

---

**Última actualización:** 2024
**Versión:** 1.0.0
**Estado:** ✅ Lista para producción
