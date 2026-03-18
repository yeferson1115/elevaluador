import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import { FasecoldaService, FasecoldaMemoria, FasecoldaRegistro } from '../../../core/services/fasecolda.service';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-fasecolda-import',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './fasecolda-import.component.html',
  styleUrls: ['./fasecolda-import.component.css']
})
export class FasecoldaImportComponent {
  importForm: FormGroup;
  selectedFile: File | null = null;
  loading = false;

  memorias: FasecoldaMemoria[] = [];
  loadingMemorias = false;
  currentPage = 1;
  pageSize = 10;
  totalItems = 0;
  totalPages = 0;
  filtroCodigo = '';

  codigoSeleccionado = '';
  registros: FasecoldaRegistro[] = [];
  loadingRegistros = false;

  editandoMemoriaCodigoOriginal: string | null = null;
  editandoMemoriaCodigoNuevo = '';
  guardandoMemoria = false;
  editandoMemoriaPesoVacio: number | null = null;

  editandoRegistroId: number | null = null;
  registroDraft: { tipo: 'clasificado' | 'corregido'; modelo: number | null; valor: number | null; peso_vacio: number | null } = {
    tipo: 'clasificado',
    modelo: null,
    valor: null,
    peso_vacio: null
  };
  guardandoRegistro = false;

  constructor(
    private fb: FormBuilder,
    private fasecoldaService: FasecoldaService,
    private alert: AlertService
  ) {
    this.importForm = this.fb.group({
      codigo_fasecolda: ['', Validators.required],
      peso_vacio: ['']
    });

    this.cargarMemorias();
  }

  onFileSelected(event: any) {
    this.selectedFile = event.target.files[0];
  }

  importar() {
    if (!this.selectedFile) {
      this.alert.error('Por favor seleccione un archivo Excel');
      return;
    }

    if (!this.importForm.valid) {
      this.alert.error('Por favor ingrese el código Fasecolda');
      return;
    }

    this.loading = true;

    const formData = new FormData();
    formData.append('file', this.selectedFile);
    formData.append('codigo_fasecolda', this.importForm.get('codigo_fasecolda')?.value);
    const pesoVacio = this.importForm.get('peso_vacio')?.value;
    if (pesoVacio !== null && pesoVacio !== undefined && pesoVacio !== '') {
      formData.append('peso_vacio', pesoVacio);
    }

    this.fasecoldaService.importarExcel(formData).subscribe({
      next: () => {
        this.loading = false;
        this.alert.success('Archivo importado correctamente');
        this.importForm.reset();
        this.selectedFile = null;
        const fileInput = document.getElementById('fileInput') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
        this.cargarMemorias();
      },
      error: (error) => {
        this.loading = false;
        this.alert.error('Error al importar archivo: ' + error.error?.message);
      }
    });
  }

  cargarMemorias(): void {
    this.loadingMemorias = true;

    this.fasecoldaService.getMemorias({
      page: this.currentPage,
      per_page: this.pageSize,
      codigo: this.filtroCodigo.trim() || undefined
    }).subscribe({
      next: (response) => {
        this.memorias = response.data || [];
        this.currentPage = response.current_page || 1;
        this.totalPages = response.last_page || 1;
        this.totalItems = response.total || 0;
        this.loadingMemorias = false;
      },
      error: () => {
        this.loadingMemorias = false;
        this.memorias = [];
        this.alert.error('No fue posible cargar el listado de memorias');
      }
    });
  }

  verRegistros(codigo: string): void {
    this.codigoSeleccionado = codigo;
    this.cancelarEdicionMemoria();
    this.cancelarEdicionRegistro();
    this.cargarRegistros();
  }

  cargarRegistros(): void {
    if (!this.codigoSeleccionado) {
      return;
    }

    this.loadingRegistros = true;
    this.fasecoldaService.getRegistros(this.codigoSeleccionado).subscribe({
      next: (response) => {
        this.registros = response.data || [];
        this.loadingRegistros = false;
      },
      error: () => {
        this.loadingRegistros = false;
        this.registros = [];
        this.alert.error('No fue posible cargar los registros de la memoria');
      }
    });
  }

  iniciarEdicionMemoria(memoria: FasecoldaMemoria): void {
    this.editandoMemoriaCodigoOriginal = memoria.codigo_fasecolda;
    this.editandoMemoriaCodigoNuevo = memoria.codigo_fasecolda;
    this.editandoMemoriaPesoVacio = memoria.peso_vacio;
  }

  cancelarEdicionMemoria(): void {
    this.editandoMemoriaCodigoOriginal = null;
    this.editandoMemoriaCodigoNuevo = '';
    this.editandoMemoriaPesoVacio = null;
    this.guardandoMemoria = false;
  }

  guardarEdicionMemoria(memoria: FasecoldaMemoria): void {
    const nuevoCodigo = this.editandoMemoriaCodigoNuevo.trim();

    const pesoVacio = this.editandoMemoriaPesoVacio === null || this.editandoMemoriaPesoVacio === undefined
      ? null
      : Number(this.editandoMemoriaPesoVacio);

    if (!nuevoCodigo) {
      this.alert.error('El código Fasecolda es obligatorio');
      return;
    }

    if (nuevoCodigo === memoria.codigo_fasecolda && pesoVacio === memoria.peso_vacio) {
      this.cancelarEdicionMemoria();
      return;
    }

    this.guardandoMemoria = true;
    this.fasecoldaService.actualizarCodigo(memoria.codigo_fasecolda, nuevoCodigo, pesoVacio).subscribe({
      next: () => {
        this.alert.success('Código actualizado correctamente');
        if (this.codigoSeleccionado === memoria.codigo_fasecolda) {
          this.codigoSeleccionado = nuevoCodigo;
          this.cargarRegistros();
        }
        this.cancelarEdicionMemoria();
        this.cargarMemorias();
      },
      error: (error) => {
        this.guardandoMemoria = false;
        this.alert.error(error?.error?.message || 'No fue posible actualizar el código');
      }
    });
  }

  iniciarEdicionRegistro(registro: FasecoldaRegistro): void {
    this.editandoRegistroId = registro.id;
    this.registroDraft = {
      tipo: registro.tipo === 'corregido' ? 'corregido' : 'clasificado',
      modelo: registro.modelo,
      valor: registro.valor,
      peso_vacio: registro.peso_vacio
    };
  }

  cancelarEdicionRegistro(): void {
    this.editandoRegistroId = null;
    this.registroDraft = { tipo: 'clasificado', modelo: null, valor: null, peso_vacio: null };
    this.guardandoRegistro = false;
  }

  guardarEdicionRegistro(registro: FasecoldaRegistro): void {
    if (!this.registroDraft.modelo || this.registroDraft.valor === null) {
      this.alert.error('Debe completar tipo, modelo y valor');
      return;
    }

    this.guardandoRegistro = true;
    this.fasecoldaService.actualizarRegistro(registro.id, {
      tipo: this.registroDraft.tipo,
      modelo: Number(this.registroDraft.modelo),
      valor: Number(this.registroDraft.valor),
      peso_vacio: this.registroDraft.peso_vacio === null ? null : Number(this.registroDraft.peso_vacio)
    }).subscribe({
      next: () => {
        this.alert.success('Registro actualizado correctamente');
        this.cancelarEdicionRegistro();
        this.cargarRegistros();
      },
      error: (error) => {
        this.guardandoRegistro = false;
        this.alert.error(error?.error?.message || 'No fue posible actualizar el registro');
      }
    });
  }

  eliminarRegistro(registro: FasecoldaRegistro): void {
    this.alert.confirm({
      title: '¿Eliminar registro?',
      text: `Se eliminará ${registro.tipo} - modelo ${registro.modelo} del código ${registro.codigo_fasecolda}`,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      this.fasecoldaService.eliminarRegistro(registro.id).subscribe({
        next: () => {
          this.alert.success('Registro eliminado correctamente');
          this.cargarRegistros();
          this.cargarMemorias();
        },
        error: (error) => {
          this.alert.error(error?.error?.message || 'No fue posible eliminar el registro');
        }
      });
    });
  }


  aplicarFiltroCodigo(): void {
    this.currentPage = 1;
    this.cancelarEdicionMemoria();
    this.cargarMemorias();
  }

  limpiarFiltroCodigo(): void {
    if (!this.filtroCodigo) {
      return;
    }

    this.filtroCodigo = '';
    this.aplicarFiltroCodigo();
  }

  eliminarMemoria(memoria: FasecoldaMemoria): void {
    this.alert.confirm({
      title: '¿Eliminar memoria?',
      text: `Se eliminarán todos los registros asociados al código ${memoria.codigo_fasecolda}`,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      this.fasecoldaService.eliminarMemoria(memoria.codigo_fasecolda).subscribe({
        next: () => {
          this.alert.success('Memoria eliminada correctamente');
          if (this.currentPage > 1 && this.memorias.length === 1) {
            this.currentPage -= 1;
          }
          if (this.codigoSeleccionado === memoria.codigo_fasecolda) {
            this.codigoSeleccionado = '';
            this.registros = [];
          }
          this.cargarMemorias();
        },
        error: (error) => {
          this.alert.error(error?.error?.message || 'No fue posible eliminar la memoria');
        }
      });
    });
  }

  onPageChange(page: number): void {
    if (page < 1 || page > this.totalPages || page === this.currentPage) {
      return;
    }

    this.currentPage = page;
    this.cargarMemorias();
  }

  formatDate(value: string | null): string {
    if (!value) {
      return '-';
    }

    return new Date(value).toLocaleString('es-CO');
  }
}
