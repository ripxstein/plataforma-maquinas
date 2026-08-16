# Plataforma Máquinas — Guía del Proyecto

Plataforma educativa interactiva para el aprendizaje de **Elementos de Máquinas** (ingeniería mecánica). Soporta dos roles: **administrador** (gestión de contenido, alumnos, códigos de acceso) y **alumno** (lectura de módulos, resolución de problemas paso a paso, seguimiento de progreso).

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
│   │   │   └── ProgresosPanel.php
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
│   │   └── UserProblemProgress.php # Progreso por problema
│   ├── Providers/
│   └── View/
│       └── Components/
│           ├── AppLayout.php
│           └── GuestLayout.php
├── database/
│   ├── migrations/                # 16 migraciones
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
├── public/                        # Assets públicos e imágenes subidas
├── tests/
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
- Las vistas Blade son delgadas; delegan la lógica a componentes Livewire.

### Roles y autenticación
- **`admin`**: acceso completo al panel `/admin/*`. Protegido por `AdminMiddleware`.
- **`user`** (alumno): acceso al área `/alumno/*`. Rutas protegidas por middleware `auth`.
- El registro requiere un **código de acceso** (`AccessCode`) que asigna un `group_name` al usuario.
- Auth scaffolding generado con **Laravel Breeze** (Livewire stack).

### Modelo de dominio

```
Module (1) ──► (N) ModuleItem (1) ──► (N) Problem (1) ──► (N) ProblemStep (1) ──► (N) ProblemStepOption
                        │                      │
                        ▼                      ▼
              UserItemProgress         UserProblemProgress
                        │
                        ▼
              UserModuleProgress
```

- **Module**: agrupación temática con `title`, `slug`, `order`.
- **ModuleItem**: contenido dentro de un módulo. Puede ser lectura (`type: reading`) o un componente interactivo (`type: component`). Tiene `content` (HTML) y `percentage` para peso del progreso.
- **Problem**: problema asociado a un item. Puede usar un `component` Livewire específico o ser dinámico (basado en `ProblemStep`). Tiene campo `is_active` para activar/desactivar.
- **ProblemStep**: paso individual con `instruction`, `answer_type` (numeric, select, text), `correct_answer`, `tolerance`, `unit`, mensajes de feedback, e imagen opcional.
- **ProblemStepOption**: opciones para steps de tipo `select`.
- **AccessCode**: código con `group_name` y estado `active` para controlar el registro.

### Progreso del alumno
- `UserItemProgress`: rastrea qué items ha leído/completado cada usuario.
- `UserModuleProgress`: progreso agregado por módulo.
- `UserProblemProgress`: estado de resolución de problemas.

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
- **TailwindCSS** para todo el styling. La fuente base es `Figtree`.
- Componentes Blade reutilizables en `resources/views/components/` (botones, modales, inputs, dropdown, WYSIWYG editor).
- El componente `edu-wysiwyg.blade.php` es un editor rico personalizado para contenido educativo.
- Las imágenes subidas por el admin se almacenan en `public/images/uploads/`.

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

# Tests
composer test
# o
php artisan test

# Linting
./vendor/bin/pint
```

---

## Rutas Principales

### Admin (`/admin/*`) — requiere `auth` + `admin`
| Ruta | Vista/Componente | Descripción |
|---|---|---|
| `/admin/inicio` | `admin.inicio` | Dashboard admin |
| `/admin/alumnos` | `Admin\AlumnosPanel` | Gestión de alumnos |
| `/admin/progresos` | `Admin\ProgresosPanel` | Seguimiento de progreso |
| `/admin/contenido` | `Admin\ContenidoPanel` | Gestión de módulos y contenido |
| `/admin/componentes` | `Admin\ComponentesPanel` | Gestión de problemas y steps |
| `/admin/codigos` | `Admin\AccessCodesPanel` | Códigos de acceso |
| `POST /admin/upload-image` | — | Subida de imágenes para el WYSIWYG |

### Alumno (`/alumno/*`) — requiere `auth`
| Ruta | Vista/Componente | Descripción |
|---|---|---|
| `/alumno/inicio` | `student.inicio` | Dashboard alumno |
| `/alumno/modulo/{slug}` | `Modulos\ModuloViewer` | Visor de módulo |
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
