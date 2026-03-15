import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule,FormArray } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-ingreso-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './ingreso-form.component.html',
  styleUrls: ['./ingreso-form.component.css'],
})
export class IngresoFormComponent implements OnInit {
  loading = false;
  form!: FormGroup;
  editMode = false;
  id!: number;

  constructor(
    private fb: FormBuilder,
    private service: IngresoService,
    private route: ActivatedRoute,
    private router: Router,
    private alert: AlertService
  ) {}

  ngOnInit() {
    this.form = this.fb.group({
      datosGenerales: this.fb.group({
        tiposervicio:['', Validators.required],
        solicitante: ['', Validators.required],
        documentoSolicitante: ['', Validators.required],
        direccion_solicitante: ['', Validators.required],
        telefono_solicitante: ['', Validators.required],
        placa: ['', Validators.required],
        ubicacionActivo: ['', Validators.required],
        fechaSolicitud: ['', Validators.required],
        fechaInspeccion: ['', Validators.required],
        fechaInforme: ['', Validators.required],
        objetoAvaluo: ['', Validators.required],
        codigoInternoMovil: ['', Validators.required],
        estado:[{ value: 'Ingresado'}, Validators.required],
      }),
      informacionBien: this.fb.group({
        tipoPropiedad: ['', Validators.required],
        fechaMatricula: ['', Validators.required],
        movil: ['', Validators.required],
        marca: ['', Validators.required],
        linea: ['', Validators.required],
        clase: ['', Validators.required],
        tipoCarroceria: ['', Validators.required],
        cantidad_ejes: ['', Validators.required],
        categoria: ['', Validators.required],
        color: ['', Validators.required],
        cilindraje: [null, Validators.required],
        modelo: [null, Validators.required],
        kilometraje: [null, Validators.required],
        cajaCambios: ['', Validators.required],
        tipoTraccion: ['', Validators.required],
        numeroPasajeros: [null, Validators.required],
        capacidadCarga: [null, Validators.required],        
        numeroChasis: ['', Validators.required],
        numeroSerie: ['', Validators.required],
        numeroMotor: ['', Validators.required],
        numeroVin: ['', Validators.required],
        peso_bruto: ['', Validators.required],
        nacionalidad: ['', Validators.required],
        propietario: ['', Validators.required],
        documento_propietario:['', Validators.required],
        empresaAfiliacion: ['', Validators.required],
        ciudad_registro: ['', Validators.required],
        no_licencia: ['', Validators.required],
        fecha_expedicion_licencia: ['', Validators.required],
        organismo_transito: ['', Validators.required],
        soat: ['', Validators.required],
        fecha_expedicion_soat:['', Validators.required],
        fecha_inicio_vigencia_soat:['', Validators.required],
        fecha_vencimiento_soat:['', Validators.required],
        entidad_expide_soat:['', Validators.required],
        estado_soat:['', Validators.required],
        rtm: [''],
        fecha_vencimiento_rtm:[''],
        centro_revision_rtm:[''],
        estado_rtm:['', Validators.required],
      }),
      estadoVehiculoRunt: this.fb.group({
        fecha_inicial_matricula:['', Validators.required],
        estado_matricula: ['', Validators.required],
        traslados_matricula: ['', Validators.required],
        tipo_servicio_vehiculo: ['', Validators.required],
        cambios_tipo_servicio: ['', Validators.required],
        fecha_ult_cambio_servicio: [''],
        cambio_color_historica: [''],
        fecha_ult_cambio_color: [''],
        color_cambiado: [''],
        cambios_blindaje: [''],
        fecha_cambio_blindaje: [''],
        repotenciado: [''],
      }),
      novedadesVehiculo: this.fb.group({
        tiene_gravamedes:[''],
        tiene_prenda: [''],
        regrabado_no_motor: [''],
        regrabado_no_chasis: [''],
        regrabado_no_serie: [''],
        regrabado_no_vin: [''],
        limitacion_propiedad: [''],
        numero_doc_proceso: [''],
        entidad_juridica: [''],
        tipo_doc_demandante: [''],
        no_identificacion_demandante: [''],
        fecha_expedicion_novedad: [''],
        fecha_radicacion: [''],
      }),
      historicoPropietarios: this.fb.array([])
    });

    this.id = Number(this.route.snapshot.paramMap.get('id'));
    if (this.id) {
      this.editMode = true;
      this.service.getByIdHttp(this.id).subscribe({
        next: (ingreso: Ingreso) => {
          const { historicoPropietarios, ...resto } = ingreso;
          this.form.patchValue(resto);

          this.historicoPropietarios.clear();
          historicoPropietarios.forEach((prop) => {
            this.historicoPropietarios.push(this.fb.group({
              nombre_empresa: [prop.nombre_empresa],
              tipo_propietario: [prop.tipo_propietario],
              tipo_identificacion: [prop.tipo_identificacion],
              numero_identificacion: [prop.numero_identificacion],
              fecha_inicio: [prop.fecha_inicio],
              estado: [prop.estado],
            }));
          });
        }
      });

    }
  }

  guardar() {
    this.loading = true;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.loading = false;
      this.alert.warning('Por favor completa todos los campos obligatorios.', 'Formulario incompleto');
      return;
    }

    const data: Omit<Ingreso, 'id'> = this.form.value;

    if (this.editMode) {
        const payload: Ingreso = {
          id: this.id,
          ...this.form.getRawValue()  // 👈 importante usar getRawValue() para incluir los campos deshabilitados
        };

        this.service.update(this.id, payload).subscribe({
          next: () => {
          this.loading = false;
          this.alert.success('El ingreso fue actualizado correctamente');
          this.router.navigate(['/admin/ingresos']);
        },
        error: () => {
          this.loading = false;
          this.alert.error('No se pudo actualizar el ingreso');
        }
      });
    } else {
      this.service.create(data).subscribe({
        next: () => {
          this.loading = false;
          this.alert.success('El ingreso fue creado correctamente');
          this.router.navigate(['/admin/ingresos']);
        },
        error: () => {
          this.loading = false;
          this.alert.error('No se pudo crear el ingreso');
        }
      });
    }
  }

  cancelar() {
    this.router.navigate(['/admin/ingresos']);
  }

  get historicoPropietarios(): FormArray {
  return this.form.get('historicoPropietarios') as FormArray;
}

nuevoPropietario(): FormGroup {
  return this.fb.group({
    nombre_empresa: [''],
    tipo_propietario: [''],
    tipo_identificacion: [''],
    numero_identificacion: [''],
    fecha_inicio: [''],
    estado: ['']
  });
}

addPropietario() {
  this.historicoPropietarios.push(this.nuevoPropietario());
}

removePropietario(index: number) {
  this.historicoPropietarios.removeAt(index);
}

}
