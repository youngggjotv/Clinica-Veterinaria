# VetAgenda - Clínica Veterinaria (Estructura PHP)

Proyecto adaptado a la estructura clásica en PHP con carpetas `assets/`, `gestores/`, `mantenedores/`, `reportes/` y control de sesiones por rol.

## Cómo ejecutarlo localmente
### Opción A: Con PHP integrado (sin instalar XAMPP)
1. Abre la carpeta `vetagenda-php` en VS Code.
2. En la terminal ejecuta:
   ```bash
   php -S localhost:8000
   ```
3. Abre `http://localhost:8000/login.php` en tu navegador.

### Opción B: Con XAMPP
1. Copia la carpeta `vetagenda-php` dentro de `C:\xampp\htdocs\`.
2. Inicia Apache en XAMPP.
3. Abre `http://localhost/vetagenda-php/login.php`.

## Credenciales de prueba
- **Recepción (`menu_principal.php`):** `recepcion` / `recepcion123`
- **Admin (`menu_admin.php`):** `admin` / `admin123`
- **Tutor (`menu_cliente.php`):** `cliente` / `cliente123`
