import { Component, OnInit } from '@angular/core';
import { RolesService } from '../../../core/services/roles.service';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { Permissions } from '../../../core/constants/permissions.const';
import { AlertService } from '../../../core/services/alert.service'; 
import { FormsModule } from '@angular/forms';

@Component({
  standalone: true,
  imports: [CommonModule,RouterModule,FormsModule],
  selector: 'app-role-list',
  templateUrl: './role-list.component.html'
})
export class RoleListComponent implements OnInit {
  roles: any[] = [];
  Permissions = Permissions;
  search: string = '';
  page = 1;
  perPage = 15;
  totalPages = 0;
  sortColumn = 'name';
  sortDirection: 'asc' | 'desc' = 'asc';
  constructor(
    private rolesService: RolesService,
    private authService: AuthService,
    private alertService: AlertService
  ) {}

  ngOnInit(): void {
    this.loadRoles();
  }

  hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

loadRoles() {
  const columnIndexMap: { [key: string]: number } = {
    name: 0
  };

  const params = {
    start: (this.page - 1) * this.perPage,
    length: this.perPage,
    'search[value]': this.search,
    'order[0][column]': columnIndexMap[this.sortColumn] ?? 0,
    'order[0][dir]': this.sortDirection,
    // Agrego la definición de columnas esperada por Yajra DataTables
    'columns[0][data]': 'name',
    'columns[0][name]': 'name',
    'columns[0][searchable]': 'true',
    'columns[0][orderable]': 'true',
    'columns[0][search][value]': ''
  };

  console.log('Params enviados:', params);

  this.rolesService.getRolesPaginated(params).subscribe({
    next: (res) => {
      console.log('Respuesta API:', res);
      this.roles = res.data;
      this.totalPages = Math.ceil(res.recordsFiltered / this.perPage);
    },
    error: (err) => {
      console.error('Error al cargar roles:', err);
      this.roles = [];
      this.totalPages = 0;
    }
  });
}


   changePage(page: number) {
    if (page >= 1 && page <= this.totalPages) {
      this.page = page;
      this.loadRoles();
    }
  }

  sortBy(column: string) {
    if (this.sortColumn === column) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = column;
      this.sortDirection = 'asc';
    }
    this.loadRoles();
  }

   delete(id: number) {
    this.alertService.confirm({
      title: '¿Eliminar rol?',
      text: 'Esta acción eliminará el rol de forma permanente.',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      icon: 'warning'
    }).then(confirmed => {
      if (confirmed) {
        this.rolesService.deleteRole(id).subscribe(() => {
          this.roles = this.roles.filter(r => r.id !== id);
          this.alertService.success('El rol fue eliminado correctamente');
        }, () => {
          this.alertService.error('Ocurrió un error al eliminar el rol');
        });
      }
    });
  }
}
