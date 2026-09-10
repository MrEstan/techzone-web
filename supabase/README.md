# Supabase Setup - TECHZONEUY

## 1. Crear proyecto
1. Ve a https://supabase.com > New Project
2. Nombre: `techzoneuy` , region: `South America (São Paulo)` (más cerca de Uruguay)
3. Guarda la DB password que te da.

## 2. Obtener credenciales
Supabase Dashboard > Settings > API
- `Project URL` -> `https://TU-PROYECTO.supabase.co`
- `anon public key` -> `eyJ...`
- `service_role key` -> `eyJ...` (solo backend, no exponer en frontend)

Dashboard > Settings > Database > Connection string > URI
- Para PHP PDO: `postgresql://postgres:[PASSWORD]@db.TU-PROYECTO.supabase.co:5432/postgres`

## 3. Crear tablas
Dashboard > SQL Editor > New Query > pega `supabase/schema.sql` > Run

Verifica: Table Editor > ver `products`, `orders`, `admin_users`.

## 4. Crear Storage bucket
Dashboard > Storage > New bucket > `product-images` > Public ON
Si ya ejecutaste `schema.sql`, el bucket ya existe. Verifica políticas en Storage > Policies.

## 5. Configurar proyecto local
Crea `.env` en raíz o edita `config/supabase.php`:
```
SUPABASE_URL=https://TU-PROYECTO.supabase.co
SUPABASE_ANON_KEY=eyJ...
SUPABASE_SERVICE_KEY=eyJ...
```
Nunca commitees `.env` (ya está en .gitignore).

## 6. Probar conexión
```bash
php supabase/test.php
```
Debe responder `Conectado OK - productos: X`

## Notas
- Las imágenes ahora se suben a Storage y se guarda la URL pública en `products.image`
- Fallback: si no hay Supabase configurado, el código usa SQLite local automáticamente.
