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
import { AvaluoService } from '../../../core/services/avaluo.service';
import { FasecoldaService } from '../../../core/services/fasecolda.service';

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
  filtroCierre: 'todos' | 'abiertos' | 'cerrados' = 'todos';
  loading = false;
  error: string | null = null;

  avaluos: Ingreso[] = [];
  currentPage = 1;
  lastPage = 1;
  selectedIds = new Set<number>();
  selectionMode: 'manual' | 'allFiltered' = 'manual';
  mostrarEdicionMasiva = false;
  // Compatibilidad temporal para plantillas antiguas
  mostrarAcordeonArchivos = false;
  mostrarImportacionMasiva = false;
  bulkEditLoading = false;
  bulkImportLoading = false;
  bulkImportMetodo: 'comercial' | 'jans' | '' = '';
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
  bulkChanges: any = {
    codigo_fasecolda: '',
    valor_chatarra_kg: null,
    ubicacion: '',
    tipo: '',
    chatarra: '',
    peso_chatarra_kg: null,
    observaciones: '',
  };

  constructor(
    private service: IngresoService,
    private router: Router,
    private alert: AlertService,
    private avaluoService: AvaluoService,
    private fasecoldaService: FasecoldaService
  ) {
    this.cargarAvaluos();
    console.log(Permissions.VIEW_INGRESO);
  }

  cargarAvaluos(page: number = 1): void {
    this.loading = true;
    this.service.getAvaluos(page, this.filtro, 'Sec Bogota').subscribe({
      next: (response) => {
        this.avaluos = response.data;
        this.sincronizarSeleccionPagina();
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

  toggleCierreDesdeTabla(avaluoId: number | null | undefined, cerradoActual: boolean | null | undefined): void {
    if (!avaluoId) {
      this.alert.warning('Este registro aún no tiene avalúo para cambiar de estado.');
      return;
    }

    const nuevoEstado = !Boolean(cerradoActual);
    this.avaluoService.actualizarCierre(avaluoId, nuevoEstado).subscribe({
      next: (response) => {
        this.alert.success(response.message);
        this.cargarAvaluos(this.currentPage);
      },
      error: () => {
        this.alert.error('No se pudo actualizar el estado de cierre del avalúo.');
      }
    });
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

  onCambioFiltroCierre(): void {
    this.limpiarSeleccion();
  }

  toggleSeleccion(id: number | undefined, checked: boolean): void {
    if (!id || this.selectionMode === 'allFiltered') {
      return;
    }

    if (checked) {
      this.selectedIds.add(id);
    } else {
      this.selectedIds.delete(id);
    }
  }

  toggleSeleccionPagina(event: Event): void {
    if (this.selectionMode === 'allFiltered') {
      return;
    }

    const checked = (event.target as HTMLInputElement).checked;

    this.avaluosFiltradosPorCierre.forEach((avaluo) => {
      if (!avaluo.id) {
        return;
      }

      if (checked) {
        this.selectedIds.add(avaluo.id);
      } else {
        this.selectedIds.delete(avaluo.id);
      }
    });
  }

  seleccionarVisibles(): void {
    this.selectionMode = 'manual';

    this.avaluosFiltradosPorCierre.forEach((avaluo) => {
      if (avaluo.id) {
        this.selectedIds.add(avaluo.id);
      }
    });
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
    if (!id) {
      return false;
    }

    return this.selectionMode === 'allFiltered' || this.selectedIds.has(id);
  }

  get haySeleccionParcialPagina(): boolean {
    const idsPagina = this.avaluosFiltradosPorCierre
      .map((avaluo) => avaluo.id)
      .filter((id): id is number => !!id);

    return this.selectionMode !== 'allFiltered'
      && idsPagina.some((id) => this.selectedIds.has(id))
      && !this.todosVisiblesSeleccionados;
  }

  get todosVisiblesSeleccionados(): boolean {
    const idsPagina = this.avaluosFiltradosPorCierre
      .map((avaluo) => avaluo.id)
      .filter((id): id is number => !!id);

    return this.selectionMode === 'allFiltered'
      || (idsPagina.length > 0 && idsPagina.every((id) => this.selectedIds.has(id)));
  }

  get totalSeleccionados(): number {
    return this.selectedIds.size;
  }

  get exportaTodosFiltrados(): boolean {
    return this.selectionMode === 'allFiltered';
  }

  get avaluosFiltradosPorCierre(): Ingreso[] {
    if (this.filtroCierre === 'todos') {
      return this.avaluos;
    }

    return this.avaluos.filter((avaluo) => {
      const cerrado = Boolean(avaluo.avaluo?.cerrado);
      return this.filtroCierre === 'cerrados' ? cerrado : !cerrado;
    });
  }

  private sincronizarSeleccionPagina(): void {
    this.avaluos = [...this.avaluos];
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

  get haySeleccionExportable(): boolean {
    return this.exportaTodosFiltrados || this.totalSeleccionados > 0;
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
  if (!this.haySeleccionExportable) {
    this.alert.warning("Debes seleccionar al menos un registro o usar 'Seleccionar todos los filtrados' antes de exportar el ZIP.");
    return;
  }

  this.loading = true;

  const ids = this.exportaTodosFiltrados ? [] : Array.from(this.selectedIds);

  this.service.exportCertificadosZipBackground(this.filtro, ids).subscribe({
    next: (response: any) => {
      this.loading = false;
      const scopeMessage = this.exportaTodosFiltrados
        ? 'todos los certificados filtrados'
        : ids.length > 0
          ? `${ids.length} certificado(s) seleccionado(s)`
          : 'todos los certificados filtrados';
      this.alert.success(`La generación del ZIP quedó en segundo plano ✅ (${scopeMessage}). Se enviará un correo a ${response?.email ?? 'tu cuenta'} con la ruta de descarga.`);
    },
    error: (error) => {
      console.error('Error al exportar certificados:', error);

      if (error.status === 404) {
        this.alert.warning('No hay certificados para exportar con el filtro actual');
      } else if (error.status === 422) {
        this.alert.warning(error.error?.message || 'Tu usuario no tiene un correo configurado para recibir la ruta de descarga');
      } else {
        this.alert.error('Error al encolar la generación del archivo ZIP');
      }

      this.loading = false;
    }
  });
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
  const ids = this.exportaTodosFiltrados ? [] : Array.from(this.selectedIds);

  this.service.bulkUpdateCompact({
    ids,
    filtro: this.filtro,
    all_filtered: this.exportaTodosFiltrados,
    changes,
    generar_zip: generarZip,
    tipo_servicio: 'Sec Bogota'
  }).subscribe({
    next: (response: Blob | any) => {
      if (generarZip) {
        const nombre = `avaluos-compact-edicion-masiva-${new Date().toISOString().slice(0, 10)}.zip`;
        this.descargarArchivo(response as Blob, nombre);
        this.alert.success('Edición masiva aplicada. Se descargó el ZIP con los PDFs actualizados.');
      } else {
        this.alert.success(response?.message || 'Edición masiva aplicada correctamente.');
      }
      this.bulkEditLoading = false;
      this.cargarAvaluos(this.currentPage);
    },
    error: (error) => {
      console.error('Error en edición masiva:', error);
      this.alert.error('No fue posible completar la edición masiva.');
      this.bulkEditLoading = false;
    }
  });
  }

  onBulkCompactFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) {
      return;
    }

    if (!this.bulkImportMetodo) {
      this.alert.warning('Antes de importar debes seleccionar el método (Comercial o Jans).');
      input.value = '';
      return;
    }

    const file = input.files[0];
    this.bulkImportLoading = true;

    this.service.bulkImportCompact(file, this.bulkImportMetodo).subscribe({
      next: (zipBlob: Blob) => {
        const nombre = `avaluos-compact-importacion-${new Date().toISOString().slice(0, 10)}.zip`;
        this.descargarArchivo(zipBlob, nombre);
        this.alert.success('Importación masiva completada. Se descargó el ZIP con los PDFs.');
        this.bulkImportLoading = false;
        input.value = '';
        this.cargarAvaluos(1);
      },
      error: (error) => {
        console.error('Error en importación masiva compact:', error);
        this.alert.error(error?.error?.message || 'No fue posible procesar el archivo de importación masiva.');
        this.bulkImportLoading = false;
        input.value = '';
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

  onCodigoFasecoldaMasivoChange(codigo: string): void {
    const codigoNormalizado = (codigo || '').trim();

    if (!codigoNormalizado) {
      this.bulkChanges.peso_chatarra_kg = null;
      return;
    }

    this.fasecoldaService.getValores(codigoNormalizado).subscribe({
      next: (response) => {
        this.bulkChanges.peso_chatarra_kg = response?.peso_vacio ?? null;
      },
      error: () => {
        this.bulkChanges.peso_chatarra_kg = null;
        this.alert.warning('No fue posible consultar el peso del código Fasecolda ingresado.');
      }
    });
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
