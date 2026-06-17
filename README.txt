============================================================
  VELINDA — Tienda de Ropa Online
  Instrucciones de instalación para AeoFree
============================================================

ARCHIVOS INCLUIDOS
──────────────────
  index.html      → Tienda pública + Panel de administración
  api.php         → Backend API (requiere PHP 8.1+ y MySQL)
  velinda_db.sql  → Estructura y datos iniciales de la base de datos
  .htaccess       → Configuración del servidor Apache
  README.txt      → Este archivo

============================================================
  PASO 1 — CREAR LA BASE DE DATOS EN AEOFREE
============================================================

1. Ingresá a tu cuenta en https://www.awardspace.com
   (AeoFree usa la plataforma AwardSpace)

2. Desde el Panel de Control → Database Manager → MySQL

3. Creá una nueva base de datos. Anotá:
   → Nombre de la base de datos (ej: tuusuario_velinda)
   → Usuario de la base de datos
   → Contraseña
   → Host (generalmente "localhost" o el que te indica el panel)

4. Entrá al phpMyAdmin desde el Panel de Control

5. Seleccioná la base de datos que creaste

6. Hacé clic en "Importar" (Import)

7. Elegí el archivo "velinda_db.sql" y hacé clic en "Continuar"

8. ¡Listo! Las tablas y datos de ejemplo se crearán automáticamente.

============================================================
  PASO 2 — CONFIGURAR EL ARCHIVO api.php
============================================================

Abrí api.php con cualquier editor de texto y editá estas líneas:

  define('DB_HOST', 'localhost');           ← Host de tu MySQL
  define('DB_NAME', 'icei_42091155');       ← Nombre de tu base de datos
  define('DB_USER', 'icei_42091155');       ← Usuario de tu base de datos
  define('DB_PASS', 'velinda12345');        ← Contraseña de tu base
  define('ADMIN_KEY', 'velinda_admin_2025'); ← ¡CAMBIÁ esta clave!

IMPORTANTE: La ADMIN_KEY es la contraseña del panel de administración.
Elegí algo seguro como: MiTienda_2025!

============================================================
  PASO 3 — SUBIR LOS ARCHIVOS POR FTP
============================================================

1. Abrí FileZilla (o el cliente FTP que uses)

2. Conectate con los datos FTP de tu cuenta AeoFree:
   → Host: ftp.tudominio.com (o el que te da el panel)
   → Usuario: tu usuario de AeoFree
   → Contraseña: tu contraseña
   → Puerto: 21

3. Navegá a la carpeta raíz de tu sitio (generalmente /htdocs/ o /www/)

4. Subí TODOS estos archivos a esa carpeta:
   ✅ index.html
   ✅ api.php
   ✅ .htaccess
   ❌ NO subas velinda_db.sql ni README.txt (no son necesarios)

NOTA: Asegurate de que .htaccess se suba también
(en algunos FTP los archivos que empiezan con . están ocultos,
activá "Mostrar archivos ocultos" en FileZilla)

============================================================
  PASO 4 — VERIFICAR LA INSTALACIÓN
============================================================

1. Abrí tu dominio en el navegador:
   https://tudominio.com

2. Deberías ver la tienda VELINDA con los productos de ejemplo.

3. Para ingresar al panel de administración:
   → Hacé clic en "⚙ Admin" en la barra superior
   → Ingresá la ADMIN_KEY que configuraste en api.php

============================================================
  PANEL DE ADMINISTRACIÓN — Funciones disponibles
============================================================

📊 Dashboard
   → Estadísticas en tiempo real (productos, clientes, compras, ventas)
   → Últimas 5 compras recientes

👗 Productos
   → Agregar, editar y eliminar productos
   → Configurar precio, precio de oferta, stock, talles y colores
   → Marcar como "Nuevo" o "Destacado"
   → URL de imagen del producto

👥 Clientes
   → Ver todos los clientes registrados
   → Agregar clientes manualmente
   → Buscar y eliminar clientes

💬 Opiniones
   → Aprobar o rechazar opiniones enviadas por clientes
   → Filtrar por pendientes
   → Eliminar opiniones

📦 Compras
   → Ver todas las compras con estado
   → Cambiar estado: pendiente → confirmado → enviado → entregado
   → Registrar compras manualmente (útil para ventas presenciales)

============================================================
  CÓMO AGREGAR IMÁGENES A LOS PRODUCTOS
============================================================

Las imágenes se cargan por URL. Opciones gratuitas:

1. ImgBB (recomendado): https://imgbb.com
   → Subí tu foto → Copiá el "Direct Link"

2. Cloudinary: https://cloudinary.com
   → Plan gratuito con 25GB de almacenamiento

3. Google Drive / Dropbox:
   → Hacé pública la imagen y usá el enlace directo

En el formulario de producto, pegá la URL completa
en el campo "URL Imagen" (ej: https://i.ibb.co/xxxx/foto.jpg)

============================================================
  PERSONALIZACIÓN RÁPIDA
============================================================

En index.html buscá y modificá:

• Nombre del negocio: "VELINDA" → tu nombre
• Descripción hero: "Moda atemporal, estilos únicos..."
• Texto de envío gratis: "Envío gratis en compras superiores a $15.000"
• Footer: "© 2025 VELINDA"
• Color principal: en :root{ --accent: #d4622a } cambiá el color hex

En api.php:
• ADMIN_KEY: tu contraseña de administrador

============================================================
  SOPORTE TÉCNICO
============================================================

Requerimientos del servidor:
  ✅ PHP 8.1 o superior
  ✅ MySQL 5.7 o superior (o MariaDB 10.3+)
  ✅ Extensión PDO habilitada
  ✅ Apache con mod_rewrite (para .htaccess)

Si tenés problemas con el .htaccess en AeoFree,
podés eliminarlo y la tienda igual funcionará.

============================================================
  SEGURIDAD — RECORDATORIOS IMPORTANTES
============================================================

⚠ Cambiá la ADMIN_KEY antes de subir al servidor
⚠ NO compartas tu ADMIN_KEY con nadie
⚠ El archivo velinda_db.sql no debe estar en el servidor público
⚠ Cambiá las contraseñas de la base de datos que sean predeterminadas

============================================================
