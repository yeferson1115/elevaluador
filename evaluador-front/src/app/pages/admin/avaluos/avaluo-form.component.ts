import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, FormArray } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';

// Servicios
import { AvaluoService } from '../../../core/services/avaluo.service';
import { AlertService } from '../../../core/services/alert.service';
import { FasecoldaService } from '../../../core/services/fasecolda.service';

@Component({
  selector: 'app-avaluo-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './avaluo-form.component.html',
  styleUrls: ['./avaluo-form.component.css'],
})
export class AvaluoFormComponent implements OnInit {
  form!: FormGroup;
  id!: number | null;
  isEdit = false;
  avaluoId?: number;
  valores: string[] = [];
  porcentajes: number[] = [];

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private service: AvaluoService,
    private alert: AlertService,
    private router: Router,
    private fasecoldaService: FasecoldaService
  ) {}

  // =====================================================
  // Ciclo de vida
  // =====================================================
  ngOnInit(): void {
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

    // Activar cálculo automático
    this.form.valueChanges.subscribe(() => {
      console.log('cambios');
      this.calcularFormulas();
    });
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

  this.form.get('avaluo.codigo_fasecolda')?.valueChanges.subscribe((codigo) => {
    if (codigo && String(codigo).trim().length > 0) {
      this.cargarValoresFasecolda();
      this.consultarValorFasecoldaPorModelo();
    }
  });

  this.form.get('modelo')?.valueChanges.subscribe(() => {
    this.consultarValorFasecoldaPorModelo();
  });
    // También forzamos un cálculo inicial
    this.calcularFormulas();
  }

  // =====================================================
  // Inicialización del formulario
  // =====================================================
  private buildForm(): void {
    this.form = this.fb.group({
      // Datos generales (solo lectura)
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

      // Subgrupo editable (avaluo)
      avaluo: this.fb.group({
        id: [null],
        tipo: [''],
        formato: [''],
        observaciones:[''],
        ingreso_id: [{ value: '', disabled: true }],
        fecha_inspeccion: [''],
        codigo_fasecolda: [''],
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
        limitaciones: this.fb.array([]),
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
          indice_reparabilidad: [{ value: 0, disabled: true }],
      }),
    });
  }

  // =====================================================
  // Cargar datos
  // =====================================================
  private loadData(id: number): void {
    this.service.getByIdHttp(id).subscribe({
      next: (resp) => {
        if (!resp) {
          this.alert.error('No se encontró el ingreso');
          this.router.navigate(['/admin/avaluos']);
          return;
        }

        this.form.patchValue(resp);

        const avaluoForm = this.form.get('avaluo');
        if (resp.avaluo) {
          avaluoForm?.patchValue(resp.avaluo);

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

          // Limitaciones
          if (resp.avaluo.limitaciones?.length) {
            const limitacionesArray = avaluoForm?.get('limitaciones') as FormArray;
            limitacionesArray.clear();
            resp.avaluo.limitaciones.forEach((item: any) => {
              limitacionesArray.push(this.fb.group({
                texto: [item.texto]
              }));
            });
          }

          // Ingreso_id
          if (!resp.avaluo.ingreso_id) {
            avaluoForm?.get('ingreso_id')?.setValue(resp.id);
          }

          this.isEdit = !!resp.avaluo.id;
        } else {
          avaluoForm?.reset();
          avaluoForm?.get('ingreso_id')?.setValue(resp.id);
          this.isEdit = false;
        }
      },
      error: () => {
        this.alert.error('No se pudo cargar el ingreso');
        this.router.navigate(['/admin/avaluos']);
      },
    });
  }

  // =====================================================
  // Cálculos automáticos
  // =====================================================
 private calcularFormulas(): void {
  const avaluoForm = this.form.get('avaluo') as FormGroup;
  const values = avaluoForm.getRawValue();

  const antiguedad = parseFloat(values.antiguedad) || 0;  
  const vidaUtil = parseFloat(values.vida_util) || 0;     
  const x = parseFloat(values.x) || 0;                    
  const estadoConservacion = parseFloat(values.estado_conservacion) || 0; 
  const valorReposicion = parseFloat(values.valor_reposicion) || 0;       
  const valorResidual = parseFloat(values.valor_residual) || 0;           
  const fechaMatricula = this.form.get('fecha_expedicion_licencia')?.value;
  const fechaInspeccion = values.fecha_inspeccion;

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

  // Valor residual
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
    const resonable = valorReposicion-((valorReposicion - valorResidual) * kValue);    
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

  // Tomar todos los valores necesarios
const valorRazonable = parseFloat(avaluoForm.get('valor_razonable')?.value) || 0;
const valorCarroceria = parseFloat(avaluoForm.get('valor_carroceria')?.value) || 0;
const valorAccesorios = parseFloat(avaluoForm.get('valor_accesorios')?.value) || 0;

const valorReparaciones = parseFloat(avaluoForm.get('valor_reparaciones')?.value) || 0;
const valorLlantas = parseFloat(avaluoForm.get('valor_llantas')?.value) || 0;
const valorPintura = parseFloat(avaluoForm.get('valor_pintura')?.value) || 0;
const valorOverhaul = parseFloat(avaluoForm.get('valor_overhaul_motor')?.value) || 0;
const factorDemerito = parseFloat(avaluoForm.get('factor_demerito')?.value) || 0;

// ---- Calcular AVALUO TOTAL ----
const avaluoTotal = (valorRazonable + valorCarroceria + valorAccesorios) -
  (valorReparaciones + valorLlantas + valorPintura + valorOverhaul + factorDemerito);

avaluoForm.get('avaluo_total')?.setValue(avaluoTotal.toFixed(2), { emitEvent: false });

// ---- Calcular INDICE DE RESPONSABILIDAD MINIMO ----
const divisor = (valorRazonable + valorCarroceria + valorAccesorios);

if (divisor > 0) {
  const indice = (valorReparaciones + valorLlantas + valorPintura + valorOverhaul + factorDemerito) / divisor;
  avaluoForm.get('indice_responsabilidad_minimo')?.setValue(indice.toFixed(5), { emitEvent: false });
} else {
  avaluoForm.get('indice_responsabilidad_minimo')?.setValue(0, { emitEvent: false });
}


// =====================================================
// Calcular valor estimado con regresión exponencial
// =====================================================
const tipoavaluo = avaluoForm.get('tipo')?.value;
if(tipoavaluo=='comercial'){
const corregidosArray = this.corregidos.getRawValue() as {modelo: number, valor: number}[];
if (corregidosArray.length > 0) {
  const puntos = corregidosArray.map(c => ({ x: +c.modelo, y: +c.valor }));
  const modeloConsultar = parseInt(this.form.get('modelo')?.value, 10);

  if (!isNaN(modeloConsultar)) {
    const estimado = this.calcularExponencial(puntos, modeloConsultar);
    if (estimado) {
      const avaluoForm = this.form.get('avaluo') as FormGroup;

      // Guardar el valor redondeado en los campos correctos
      avaluoForm.get('valor_razonable')?.setValue(estimado, { emitEvent: false });
      avaluoForm.get('valor_resonable')?.setValue(estimado, { emitEvent: false });

      console.log('Valor razonable estimado (corregidos):', estimado);
    }
  }
}
}


// =====================================================
// Calcular valor total chatarra
// =====================================================
const valorChatarraKg = parseFloat(avaluoForm.get('valor_chatarra_kg')?.value) || 0;
const pesoChatarraKg = parseFloat(avaluoForm.get('peso_chatarra_kg')?.value) || 0;

const valorTotalChatarra = valorChatarraKg * pesoChatarraKg;
avaluoForm.get('valor_total_chatarra')?.setValue(valorTotalChatarra.toFixed(0), { emitEvent: false });




this.calcularIndiceReparabilidad();
}

  // =====================================================
  // Guardar
  // =====================================================
  guardar() {
    const payload = this.form.getRawValue();

    if (this.isEdit && payload.avaluo?.id) {
      this.service.update(payload.avaluo.id, payload).subscribe({
        next: () => {
          this.alert.success('Avalúo actualizado correctamente');
          this.router.navigate(['/admin/avaluos']);
        },
        error: () => this.alert.error('Error al actualizar avalúo')
      });
    } else {
      this.service.create(payload).subscribe({
        next: () => this.alert.success('Avalúo creado correctamente'),
        error: () => this.alert.error('Error al crear avalúo'),
      });
    }
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

  // =====================================================
  // Getters de FormArray
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
// Calcular regresión exponencial
// =====================================================
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


get limitaciones(): FormArray {
  return this.form.get('avaluo.limitaciones') as FormArray;
}

addLimitacion() {
  this.limitaciones.push(this.fb.group({ texto: [''] }));
}

removeLimitacion(i: number) {
  this.limitaciones.removeAt(i);
}


cargarValoresFasecolda(): void {
  const codigoFasecolda = this.form.get('avaluo.codigo_fasecolda')?.value;

  if (!codigoFasecolda) {
    return;
  }

  this.fasecoldaService.getValores(codigoFasecolda).subscribe({
    next: (response) => {
      if (response.success && response.data) {
        this.aplicarValoresFasecolda(response.data);
      }
    },
    error: () => {
      this.alert.error('No fue posible cargar valores de Fasecolda');
    }
  });
}

private aplicarValoresFasecolda(data: any): void {
  const clasificadosArray = this.clasificados;
  const corregidosArray = this.corregidos;

  clasificadosArray.clear();
  corregidosArray.clear();

  if (data.clasificado && Array.isArray(data.clasificado)) {
    data.clasificado.forEach((item: any) => {
      clasificadosArray.push(this.fb.group({
        modelo: [item.modelo || ''],
        valor: [item.valor || '']
      }));
    });
  }

  if (data.corregido && Array.isArray(data.corregido)) {
    data.corregido.forEach((item: any) => {
      corregidosArray.push(this.fb.group({
        modelo: [item.modelo || ''],
        valor: [item.valor || '']
      }));
    });
  }
}

consultarValorFasecoldaPorModelo(): void {
  const codigoFasecolda = this.form.get('avaluo.codigo_fasecolda')?.value;
  const modelo = this.form.get('modelo')?.value;

  if (!codigoFasecolda || !modelo) {
    return;
  }

  this.fasecoldaService.buscarPorModelo(codigoFasecolda, Number(modelo)).subscribe({
    next: (response) => {
      if (response.success && response.data) {
        const valorSugerido = response.data.corregido?.valor || response.data.clasificado?.valor;
        const controlValorReposicion = this.form.get('avaluo.valor_reposicion');

        if (valorSugerido && controlValorReposicion && !controlValorReposicion.value) {
          controlValorReposicion.setValue(valorSugerido);
        }
      }
    }
  });
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
  
  // Actualizar el campo en el formulario
  avaluoForm.get('indice_reparabilidad')?.setValue(indice_reparabilidad.toFixed(4), { emitEvent: false });
}

}
