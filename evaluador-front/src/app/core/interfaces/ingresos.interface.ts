
export interface DatosGenerales {
  tiposervicio: string;
  solicitante: string;
  documentoSolicitante: string;
  direccion_solicitante: string;
  telefono_solicitante: string;
  placa: string;
  ubicacionActivo: string;
  fechaSolicitud: string;       // formato YYYY-MM-DD
  fechaInspeccion: string;      // formato YYYY-MM-DD
  fechaInforme: string;         // formato YYYY-MM-DD
  objetoAvaluo: string;
  codigoInternoMovil: string;
  estado:string;
}

export interface InformacionBien {
  tipoPropiedad: string;
  fechaMatricula: string;       // formato YYYY-MM-DD
  movil: string;
  marca: string;
  linea: string;
  clase: string;
  tipoCarroceria: string;
  cantidad_ejes:number;
  categoria: string;
  color: string;
  cilindraje: number;
  modelo: number;
  kilometraje: number;
  cajaCambios: string;
  tipoTraccion: string;
  numeroPasajeros: number;
  capacidadCarga: number;
  llantaDelanteraIzquierda: string;
  llantaDelanteraDerecha: string;
  llantaTraseraIzquierda: string;
  llantaTraseraDerecha: string;
  llantaRepuesto: string;
  numeroChasis: string;
  numeroSerie: string;
  numeroMotor: string;
  numeroVin:string
  peso_bruto: string,
  nacionalidad: string;
  propietario: string;
  documento_propietario:string;
  empresaAfiliacion: string;
  ciudad_registro: string;
  no_licencia: string;
  fecha_expedicion_licencia: string;
  organismo_transito: string;
  soat: string;
  fecha_expedicion_soat: string;
  fecha_inicio_vigencia_soat: string;
  fecha_vencimiento_soat: string;
  entidad_expide_soat: string;
  estado_soat: string;
  rtm: string;
  fecha_vencimiento_rtm: string;
  centro_revision_rtm: string;
  estado_rtm: string;
  peso_mermado:string;
  estado_registro_runt:string;
  capacidad_ton:string;
}

export interface EstadoVehiculoRunt {
  fecha_inicial_matricula: string;
  estado_matricula: string;
  traslados_matricula: string;
  tipo_servicio_vehiculo: string;
  cambios_tipo_servicio: string;
  fecha_ult_cambio_servicio: string;
  cambio_color_historica: string;
  fecha_ult_cambio_color: string;
  color_cambiado: string;
  cambios_blindaje: string;
  fecha_cambio_blindaje: string;
  repotenciado: string;
}

export interface NovedadesVehiculo {
  tiene_gravamedes: string;
  tiene_prenda: string;
  regrabado_no_motor: string;
  regrabado_no_chasis: string;
  regrabado_no_serie: string;
  regrabado_no_vin: string;
  limitacion_propiedad: string;
  numero_doc_proceso: string;
  entidad_juridica: string;
  tipo_doc_demandante: string;
  no_identificacion_demandante: string;
  fecha_expedicion_novedad: string;
  fecha_radicacion: string;
}

export interface HistoricoPropietario {
  nombre_empresa: string;
  tipo_propietario: string;
  tipo_identificacion: string;
  numero_identificacion: string;
  fecha_inicio: string;
  estado: string;
}

export interface Ingreso {
  id: number;
  datosGenerales: DatosGenerales;
  informacionBien: InformacionBien;
  estadoVehiculoRunt: EstadoVehiculoRunt;
  novedadesVehiculo: NovedadesVehiculo;
  historicoPropietarios: HistoricoPropietario[];
  inspeccion:Inspeccion;
  avaluo:Avaluo;


}

export interface Avaluo {
  id?: number | null;
  tipo: string | null;
  ingreso_id: number | null;
  fecha_inspeccion: Date | null;
  vida_util_probable: number | null;
  vida_usada_dias: number | null;
  vida_usada_meses: number | null;
  vida_usada_anos: number | null;
  vida_util_remate: number | null;
  vida_util_anos: number | null;
  antiguedad: number | null;
  vida_util: number | null;
  valor_reposicion: number | null;
  valor_residual: number | null;
  estado_conservacion: string | null;
  x: number | null;
  k: number | null;
  valor_resonable: number | null;
  capacidad_transportadora: number | null;
  valor_razonable: number | null;
  valor_carroceria: number | null;
  valor_reparaciones: number | null;
  valor_llantas: number | null;
  valor_pintura: number | null;
  valor_overhaul_motor: number | null;
  factor_demerito: number | null;
  valor_accesorios: number | null;
  indice_responsabilidad_minimo: number | null;
  avaluo_total: number | null;
  no_factura: string | null;
  declaracion_importacion: string | null;
  fecha_importacion: string | null;
  registro_maquinaria: string | null;
  gps: string | null;
  file:string|null; 
  evaluador :string|null; 
  ubicacion :string|null; 
  cerrado?: boolean | null;
}




export interface Inspeccion {
  id?: number | null;
  ingreso_id: number | null;
  aseguradora: string | null;
  intermediaria: string | null;
  combustible: string | null;
  tipo_pintura: string | null;
  servicio: string | null;
  kilometraje: number | null;
  color: string | null;
  centro_inspeccion: string | null;
  valor_mercado: number | null;
  valor_evaluador: number | null;
  valor_accesorios: number | null;
  resultado: string | null;
  intermediario: string | null;
  turno: string | null;
  cod_fasecolda: string | null;
  valor_fasecolda: number | null;
  novedades_inspeccion: string | null;
  ciudad:string | null;
  observaciones: string | null;
  expide_para: string | null;
  file:string|null;  
}
