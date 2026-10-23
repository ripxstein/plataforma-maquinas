# Plataforma Máquinas — Guía del Proyecto

Plataforma educativa interactiva para el aprendizaje de **Elementos de Máquinas** (ingeniería mecánica). Soporta dos roles: **administrador** (gestión de contenido, alumnos, códigos de acceso) y **alumno** (lectura de módulos, resolución de problemas paso a paso, seguimiento de progreso).

## Instrucción de prompts TOTORO

Si el mensaje del usuario **empieza por `TOTORO`**, convierte lo que sigue en un prompt elaborado con estas secciones: **Contexto y fuentes, Objetivo, Entregables, Límites de autonomía y Definición de completado**. Si faltan datos, completa supuestos razonables usando el contexto conocido del usuario y del proyecto. Esta regla se aplica solo a mensajes que empiezan por esa palabra.

---

## Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje backend | PHP | ^8.2 |
| Framework backend | Laravel | ^12.0 |
| Interactividad frontend | Livewire | ^3.6 |
| Livewire single-file components | Livewire Volt | ^1.7 |
| CSS / UI | TailwindCSS (via PostCSS) | ^3.1 |
| Tailwind plugin | @tailwindcss/forms | ^0.5 |
| Bundler | Vite | ^8.0 |
| Vite plugin | laravel-vite-plugin | ^3.0 |
| Base de datos | MySQL | — (vía Laragon) |
| Testing | Pest | ^3.0 |
| Linting / Formato | Laravel Pint | ^1.27 |
| Auth scaffolding | Laravel Breeze | ^2.4 (dev) |
| Entorno local | Laragon (Windows) | — |

---

## Estructura del Proyecto

```
plataforma-maquinas/
├── app/
│   ├── Auth/CachedUserProvider.php # Usuario de sesión cacheado por ID
│   ├── Console/Commands/CacheModulesCommand.php # php artisan modules:cache
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Auth/              # Controladores de autenticación (Breeze)
│   │   └── Middleware/
│   │       └── AdminMiddleware.php # Protege rutas /admin/*
│   ├── Livewire/
│   │   ├── Actions/
│   │   │   └── Logout.php
│   │   ├── Admin/                 # Paneles del dashboard admin
│   │   │   ├── AccessCodesPanel.php
│   │   │   ├── AlumnosPanel.php
│   │   │   ├── ComponentesPanel.php
│   │   │   ├── ContenidoPanel.php
│   │   │   ├── ProgresosPanel.php
│   │   │   └── SitemapDesigner.php # Diseñador de mapas conceptuales
│   │   ├── Forms/
│   │   │   └── LoginForm.php
│   │   ├── Modulos/
│   │   │   └── ModuloViewer.php   # Visor de módulos para alumnos
│   │   └── Problemas/
│   │       ├── Problema1.php      # Problema legacy hardcoded
│   │       ├── Problema2.php      # Problema legacy hardcoded
│   │       └── ProblemaDinamico.php # Problema dinámico basado en steps
│   ├── Models/
│   │   ├── AccessCode.php         # Códigos de acceso para registro
│   │   ├── Module.php             # Módulo de contenido
│   │   ├── ModuleItem.php         # Elemento dentro de un módulo
│   │   ├── Problem.php            # Problema asociado a un ModuleItem
│   │   ├── ProblemStep.php        # Paso individual de un problema
│   │   ├── ProblemStepOption.php  # Opciones de selección múltiple
│   │   ├── User.php               # Usuario (roles: admin, user)
│   │   ├── UserItemProgress.php   # Progreso de lectura por item
│   │   ├── UserModuleProgress.php # Progreso por módulo
│   │   ├── UserProblemProgress.php # Progreso por problema
│   │   └── ModuleSitemap.php      # Mapa conceptual de un módulo
│   ├── Services/
│   │   ├── ModuleCacheService.php # Caché de módulos, items, problemas y mapas
│   │   └── SitemapService.php     # Marcas de conceptos y saneamiento del mapa
│   ├── Providers/
│   └── View/
│       └── Components/
│           ├── AppLayout.php
│           └── GuestLayout.php
├── database/
│   ├── migrations/                # 19 migraciones al 22-10-2026
│   ├── factories/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ModuleSeeder.php       # Seed con contenido de módulos
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── admin/                 # Vistas del panel admin
│       ├── student/               # Vistas del área de alumnos
│       ├── components/            # Blade components reutilizables
│       ├── layouts/
│       │   ├── app.blade.php      # Layout principal (sidebar + nav)
│       │   └── guest.blade.php    # Layout para auth (login/register)
│       ├── livewire/              # Vistas de componentes Livewire
│       │   ├── admin/
│       │   ├── modulos/
│       │   ├── problemas/
│       │   ├── pages/             # Volt pages (auth)
│       │   └── partials/
│       └── partials/
│           ├── sidebar-admin.blade.php
│           └── sidebar-user.blade.php
├── routes/
│   ├── web.php                    # Rutas principales
│   ├── auth.php                   # Rutas de autenticación (Volt)
│   └── console.php
├── config/                        # Configuración estándar de Laravel
├── public/                        # Assets públicos, imágenes y js/sitemap-diagram.js
├── tests/                        # Pest: autenticación, componentes, progreso y caché
├── composer.json
├── package.json
├── vite.config.js
└── tailwind.config.js
```

---

## Arquitectura y Patrones

### Patrón general
- **No usa controladores REST tradicionales** para la lógica de negocio. Toda la interactividad se maneja con **componentes Livewire full-class** (`app/Livewire/`).
- Las rutas de autenticación usan **Livewire Volt** (single-file components en `resources/views/livewire/pages/auth/`).
- Las páginas Blade de administración montan componentes Livewire. `/alumno/modulo/{slug}` devuelve `student.modulo`, que monta `Modulos\ModuloViewer`; no apunta directamente a la clase Livewire.
- El mapa conceptual se edita con `Admin\SitemapDesigner`, se guarda en `ModuleSitemap` y se presenta al inicio del visor. La interacción del lienzo usa Alpine y `public/js/sitemap-diagram.js`; las marcas de conceptos se extraen del HTML de las lecturas mediante `SitemapService`.

### Roles y autenticación
- **`admin`**: acceso completo al panel `/admin/*`. Protegido por `AdminMiddleware`.
- **`user`** (alumno): acceso al área `/alumno/*`. Rutas protegidas por middleware `auth`.
- El registro requiere un **código de acceso** (`AccessCode`) que asigna un `group_name` al usuario.
- Auth scaffolding generado con **Laravel Breeze** (Livewire stack).
- `config/auth.php` usa `App\Auth\CachedUserProvider` para recuperar por ID usuarios de sesión durante 300 segundos. `User` invalida `auth_user_{id}` al guardar o eliminar; `Logout` también la invalida. Mantener esta invalidación al introducir otras vías de modificación de usuarios.

### Modelo de dominio

```
Module (1) ──► (N) ModuleItem (1) ──► (N) Problem (1) ──► (N) ProblemStep (1) ──► (N) ProblemStepOption
   │                       │                       │
   │                       ▼                       ▼
   │              UserItemProgress       UserProblemProgress
   ├──► (1) ModuleSitemap
   └──► (N) UserModuleProgress
```

- **Module**: agrupación temática con `title`, `slug`, `order`.
- **ModuleItem**: lectura de un módulo. El valor que crean el panel y el seeder es `type: lectura`; contiene HTML en `content`, `order` y `percentage`. No introducir `reading` o `component` sin una migración de datos y revisión del código que los consume.
- **Problem**: problema asociado a una lectura. `component` puede referir a `problemas.problema1`, `problemas.problema2` o `problemas.problema-dinamico`. `is_active` controla visibilidad; `is_example` distingue ejemplos guiados de ejercicios evaluados. Solo estos últimos aportan su `percentage` al progreso del módulo.
- **ProblemStep**: paso de un problema dinámico. Los tipos usados son `numeric`, `multiple_choice`, `true_false`, `text` e `informational`. Incluye respuesta esperada, tolerancia absoluta o porcentual, mensajes e imagen opcional.
- **ProblemStepOption**: opciones para pasos `multiple_choice`, con `is_correct` y `order`.
- **ModuleSitemap**: nodos, conexiones y visibilidad (`is_active`) del mapa conceptual de un módulo.
- **AccessCode**: código con `group_name` y estado `active` para controlar el registro.

### Progreso del alumno
- `UserItemProgress`: rastrea qué items ha leído/completado cada usuario.
- `UserModuleProgress`: progreso agregado por módulo.
- `UserProblemProgress`: estado de resolución de problemas.
- `ModuloViewer` desbloquea lecturas según `UserModuleProgress.unlocked_order`. Los ejemplos y los problemas evaluados se presentan en secuencia; al completarse un problema, el componente hijo emite `problema-completado`.
- El progreso de lectura y problemas se consulta por lotes y se guarda en `user_progress_{userId}_{moduleId}` durante 30 segundos; se invalida al completar contenido.

### Caché y rendimiento
- `ModuleCacheService` mantiene `sidebar_modules`, `module_slug_{slug}`, `module_items_{moduleId}` y `module_sitemap_{moduleId}`. El contenido del módulo y el mapa tienen TTL de una hora. `php artisan modules:cache` precarga sidebar, módulos, lecturas y problemas activos.
- En `ModuloViewer`, `module`, `items` y `visibleItems` son propiedades `#[Computed]`; `slug` y `unlockedOrder` son escalares `#[Locked]`. **No volver a poner colecciones de modelos Eloquent en propiedades públicas del visor**: Livewire puede restaurar cada modelo con consultas por elemento durante la hidratación.
- Al cambiar el slug de un módulo, invalidar el slug anterior y el nuevo. Al mover una lectura o problema, invalidar la caché del módulo origen y del destino. `ContenidoPanel` y `ComponentesPanel` ya siguen esta regla; aplicarla también a nuevos puntos de escritura.
- Los listados de administración usan columnas seleccionadas, conteos agregados y búsquedas con debounce. Mantener las relaciones cargadas solo donde la vista las necesita; comprobar el número de consultas en una petición Livewire real antes de añadir nuevas capas de caché.

---

## Convenciones de Código

### Idioma
- **Código fuente**: inglés (nombres de clases, métodos, variables, campos de BD).
- **UI y contenido**: español (vistas Blade, textos, mensajes al usuario).
- **Comentarios**: español.

### Nombrado
- Modelos: `PascalCase` singular (`Module`, `ProblemStep`).
- Tablas BD: `snake_case` plural (`modules`, `problem_steps`).
- Componentes Livewire: `PascalCase` en subdirectorios por dominio (`Admin/ContenidoPanel`, `Problemas/ProblemaDinamico`).
- Vistas Blade: `kebab-case` con extensión `.blade.php`.
- Rutas: agrupadas por prefijo (`admin.*`, `student.*`).

### Base de datos
- Se usa **MySQL** como BD principal (base de datos `plataforma`, servida por Laragon).
- Migraciones estándar de Laravel con timestamps.
- Las relaciones se definen en ambos lados de los modelos Eloquent.

### Frontend
- **TailwindCSS** es la herramienta principal de estilo; también existe CSS propio para la interfaz educativa y el mapa conceptual. Reutilizar estilos existentes y reservar CSS nuevo para necesidades que Tailwind no cubra bien. La fuente base es `Figtree`.
- Componentes Blade reutilizables en `resources/views/components/` (botones, modales, inputs, dropdown, WYSIWYG editor).
- El componente `edu-wysiwyg.blade.php` es un editor rico personalizado para contenido educativo.
- Las imágenes subidas por el admin se almacenan en `public/images/uploads/`.
- KaTeX y CodeMirror se cargan desde assets locales en el layout. El visor y el diseñador del mapa usan `public/js/sitemap-diagram.js`.

---

## Comandos de Desarrollo

```bash
# Instalar dependencias
composer install
npm install

# Servidor de desarrollo (todo en uno: server + queue + logs + vite)
composer dev

# Solo Vite
npm run dev

# Build de producción
npm run build

# Migraciones
php artisan migrate

# Seed de módulos
php artisan db:seed

# Precargar caché de módulos y menú
php artisan modules:cache

# Tests
composer test
# o
php artisan test

# Linting
./vendor/bin/pint
```

Las pruebas automatizadas usan SQLite en memoria (`phpunit.xml`); la aplicación local usa MySQL. Los casos de `tests/Feature/CacheConsistencyTest.php` comprueban invalidación de usuarios y contenido, y que la hidratación del visor no vuelva a consultar cada lectura. Al 22-10-2026, la suite completa tenía tres fallos conocidos en `Feature/ExampleTest`, `Feature/ProfileTest` y el caso de rollback de `Feature/PerformanceAndProgressTest`; distinguirlos de regresiones nuevas y actualizar esta nota cuando se corrijan.

---

## Rutas Principales

### Admin (`/admin/*`) — requiere `auth` + `admin`
| Ruta | Vista/Componente | Descripción |
|---|---|---|
| `/admin/inicio` | `admin.inicio` | Dashboard admin |
| `/admin/alumnos` | `admin.alumnos` → `Admin\AlumnosPanel` | Gestión de alumnos |
| `/admin/progresos` | `admin.progresos` → `Admin\ProgresosPanel` | Seguimiento de progreso |
| `/admin/contenido` | `admin.contenido` → `Admin\ContenidoPanel` | Gestión de módulos, lecturas y mapas |
| `/admin/componentes` | `admin.componentes` → `Admin\ComponentesPanel` | Gestión de problemas y pasos |
| `/admin/codigos` | `admin.codigos` → `Admin\AccessCodesPanel` | Códigos de acceso |
| `POST /admin/upload-image` | — | Subida de imágenes para el WYSIWYG |

### Alumno (`/alumno/*`) — requiere `auth`
| Ruta | Vista/Componente | Descripción |
|---|---|---|
| `/alumno/inicio` | `student.inicio` | Dashboard alumno |
| `/alumno/modulo/{slug}` | `student.modulo` → `Modulos\ModuloViewer` | Vista Blade que monta el visor Livewire |
| `/alumno/criterios` | `student.criterios` | Sección criterios |
| `/alumno/concentracion` | `student.concentracion` | Sección concentración de esfuerzos |
| `/alumno/fallas` | `student.fallas` | Sección fallas |
| `/alumno/aplicacion` | `student.aplicacion` | Sección aplicación |
| `/alumno/ejes` | `student.ejes` | Sección ejes |
| `/alumno/retos` | `student.retos` | Sección retos |
| `/alumno/bibliografia` | `student.bibliografia` | Bibliografía |

### Auth — middleware `guest`
- `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` (Livewire Volt)

---

## Reglas para Agentes IA

> [!IMPORTANT]
> Estas reglas deben seguirse al contribuir código a este proyecto.

1. **Respetar el patrón Livewire**: toda lógica de UI interactiva debe implementarse como componentes Livewire, no como controladores REST + JS vanilla.
2. **MySQL**: la base de datos es MySQL (servida por Laragon). Se pueden usar funciones específicas de MySQL si es necesario, pero evitar las de PostgreSQL.
3. **Mantener la consistencia de idioma**: código en inglés, UI en español.
4. **TailwindCSS**: usar clases de Tailwind para el styling. No agregar CSS custom a menos que sea estrictamente necesario.
5. **Migraciones**: crear migraciones para cualquier cambio de esquema. Nunca modificar migraciones existentes que ya fueron ejecutadas.
6. **Modelos**: definir `$fillable`, `$casts` y relaciones Eloquent en ambos lados.
7. **Seeders**: si se agrega contenido por defecto, actualizar `ModuleSeeder` o crear un seeder nuevo.
8. **Componentes Blade**: reutilizar los componentes existentes en `resources/views/components/` antes de crear nuevos.
9. **Tests**: usar **Pest** para escribir tests. Ejecutar `php artisan test` para verificar antes de dar por terminado.
10. **No modificar** archivos de configuración estándar de Laravel (`config/`) sin razón justificada.
11. **Caché de usuarios**: toda nueva operación que guarde o elimine un `User` debe preservar los eventos Eloquent de invalidación de `auth_user_{id}`. Si se usa una actualización masiva por query builder, invalidar las claves afectadas explícitamente.
12. **Caché de contenido**: usar `ModuleCacheService::forgetModule()` al modificar módulos, lecturas o problemas. Incluir slug anterior/nuevo y módulo origen/destino cuando cambien. El mapa usa `forgetSitemap()`.
13. **Estado Livewire**: conservar colecciones de módulos, lecturas y problemas en propiedades computadas o variables de render, no en propiedades públicas persistentes. Proteger los identificadores y estados que el cliente no debe alterar con `#[Locked]` o volver a consultarlos en el servidor.
14. **Progreso**: cualquier acción pública que escriba avance debe validar en servidor usuario, pertenencia del item o problema, estado activo y reglas de desbloqueo. No confiar en botones deshabilitados ni en el orden mostrado en Blade.
15. **Verificación**: para cambios en caché o consultas, añadir pruebas Pest que comprueben comportamiento y, cuando proceda, consultas en una segunda petición Livewire. Revisar el diff y ejecutar la suite completa; reportar con precisión fallos existentes y nuevos.

## Riesgos pendientes identificados

- `ModuloViewer::completeReading()` y `completeProblem()` aún necesitan validaciones de desbloqueo y pertenencia en el servidor. `ProblemaDinamico::nextExampleStep()` y `checkStep()` deben exigir el paso esperado antes de registrar finalización. Estas reglas visuales no son controles de autorización.
- `DatabaseSeeder` crea un administrador con credenciales fijas. No usar ese seeder en un entorno expuesto sin sustituir ese mecanismo de provisión.
- La limpieza de HTML de `ContenidoPanel` usa expresiones regulares; revisar el tratamiento de contenido rico antes de aceptar autores no confiables o ampliar permisos de edición.
