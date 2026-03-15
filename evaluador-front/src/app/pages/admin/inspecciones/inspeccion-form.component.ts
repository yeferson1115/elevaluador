import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule,FormArray  } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { InspeccionSelectComponent } from '../../../components/inspeccion-select.component';

// Ajusta estas rutas a tu proyecto
import { InspeccionService } from '../../../core/services/inspeccion.service';
import { AlertService } from '../../../core/services/alert.service';

@Component({
  selector: 'app-inspeccion-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule,InspeccionSelectComponent],
  templateUrl: './inspeccion-form.component.html',
  styleUrls: ['./inspeccion-form.component.css'],
})
export class InspeccionFormComponent implements OnInit {
  form!: FormGroup;
  id!: number | null;
  isEdit = false;
  avaluoId?: number;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private service: InspeccionService,
    private alert: AlertService,
    private router: Router
  ) {}

ngOnInit(): void {
  this.buildForm();

  this.id = Number(this.route.snapshot.paramMap.get('id')) || null;

  if (this.id) {
    this.service.getByIdHttp(this.id).subscribe({
      next: (resp) => {
        if (!resp) {
          this.alert.error('No se encontró el ingreso');
          this.router.navigate(['/admin/inspecciones']);
          return;
        }

        // Rellenar campos generales (solo lectura)
        this.form.patchValue(resp);

        // Subform de avaluo (editable)
        const inspeccionForm = this.form.get('inspeccion');

        if (resp.inspeccion) {
          // Si ya existe avaluo, lo cargo
          inspeccionForm?.patchValue(resp.inspeccion);

           // Cargar inspección visual
          if (resp.inspeccion.inspeccion_visual && resp.inspeccion.inspeccion_visual.length > 0) {
            this.revisionVisual.clear();
            resp.inspeccion.inspeccion_visual.forEach((revision: any) => {
              this.revisionVisual.push(this.fb.group({
                zona: [revision.zona],
                estado: [revision.estado]
              }));
            });
          }

          // Validación: si ingreso_id está null → asigno id de ingreso
          if (!resp.inspeccion.ingreso_id) {
            inspeccionForm?.get('ingreso_id')?.setValue(resp.id);
          }

          this.isEdit = !!resp.inspeccion.id;

 if (resp.inspeccion.inspeccion_accesorios && resp.inspeccion.inspeccion_accesorios.length > 0) {
  this.accesorios.clear(); // Limpia el FormArray antes de cargar datos nuevos
  resp.inspeccion.inspeccion_accesorios.forEach((accesorio: any) => {
    this.accesorios.push(this.fb.group({
      decripcion: [accesorio.decripcion || ''],
      marca_ref: [accesorio.marca_ref || ''],
      cantidad: [accesorio.cantidad ?? null],
      valor: [accesorio.valor ?? null]
    }));
  });
} else {
  // Si no hay accesorios, inicializa con un accesorio vacío para mostrar una fila
  if (this.accesorios.length === 0) {
    this.agregarAccesorio();
  }
}


        } else {
          // Si no existe avaluo, preparo nuevo con ingreso_id asignado
          inspeccionForm?.reset();
          inspeccionForm?.get('ingreso_id')?.setValue(resp.id);
          this.isEdit = false;
        }
      },
      error: () => {
        this.alert.error('No se pudo cargar el ingreso');
        this.router.navigate(['/admin/inspecciones']);
      }
    });
  } else {
    // Si no hay id en la URL → nuevo ingreso/avaluo
    this.isEdit = false;
  }
}



private buildForm(): void {
  this.form = this.fb.group({
    id: [{ value: '', disabled: true }],
    tiposervicio: [{ value: '', disabled: true }],
    solicitante: [{ value: '', disabled: true }],
    documento_solicitante: [{ value: '', disabled: true }],
    direccion_solicitante: [{ value: '', disabled: true }],
    telefono_solicitante: [{ value: '', disabled: true }],
    placa: [{ value: '', disabled: true }],
    ubicacion_activo: [{ value: '', disabled: true }],
    fecha_solicitud: [{ value: '', disabled: true }],
    fecha_inspeccion: [{ value: '', disabled: true }],
    fecha_informe: [{ value: '', disabled: true }],
    objeto_avaluo: [{ value: '', disabled: true }],
    codigo_interno_movil: [{ value: '', disabled: true }],
    tipo_propiedad: [{ value: '', disabled: true }],
    fecha_matricula: [{ value: '', disabled: true }],
    movil: [{ value: '', disabled: true }],
    marca: [{ value: '', disabled: true }],
    linea: [{ value: '', disabled: true }],
    clase: [{ value: '', disabled: true }],
    tipo_carroceria: [{ value: '', disabled: true }],
    categoria: [{ value: '', disabled: true }],
    color: [{ value: '', disabled: true }],
    cilindraje: [{ value: '', disabled: true }],
    modelo: [{ value: '', disabled: true }],
    kilometraje: [{ value: '', disabled: true }],
    caja_cambios: [{ value: '', disabled: true }],
    tipo_traccion: [{ value: '', disabled: true }],
    numero_pasajeros: [{ value: '', disabled: true }],
    capacidad_carga: [{ value: '', disabled: true }],
    numero_chasis: [{ value: '', disabled: true }],
    numero_serie: [{ value: '', disabled: true }],
    numero_motor: [{ value: '', disabled: true }],
    nacionalidad: [{ value: '', disabled: true }],
    propietario: [{ value: '', disabled: true }],
    empresa_afiliacion: [{ value: '', disabled: true }],
    soat: [{ value: '', disabled: true }],
    fecha_expedicion_soat: [{ value: '', disabled: true }],
    fecha_inicio_vigencia_soat: [{ value: '', disabled: true }],
    fecha_vencimiento_soat: [{ value: '', disabled: true }],
    entidad_expide_soat: [{ value: '', disabled: true }],
    estado_soat: [{ value: '', disabled: true }],
    rtm: [{ value: '', disabled: true }],
    fecha_vencimiento_rtm: [{ value: '', disabled: true }],
    centro_revision_rtm: [{ value: '', disabled: true }],
    estado_rtm: [{ value: '', disabled: true }],
    ciudad_registro: [{ value: '', disabled: true }],
    no_licencia: [{ value: '', disabled: true }],
    fecha_expedicion_licencia: [{ value: '', disabled: true }],
    organismo_transito: [{ value: '', disabled: true }],
    estado: [{ value: '', disabled: true }],
    created_at: [{ value: '', disabled: true }],
    updated_at: [{ value: '', disabled: true }],

    inspeccion: this.fb.group({
        id: [null],
        aseguradora:[''],
        intermediaria:[''],
        combustible:[''],
        tipo_pintura:[''],
        servicio:[''],        
        color:[''],
        centro_inspeccion:[''],
        valor_mercado:[''],
        valor_evaluador:[''],
        valor_accesorios:[''],
        cod_fasecolda:[''],
        valor_fasecolda:[''],
        novedades_inspeccion:[''],
        resultado:[''],
        turno:[''],
        intermediario:[''],
        ciudad:[''],
        observaciones:[''],
        expide_para:[''],
        desviacion_km:[''],
        tipo_vehiculo:[''],


        // Subgrupo editable
    inspeccion_mecanica: this.fb.group({
      id: [null],      
      funcionamiento_a_a: [''],
      nivel_aceite_direccion_hidraulica: [''],
      nivel_aceite_motor: [''],
      nivel_agua_limpiavidrios: [''],
      nivel_liquido_frenos: [''],
      nivel_liquido_embrague: [''],
      nivel_refrigerante_motor: [''],
      soportes_caja_velocidades: [''],
      viscosidad_aceite_motor: [''],
      estado_cables_instalacion_alta: [''],
      estado_carcasa_caja_velocidades: [''],
      estado_correas: [''],
      estado_externo_bateria: [''],
      estado_filtro_aire: [''],
      estado_manqgueras_radiador: [''],
      estado_radiador: [''],
      estado_radiador_a_a: [''],
      estado_soporte_motor: [''],
      tension_correas: ['']
    }),
    inspeccion_tapiceria: this.fb.group({
      id: [null],
      estado_timon: [''],
      estados_tapizados_puerta: [''],
      estados_tapizado_asientos: [''],
      estado_tapiceria_techo: ['']
    }),
    inspeccion_funcionamiento: this.fb.group({
      id: [null],
      asientos_delantero: [''],
      bocina: [''],
      calefaccion: [''],
      desempanador: [''],
      ecendedor: [''],
      espejos_electricos: [''],
      limpiabrisas_del: [''],
      limpiabrisas_tra: [''],
      luz_interior: [''],
      radio: [''],
      encendido_arranque: [''],
      tacometro: [''],
      techo_corredizo: [''],
      velocimetro: ['']
    }),
     inspeccion_luces: this.fb.group({
      id: [null],
      direccionales: [''],
      luces_altas: [''],
      luces_bajas: [''],
      luces_exploradoras: [''],
      luces_frenos: [''],
      lueces_medias: [''],
      luces_parqueo: [''],
      luces_placa: [''],
      luces_reversa: ['']
    }),
    inspeccion_exterior: this.fb.group({
      id: [null],
      vidrios: [''],
      tapiceria_accesorios: [''],
      fugas_fluidos: [''],
      ajuste_cierre_capo: [''],
      ajuste_cierre_puestas_delantera_izq: [''],
      ajuste_cierre_puestas_delantera_der: [''],
      ajuste_cierre_puertas_trasera_izq: [''],
      ajuste_cierre_puertas_trasera_der: [''],
      ajuste_cierre_tapa_baul_compuerta: ['']
    }),
    inspeccion_parte_baja: this.fb.group({
      id: [null],
      carter: [''],
      cauchos_suspension: [''],
      guardapolvos_caja_direccion: [''],
      guardapolvos_eje: [''],
      protectores_inferiores: [''],
      estado_catalizador: [''],
      estado_silenciador_escape: [''],
      estado_tijeras: [''],
      estado_tuberias_frenos: [''],
      estado_tubo_exhosto: [''],
      fuga_aceite_caja_velocidades: [''],
      fuga_direccion_hidraulica: [''],
      fuga_aceite_motor: [''],
      fuga_amortiguadores: [''],
      fuga_liquido_embrague: [''],
      fuga_liquido_frenos: [''],
      fuga_combustible_tanque: ['']
    }),
    inspeccion_indicadores: this.fb.group({
      id: [null],
      testigo_abs: [''],
      testigo_aceite: [''],
      testigo_airbag: [''],
      testigo_check_engine: [''],
      testigo_frenos: [''],
      testigo_combustible: ['']
    }),
    accesorios: this.fb.array([]),
    inspeccion_visual: this.fb.array([
      this.crearRevision('vista-superior'),
      this.crearRevision('vista-lateral-izq'),
      this.crearRevision('vista-frontal'),
      this.crearRevision('vista-lateral-der'),
      this.crearRevision('vista-trasera'),
      this.crearRevision('vista-inferior'),
      
    ]),
    inspeccion_revision_visual_punto_liviano: this.fb.group({
      id: [null],
      paragolpes_delantero: [''],
      soporte_paragolpes_der: [''],
      soporte_paragolpes_izq:[''],
      rejilla_paragolpes: [''],
      capo: [''],
      bisagra_capo: [''],
      persiana: [''],
      unidad_farola_der: [''],
      unidad_farola_izq:[''],
      luz_posicion_der: [''],
      luz_posicion_izq:[''],
      exploradora_der: [''],
      exploradora_izq:[''],
      cocuyo_der: [''],
      cocuyo_izq: [''],
      paragolpes_trasero:[''],
      soporte_paragolpes_tras:[''],
      tapa_baul_compuerta: [''],
      panel_trasero:[''],
      piso_baul:[''],
      stop_der:[''],
      stop_izq:[''],
      stop_compuerta_der:[''],
      stop_compuerta_izq:[''],
      tercer_stop:[''],
      tapizado_capota:[''],
      alfombra_piso:[''],
      tapizado_puerta_delantera_der:[''],
      tapizado_puerta_delantera_izq:[''],
      tapizado_puerta_trasera_der:[''],
      tapizado_puerta_trasera_izq:[''],
      tapizado_paral_parabrisas_der:[''],
      tapizado_paral_parabrisas_izq:[''],
      tapizado_paral_central_der:[''],
      tapizado_paral_central_izq:[''],
      tapizado_baul_der:[''],
      tapizado_baul_izq:[''],
      abullonado_millare:[''],
      consola_central:[''],
      mecanismo_elevavidrios_principal:[''],
      elevavidrios_puerta_delantera_der:[''],
      elevavidrios_puerta_delantera_izq:[''],
      elevavidrios_puerta_trasera_der:[''],
      elevavidrios_puerta_trasera_izq:[''],
      caja_direccion:[''],
      brazo_direccion:[''],
      terminal_direccion:[''],
      motor:[''],
      caja_de_velocidades:[''],
      traccion_doble:[''],
      modulo_ECM_ECU_PCM:[''],
      bomba_inyección:[''],
      turbo:[''],
      alternador:[''],
      caja_direccion_mec:[''],
      bateria:[''],
      sistema_exhosto:[''],
      catalizador:[''],
      embrague_termico:[''],
      eje_delantero:[''],
      instalacion_electrica_motor:[''],
      radiador:[''],
      condensador:[''],
      tijera:[''],
      portamangueta:[''],
      amortiguador_delantero_der:[''],
      amortiguador_delantero_izq:[''],
      muelle_delantero_der:[''],
      muelle_delantero_izq:[''],
      muelle_trasero_der:[''],
      muelle_trasero_izq:[''],
      amortiguador_trasero_der:[''],
      amortiguador_trasero_izq:[''],
      puente_delantero:[''],
      cuna_motor:[''],
      puente_trasero:[''],
      suspension_multilink_trasera:[''],
      punta_chasis_delantera_der:[''],
      punta_chasis_delantera_izq:[''],
      punta_chasis_trasera_der:[''],
      punta_chasis_trasera_izq:[''],
      viga_chasis:[''],
      traviesa_chasis:[''],
      piso_habitaculo:[''],
      panoramico_delantero:[''],
      panoramico_trasero:[''],
      vidrio_puerta_delantera_der:[''],
      vidrio_puerta_delantera_izq:[''],
      vidrio_puerta_trasera_der:[''],
      vidrio_puerta_trasera_izq:[''],
      capota:[''],
      antena_capota:[''],
      guardafango_der:[''],
      guardafango_izq:[''],
      cocuyo_guardafango_der:[''],
      cocuyo_guardafango_izq:[''],
      puerta_delantera_der:[''],
      puerta_delantera_izq:[''],
      puerta_trasera_der:[''],
      puerta_trasera_izq:[''],
      costado_der:[''],
      costado_izq:[''],
      paral_puerta_der:[''],
      paral_puerta_izq:[''],
      paral_parabrisas_der:[''],
      paral_parabrisas_izq:[''],
      estribo_der:[''],
      estribo_izq:[''],
      paral_central_der:[''],
      paral_central_izq:[''],

      unidad_farola_moto: [''],
      visera: [''],
      direccionales_moto: [''],
      manillar: [''],
      espejo_izq_moto: [''],
      espejo_der_moto: [''],
      carenaje_delantero: [''],
      horquilla: [''],
      guardafango_frontal: [''],
      tanque_combustible: [''],
      sillon: [''],
      chasis: [''],
      estribo_moto: [''],
      tapa_lateral_izq: [''],
      tapa_lateral_der: [''],
      tapa_trasera_izq: [''],
      tapa_trasera_der: [''],
      guardafango_trasero: [''],
      stop_moto: [''],
      pata: [''],
      caballete: [''],
      mango_calapie: [''],
      maleta: [''],
      cofre_trasero: [''],
      barra_telescopica_izq: [''],
      barra_telescopica_der: [''],
      amortiguador_trasero_moto: [''],
      motor_moto: [''],
      kit_arrastre: [''],
      sistema_escape: [''],
      bateria_moto: [''],
      mango_acelerador: [''],
      manigueta_freno: [''],
      manigueta_embrague: [''],
      deposito_liquido_hidraulico: [''],
      tablero_instrumentos: [''],
      pedal_freno: [''],
      pedal_cambios: [''],
      disco_campana_delantera: [''],
      disco_campana_trasera: [''],
      aceite_motor_fugas: [''],
      combustible_fugas: [''],
      llanta_delantera_moto: [''],
      llanta_trasera_moto: [''],
    }),
    
    inspeccion_revision_visual: this.fb.group({
      id: [null],
      pintura: [''],
      desviacion_km: [''],
      ruedas_traseras: [''],
      ruedas_delanteras: [''],
      llanta_del_izq: [''],
      llanta_del_der: [''],
      llanta_tras_izq: [''],
      llanta_tras_der: [''],
      freno_mano: [''],
      suspension_tras: [''],
      suspension_delantera: [''],
      unidad_farola_moto: [''],
      visera: [''],
      direccionales_moto: [''],
      manillar: [''],
      espejo_izq_moto: [''],
      espejo_der_moto: [''],
      carenaje_delantero: [''],
      horquilla: [''],
      guardafango_frontal: [''],
      tanque_combustible: [''],
      sillon: [''],
      chasis: [''],
      estribo_moto: [''],
      tapa_lateral_izq: [''],
      tapa_lateral_der: [''],
      tapa_trasera_izq: [''],
      tapa_trasera_der: [''],
      guardafango_trasero: [''],
      stop_moto: [''],
      pata: [''],
      caballete: [''],
      mango_calapie: [''],
      maleta: [''],
      cofre_trasero: [''],
      barra_telescopica_izq: [''],
      barra_telescopica_der: [''],
      amortiguador_trasero_moto: [''],
      motor_moto: [''],
      kit_arrastre: [''],
      sistema_escape: [''],
      bateria_moto: [''],
      mango_acelerador: [''],
      manigueta_freno: [''],
      manigueta_embrague: [''],
      deposito_liquido_hidraulico: [''],
      tablero_instrumentos: [''],
      pedal_freno: [''],
      pedal_cambios: [''],
      disco_campana_delantera: [''],
      disco_campana_trasera: [''],
      aceite_motor_fugas: [''],
      combustible_fugas: [''],
      llanta_delantera_moto: [''],
      llanta_trasera_moto: [''],
    }),

  
    }),

    
  });
}

guardar() {
  if (this.form.invalid) {
    this.alert.error('Por favor, complete los campos requeridos correctamente.');
    return;
  }

  const inspeccionData = this.form.getRawValue();
  console.log(inspeccionData);

  if (!inspeccionData) {
    this.alert.error('No hay datos de inspección para guardar.');
    return;
  }

  
  if (this.isEdit && inspeccionData.id) {
    // Actualizar avalúo existente
    this.service.update(inspeccionData.id, inspeccionData).subscribe({
      next: () => {
        this.alert.success('Avalúo actualizado correctamente');
        // Aquí puedes redirigir o hacer otra acción
        this.router.navigate(['/admin/inspecciones']);
      },
      error: () => this.alert.error('Error al actualizar avalúo')
    });
  } else {
    // Crear nuevo avalúo
    this.service.create(inspeccionData).subscribe({
      next: () => {
        this.alert.success('Avalúo creado correctamente');
        // Aquí puedes limpiar el formulario o redirigir
        this.router.navigate(['/admin/inspecciones']);
      },
      error: () => this.alert.error('Error al crear avalúo')
    });
  }
}


get accesorios(): FormArray {
  return this.form.get('inspeccion.accesorios') as FormArray;
}

crearAccesorio(): FormGroup {
  return this.fb.group({
    id: [null],
    decripcion: [''],
    marca_ref: [''],
    cantidad: [null],
    valor: [null]
  });
}

agregarAccesorio() {
  this.accesorios.push(this.crearAccesorio());
}

eliminarAccesorio(index: number) {
  this.accesorios.removeAt(index);
}

crearRevision(zona: string): FormGroup {
  return this.fb.group({
    zona: [zona],
    estado: [null] // 'Bueno' | 'Aceptable' | 'Malo'
  });
}

get revisionVisual(): FormArray {
  return this.form.get('inspeccion.inspeccion_visual') as FormArray;
}

get revisionVisualArray(): FormGroup[] {
  return (this.form.get('inspeccion.inspeccion_visual') as FormArray).controls as FormGroup[];
}
}
