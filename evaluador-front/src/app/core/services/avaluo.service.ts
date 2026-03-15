import { Injectable } from '@angular/core';
import { Ingreso } from '../interfaces/ingresos.interface';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { Ingresos } from '../models/ingreso.model';


@Injectable({ providedIn: 'root' })
export class AvaluoService {
  private baseUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  /**
   * Obtener todos los avalúos sin paginación ni filtro
   */
  getAll(): Observable<Ingreso[]> {
    return this.http.get<Ingreso[]>(`${this.baseUrl}/avaluo`);
  }

  /**
   * Obtener avalúos paginados con búsqueda
   */
getAvaluos(page = 1, filtro = '', tipo: string = ''): Observable<any> {
  return this.http
    .get<any>(`${this.baseUrl}/ingreso`, {
      params: {
        page: page.toString(),
        search: filtro,
        ...(tipo ? { tipo } : {}) // 👈 solo lo agrega si viene
      }
    })
    .pipe(
      map(response => {
        return {
          ...response,
          data: response.data.map((item: any) => this.mapToAvaluo(item))
        };
      })
    );
}


  

  /**
   * Obtener un avalúo por ID
   */
getByIdHttp(id: number): Observable<Ingresos> {
  return this.http.get<Ingresos>(`${this.baseUrl}/avaluo/${id}`);
}

  /**
   * Crear un nuevo avalúo
   */
create(avaluo: Omit<Ingresos, 'id'>): Observable<Ingresos> {
  return this.http.post<Ingresos>(`${this.baseUrl}/avaluo`, avaluo);
}

  /**
   * Actualizar un avalúo existente
   */
update(id: number, avaluo: Partial<Ingresos>): Observable<Ingresos> {
  return this.http.put<Ingresos>(`${this.baseUrl}/avaluo/${id}`, avaluo);
}

  /**
   * Eliminar un avalúo por ID
   */
  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/ingreso/${id}`);
  }

  /**
   * Mapea la respuesta plana del backend a la estructura anidada del frontend
   */
  private mapToAvaluo(item: any): Ingreso {
    return {
      id: item.id,
      datosGenerales: {
        tiposervicio: item.tiposervicio,
        solicitante: item.solicitante,
        documentoSolicitante: item.documento_solicitante,
        direccion_solicitante: item.direccion_solicitante,
        telefono_solicitante: item.telefono_solicitante,
        placa: item.placa,
        ubicacionActivo: item.ubicacion_activo,
        fechaSolicitud: item.fecha_solicitud,
        fechaInspeccion: item.fecha_inspeccion,
        fechaInforme: item.fecha_informe,
        objetoAvaluo: item.objeto_avaluo,
        codigoInternoMovil: item.codigo_interno_movil,
        estado:item.estado,
      },
      informacionBien: {
        tipoPropiedad: item.tipo_propiedad,
        fechaMatricula: item.fecha_matricula,
        movil: item.movil,
        marca: item.marca,
        linea: item.linea,
        clase: item.clase,
        tipoCarroceria: item.tipo_carroceria,
        cantidad_ejes: item.cantidad_ejes,
        categoria: item.categoria,
        color: item.color,
        cilindraje: item.cilindraje,
        modelo: item.modelo,
        kilometraje: item.kilometraje,
        cajaCambios: item.caja_cambios,
        tipoTraccion: item.tipo_traccion,
        numeroPasajeros: item.numero_pasajeros,
        capacidadCarga: item.capacidad_carga,
        llantaDelanteraIzquierda: item.llanta_delantera_izquierda,
        llantaDelanteraDerecha: item.llanta_delantera_derecha,
        llantaTraseraIzquierda: item.llanta_trasera_izquierda,
        llantaTraseraDerecha: item.llanta_trasera_derecha,
        llantaRepuesto: item.llanta_repuesto,
        numeroChasis: item.numero_chasis,
        numeroSerie: item.numero_serie,
        numeroMotor: item.numero_motor,
        numeroVin:item.numeroVin,
        peso_bruto: item.peso_bruto,
        nacionalidad: item.nacionalidad,
        propietario: item.propietario,
        documento_propietario:item.documento_propietario,
        empresaAfiliacion: item.empresa_afiliacion,
        ciudad_registro: item.ciudad_registro,
        no_licencia: item.no_licencia,
        fecha_expedicion_licencia: item.fecha_expedicion_licencia,
        organismo_transito: item.organismo_transito,
        soat: item.soat,
        fecha_expedicion_soat:item.fecha_expedicion_soat,
        fecha_inicio_vigencia_soat:item.fecha_inicio_vigencia_soat,
        fecha_vencimiento_soat:item.fecha_vencimiento_soat,
        entidad_expide_soat:item.entidad_expide_soat,
        estado_soat:item.estado_soat,
        fecha_vencimiento_rtm:item.fecha_vencimiento_rtm,
        centro_revision_rtm:item.centro_revision_rtm,
        estado_rtm:item.estado_rtm,
        rtm: item.rtm,
        peso_mermado:item.peso_mermado,
        estado_registro_runt:item.estado_registro_runt,
        capacidad_ton:item.capacidad_ton
      },
      estadoVehiculoRunt: {
        fecha_inicial_matricula: item.fecha_inicial_matricula,
        estado_matricula: item.estado_matricula,
        traslados_matricula: item.traslados_matricula,
        tipo_servicio_vehiculo: item.tipo_servicio_vehiculo,
        cambios_tipo_servicio: item.cambios_tipo_servicio,
        fecha_ult_cambio_servicio: item.fecha_ult_cambio_servicio,
        cambio_color_historica: item.cambio_color_historica,
        fecha_ult_cambio_color: item.fecha_ult_cambio_color,
        color_cambiado: item.color_cambiado,
        cambios_blindaje: item.cambios_blindaje,
        fecha_cambio_blindaje: item.fecha_cambio_blindaje,
        repotenciado: item.repotenciado,
      },
       novedadesVehiculo: {
        tiene_gravamedes: item.tiene_gravamedes,
        tiene_prenda: item.tiene_prenda,
        regrabado_no_motor: item.regrabado_no_motor,
        regrabado_no_chasis: item.regrabado_no_chasis,
        regrabado_no_serie: item.regrabado_no_serie,
        regrabado_no_vin: item.regrabado_no_vin,
        limitacion_propiedad: item.limitacion_propiedad,
        numero_doc_proceso: item.numero_doc_proceso,
        entidad_juridica: item.entidad_juridica,
        tipo_doc_demandante: item.tipo_doc_demandante,
        no_identificacion_demandante: item.no_identificacion_demandante,
        fecha_expedicion_novedad: item.fecha_expedicion_novedad,
        fecha_radicacion: item.fecha_radicacion,
      },
      historicoPropietarios: (item.historico_propietarios ?? []).map((prop: any) => ({
        nombre_empresa: prop.nombre_empresa,
        tipo_propietario: prop.tipo_propietario,
        tipo_identificacion: prop.tipo_identificacion,
        numero_identificacion: prop.numero_identificacion,
        fecha_inicio: prop.fecha_inicio,
        estado: prop.estado,
      })),      
      inspeccion: item.inspeccion || [],
      avaluo: item.avaluo || [],
      

    };
  }
 getImagenes(avaluoId: number) {
  return this.http.get<any[]>(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes`);
}

uploadImagen(avaluoId: number, data: FormData) {
  return this.http.post<{imagenes: string[]}>(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes`, data);
}

deleteImagen(avaluoId: number, categoria: string, url: string) {
  return this.http.post(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes/delete`, { categoria, url });
}



}
