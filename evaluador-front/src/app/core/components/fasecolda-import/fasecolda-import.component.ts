// components/fasecolda-import/fasecolda-import.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { FasecoldaService } from '../../../core/services/fasecolda.service';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-fasecolda-import',
  standalone: true, // ← ESTO ES IMPORTANTE
  imports: [CommonModule, ReactiveFormsModule], // ← AGREGAR ReactiveFormsModule
  templateUrl: './fasecolda-import.component.html',
  styleUrls: ['./fasecolda-import.component.css']
})
export class FasecoldaImportComponent {
  importForm: FormGroup;
  selectedFile: File | null = null;
  loading = false;

  constructor(
    private fb: FormBuilder,
    private fasecoldaService: FasecoldaService,
    private alert: AlertService
  ) {
    this.importForm = this.fb.group({
      codigo_fasecolda: ['', Validators.required]
    });
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
      next: (response) => {
        this.loading = false;
        this.alert.success('Archivo importado correctamente');
        this.importForm.reset();
        this.selectedFile = null;
        // Limpiar input file
        const fileInput = document.getElementById('fileInput') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
      },
      error: (error) => {
        this.loading = false;
        this.alert.error('Error al importar archivo: ' + error.error?.message);
      }
    });
  }
}