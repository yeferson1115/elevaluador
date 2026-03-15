import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { ValoresRepuestosService } from '../../../services/valores-repuestos.service'; 
import { AlertService } from '../../../services/alert.service';
import { ValoresRepuesto } from '../../../models/valores-repuesto.model';

@Component({
  selector: 'app-valores-repuesto-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './valores-repuesto-form.component.html'
})
export class ValoresRepuestoFormComponent implements OnInit {
  form!: FormGroup;
  loading = false;
  isEditMode = false;
  currentId?: number;
  tipos: string[] = [];
  
  // Para usar Math en la plantilla
  Math = Math;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private valoresRepuestoService: ValoresRepuestosService,
    private alertService: AlertService
  ) { }

  ngOnInit(): void {
    this.initForm();
    this.loadTipos();
    this.checkEditMode();
  }

  initForm(): void {
    this.form = this.fb.group({
      cilindraje_to: ['', [Validators.required, Validators.maxLength(10)]],
      cilindraje_from: ['', [Validators.required, Validators.maxLength(10)]],
      tipo: ['', [Validators.required, Validators.maxLength(50)]],
      llantas: [null, [Validators.min(0)]],
      tapiceria: [null, [Validators.min(0)]],
      soat: [null, [Validators.min(0)]],
      rtm: [null, [Validators.min(0)]],
      kit_arrastre: [null, [Validators.min(0)]],
      motor_mantenimiento: [null, [Validators.min(0)]],
      pintura: [null, [Validators.min(0)]],
      latoneria: [null, [Validators.min(0)]],
      chasis: [null, [Validators.min(0)]],
      frenos: [null, [Validators.min(0)]],
      bateria: [null, [Validators.min(0)]],
      tanque_combustible: [null, [Validators.min(0)]],
      llave: [null, [Validators.min(0)]],
      sis_electrico: [null, [Validators.min(0)]]
    });
  }

  loadTipos(): void {
    this.valoresRepuestoService.getTipos().subscribe({
      next: (tipos: string[]) => {
        console.log('Tipos cargados:', tipos);
        this.tipos = tipos || [];
      },
      error: (error) => {
        console.error('Error loading tipos', error);
        this.alertService.error('Error al cargar los tipos de vehículo');
      }
    });
  }

  checkEditMode(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.isEditMode = true;
      this.currentId = +id;
      this.loadData(this.currentId);
    }
  }

  loadData(id: number): void {
    this.loading = true;
    this.valoresRepuestoService.getValoresRepuestoById(id)
      .subscribe({
        next: (valoresRepuesto: ValoresRepuesto) => {
          console.log('Datos cargados:', valoresRepuesto);
          
          // Ahora valoresRepuesto es directamente el objeto
          this.form.patchValue(valoresRepuesto);
          this.loading = false;
        },
        error: (error) => {
          console.error('Error loading data', error);
          this.alertService.error('Error al cargar los datos del registro');
          this.loading = false;
          this.router.navigate(['/admin/valores-repuesto']);
        }
      });
  }

  onSubmit(): void {
    if (this.form.invalid) {
      this.markFormGroupTouched(this.form);
      this.alertService.warning('Por favor, complete todos los campos requeridos');
      return;
    }

    this.loading = true;
    const formData = this.form.value;

    // Convertir strings vacíos a null
    Object.keys(formData).forEach(key => {
      if (formData[key] === '') {
        formData[key] = null;
      }
    });

    console.log('Datos a guardar:', formData);
    console.log('Es edición?', this.isEditMode);
    console.log('ID:', this.currentId);

    const request = this.isEditMode && this.currentId
      ? this.valoresRepuestoService.updateValoresRepuesto(this.currentId, formData)
      : this.valoresRepuestoService.createValoresRepuesto(formData);

    request.subscribe({
      next: (response: any) => {
        console.log('Respuesta del servidor:', response);
        
        // Verificar si la respuesta tiene success
        if (response && response.success) {
          this.alertService.success(
            this.isEditMode ? 'Registro actualizado exitosamente' : 'Registro creado exitosamente'
          );
          this.router.navigate(['/admin/valores-repuesto']);
        } else {
          // Si no tiene success, pero la petición fue exitosa (status 200-299)
          // asumimos que fue exitoso
          this.alertService.success(
            this.isEditMode ? 'Registro actualizado exitosamente' : 'Registro creado exitosamente'
          );
          this.router.navigate(['/admin/valores-repuesto']);
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error saving', error);
        
        // Mostrar errores de validación
        if (error.status === 422 && error.error && error.error.errors) {
          this.handleValidationErrors(error.error.errors);
        } else if (error.status === 409) {
          this.alertService.error(error.error?.message || 'El registro ya existe');
        } else if (error.error && error.error.message) {
          this.alertService.error(error.error.message);
        } else {
          this.alertService.error('Error al guardar el registro');
        }
        this.loading = false;
      }
    });
  }

  handleValidationErrors(errors: any): void {
    // Mostrar cada error en su campo correspondiente
    Object.keys(errors).forEach(key => {
      const control = this.form.get(key);
      if (control) {
        control.setErrors({ serverError: errors[key][0] });
      }
    });
    
    // Mostrar mensaje general
    this.alertService.warning('Por favor, verifique los errores en el formulario');
  }

  markFormGroupTouched(formGroup: FormGroup): void {
    Object.values(formGroup.controls).forEach(control => {
      control.markAsTouched();
      if ((control as any).controls) {
        this.markFormGroupTouched(control as FormGroup);
      }
    });
  }

  cancel(): void {
    this.router.navigate(['/admin/valores-repuesto']);
  }

  getErrorMessage(controlName: string): string {
    const control = this.form.get(controlName);
    if (control?.errors) {
      if (control.errors['required']) {
        return 'Este campo es requerido';
      }
      if (control.errors['min']) {
        return 'El valor debe ser mayor o igual a 0';
      }
      if (control.errors['maxlength']) {
        return `Máximo ${control.errors['maxlength'].requiredLength} caracteres`;
      }
      if (control.errors['serverError']) {
        return control.errors['serverError'];
      }
    }
    return '';
  }

  isFieldInvalid(fieldName: string): boolean {
    const field = this.form.get(fieldName);
    return field ? (field.invalid && (field.dirty || field.touched)) : false;
  }
}