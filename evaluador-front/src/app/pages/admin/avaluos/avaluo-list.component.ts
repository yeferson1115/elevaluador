import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgIf, NgFor } from '@angular/common';
import { HasPermissionDirective } from '../../../core/directives/has-permission.directive';
import { Permissions } from '../../../core/constants/permissions.const';
import { environment } from '../../../../environments/environment';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { AlertService } from '../../../core/services/alert.service';

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
  }

  cargarAvaluos(page: number = 1): void {
    this.loading = true;
    this.service.getAvaluos(page, this.filtro,'Avaluo').subscribe({
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
    this.router.navigate([`/admin/avaluos/${id}/edit`]);
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
    return environment.url + `documentos/${ruta}`;
  }

  exportarExcelLocal(): void {
  if (!this.avaluos.length) {
    alert('No hay datos para exportar.');
    return;
  }

  // Creamos un arreglo plano de objetos simples
  const datos = this.avaluos.map(a => ({
    'Placa': a.datosGenerales.placa,
    'Solicitante': a.datosGenerales.solicitante,
    'Documento': a.datosGenerales.documentoSolicitante,
    'Ubicación': a.datosGenerales.ubicacionActivo,
    'Fecha solicitud': a.avaluo.fecha_inspeccion,
    'Marca': a.informacionBien.marca,
    'Línea': a.informacionBien.linea,
    'Modelo': a.informacionBien.modelo,
    'Valor Reposición': a.avaluo?.valor_reposicion,
    'Valor Residual': a.avaluo?.valor_residual,
    'Valor Total': a.avaluo?.avaluo_total,
  }));

  const ws: XLSX.WorkSheet = XLSX.utils.json_to_sheet(datos);
  const wb: XLSX.WorkBook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Avalúos');

  const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
  const blob = new Blob([wbout], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  });

  saveAs(blob, 'avaluos.xlsx');
}

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

}
