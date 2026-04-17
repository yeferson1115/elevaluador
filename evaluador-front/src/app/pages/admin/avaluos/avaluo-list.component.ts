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
  bulkEditLoading = false;
  error: string | null = null;

  avaluos: Ingreso[] = [];
  currentPage = 1;
  lastPage = 1;
  selectedIds = new Set<number>();
  selectionMode: 'manual' | 'allFiltered' = 'manual';
  ubicaciones: string[] = [
    'PATIOS',
    'ALAMOS 200',
    'ALAMOS 201',
    'FONTIBÓN 1',
    'FONTIBÓN 2',
    'PATIO SUR',
    'PATIO 50',
    'SUBA',
    'TRANSITORIO'
  ];
  tiposVehiculo: string[] = [
    'AUTOMOVIL',
    'BUS',
    'BUSETA',
    'CAMION',
    'CAMION MAS 6 TON',
    'CAMIONETA',
    'CAMPERO',
    'MICROBUS',
    'MOTOCARRO',
    'MOTOCICLETA'
  ];
  bulkChanges: any = {
    codigo_fasecolda: '',
    valor_chatarra_kg: null,
    ubicacion: '',
    cilindraje: null,
    tipo_vehiculo: '',
    fecha_inspeccion: '',
    tipo: '',
    chatarra: '',
    peso_chatarra_kg: null,
    observaciones: '',
  };

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
    this.limpiarSeleccion();
    this.cargarAvaluos(1);
  }

  toggleSeleccion(id: number | undefined, checked: boolean): void {
    if (!id || this.selectionMode === 'allFiltered') return;
    checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
  }

  toggleSeleccionPagina(event: Event): void {
    if (this.selectionMode === 'allFiltered') return;
    const checked = (event.target as HTMLInputElement).checked;
    this.avaluos.forEach((avaluo) => {
      if (!avaluo.id) return;
      checked ? this.selectedIds.add(avaluo.id) : this.selectedIds.delete(avaluo.id);
    });
  }

  seleccionarVisibles(): void {
    this.selectionMode = 'manual';
    this.avaluos.forEach((avaluo) => avaluo.id && this.selectedIds.add(avaluo.id));
  }

  seleccionarTodosFiltrados(): void {
    this.selectionMode = 'allFiltered';
    this.selectedIds.clear();
  }

  limpiarSeleccion(): void {
    this.selectionMode = 'manual';
    this.selectedIds.clear();
  }

  estaSeleccionado(id: number | undefined): boolean {
    if (!id) return false;
    return this.selectionMode === 'allFiltered' || this.selectedIds.has(id);
  }

  get totalSeleccionados(): number {
    return this.selectedIds.size;
  }

  get todosVisiblesSeleccionados(): boolean {
    const idsPagina = this.avaluos.map(a => a.id).filter((id): id is number => !!id);
    return this.selectionMode === 'allFiltered'
      || (idsPagina.length > 0 && idsPagina.every((id) => this.selectedIds.has(id)));
  }

  get haySeleccionExportable(): boolean {
    return this.selectionMode === 'allFiltered' || this.totalSeleccionados > 0;
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

  aplicarEdicionMasiva(): void {
    this.ejecutarEdicionMasiva(false);
  }

  generarZipEdicionMasiva(): void {
    this.ejecutarEdicionMasiva(true);
  }

  private ejecutarEdicionMasiva(generarZip: boolean): void {
    if (!this.haySeleccionExportable) {
      this.alert.warning('Selecciona al menos un registro (o todos los filtrados) para edición masiva.');
      return;
    }

    const changes: Record<string, any> = {};
    Object.entries(this.bulkChanges).forEach(([key, value]) => {
      if (value !== null && value !== undefined && `${value}`.trim() !== '') {
        changes[key] = value;
      }
    });

    if (Object.keys(changes).length === 0) {
      this.alert.warning('Debes diligenciar al menos un campo para aplicar en bloque.');
      return;
    }

    this.bulkEditLoading = true;
    const ids = this.selectionMode === 'allFiltered' ? [] : Array.from(this.selectedIds);

    this.service.bulkUpdateCompact({
      ids,
      filtro: this.filtro,
      all_filtered: this.selectionMode === 'allFiltered',
      changes,
      generar_zip: generarZip,
      tipo_servicio: 'Avaluo'
    }).subscribe({
      next: (response: Blob | any) => {
        if (generarZip) {
          const nombre = `avaluos-pro-edicion-masiva-${new Date().toISOString().slice(0, 10)}.zip`;
          this.descargarArchivo(response as Blob, nombre);
          this.alert.success('Edición masiva aplicada. Se descargó el ZIP con los PDFs actualizados.');
        } else {
          const totalErrores = Array.isArray(response?.errores) ? response.errores.length : 0;
          if (totalErrores > 0) {
            this.alert.warning(`${response?.message || 'Edición masiva aplicada parcialmente.'} ${totalErrores} registro(s) no se pudieron actualizar.`);
          } else {
            this.alert.success(response?.message || 'Edición masiva aplicada correctamente.');
          }
        }
        this.bulkEditLoading = false;
        this.cargarAvaluos(this.currentPage);
      },
      error: (error) => {
        this.alert.error(error?.error?.message || 'No fue posible completar la edición masiva.');
        this.bulkEditLoading = false;
      }
    });
  }

}
