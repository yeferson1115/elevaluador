import { Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';
import { PermissionGuard } from './core/guards/permission.guard';
import { Permissions } from './core/constants/permissions.const';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./layouts/public-layout/public-layout.component').then(
        (m) => m.PublicLayoutComponent
      ),
    children: [
      { path: '', redirectTo: 'login', pathMatch: 'full' },
      { path: 'login', loadComponent: () => import('./pages/public/login/login.component').then(m => m.LoginComponent) }
    ],
  },
  {
    path: 'admin',
    canActivate: [AuthGuard],
    loadComponent: () =>
      import('./layouts/admin-layout/admin-layout.component').then(
        (m) => m.AdminLayoutComponent
      ),
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./pages/admin/dashboard/dashboard.component').then(
            (m) => m.DashboardComponent
          ),
      },
      {
        path: 'roles',
        loadComponent: () =>
          import('./pages/admin/roles/role-list.component').then(
            (m) => m.RoleListComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_ROLES },
      },
      {
        path: 'roles/create',
        loadComponent: () =>
          import('./pages/admin/roles/role-form.component').then(
            (m) => m.RoleFormComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.CREATE_ROLES },
      },
      {
        path: 'roles/:id/edit',
        loadComponent: () =>
          import('./pages/admin/roles/role-form.component').then(
            (m) => m.RoleFormComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_ROLES },
      },

      // Rutas para usuarios
      {
        path: 'usuarios',
        loadComponent: () =>
          import('./pages/admin/users/user-list.component').then(
            (m) => m.UserListComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_USERS },
      },
      {
        path: 'usuarios/create',
        loadComponent: () =>
          import('./pages/admin/users/user-form.component').then(
            (m) => m.UserFormComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.CREATE_USERS },
      },
      {
        path: 'usuarios/:id/edit',
        loadComponent: () =>
          import('./pages/admin/users/user-form.component').then(
            (m) => m.UserFormComponent
          ),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_USERS },
      },     
      
      // Rutas para ingresos
      {
        path: 'ingresos',
        loadComponent: () =>
          import('./pages/admin/ingresos/ingreso-list.component').then(m => m.IngresoListComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_INGRESO },
      },
      {
        path: 'ingresos/create',
        loadComponent: () =>
          import('./pages/admin/ingresos/ingreso-form.component').then(m => m.IngresoFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.CREATE_INGRESO },
      },
      {
        path: 'ingresos/:id/edit',
        loadComponent: () =>
          import('./pages/admin/ingresos/ingreso-form.component').then(m => m.IngresoFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_INGRESO },
      },
      {
        path: 'ingresos/:id/imagenes',
        loadComponent: () => import('./pages/admin/ingresos-imagenes/ingreso-imagenes.component')
          .then(m => m.IngresoImagenesComponent)
      },
      
      // Rutas para avalúos
      {
        path: 'avaluos',
        loadComponent: () =>
          import('./pages/admin/avaluos/avaluo-list.component').then(m => m.AvaluoListComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_AVALUO },
      },
      {
        path: 'avaluos/:id/edit',
        loadComponent: () =>
          import('./pages/admin/avaluos/avaluo-form.component').then(m => m.AvaluoFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_AVALUO },
      },

      // Rutas para inspecciones
      {
        path: 'inspecciones',
        loadComponent: () =>
          import('./pages/admin/inspecciones/inspeccion-list.component').then(m => m.InspeccionListComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_INSPECTION },
      },
      {
        path: 'inspecciones/:id/edit',
        loadComponent: () =>
          import('./pages/admin/inspecciones/inspeccion-form.component').then(m => m.InspeccionFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_INSPECTION },
      },

      // Rutas para SEC Movilidad Bogotá
      {
        path: 'avaluo-sec-bgta',
        loadComponent: () =>
          import('./pages/admin/sec-movilidad-bog/avaluo-list.component').then(m => m.AvaluoListComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_INGRESO },
      },
      {
        path: 'avaluo-sec-bgta/create',
        loadComponent: () =>
          import('./pages/admin/sec-movilidad-bog/avaluo-form.component').then(m => m.AvaluoFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.CREATE_INGRESO },
      },
      {
        path: 'avaluo-sec-bgta/:id/edit',
        loadComponent: () =>
          import('./pages/admin/sec-movilidad-bog/avaluo-form.component').then(m => m.AvaluoFormComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.EDIT_INGRESO },
      },

      // Rutas para Fasecolda (Memorias)
      {
        path: 'memorias',
        loadComponent: () =>
          import('./core/components/fasecolda-import/fasecolda-import.component').then(m => m.FasecoldaImportComponent),
        canActivate: [PermissionGuard],
        data: { permission: Permissions.VIEW_INGRESO },
      },

      // *** RUTAS PARA VALORES DE REPUESTOS - ACTUALIZADAS ***
      {
        path: 'valores-repuesto',
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./core/components/valores-repuesto/valores-repuesto-list/valores-repuesto-list.component')
                .then(m => m.ValoresRepuestoListComponent),
            canActivate: [PermissionGuard],
            data: { permission: Permissions.VIEW_VAL},
          },
          {
            path: 'nuevo',
            loadComponent: () =>
              import('./core/components/valores-repuesto/valores-repuesto-form/valores-repuesto-form.component')
                .then(m => m.ValoresRepuestoFormComponent),
            canActivate: [PermissionGuard],
            data: { permission: Permissions.CREATE_VAL },
          },
          {
            path: 'editar/:id',
            loadComponent: () =>
              import('./core/components/valores-repuesto/valores-repuesto-form/valores-repuesto-form.component')
                .then(m => m.ValoresRepuestoFormComponent),
            canActivate: [PermissionGuard],
            data: { permission: Permissions.EDIT_VAL },
          },
          {
            path: ':id',
            loadComponent: () =>
              import('./core/components/valores-repuesto/valores-repuesto-detail/valores-repuesto-detail.component')
                .then(m => m.ValoresRepuestoDetailComponent),
            canActivate: [PermissionGuard],
            data: { permission: Permissions.VIEW_VAL },
          }
        ]
      },
    ],
  },
];