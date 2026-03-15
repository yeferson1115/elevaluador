export interface InspeccionAccesorios {
  id?: number | null;
  inspeccion_id: number | null;
  decripcion: string | null;
  marca_ref: string | null;
  cantidad: number | null;
  valor: number | null;
}

export interface InspeccionExterior {
  id?: number | null;
  inspeccion_id: number | null;
  vidrios: string | null;
  tapiceria_accesorios: string | null;
  fugas_fluidos: string | null;
  ajuste_cierre_capo: string | null;
  ajuste_cierre_puestas_delantera_izq: string | null;
  ajuste_cierre_puestas_delantera_der: string | null;
  ajuste_cierre_puertas_trasera_izq: string | null;
  ajuste_cierre_puertas_trasera_der: string | null;
  ajuste_cierre_tapa_baul_compuerta: string | null;
}

export interface InspeccionFuncionamiento {
  id?: number | null;
  inspeccion_id: number | null;
  asientos_delantero: string | null;
  bocina: string | null;
  calefaccion: string | null;
  desempanador: string | null;
  ecendedor: string | null;
  espejos_electricos: string | null;
  limpiabrisas_del: string | null;
  limpiabrisas_tra: string | null;
  luz_interior: string | null;
  radio: string | null;
  encendido_arranque: string | null;
  tacometro: string | null;
  techo_corredizo: string | null;
  velocimetro: string | null;
}

export interface InspeccionIndicadores {
  id?: number | null;
  inspeccion_id: number | null;
  testigo_abs: string | null;
  testigo_aceite: string | null;
  testigo_airbag: string | null;
  testigo_check_engine: string | null;
  testigo_frenos: string | null;
  testigo_combustible: string | null;
}

export interface InspeccionLuces {
  id?: number | null;
  inspeccion_id: number | null;
  direccionales: string | null;
  luces_altas: string | null;
  luces_bajas: string | null;
  luces_exploradoras: string | null;
  luces_frenos: string | null;
  lueces_medias: string | null;
  luces_parqueo: string | null;
  luces_placa: string | null;
  luces_reversa: string | null;
}

export interface InspeccionMecanica {
  id?: number | null;
  inspeccion_id: number | null;
  kilometraje: number | null;
  funcionamiento_a_a: string | null;
  nivel_aceite_direccion_hidraulica: string | null;
  nivel_aceite_motor: string | null;
  nivel_agua_limpiavidrios: string | null;
  nivel_liquido_frenos: string | null;
  nivel_liquido_embrague: string | null;
  nivel_refrigerante_motor: string | null;
  soportes_caja_velocidades: string | null;
  viscosidad_aceite_motor: string | null;
  estado_cables_instalacion_alta: string | null;
  estado_carcasa_caja_velocidades: string | null;
  estado_correas: string | null;
  estado_externo_bateria: string | null;
  estado_filtro_aire: string | null;
  estado_manqgueras_radiador: string | null;
  estado_radiador: string | null;
  estado_radiador_a_a: string | null;
  estado_soporte_motor: string | null;
  tension_correas: string | null;
}

export interface InspeccionTapiceria {
  id?: number | null;
  inspeccion_id: number | null;
  estado_timon: string | null;
  estados_tapizados_puerta: string | null;
  estados_tapizado_asientos: string | null;
  estado_tapiceria_techo: string | null;
}

export interface InspeccionParteBaja {
   id?: number | null;
  inspeccion_id: number | null;
  carter: string | null;
  cauchos_suspension: string | null;
  guardapolvos_caja_direccion: string | null;
  guardapolvos_eje: string | null;
  protectores_inferiores: string | null;
  estado_catalizador: string | null;
  estado_silenciador_escape: string | null;
  estado_tijeras: string | null;
  estado_tuberias_frenos: string | null;
  estado_tubo_exhosto: string | null;
  fuga_aceite_caja_velocidades: string | null;
  fuga_direccion_hidraulica: string | null;
  fuga_aceite_motor: string | null;
  fuga_amortiguadores: string | null;
  fuga_liquido_embrague: string | null;
  fuga_liquido_frenos: string | null;
  fuga_combustible_tanque: string | null;
}
export interface InspeccionVisual {
  id?: number | null;
  zona: string;
  estado: string | null; // 'Bueno' | 'Aceptable' | 'Malo'
}

export interface RevisionVisual {
  id?: number | null;
  pintura: string | null;
  desviacion_km: string | null;
  ruedas_traseras: string | null;
  ruedas_delanteras: string | null;
  llanta_del_izq: string | null;
  llanta_del_der: string | null;
  llanta_tras_izq: string | null;
  llanta_tras_der: string | null;
  freno_mano: string | null;
  suspension_tras: string | null;
  suspension_delantera: string | null;
}

export interface InspeccionRevisionVisualPuntoLiviano {
  id?: number | null;
  paragolpes_delantero: string | null;
  soporte_paragolpes_der: string | null;
  soporte_paragolpes_izq: string | null;
  rejilla_paragolpes: string | null;
  capo: string | null;
  bisagra_capo: string | null;
  persiana: string | null;
  unidad_farola_der: string | null;
  unidad_farola_izq: string | null;
  luz_posicion_der: string | null;
  luz_posicion_izq: string | null;
  exploradora_der: string | null;
  exploradora_izq:string | null;
  cocuyo_der: string | null;
  cocuyo_izq: string | null;
  paragolpes_trasero: string | null;
  soporte_paragolpes_tras: string | null;
  tapa_baul_compuerta: string | null;
  panel_trasero: string | null;
  piso_baul: string | null;
  stop_der: string | null;
  stop_izq: string | null;
  stop_compuerta_der: string | null;
  stop_compuerta_izq: string | null;
  tercer_stop: string | null;
  tapizado_capota: string | null;
  alfombra_piso: string | null;
  tapizado_puerta_delantera_der: string | null;
  tapizado_puerta_delantera_izq: string | null;
  tapizado_puerta_trasera_der: string | null;
  tapizado_puerta_trasera_izq: string | null;
  tapizado_paral_parabrisas_der: string | null;
  tapizado_paral_parabrisas_izq: string | null;
  tapizado_paral_central_der: string | null;
  tapizado_paral_central_izq: string | null;
  tapizado_baul_der: string | null;
  tapizado_baul_izq: string | null;
  abullonado_millare: string | null;
  consola_central: string | null;
  mecanismo_elevavidrios_principal: string | null;
  elevavidrios_puerta_delantera_der: string | null;
  elevavidrios_puerta_delantera_izq: string | null;
  elevavidrios_puerta_trasera_der: string | null;
  elevavidrios_puerta_trasera_izq:string | null;
  caja_direccion: string | null;
  brazo_direccion: string | null;
  terminal_direccion: string | null;
  motor: string | null;
  caja_de_velocidades: string | null;
  traccion_doble: string | null;
  modulo_ECM_ECU_PCM: string | null;
  bomba_inyección: string | null;
  turbo: string | null;
  alternador: string | null;
  caja_direccion_mec: string | null;
  bateria: string | null;
  sistema_exhosto: string | null;
  catalizador: string | null;
  embrague_termico: string | null;
  eje_delantero: string | null;
  instalacion_electrica_motor: string | null;
  radiador: string | null;
  condensador: string | null;
  tijera: string | null;
  portamangueta: string | null;
  amortiguador_delantero_der: string | null;
  amortiguador_delantero_izq:string | null;
  muelle_delantero_der: string | null;
  muelle_delantero_izq:string | null;
  muelle_trasero_der: string | null;
  muelle_trasero_izq:string | null;
  amortiguador_trasero_der: string | null;
  amortiguador_trasero_izq:string | null;
  puente_delantero: string | null;
  cuna_motor: string | null;
  puente_trasero: string | null;
  suspension_multilink_trasera: string | null;
  punta_chasis_delantera_der: string | null;
  punta_chasis_delantera_izq:string | null;
  punta_chasis_trasera_der: string | null;
  punta_chasis_trasera_izq: string | null;
  viga_chasis: string | null;
  traviesa_chasis: string | null;
  piso_habitaculo: string | null;
  panoramico_delantero: string | null;
  panoramico_trasero: string | null;
  vidrio_puerta_delantera_der: string | null;
  vidrio_puerta_delantera_izq:string | null;
  vidrio_puerta_trasera_der: string | null;
  vidrio_puerta_trasera_izq:string | null;
  capota: string | null;
  antena_capota: string | null;
  guardafango_der: string | null;
  guardafango_izq: string | null;
  cocuyo_guardafango_der: string | null;
  cocuyo_guardafango_izq:string | null;
  puerta_delantera_der: string | null;
  puerta_delantera_izq:string | null;
  puerta_trasera_der: string | null;
  puerta_trasera_izq:string | null;
  costado_der: string | null;
  costado_izq:string | null;
  paral_puerta_der: string | null;
  paral_puerta_izq:string | null;
  paral_parabrisas_der: string | null;
  paral_parabrisas_izq: string | null;
  estribo_der: string | null;
  estribo_izq:string | null;
  paral_central_der: string | null;
  paral_central_izq:string | null;
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
  file?:string|null;
  tipo_vehiculo?:string|null;

  inspeccion_exterior: InspeccionExterior;
  inspeccion_funcionamiento: InspeccionFuncionamiento;
  inspeccion_indicadores: InspeccionIndicadores;
  inspeccion_luces: InspeccionLuces;
  inspeccion_mecanica: InspeccionMecanica;
  inspeccion_tapiceria: InspeccionTapiceria;
  inspeccion_accesorios: InspeccionAccesorios[];
  inspeccion_parte_baja: InspeccionParteBaja;
  inspeccion_visual: InspeccionVisual[];
  inspeccion_revision_visual:RevisionVisual;
  inspeccion_revision_visual_punto_liviano:InspeccionRevisionVisualPuntoLiviano;
  
}