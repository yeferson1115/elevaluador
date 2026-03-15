import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormArray } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { IngresoService } from '../../../core/services/Ingreso.service';
import { Ingreso } from '../../../core/interfaces/ingresos.interface';
import { AlertService } from '../../../core/services/alert.service';
import { AvaluoService } from '../../../core/services/avaluo.service';
import { ValoresRepuestosService } from '../../../core/services/valores-repuestos.service';
import { FasecoldaService } from '../../../core/services/fasecolda.service';

@Component({
  selector: 'app-avaluo-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './avaluo-form.component.html',
  styleUrls: ['./avaluo-form.component.css'],
})
export class AvaluoFormComponent implements OnInit {
  loading = false;
  form!: FormGroup;
  editMode = false;
  id!: number | null;
  isEdit = false;
  avaluoId?: number;
  valores: string[] = [];
  porcentajes: number[] = [];
  private claseAnterior: string = '';
  private cilindrajeAnterior: number = 0;
  private debounceTimer: any;

  private valoresInicialesCargados = false;
  private claseOriginal: string = '';
  private cilindrajeOriginal: number = 0;
  

  // Agregar este array en la clase
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
  evaluadores:string[] = [
    'Ivan Mora',
    'Jhonny Rodríguez', 
    'Mauricio Garcia', 
    'Lenin Ariza',
    'German Galvis'
   
  ];
  clases:string[] = [
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

  constructor(
    private fb: FormBuilder,
    private service: AvaluoService,
    private route: ActivatedRoute,
    private router: Router,
    private alert: AlertService,
    private valoresRepuestosService: ValoresRepuestosService,
    private fasecoldaService: FasecoldaService,
  ) {}

  ngOnInit() {
    this.ubicaciones = [
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

    this.porcentajes = [];
    for (let i = 1; i <= 100; i++) {
      this.porcentajes.push(i);
    }
    // Lista de valores para "x"
    for (let i = 1; i <= 10; i += 0.1) {
      this.valores.push(i.toFixed(1)); // "1.0", "1.1", ..., "10.0"
    }

    this.buildForm();

    this.id = Number(this.route.snapshot.paramMap.get('id')) || null;
    if (this.id) {
      this.loadData(this.id);
    } else {
      this.isEdit = false;
    }

      // Listener para calcular Peso Mermado cuando cambia Peso Vacío
    this.form.get('peso_bruto')?.valueChanges.subscribe((pesoVacio) => {
      this.calcularPesoMermado(pesoVacio);
    });

      this.form.get('avaluo.chatarra')?.valueChanges.subscribe((chatarraValue) => {
    if (chatarraValue === 'Si') {
      // Cuando se selecciona "Si" en Chatarra, sincronizar peso_mermado con peso_chatarra_kg
      const pesoMermado = this.form.get('peso_mermado')?.value;
      if (pesoMermado && pesoMermado > 0) {
        const avaluoForm = this.form.get('avaluo') as FormGroup;
        avaluoForm.get('peso_chatarra_kg')?.setValue(pesoMermado, { emitEvent: false });
        this.calcularValorChatarra();
      }
    }
  });
    this.form.get('peso_mermado')?.valueChanges.subscribe(() => {
      this.syncPesoMermadoToChatarra();
    });

  
  this.form.get('avaluo.valor_chatarra_kg')?.valueChanges.subscribe(() => {
    this.calcularValorChatarra();
  });

   this.form.get('clase')?.valueChanges.subscribe((clase) => {
    if (clase !== this.claseAnterior) {
      this.claseAnterior = clase;
      this.buscarValoresRepuestoDebounced();
    }
  });

  this.form.get('cilindraje')?.valueChanges.subscribe((cilindraje) => {
    if (cilindraje !== this.cilindrajeAnterior) {
      this.cilindrajeAnterior = cilindraje;
      this.buscarValoresRepuestoDebounced();
    }
  });

   this.form.get('avaluo.codigo_fasecolda')?.valueChanges.subscribe((codigo) => {
    if (codigo && codigo.trim() !== '') {
      setTimeout(() => {
        this.cargarValoresFasecolda();
      }, 500);
    }
  });

   this.form.valueChanges.subscribe(() => {
      console.log('cambios');
      this.calcularFormulas();
    });

    // Escuchar cambios en los campos que afectan el índice de reparabilidad
  this.form.valueChanges.subscribe(() => {
    console.log('cambios');
    this.calcularFormulas(); // Esto ya incluye el cálculo del índice de reparabilidad
  });
  
  // También escuchar cambios específicos en campos clave
  const camposReparabilidad = [
    'latoneria_valor', 'valor_pintura', 'motor_valor', 'chasis_valor',
    'tapiceria_valor', 'refrigeracion_valor', 'electrico_valor', 
    'valor_llantas', 'transmision_valor', 'vidrios_valor', 'tanque_valor',
    'bateria_valor', 'frenos_valor', 'llave_valor',
    'valor_faltantes', 'valor_RTM', 'valor_SOAT',
    'valor_razonable', 'factor_demerito'
  ];
  
  camposReparabilidad.forEach(campo => {
    this.form.get(`avaluo.${campo}`)?.valueChanges.subscribe(() => {
      setTimeout(() => {
        this.calcularIndiceReparabilidad();
      }, 100);
    });
  });
  
  
  this.calcularFormulas();
  }

buscarValoresRepuestoDebounced(esCambioManual: boolean = false): void {
  clearTimeout(this.debounceTimer);
  this.debounceTimer = setTimeout(() => {
    this.buscarValoresRepuesto(esCambioManual);
  }, 500);
}

buscarValoresRepuesto(esCambioManual: boolean = false): void {
  const clase = this.form.get('clase')?.value;
  const cilindraje = this.form.get('cilindraje')?.value;

  if (clase && cilindraje && cilindraje > 0) {
    console.log(`Buscando valores (cambio manual: ${esCambioManual}):`, { clase, cilindraje });
    
    this.valoresRepuestosService.buscarPorCilindraje(clase, cilindraje)
      .subscribe({
        next: (response) => {
          if (response.success && response.data) {
            // Pasar el flag esCambioManual al aplicar los valores
            this.aplicarValoresRepuesto(response.data, esCambioManual);
            
            if (esCambioManual) {
              this.alert.success('Valores actualizados según nueva configuración');
            }
          }
        },
        error: (error) => {
          console.error('Error al buscar valores de repuesto:', error);
        }
      });
  }
}

// Método para aplicar los valores encontrados
// Método para aplicar los valores encontrados en los campos correctos
aplicarValoresRepuesto(valores: any, esCambioManual: boolean = false): void {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  
  console.log('Aplicando valores de repuestos:', {
    valores,
    esCambioManual,
    valoresInicialesCargados: this.valoresInicialesCargados
  });
  
  // Determinar el comportamiento
  const reemplazarTodo = esCambioManual || !this.valoresInicialesCargados;
  
  if (reemplazarTodo) {
    // REEMPLAZAR TODOS los valores (cambio manual o nuevo avalúo)
    this.aplicarTodosLosValores(avaluoForm, valores);
    console.log('Todos los valores reemplazados');
    
    // Mostrar notificación si fue por cambio manual
    if (esCambioManual) {
      this.alert.info('Valores actualizados según nueva clase/cilindraje');
    }
  } else {
    // SOLO llenar campos VACÍOS (carga inicial de datos existentes)
    this.aplicarSoloCamposVacios(avaluoForm, valores);
    console.log('Solo campos vacíos llenados');
  }
  
  // Forzar recálculo de fórmulas
  setTimeout(() => {
    this.calcularFormulas();
  }, 100);
}

private aplicarTodosLosValores(avaluoForm: FormGroup, valores: any): void {
  // Mapeo completo de valores
  const mapeo = {
    'latoneria': 'latoneria_valor',
    'rtm': 'valor_RTM',
    'soat': 'valor_SOAT',
    'llantas': 'valor_llantas',
    'motor_mantenimiento': 'motor_valor',
    'chasis': 'chasis_valor',
    'frenos': 'frenos_valor',
    'tanque_combustible': 'tanque_valor',
    'bateria': 'bateria_valor',
    'llave': 'llave_valor',
    'sis_electrico': 'electrico_valor',
    'pintura': 'valor_pintura',
    'tapiceria': 'tapiceria_valor',
    'kit_arrastre':'transmision_valor'
  };
  
  // Aplicar todos los valores que existan
  Object.entries(mapeo).forEach(([key, campoForm]) => {
    if (valores[key] !== null && valores[key] !== undefined) {
      avaluoForm.get(campoForm)?.setValue(valores[key], { emitEvent: false });
      console.log(`${campoForm} = ${valores[key]}`);
    }
  });
}

private aplicarSoloCamposVacios(avaluoForm: FormGroup, valores: any): void {
  // Mapeo completo de valores
  const mapeo = {
    'latoneria': 'latoneria_valor',
    'rtm': 'valor_RTM',
    'soat': 'valor_SOAT',
    'llantas': 'valor_llantas',
    'motor_mantenimiento': 'motor_valor',
    'chasis': 'chasis_valor',
    'frenos': 'frenos_valor',
    'tanque_combustible': 'tanque_valor',
    'bateria': 'bateria_valor',
    'llave': 'llave_valor',
    'sis_electrico': 'electrico_valor',
    'pintura': 'valor_pintura',
    'tapiceria': 'tapiceria_valor'
  };
  
  // Aplicar solo si el campo está vacío
  Object.entries(mapeo).forEach(([key, campoForm]) => {
    if (valores[key] && this.campoEstaVacio(avaluoForm, campoForm)) {
      avaluoForm.get(campoForm)?.setValue(valores[key], { emitEvent: false });
      console.log(`${campoForm} (vacío) = ${valores[key]}`);
    }
  });
}

 private campoEstaVacio(formGroup: FormGroup, campo: string): boolean {
    const valor = formGroup.get(campo)?.value;
    return valor === null || valor === undefined || valor === '' || valor === 0 || valor === '0';
  }

calcularPesoMermado(pesoVacio: number | null): void {
  if (pesoVacio !== null && pesoVacio !== undefined && pesoVacio > 0) {
    // Calcular Peso Mermado = Peso Vacío * 0.75
    const pesoMermado = pesoVacio * 0.75;
    
    // Actualizar el campo peso_mermado
    this.form.get('peso_mermado')?.setValue(pesoMermado, { emitEvent: false });
    
    // IMPORTANTE: Verificar si el campo chatarra está en "Si" y sincronizar
    const esChatarra = this.form.get('avaluo.chatarra')?.value;
    
    // También escuchar cambios en el campo chatarra para actualizar
    if (esChatarra === 'Si') {
      // Sincronizar peso_mermado con peso_chatarra_kg
      const avaluoForm = this.form.get('avaluo') as FormGroup;
      avaluoForm.get('peso_chatarra_kg')?.setValue(pesoMermado, { emitEvent: false });
      
      // Recalcular valor total chatarra
      this.calcularValorChatarra();
    }
  }
}

  private buildForm(): void {
    this.form = this.fb.group({
      placa: ['', Validators.required],
      fecha_solicitud: [{ value: '' }],
      marca: ['', Validators.required],
      linea: ['', Validators.required],
      clase: ['', Validators.required],      
      color: ['', Validators.required],
      cilindraje: [null, Validators.required],
      modelo: [null, Validators.required],
      kilometraje: [null, Validators.required],
      caja_cambios: [{ value: ''}],
      cantidad_ejes: [{ value: ''}],
      peso_bruto:[{ value: ''}],
      peso_mermado:[{ value: ''}],  
      estado_registro_runt:[{ value: ''}], 
      capacidad_ton: [{ value: ''}],    
      numero_chasis: [{ value: ''}],
      numero_serie: [{ value: ''}],
      numero_motor: [{ value: '' }],
      numeroVin: ['', Validators.required],
      tipo_servicio_vehiculo: ['', Validators.required],
      tipo_carroceria: [{ value: '' }],
      fecha_inspeccion: [{ value: '' }],
      porc_reposicion: [{ value: '' }],

      // Datos generales (solo lectura)
      id: [{ value: '', disabled: true }],
      tiposervicio: [{ value: 'Sec Bogota', disabled: true }],
      solicitante: [{ value: '', disabled: true }],
      documento_solicitante: [{ value: '', disabled: true }],
      direccion_solicitante: [{ value: '', disabled: true }],
      telefono_solicitante: [{ value: '', disabled: true }],
      ubicacion_activo: [{ value: '', disabled: true }],
      fecha_informe: [{ value: '', disabled: true }],
      objeto_avaluo: [{ value: '', disabled: true }],
      codigo_interno_movil: [{ value: '', disabled: true }],
      tipo_propiedad: [{ value: '', disabled: true }],
      fecha_matricula: [{ value: ''}],
      movil: [{ value: '', disabled: true }],
      categoria: [{ value: '', disabled: true }],
      tipo_traccion: [{ value: '', disabled: true }],
      numero_pasajeros: [{ value: '' }],
      capacidad_carga: [{ value: '', disabled: true }],
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

      // Subgrupo editable (avaluo)
      avaluo: this.fb.group({
        id: [null],
        tipo: [''],
        formato: ['Sec Bogota'],
        ubicacion: [''],
        evaluador: [''],
        observaciones:[''],
        ingreso_id: [{ value: '', disabled: true }],
        fecha_inspeccion: [''],
        vida_util_probable: [''],
        vida_usada_dias: [{ value: '', disabled: true }],
        vida_usada_meses: [{ value: '', disabled: true }],
        vida_usada_anos: [{ value: '', disabled: true }],
        vida_util_remate: [''],
        vida_util_anos: [''],
        antiguedad: [{ value: '', disabled: true }],   // L14
        vida_util: [{ value: '', disabled: true }],    // L15
        estado_conservacion: [''],                     // L18
        x: [''],                                       // L19
        k: [{ value: '', disabled: true }],            // calculado
        valor_reposicion: [''],
        porc_reposicion: [''],
        valor_residual: [{ value: '', disabled: true }],
        valor_resonable: [{ value: '', disabled: true }],
        capacidad_transportadora: [''],
        valor_razonable: [{ value: '', disabled: true }],
        valor_carroceria: [''],
        valor_reparaciones: [''],
        valor_llantas: [''],
        valor_pintura: [''],
        valor_overhaul_motor: [''],
        factor_demerito: [''],
        valor_accesorios: [''],
        indice_responsabilidad_minimo: [{ value: '', disabled: true }],
        avaluo_total: [{ value: '', disabled: true }],
        no_factura: [''],
        declaracion_importacion: [''],
        fecha_importacion: [''],
        registro_maquinaria: [''],
        gps: [''],
        clasificados: this.fb.array([]),
        corregidos: this.fb.array([]),
        limitaciones: this.fb.array([]), // Array para limitaciones
        llanta_delantera_izquierda: [''],
        llanta_delantera_derecha: [''],
        llanta_trasera_izquierda: [''],
        llanta_trasera_derecha: [''],
        llanta_repuesto: [''],
        latoneria_estado: [''], 
        latoneria_valor: [''],
        pintura_estado: [''],
        tapiceria_estado: [''], 
        tapiceria_valor: [''],
        motor_estado: [''], 
        motor_valor: [''],
        chasis_estado: [''], 
        chasis_valor: [''],
        transmision_estado: [''], 
        transmision_valor: [''],
        frenos_estado: [''], 
        frenos_valor: [''],
        refrigeracion_estado: [''], 
        refrigeracion_valor: [''],
        electrico_estado: [''], 
        electrico_valor: [''],
        tanque_estado: [''], 
        tanque_valor: [''],
        bateria_estado: [''], 
        bateria_valor: [''],
        llantas_estado: [''], 
        llantas_valor: [''],
        llave_estado: [''],
        llave_valor: [''],
        vidrios_estado: [''], 
        vidrios_valor: [''],
        chatarra:[''],
        valor_chatarra_kg:[0],
        peso_chatarra_kg:[0],
        valor_total_chatarra: [{ value: 0, disabled: true }],
        valor_RTM:[''],
        valor_SOAT:[''],
        valor_faltantes:[''],
        codigo_fasecolda:[''],
        indice_reparabilidad: [{ value: 0, disabled: true }],
      }),
    });
  }

  private loadData(id: number): void {
  this.service.getByIdHttp(id).subscribe({
    next: (resp) => {
      if (!resp) {
        this.alert.error('No se encontró el ingreso');
        this.router.navigate(['/admin/avaluo-sec-bgta']);
        return;
      }

      this.form.patchValue(resp);

      const avaluoForm = this.form.get('avaluo');
      if (resp.avaluo) {
        avaluoForm?.patchValue(resp.avaluo);
         // Guardar valores originales
        this.claseOriginal = resp.clase || '';
        this.cilindrajeOriginal = resp.cilindraje || 0;

        // Clasificados
        if (resp.avaluo.clasificados?.length) {
          const clasificadosArray = avaluoForm?.get('clasificados') as FormArray;
          clasificadosArray.clear();
          resp.avaluo.clasificados.forEach((item: any) => {
            clasificadosArray.push(this.fb.group({
              modelo: [item.modelo],
              valor: [item.valor]
            }));
          });
        }

        // Corregidos
        if (resp.avaluo.corregidos?.length) {
          const corregidosArray = avaluoForm?.get('corregidos') as FormArray;
          corregidosArray.clear();
          resp.avaluo.corregidos.forEach((item: any) => {
            corregidosArray.push(this.fb.group({
              modelo: [item.modelo],
              valor: [item.valor]
            }));
          });
        }

        // Limitaciones - AGREGADO: Solo agregar limitaciones por defecto si no hay existentes
        const limitacionesArray = avaluoForm?.get('limitaciones') as FormArray;
        limitacionesArray.clear();
        
        if (resp.avaluo.limitaciones?.length) {
          // Si ya existen limitaciones, cargarlas normalmente
          resp.avaluo.limitaciones.forEach((item: any) => {
            limitacionesArray.push(this.fb.group({
              texto: [item.texto]
            }));
          });
        } else {
          // Si NO hay limitaciones, agregar las por defecto
          this.agregarLimitacionesPorDefecto();
        }

        // Ingreso_id
        if (!resp.avaluo.ingreso_id) {
          avaluoForm?.get('ingreso_id')?.setValue(resp.id);
        }

        this.isEdit = !!resp.avaluo.id;
         setTimeout(() => {
          this.valoresInicialesCargados = true;
          
          // Buscar valores pero solo para llenar campos vacíos
          this.buscarValoresRepuestoParaCamposVacios();
          
          // Configurar listeners
          this.configurarListeners();
        }, 800);

      } else {
        avaluoForm?.reset();
        // Establecer formato por defecto
        avaluoForm?.get('formato')?.setValue('Sec Bogota');
        avaluoForm?.get('ingreso_id')?.setValue(resp.id);
        
        // AGREGADO: Para nuevos avalúos sin limitaciones, agregar las por defecto
        this.agregarLimitacionesPorDefecto();
        
        this.isEdit = false;
        // Para nuevo avalúo, NO hay valores iniciales
        this.valoresInicialesCargados = false;
        
        setTimeout(() => {
          this.configurarListeners();
        }, 100);
      }
    },
    error: () => {
      this.alert.error('No se pudo cargar el ingreso');
      this.router.navigate(['/admin/avaluo-sec-bgta']);
    },
  });
}

private buscarValoresRepuestoParaCamposVacios(): void {
  const clase = this.form.get('clase')?.value;
  const cilindraje = this.form.get('cilindraje')?.value;
  
  if (clase && cilindraje && cilindraje > 0) {
    console.log('Búsqueda inicial para campos vacíos:', { clase, cilindraje });
    
    this.valoresRepuestosService.buscarPorCilindraje(clase, cilindraje)
      .subscribe({
        next: (response) => {
          if (response.success && response.data) {
            // En la carga inicial, solo llenar campos vacíos
            this.aplicarValoresRepuesto(response.data, false); // false = solo campos vacíos
            
            // Verificar si se llenaron campos y mostrar mensaje
            this.mostrarResumenCamposLlenados();
          }
        },
        error: (error) => {
          console.error('Error en búsqueda inicial:', error);
        }
      });
  }
}
private mostrarResumenCamposLlenados(): void {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  const campos = [
    'latoneria_valor', 'valor_RTM', 'valor_SOAT', 'valor_llantas',
    'motor_valor', 'chasis_valor', 'frenos_valor', 'tanque_valor',
    'bateria_valor', 'llave_valor', 'electrico_valor', 'valor_pintura',
    'tapiceria_valor'
  ];
  
  const camposLlenados = campos.filter(campo => {
    const valor = avaluoForm.get(campo)?.value;
    return valor && valor !== 0 && valor !== '';
  });
  
  if (camposLlenados.length > 0) {
    console.log(`${camposLlenados.length} campos llenados automáticamente`);
  }
}

private configurarListeners(): void {
  // Variables para detectar cambios
  let claseActual = this.form.get('clase')?.value;
  let cilindrajeActual = this.form.get('cilindraje')?.value;
  
  // Listener para CLASE
  this.form.get('clase')?.valueChanges.subscribe((nuevaClase) => {
    if (nuevaClase && nuevaClase !== claseActual) {
      console.log('Clase cambiada:', { anterior: claseActual, nueva: nuevaClase });
      claseActual = nuevaClase;
      
      // Verificar si es un cambio significativo (no la carga inicial)
      const esCambioSignificativo = this.valoresInicialesCargados && 
        (this.claseOriginal !== nuevaClase || this.cilindrajeOriginal !== cilindrajeActual);
      
      if (esCambioSignificativo || !this.valoresInicialesCargados) {
        // Buscar valores con comportamiento de reemplazo total
        this.buscarValoresRepuestoDebounced(true);
      }
    }
  });
  
  // Listener para CILINDRAJE
  this.form.get('cilindraje')?.valueChanges.subscribe((nuevoCilindraje) => {
    if (nuevoCilindraje && nuevoCilindraje !== cilindrajeActual) {
      console.log('Cilindraje cambiado:', { anterior: cilindrajeActual, nueva: nuevoCilindraje });
      cilindrajeActual = nuevoCilindraje;
      
      // Verificar si es un cambio significativo (no la carga inicial)
      const esCambioSignificativo = this.valoresInicialesCargados && 
        (this.claseOriginal !== claseActual || this.cilindrajeOriginal !== nuevoCilindraje);
      
      if (esCambioSignificativo || !this.valoresInicialesCargados) {
        // Buscar valores con comportamiento de reemplazo total
        this.buscarValoresRepuestoDebounced(true);
      }
    }
  });
  
  // ... otros listeners existentes ...
}

// AGREGAR este nuevo método en tu clase:
private agregarLimitacionesPorDefecto(): void {
  const limitacionesPorDefecto = [
    'Las condiciones climatológicas, ambientales, de iluminación y de almacenamiento del vehículo al momento de la inspección pueden influir en la apreciación del estado físico y estético de los componentes, constituyéndose en una limitación inherente al proceso de inspección técnica.',
    'La inspección técnica se limita a una evaluación visual y funcional de los sistemas, subconjuntos y componentes del vehículo que se encuentran accesibles y ensamblados, sin realizar desarmes parciales o totales, los cuales se encuentran fuera del alcance del presente avalúo.',
    'El bien objeto del presente avalúo se encuentra sometido a gastos continuos derivados de su permanencia en patios oficiales y/o privados.',
    'El presente avalúo se realizó sin efectuar desarmes, pruebas invasivas ni intervenciones mecánicas, limitándose la verificación del motor a una inspección visual externa. Por lo anterior, no se puede determinar el estado real de los componentes internos ni de los sistemas asociados.',
    'No se realiza validación de los sistemas de identificación del vehículo ni consulta de antecedentes judiciales, constituyéndose esta condición como una limitación del presente avalúo técnico.'
  ];

  const limitacionesArray = this.form.get('avaluo.limitaciones') as FormArray;
  
  // Limpiar array primero (por si acaso)
  limitacionesArray.clear();
  
  // Agregar cada limitación por defecto
  limitacionesPorDefecto.forEach(texto => {
    limitacionesArray.push(this.fb.group({
      texto: [texto]
    }));
  });
}

  private calcularFormulas(): void {
    const avaluoForm = this.form.get('avaluo') as FormGroup;
    const values = avaluoForm.getRawValue();

    const antiguedad = parseFloat(values.antiguedad) || 0;  
    const vidaUtil = parseFloat(values.vida_util) || 0;     
    const x = parseFloat(values.x) || 0;                    
    const estadoConservacion = parseFloat(values.estado_conservacion) || 0; 
    const valorReposicion = parseFloat(values.valor_reposicion) || 0;       
    const valorResidual = parseFloat(values.valor_residual) || 0;           
    const fechaMatricula = this.form.get('fecha_matricula')?.value;
    const fechaInspeccion = this.form.get('fecha_inspeccion')?.value;

    // Copiar vida_util_probable → vida_util
    if (values.vida_util_probable && values.vida_util_probable !== avaluoForm.get('vida_util')?.value) {
      avaluoForm.get('vida_util')?.setValue(values.vida_util_probable, { emitEvent: false });
    }

    // Calcular K
    if (vidaUtil > 0 && x > 0 && antiguedad > 0 && estadoConservacion >= 0) {
      const ratio = antiguedad / vidaUtil;
      const potencia = Math.pow(ratio, 1 / x);
      const k = potencia + ((1 - potencia) * estadoConservacion);
      avaluoForm.get('k')?.setValue(k.toFixed(5), { emitEvent: false });
    }

    // Valor residual con porcentaje dinámico
    const porcReposicion = parseFloat(values.porc_reposicion) || 0;

    if (valorReposicion > 0 && porcReposicion > 0) {
      const porcentajeDecimal = porcReposicion / 100; // Convertimos a decimal
      const vr = valorReposicion * porcentajeDecimal;
      avaluoForm.get('valor_residual')?.setValue(vr.toFixed(2), { emitEvent: false });
    }

    // Valor razonable
    const kValue = parseFloat(avaluoForm.get('k')?.value) || 0;
    if (valorReposicion > 0 && valorResidual > 0 && kValue > 0) {
      const resonable = valorReposicion - ((valorReposicion - valorResidual) * kValue);
      const redondeado = Math.round(resonable / 100) * 100;
      avaluoForm.get('valor_resonable')?.setValue(redondeado, { emitEvent: false });
      avaluoForm.get('valor_razonable')?.setValue(redondeado, { emitEvent: false });
    }

    // Vida usada (días / meses / años)
    if (fechaMatricula && fechaInspeccion) {
      const f1 = new Date(fechaMatricula);
      const f2 = new Date(fechaInspeccion);

      if (!isNaN(f1.getTime()) && !isNaN(f2.getTime()) && f2 >= f1) {
        const dias = this.dias360(f1, f2);
        avaluoForm.get('vida_usada_dias')?.setValue(dias, { emitEvent: false });

        const diffDays = (f2.getTime() - f1.getTime()) / (1000 * 60 * 60 * 24);
        const yearLength = this.esBisiesto(f1.getFullYear()) ? 366 : 365;
        const fracYear = diffDays / yearLength;

        const meses = fracYear * 12;
        const anos = fracYear;

        avaluoForm.get('vida_usada_meses')?.setValue(meses.toFixed(2), { emitEvent: false });
        avaluoForm.get('vida_usada_anos')?.setValue(anos.toFixed(2), { emitEvent: false });
        avaluoForm.get('antiguedad')?.setValue(parseFloat(meses.toFixed(2)), { emitEvent: false });
      }
    }

    // Vida útil remanente
    const vidaUtilProbable = parseFloat(values.vida_util_probable) || 0;
    const vidaUsadaMeses = parseFloat(avaluoForm.get('vida_usada_meses')?.value) || 0;
    if (vidaUtilProbable > 0 || vidaUsadaMeses > 0) {
      const remanente = vidaUtilProbable - vidaUsadaMeses;
      avaluoForm.get('vida_util_remate')?.setValue(remanente.toFixed(2), { emitEvent: false });
    }

    // Calcular AVALUO TOTAL
    const valorRazonable = parseFloat(avaluoForm.get('valor_razonable')?.value) || 0;
    const valorCarroceria = parseFloat(avaluoForm.get('valor_carroceria')?.value) || 0;
    const valorAccesorios = parseFloat(avaluoForm.get('valor_accesorios')?.value) || 0;
    const valorReparaciones = parseFloat(avaluoForm.get('valor_reparaciones')?.value) || 0;
    const valorLlantas = parseFloat(avaluoForm.get('valor_llantas')?.value) || 0;
    const valorPintura = parseFloat(avaluoForm.get('valor_pintura')?.value) || 0;
    const valorOverhaul = parseFloat(avaluoForm.get('valor_overhaul_motor')?.value) || 0;
    const factorDemerito = parseFloat(avaluoForm.get('factor_demerito')?.value) || 0;

    const avaluoTotal = (valorRazonable + valorCarroceria + valorAccesorios) -
      (valorReparaciones + valorLlantas + valorPintura + valorOverhaul + factorDemerito);

    avaluoForm.get('avaluo_total')?.setValue(avaluoTotal.toFixed(2), { emitEvent: false });

    // Calcular INDICE DE RESPONSABILIDAD MINIMO
    const divisor = (valorRazonable + valorCarroceria + valorAccesorios);
    if (divisor > 0) {
      const indice = (valorReparaciones + valorLlantas + valorPintura + valorOverhaul + factorDemerito) / divisor;
      avaluoForm.get('indice_responsabilidad_minimo')?.setValue(indice.toFixed(5), { emitEvent: false });
    } else {
      avaluoForm.get('indice_responsabilidad_minimo')?.setValue(0, { emitEvent: false });
    }

    // Calcular valor estimado con regresión exponencial (solo para tipo comercial)
    const tipoavaluo = avaluoForm.get('tipo')?.value;
    if (tipoavaluo == 'comercial') {
      const corregidosArray = this.corregidos.getRawValue() as {modelo: number, valor: number}[];
      if (corregidosArray.length > 0) {
        const puntos = corregidosArray.map(c => ({ x: +c.modelo, y: +c.valor }));
        const modeloConsultar = parseInt(this.form.get('modelo')?.value, 10);

        if (!isNaN(modeloConsultar)) {
          const estimado = this.calcularExponencial(puntos, modeloConsultar);
          if (estimado) {
            avaluoForm.get('valor_razonable')?.setValue(estimado, { emitEvent: false });
            avaluoForm.get('valor_resonable')?.setValue(estimado, { emitEvent: false });
          }
        }
      }
    }

    // Calcular valor total chatarra
    const valorChatarraKg = parseFloat(avaluoForm.get('valor_chatarra_kg')?.value) || 0;
    const pesoChatarraKg = parseFloat(avaluoForm.get('peso_chatarra_kg')?.value) || 0;
    const valorTotalChatarra = valorChatarraKg * pesoChatarraKg;
    avaluoForm.get('valor_total_chatarra')?.setValue(valorTotalChatarra.toFixed(0), { emitEvent: false });

    this.calcularIndiceReparabilidad();
  }

  guardar() {
    const payload = this.form.getRawValue();

    // Asegurarse de que el formato sea 'Sec Bogota'
    if (!payload.avaluo.formato) {
      payload.avaluo.formato = 'Sec Bogota';
    }

    if (this.isEdit && payload.avaluo?.id) {
      this.service.update(payload.avaluo.id, payload).subscribe({
        next: () => {
          this.alert.success('Avalúo actualizado correctamente');
          this.router.navigate(['/admin/avaluo-sec-bgta']);
        },
        error: () => this.alert.error('Error al actualizar avalúo')
      });
    } else {
      this.service.create(payload).subscribe({
        next: () => {
          this.alert.success('Avalúo creado correctamente');
          this.router.navigate(['/admin/avaluo-sec-bgta']);
        },
        error: () => this.alert.error('Error al crear avalúo'),
      });
    }
  }

  // =====================================================
  // MÉTODOS PARA LIMITACIONES
  // =====================================================

  get limitaciones(): FormArray {
    return this.form.get('avaluo.limitaciones') as FormArray;
  }

  addLimitacion() {
    this.limitaciones.push(this.fb.group({ texto: [''] }));
  }

  removeLimitacion(i: number) {
    this.limitaciones.removeAt(i);
  }

  // =====================================================
  // Getters de FormArray (clasificados y corregidos)
  // =====================================================

  get clasificados(): FormArray {
    return this.form.get('avaluo.clasificados') as FormArray;
  }

  get corregidos(): FormArray {
    return this.form.get('avaluo.corregidos') as FormArray;
  }

  // =====================================================
  // Métodos de arrays dinámicos
  // =====================================================

  addClasificado() {
    this.clasificados.push(this.fb.group({ modelo: [''], valor: [''] }));
  }

  removeClasificado(i: number) {
    this.clasificados.removeAt(i);
  }

  addCorregido() {
    this.corregidos.push(this.fb.group({ modelo: [''], valor: [''] }));
  }

  removeCorregido(i: number) {
    this.corregidos.removeAt(i);
  }

  // =====================================================
  // Helpers
  // =====================================================

  private dias360(fechaInicio: Date, fechaFin: Date): number {
    const d1 = new Date(fechaInicio);
    const d2 = new Date(fechaFin);

    let d1Day = d1.getDate();
    let d2Day = d2.getDate();

    if (d1Day === 31) d1Day = 30;
    if (d2Day === 31 && d1Day === 30) d2Day = 30;

    return (d2.getFullYear() - d1.getFullYear()) * 360 +
           (d2.getMonth() - d1.getMonth()) * 30 +
           (d2Day - d1Day);
  }

  private esBisiesto(year: number): boolean {
    return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
  }

  private calcularExponencial(corregidos: {x: number, y: number}[], modeloConsultar: number) {
    const n = corregidos.length;
    if (n === 0) return null;

    let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;

    corregidos.forEach(p => {
      const x = p.x;
      const y = Math.log(p.y); // regresión exponencial → ln(y)
      sumX += x;
      sumY += y;
      sumXY += x * y;
      sumXX += x * x;
    });

    const b = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const a = (sumY - b * sumX) / n;

    const A = Math.exp(a);
    const B = b;

    const valorEstimado = A * Math.exp(B * modeloConsultar);

    return Math.round(valorEstimado); // lo devolvemos redondeado
  }

  cancelar() {
    this.router.navigate(['/admin/avaluo-sec-bgta']);
  }

  get historicoPropietarios(): FormArray {
    return this.form.get('historicoPropietarios') as FormArray;
  }

  // En la sección de métodos de la clase, agrega este método
syncPesoMermadoToChatarra() {
  const pesoMermado = this.form.get('peso_mermado')?.value;
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  
  if (pesoMermado !== null && pesoMermado !== undefined && pesoMermado !== '') {
    // Actualizar el campo peso_chatarra_kg
    avaluoForm.get('peso_chatarra_kg')?.setValue(pesoMermado, { emitEvent: false });
    
    // Recalcular valor total chatarra
    this.calcularValorChatarra();
  }
}

// Método separado para calcular valor total chatarra
calcularValorChatarra() {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  const valorChatarraKg = parseFloat(avaluoForm.get('valor_chatarra_kg')?.value) || 0;
  const pesoChatarraKg = parseFloat(avaluoForm.get('peso_chatarra_kg')?.value) || 0;
  const valorTotalChatarra = valorChatarraKg * pesoChatarraKg;
  
  avaluoForm.get('valor_total_chatarra')?.setValue(valorTotalChatarra.toFixed(0), { emitEvent: false });
}

cargarValoresFasecolda() {
  const codigoFasecolda = this.form.get('avaluo.codigo_fasecolda')?.value;
  
  if (codigoFasecolda) {
    this.fasecoldaService.getValores(codigoFasecolda).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.cargarDatosEnFormulario(response.data);
        }
      },
      error: (error) => {
        console.error('Error al cargar valores Fasecolda:', error);
      }
    });
  }
}

private cargarDatosEnFormulario(data: any) {
  // Limpiar arrays existentes
  const clasificadosArray = this.clasificados;
  const corregidosArray = this.corregidos;
  
  clasificadosArray.clear();
  corregidosArray.clear();
  
  // Cargar clasificados
  if (data.clasificado) {
    data.clasificado.forEach((item: any) => {
      clasificadosArray.push(this.fb.group({
        modelo: [item.modelo],
        valor: [item.valor]
      }));
    });
  }
  
  // Cargar corregidos
  if (data.corregido) {
    data.corregido.forEach((item: any) => {
      corregidosArray.push(this.fb.group({
        modelo: [item.modelo],
        valor: [item.valor]
      }));
    });
  }
  
  // Buscar valor para el modelo actual del vehículo
  this.buscarValorPorModelo();
}

buscarValorPorModelo() {
  const modelo = this.form.get('modelo')?.value;
  const codigoFasecolda = this.form.get('avaluo.codigo_fasecolda')?.value;
  
  if (modelo && codigoFasecolda) {
    this.fasecoldaService.buscarPorModelo(codigoFasecolda, modelo).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.sugerirValorReposicion(response.data);
        }
      }
    });
  }
}

private sugerirValorReposicion(data: any) {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  
  // Priorizar valor corregido, si no existe usar clasificado
  const valorSugerido = data.corregido?.valor || data.clasificado?.valor;
  
  if (valorSugerido) {
    // Preguntar al usuario si quiere usar este valor
    if (confirm(`¿Desea usar el valor Fasecolda de $${valorSugerido} para valor de reposición?`)) {
      avaluoForm.get('valor_reposicion')?.setValue(valorSugerido);
    }
  }
}

private calcularIndiceReparabilidad(): void {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  
  // Obtener todos los valores necesarios
  const latoneria_valor = parseFloat(avaluoForm.get('latoneria_valor')?.value) || 0;
  const valor_pintura = parseFloat(avaluoForm.get('valor_pintura')?.value) || 0;
  const motor_valor = parseFloat(avaluoForm.get('motor_valor')?.value) || 0;
  const chasis_valor = parseFloat(avaluoForm.get('chasis_valor')?.value) || 0;
  const tapiceria_valor = parseFloat(avaluoForm.get('tapiceria_valor')?.value) || 0;
  const refrigeracion_valor = parseFloat(avaluoForm.get('refrigeracion_valor')?.value) || 0;
  const electrico_valor = parseFloat(avaluoForm.get('electrico_valor')?.value) || 0;
  const valor_llantas = parseFloat(avaluoForm.get('valor_llantas')?.value) || 0;
  const transmision_valor = parseFloat(avaluoForm.get('transmision_valor')?.value) || 0;
  const vidrios_valor = parseFloat(avaluoForm.get('vidrios_valor')?.value) || 0;
  const tanque_valor = parseFloat(avaluoForm.get('tanque_valor')?.value) || 0;
  const bateria_valor = parseFloat(avaluoForm.get('bateria_valor')?.value) || 0;
  const frenos_valor = parseFloat(avaluoForm.get('frenos_valor')?.value) || 0;
  const llave_valor = parseFloat(avaluoForm.get('llave_valor')?.value) || 0;
  
   
  
  const valor_faltantes = parseFloat(avaluoForm.get('valor_faltantes')?.value) || 0;
  const valor_RTM = parseFloat(avaluoForm.get('valor_RTM')?.value) || 0;
  const valor_SOAT = parseFloat(avaluoForm.get('valor_SOAT')?.value) || 0;
  
  const valor_razonable = parseFloat(avaluoForm.get('valor_razonable')?.value) || 0;
  const factor_demerito = parseFloat(avaluoForm.get('factor_demerito')?.value) || 1;
  
  // Calcular total de componentes (suma de todos los valores de repuestos)
  const total_componentes = 
    latoneria_valor +
    valor_pintura +
    motor_valor +
    chasis_valor +
    tapiceria_valor +
    refrigeracion_valor +
    electrico_valor +
    valor_llantas +
    transmision_valor +
    vidrios_valor +
    tanque_valor +
    bateria_valor +
    frenos_valor +
    llave_valor;
  
  // Calcular valor comercial (considerando factor de demérito)
  const valor_comercial = valor_razonable * factor_demerito;
  
  // Calcular gastos totales
  const gastos = 
    valor_faltantes +
    valor_RTM +
    valor_SOAT +
    total_componentes;
  
  // Calcular índice de reparabilidad
  let indice_reparabilidad = 0;
  if (gastos > 0 && valor_comercial > 0) {
    indice_reparabilidad = gastos / valor_comercial;
    // Redondear a 4 decimales
    indice_reparabilidad = Math.round(indice_reparabilidad * 10000) / 10000;
  }
  indice_reparabilidad=indice_reparabilidad*100;
  
  // Actualizar el campo en el formulario
  avaluoForm.get('indice_reparabilidad')?.setValue(indice_reparabilidad.toFixed(4), { emitEvent: false });
}


}