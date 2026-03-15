import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgIf, NgFor } from '@angular/common';
import { HasPermissionDirective } from '../../../core/directives/has-permission.directive';
import { Permissions } from '../../../core/constants/permissions.const';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-ingreso-list',
  standalone: true,
  templateUrl: './ingreso-list.component.html',
  styleUrls: ['./ingreso-list.component.css'],
  imports: [CommonModule, FormsModule, NgIf, NgFor,HasPermissionDirective]
})
export class IngresoListComponent {
  Permissions = Permissions;
  
  filtro = '';
  loading = false;
  error: string | null = null;

  avaluos: Ingreso[] = [];
  currentPage = 1;
  lastPage = 1;

  constructor(private service: IngresoService, private router: Router,private alert: AlertService) {
    this.cargarAvaluos();
    console.log(Permissions.VIEW_INGRESO);
  }

  cargarAvaluos(page: number = 1): void {
    this.loading = true;
    this.service.getAvaluos(page, this.filtro).subscribe({
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

  nuevo(): void {
    this.router.navigate(['/admin/ingresos/create']);
  }

  editar(id: number): void {
    this.router.navigate([`/admin/ingresos/${id}/edit`]);
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

onFileSelected(event: Event): void {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;

  const file = input.files[0];
  this.loading = true;

  this.service.import(file).subscribe({
    next: (res) => {
      this.alert.success(res.message || 'Importación exitosa ✅');
      this.cargarAvaluos(); // refresca la tabla
      this.loading = false;
      input.value = ''; // limpia el input
    },
    error: (err) => {
      console.error('Error en importación:', err);

      // 🔎 Muestra mensaje detallado si Laravel lo manda
      const message =
        err.error?.error ||    // tu catch con $e->getMessage()
        err.error?.message ||  // mensaje genérico
        'Error desconocido al importar';

      this.alert.error(message);
      this.loading = false;
      input.value = '';
    }
  });
}




}
