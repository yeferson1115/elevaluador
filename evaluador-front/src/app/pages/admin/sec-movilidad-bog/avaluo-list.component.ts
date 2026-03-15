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
import { environment } from '../../../../environments/environment';
import { HttpResponse } from '@angular/common/http';

@Component({
  selector: 'app-avaluo-list',
  standalone: true,
  templateUrl: './avaluo-list.component.html',
  styleUrls: ['./avaluo-list.component.css'],
  imports: [CommonModule, FormsModule, NgIf, NgFor,HasPermissionDirective]
})
export class AvaluoListComponent {
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
    this.service.getAvaluos(page, this.filtro, 'Sec Bogota').subscribe({
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
    this.router.navigate(['/admin/avaluo-sec-bgta/create']);
  }

  editar(id: number): void {
    this.router.navigate([`/admin/avaluo-sec-bgta/${id}/edit`]);
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

  this.service.importSecBog(file).subscribe({
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


 getDocumentoUrl(ruta: string | null): string {
    if (!ruta) {
      return ''; // o una URL por defecto
    }
    return environment.url + `documentos/${ruta}`;
  }

  exportarExcel(): void {
  this.loading = true;
  
  this.service.exportSecBog(this.filtro).subscribe({
    next: (blob: Blob) => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `avaluos-sec-bogota-${new Date().toISOString().split('T')[0]}.xlsx`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      this.loading = false;
    },
    error: (error) => {
      console.error('Error al exportar:', error);
      this.alert.error('Error al generar el archivo Excel');
      this.loading = false;
    }
  });
}


exportarCertificadosZip(): void {
  this.loading = true;
  
  this.service.exportCertificadosZip(this.filtro).subscribe({
    next: (blob: Blob) => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `certificados-sec-bogota-${new Date().toISOString().split('T')[0]}.zip`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      this.loading = false;
      this.alert.success('Certificados exportados exitosamente ✅');
    },
    error: (error) => {
      console.error('Error al exportar certificados:', error);
      
      // Manejo de errores específicos
      if (error.status === 404) {
        this.alert.warning('No hay certificados para exportar con el filtro actual');
      } else {
        this.alert.error('Error al generar el archivo ZIP');
      }
      
      this.loading = false;
    }
  });
}

// En tu AvaluoListComponent

verPdf(id: number | null | undefined, action: 'view' | 'download' = 'view'): void {
    // Validar que el id sea un número válido
    if (id === null || id === undefined || isNaN(id)) {
      this.alert.error('ID de avalúo no válido');
      return;
    }
    
    this.loading = true;
    
    if (action === 'view') {
      // Para ver en el navegador
      this.service.verPdfEnNavegador(id).subscribe({
        next: () => {
          this.loading = false;
        },
        error: (error) => {
          console.error('Error al abrir PDF:', error);
          this.alert.error('Error al abrir el PDF');
          this.loading = false;
        }
      });
    } else {
      // Para descargar
      this.service.descargarPdf(id).subscribe({
        next: (resultado) => {
          this.descargarArchivo(resultado.blob, resultado.nombre);
          this.loading = false;
        },
        error: (error) => {
          console.error('Error al descargar PDF:', error);
          this.alert.error('Error al descargar el PDF');
          this.loading = false;
        }
      });
    }
  }

  private descargarArchivo(blob: Blob, nombre: string): void {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  }

  getPaginas(): number[] {
    const paginas = [];
    const inicio = Math.max(2, this.currentPage - 2);
    const fin = Math.min(this.lastPage - 1, this.currentPage + 2);
    
    for (let i = inicio; i <= fin; i++) {
      paginas.push(i);
    }
    
    return paginas;
  }

  // Para la solución 2
  getPaginasVisibles(): number[] {
    const paginas = [];
    let inicio = Math.max(1, this.currentPage - 2);
    let fin = Math.min(this.lastPage, this.currentPage + 2);
    
    // Ajustar para mostrar siempre 5 páginas si es posible
    if (fin - inicio < 4) {
      if (inicio === 1) {
        fin = Math.min(this.lastPage, inicio + 4);
      } else if (fin === this.lastPage) {
        inicio = Math.max(1, fin - 4);
      }
    }
    
    for (let i = inicio; i <= fin; i++) {
      paginas.push(i);
    }
    
    return paginas;
  }

  getTodasPaginas(): number[] {
    const paginas = [];
    for (let i = 1; i <= this.lastPage; i++) {
      paginas.push(i);
    }
    return paginas;
  }

}
