import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgIf, NgFor } from '@angular/common';
import { environment } from '../../../../environments/environment';
import { HasPermissionDirective } from '../../../core/directives/has-permission.directive';
import { Permissions } from '../../../core/constants/permissions.const';

@Component({
  selector: 'app-inspeccion-list',
  standalone: true,
  templateUrl: './inspeccion-list.component.html',
  styleUrls: ['./inspeccion-list.component.css'],
  imports: [CommonModule, FormsModule, NgIf, NgFor,HasPermissionDirective]
})
export class InspeccionListComponent {
  Permissions = Permissions;
  filtro = '';
  loading = false;
  error: string | null = null;

  avaluos: Ingreso[] = [];
  currentPage = 1;
  lastPage = 1;

  constructor(private service: IngresoService, private router: Router) {
    this.cargarAvaluos();
  }

  cargarAvaluos(page: number = 1): void {
    this.loading = true;
    this.service.getAvaluos(page, this.filtro,'Inspección').subscribe({
      next: (response) => {
        this.avaluos = response.data;
        this.currentPage = response.current_page;
        this.lastPage = response.last_page;
        this.loading = false;
      },
      error: () => {
        this.error = 'Error al cargar los avalúos';
        this.loading = false;
      }
    });
  }

  editar(id: number): void {
    this.router.navigate([`/admin/inspecciones/${id}/edit`]);
  }

  eliminar(id: number): void {
    if (confirm('¿Deseas eliminar este registro?')) {
      this.service.delete(id).subscribe({
        next: () => this.cargarAvaluos(this.currentPage),
        error: () => alert('Error al eliminar')
      });
    }
  }

  irPagina(pagina: number): void {
    if (pagina >= 1 && pagina <= this.lastPage) {
      this.cargarAvaluos(pagina);
    }
  }

  onBuscar(): void {
    this.cargarAvaluos(1);
  }

  irAImagenes(id: number): void {
    this.router.navigate([`/admin/ingresos/${id}/imagenes`]);
  }

  getDocumentoUrl(ruta: string | null): string {
    if (!ruta) {
      return ''; // o una URL por defecto
    }
    return environment.url + `documentos/inspecciones/${ruta}`;
  }
}