# Estructura de la API de Egresados CURN

## 📁 Estructura del Proyecto

La API ha sido reorganizada siguiendo las mejores prácticas y el patrón del proyecto de referencia:

```
back_egresados/
├── .htaccess                    # Redirige todo a public/
├── index.php                    # Punto de entrada raíz (redirige a public/)
├── composer.json                # Dependencias y autoload PSR-4
├── .env                         # Variables de entorno
├── public/                      # Directorio público (DocumentRoot)
│   ├── .htaccess               # Configuración de reescritura
│   └── index.php               # Punto de entrada real
├── src/
│   ├── App/                    # Configuración de la aplicación
│   │   ├── App.php            # Bootstrap principal
│   │   ├── Config.php         # Configuración de la app
│   │   ├── Dependencies.php   # Inyección de dependencias
│   │   ├── Routes.php         # Definición de rutas principales
│   │   ├── Routes/            # Rutas organizadas por módulo
│   │   │   ├── Auth.php
│   │   │   ├── Programas.php
│   │   │   ├── Preguntas.php
│   │   │   ├── Cuestionario.php
│   │   │   ├── Usuario.php
│   │   │   └── Respuestas.php
│   │   └── Middleware/        # Middlewares personalizados
│   ├── Controllers/           # Controladores de la API
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── ProgramasController.php
│   │   ├── PreguntasController.php
│   │   ├── CuestionarioController.php
│   │   └── UsuarioController.php
│   ├── Models/                # Modelos de datos (futuro)
│   └── routes/                # Rutas antiguas (se pueden eliminar)
├── config/                    # Configuraciones adicionales
├── middleware/                # Middlewares antiguos (verificar uso)
└── vendor/                    # Dependencias de Composer
```

## 🚀 Características Principales

### 1. **Autoload PSR-4**
- Namespace: `App\`
- Permite usar clases sin require manual
- Ejemplo: `use App\Controllers\AuthController;`

### 2. **Inyección de Dependencias (PHP-DI)**
- Container DI para gestionar dependencias
- Base de datos accesible desde `$container->get('db')`

### 3. **Controladores con Namespaces**
- Todos los controladores heredan de `BaseController`
- Métodos comunes: `successResponse()`, `errorResponse()`, `getJsonInput()`

### 4. **Rutas Organizadas**
- Rutas agrupadas bajo `/api`
- Archivos de rutas separados por módulo
- CORS configurado globalmente

### 5. **Configuración Centralizada**
- Variables de entorno en `.env`
- Configuración de DB en `Config.php`
- Dependencies en `Dependencies.php`

## 📡 Endpoints Disponibles

### Autenticación
- `POST /api/auth/login` - Login de usuarios
- `GET /api/auth/verify` - Verificar token JWT
- `POST /api/auth/refresh` - Refrescar token
- `POST /api/auth/logout` - Cerrar sesión

### Programas
- `GET /api/programas` - Obtener todos los programas
- `GET /api/programas/{id}` - Obtener programa por ID

### Preguntas
- `GET /api/preguntas` - Obtener todas las preguntas

### Cuestionario
- `POST /api/cuestionario/responder` - Guardar respuesta

### Usuario
- `GET /api/usuario/perfil` - Obtener perfil del usuario
- `PUT /api/usuario/perfil` - Actualizar perfil

### Utilidades
- `GET /` - Información de la API
- `GET /api/test` - Verificar funcionamiento

## 🔧 Variables de Entorno (.env)

```env
DB_HOST=localhost
DB_NAME=curn
DB_USER=root
DB_PASS=
JWT_SECRET=tu_secret_key_aqui
```

## 🎯 Próximos Pasos

1. ✅ Estructura reorganizada según proyecto de referencia
2. ✅ Controllers implementados con namespaces
3. ✅ Rutas organizadas por módulos
4. ✅ DI Container configurado
5. ⏳ Implementar middleware de autenticación
6. ⏳ Crear modelos para entidades
7. ⏳ Agregar validación de datos
8. ⏳ Implementar logging
9. ⏳ Documentación Swagger/OpenAPI

## 📝 Notas Importantes

- La carpeta `src/routes/` antigua se puede eliminar una vez verificado que todo funciona
- El middleware de autenticación debe implementarse en `src/App/Middleware/`
- Los archivos .htaccess permiten URLs limpias sin `index.php`
- Todas las rutas están bajo el prefijo `/api` para mejor organización

## 🔄 Comparación con Proyecto de Referencia

| Característica | API-RESULTADO-DE-APRENDIZAJE | back_egresados |
|---------------|------------------------------|----------------|
| Estructura PSR-4 | ✅ | ✅ |
| PHP-DI Container | ✅ | ✅ |
| Controllers con namespaces | ✅ | ✅ |
| Rutas organizadas | ✅ | ✅ |
| BaseController | ✅ | ✅ |
| Config centralizado | ✅ | ✅ |
| .htaccess público | ✅ | ✅ |

## 🛠️ Comandos Útiles

```bash
# Actualizar dependencias
composer update

# Regenerar autoload
composer dump-autoload

# Instalar nuevas dependencias
composer require vendor/package
```

---
**Última actualización:** 2024
**Versión API:** 1.0.0
