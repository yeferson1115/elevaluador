// components/fasecolda-import/fasecolda-import.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import { FasecoldaService, FasecoldaMemoria } from '../../../core/services/fasecolda.service';
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

  constructor(
    private fb: FormBuilder,
    private fasecoldaService: FasecoldaService,
    private alert: AlertService
  ) {
    this.importForm = this.fb.group({
      codigo_fasecolda: ['', Validators.required]
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
      per_page: this.pageSize
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

  editarMemoria(memoria: FasecoldaMemoria): void {
    this.alert.confirm({
      title: 'Editar código de memoria',
      text: `Se editará la memoria ${memoria.codigo_fasecolda}`,
      confirmButtonText: 'Continuar',
      cancelButtonText: 'Cancelar',
      icon: 'question'
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      const nuevoCodigo = window.prompt('Ingrese el nuevo código Fasecolda', memoria.codigo_fasecolda)?.trim();

      if (!nuevoCodigo || nuevoCodigo === memoria.codigo_fasecolda) {
        return;
      }

      this.fasecoldaService.actualizarCodigo(memoria.codigo_fasecolda, nuevoCodigo).subscribe({
        next: () => {
          this.alert.success('Código actualizado correctamente');
          this.cargarMemorias();
        },
        error: (error) => {
          this.alert.error(error?.error?.message || 'No fue posible actualizar el código');
        }
      });
    });
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
