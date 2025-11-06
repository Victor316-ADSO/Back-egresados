# 🛠️ Comandos Útiles - API Egresados CURN

## 📦 Composer

### Instalar Dependencias
```bash
# Desarrollo (con dev dependencies)
composer install

# Producción (sin dev dependencies)
composer install --no-dev --optimize-autoloader

# Actualizar dependencias
composer update

# Actualizar una dependencia específica
composer update slim/slim
```

### Autoload
```bash
# Regenerar autoload
composer dump-autoload

# Regenerar autoload optimizado (producción)
composer dump-autoload -o

# Validar composer.json
composer validate
```

### Información
```bash
# Ver dependencias instaladas
composer show

# Ver dependencias de un paquete
composer show slim/slim

# Buscar paquetes
composer search jwt
```

---

## 🔍 PHP

### Verificar Sintaxis
```bash
# Verificar un archivo
php -l src/App/App.php

# Verificar todos los archivos PHP
find src -name "*.php" -exec php -l {} \;

# En Windows PowerShell
Get-ChildItem -Path src -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
```

### Información de PHP
```bash
# Ver versión
php -v

# Ver extensiones instaladas
php -m

# Ver configuración
php -i

# Ver info de una extensión
php -m | grep pdo_mysql
```

### Ejecutar Código
```bash
# Generar JWT secret
php -r "echo bin2hex(random_bytes(32));"

# Ver configuración de variables
php -r "echo ini_get('display_errors');"
```

---

## 🌐 Apache/Servidor

### Apache (Linux)
```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2

# Ver status
sudo systemctl status apache2

# Ver logs
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

### Apache (XAMPP Windows)
```powershell
# Iniciar Apache
C:\xampp\apache_start.bat

# Detener Apache
C:\xampp\apache_stop.bat

# Ver logs
Get-Content C:\xampp\apache\logs\error.log -Tail 50 -Wait
```

### Verificar Configuración
```bash
# Verificar sintaxis de configuración
apache2ctl configtest

# Ver módulos habilitados
apache2ctl -M | grep rewrite
```

---

## 🗄️ MySQL/Base de Datos

### Conexión
```bash
# Conectar a MySQL
mysql -u root -p

# Conectar a una base de datos específica
mysql -u root -p curn

# Ejecutar SQL desde archivo
mysql -u root -p curn < backup.sql
```

### Comandos en MySQL
```sql
-- Ver bases de datos
SHOW DATABASES;

-- Usar una base de datos
USE curn;

-- Ver tablas
SHOW TABLES;

-- Describir tabla
DESCRIBE egresados;

-- Ver usuarios
SELECT User, Host FROM mysql.user;

-- Crear usuario
CREATE USER 'usuario'@'localhost' IDENTIFIED BY 'password';

-- Dar permisos
GRANT ALL PRIVILEGES ON curn.* TO 'usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Backup y Restore
```bash
# Hacer backup
mysqldump -u root -p curn > backup_$(date +%F).sql

# Restaurar backup
mysql -u root -p curn < backup_2024-11-06.sql

# Backup de tabla específica
mysqldump -u root -p curn egresados > egresados_backup.sql
```

---

## 🧪 Testing

### cURL
```bash
# GET request
curl http://localhost/back_egresados/

# GET con formato
curl -s http://localhost/back_egresados/ | jq

# POST request
curl -X POST http://localhost/back_egresados/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"programa":"123","identificacion":"1234567890"}'

# Con token
curl -X GET http://localhost/back_egresados/api/usuario/perfil \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### HTTPie (alternativa moderna a cURL)
```bash
# Instalar
pip install httpie

# GET
http localhost/back_egresados/

# POST
http POST localhost/back_egresados/api/auth/login \
  programa=123 identificacion=1234567890

# Con token
http GET localhost/back_egresados/api/usuario/perfil \
  "Authorization:Bearer TU_TOKEN"
```

---

## 📁 Archivos y Permisos

### Linux
```bash
# Ver permisos
ls -la

# Cambiar dueño (servidor web)
sudo chown -R www-data:www-data /var/www/html/back_egresados

# Permisos directorios
find /var/www/html/back_egresados -type d -exec chmod 755 {} \;

# Permisos archivos
find /var/www/html/back_egresados -type f -exec chmod 644 {} \;

# Proteger .env
chmod 600 .env

# Ver espacio en disco
df -h
```

### Windows
```powershell
# Ver permisos
Get-Acl C:\xampp\htdocs\back_egresados

# Cambiar permisos (ejecutar como admin)
icacls "C:\xampp\htdocs\back_egresados" /grant Users:(OI)(CI)F
```

---

## 🔐 Git

### Básicos
```bash
# Ver status
git status

# Ver cambios
git diff

# Agregar archivos
git add .
git add src/Controllers/

# Commit
git commit -m "Reorganización completa de la API"

# Push
git push origin main
```

### Ramas
```bash
# Ver ramas
git branch

# Crear rama
git checkout -b feature/nueva-funcionalidad

# Cambiar de rama
git checkout main

# Merge
git merge feature/nueva-funcionalidad
```

### Útiles
```bash
# Ver log
git log --oneline --graph

# Ver commits de un archivo
git log --follow src/App/App.php

# Deshacer cambios (cuidado)
git checkout -- archivo.php

# Ver archivos ignorados
git status --ignored
```

---

## 📊 Monitoreo

### Logs
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Apache access log
tail -f /var/log/apache2/access.log

# PHP error log
tail -f /var/log/php/error.log

# MySQL error log
tail -f /var/log/mysql/error.log
```

### Procesos
```bash
# Ver procesos de Apache
ps aux | grep apache2

# Ver procesos de MySQL
ps aux | grep mysql

# Uso de memoria
free -h

# Uso de CPU
top
htop
```

### Red
```bash
# Ver puertos en uso
netstat -tlnp

# Verificar si puerto 80 está abierto
netstat -tlnp | grep :80

# Test de conexión
ping localhost
curl -I http://localhost/back_egresados/
```

---

## 🚀 Deployment

### Pre-Deploy
```bash
# Verificar sintaxis PHP
find src -name "*.php" -exec php -l {} \;

# Validar composer.json
composer validate

# Instalar dependencias de producción
composer install --no-dev --optimize-autoloader

# Limpiar caché (si aplica)
composer dump-autoload -o
```

### Deploy
```bash
# Rsync a servidor
rsync -avz --exclude 'vendor' --exclude '.git' \
  /local/back_egresados/ \
  user@servidor:/var/www/html/back_egresados/

# SSH al servidor e instalar
ssh user@servidor
cd /var/www/html/back_egresados
composer install --no-dev --optimize-autoloader
```

### Post-Deploy
```bash
# Verificar permisos
chmod -R 755 /var/www/html/back_egresados
chmod 600 /var/www/html/back_egresados/.env

# Reiniciar Apache
sudo systemctl restart apache2

# Verificar funcionamiento
curl -I https://tudominio.com/api/test
```

---

## 🔧 Troubleshooting

### Error 500
```bash
# Ver últimos errores
tail -n 50 /var/log/apache2/error.log

# Verificar permisos
ls -la public/index.php

# Verificar sintaxis PHP
php -l public/index.php
```

### 404 - Rutas no funcionan
```bash
# Verificar mod_rewrite
apache2ctl -M | grep rewrite

# Si no está:
sudo a2enmod rewrite
sudo systemctl restart apache2

# Verificar .htaccess
cat .htaccess
cat public/.htaccess
```

### Error de Base de Datos
```bash
# Probar conexión
mysql -u root -p -h localhost curn

# Verificar .env
cat .env

# Ver logs de MySQL
tail -f /var/log/mysql/error.log
```

### Composer Errors
```bash
# Limpiar caché
composer clear-cache

# Reinstalar vendor
rm -rf vendor
composer install

# Regenerar autoload
composer dump-autoload -o
```

---

## 📦 Backup

### Full Backup
```bash
#!/bin/bash
# backup-api.sh

DATE=$(date +%F-%H%M%S)
BACKUP_DIR="/var/backups/back_egresados"

# Crear directorio
mkdir -p $BACKUP_DIR

# Backup de archivos
tar -czf $BACKUP_DIR/files-$DATE.tar.gz \
  --exclude='vendor' \
  --exclude='.git' \
  /var/www/html/back_egresados/

# Backup de base de datos
mysqldump -u root -p curn > $BACKUP_DIR/db-$DATE.sql

echo "Backup completado: $BACKUP_DIR"
```

### Restore
```bash
# Restaurar archivos
tar -xzf files-2024-11-06.tar.gz -C /

# Restaurar base de datos
mysql -u root -p curn < db-2024-11-06.sql
```

---

## 🔄 Mantenimiento

### Limpieza
```bash
# Limpiar logs antiguos
find /var/log -name "*.log" -mtime +30 -delete

# Limpiar backups antiguos
find /var/backups -name "*.tar.gz" -mtime +60 -delete

# Limpiar composer cache
composer clear-cache
```

### Optimización
```bash
# Optimizar tablas MySQL
mysqlcheck -o curn -u root -p

# Optimizar autoload
composer dump-autoload -o --classmap-authoritative

# Verificar espacio
du -sh /var/www/html/back_egresados
```

---

## 📝 Notas

### Atajos Útiles
```bash
# Alias útiles (agregar a ~/.bashrc o ~/.zshrc)
alias api-log='tail -f /var/log/apache2/error.log'
alias api-cd='cd /var/www/html/back_egresados'
alias api-restart='sudo systemctl restart apache2'
alias api-test='curl -s http://localhost/back_egresados/api/test | jq'
```

### Variables de Entorno
```bash
# Ver todas las variables
printenv

# Ver una específica
echo $DB_HOST

# Cargar .env en shell (para testing)
export $(cat .env | xargs)
```

---

**Última actualización:** 06 Nov 2024
**Nota:** Ajusta rutas según tu sistema operativo y configuración
