import { Injectable } from '@angular/core';
import { Ingreso } from '../interfaces/ingresos.interface';
import { HttpClient,HttpParams,HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { GetImagenesResponse, ImagenResponse } from '../models/ingresoimagenes.model';

@Injectable({ providedIn: 'root' })
export class IngresoService {
  private baseUrl = environment.apiUrl;
  private apiUrl = `${environment.apiUrl}/ingresos`;

  constructor(private http: HttpClient) {}

  /**
   * Obtener todos los avalúos sin paginación ni filtro
   */
  getAll(): Observable<Ingreso[]> {
    return this.http.get<Ingreso[]>(`${this.baseUrl}/ingresos`);
  }

  /**
   * Obtener avalúos paginados con búsqueda
   */
getAvaluos(page = 1, filtro = '', tipo: string = '', forceRefresh = false): Observable<any> {
  return this.http
    .get<any>(`${this.baseUrl}/ingreso`, {
      params: {
        page: page.toString(),
        search: filtro,
        ...(tipo ? { tipo } : {}),
        ...(forceRefresh ? { _ts: Date.now().toString() } : {})
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
  getByIdHttp(id: number): Observable<Ingreso> {
    return this.http.get<any>(`${this.baseUrl}/ingreso/${id}`).pipe(
      map(response => this.mapToAvaluo(response))
    );
  }

  /**
   * Crear un nuevo avalúo
   */
  create(avaluo: Omit<Ingreso, 'id'>): Observable<Ingreso> {
    return this.http.post<Ingreso>(`${this.baseUrl}/ingreso`, avaluo);
  }

  /**
   * Actualizar un avalúo existente
   */
  update(id: number, avaluo: Ingreso): Observable<Ingreso> {
    return this.http.put<Ingreso>(`${this.baseUrl}/ingreso/${id}`, avaluo);
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
        numeroVin: item.numeroVin,
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
        cantidad_ejes: item.cantidad_ejes,
        peso_mermado: item.peso_mermado,
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
getImagenes(avaluoId: number): Observable<GetImagenesResponse> {
  return this.http.get<GetImagenesResponse>(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes`);
}
uploadImagen(avaluoId: number, data: FormData) {
  return this.http.post<{imagenes: ImagenResponse[]}>(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes`, data);
}

deleteImagen(avaluoId: number, categoria: string, url: string) {
  return this.http.post(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes/delete`, { categoria, url });
}

reordenarImagenes(avaluoId: number, categoria: string, orden: number[]) {
  return this.http.post(`${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes/reorder`, { categoria, orden });
}

rotarImagen(avaluoId: number, categoria: string, url: string, grados: number) {
  return this.http.post<{success: boolean; url: string; rotacion: number}>(
    `${this.baseUrl}/ingresos-imagenes/${avaluoId}/imagenes/rotate`,
    { categoria, url, grados }
  );
}

import(file: File): Observable<any> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post(`${this.baseUrl}/ingreso/import`, formData);
  }

  importSecBog(file: File): Observable<any> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post(`${this.baseUrl}/ingreso/importmovilidad`, formData);
  }

  exportSecBog(filtro: string = ''): Observable<any> {
    const params = new HttpParams().set('filtro', filtro);
    return this.http.get(`${this.apiUrl}/export-sec-bog`, {
      params,
      responseType: 'blob'
    });
  }

  exportCertificadosZipBackground(filtro: string = '', ids: number[] = []): Observable<any> {
    return this.http.post(`${this.apiUrl}/export-certificados-zip-background`, {
      filtro,
      ids
    });
  }

  exportCertificadosZip(filtro: string = '', ids: number[] = []): Observable<any> {
    let params = new HttpParams().set('filtro', filtro);

    ids.forEach((id) => {
      params = params.append('ids[]', id.toString());
    });

    return this.http.get(`${this.apiUrl}/export-certificados-zip`, {
      params,
      responseType: 'blob'
    });
  }

  bulkUpdateCompact(payload: {
    ids: number[];
    filtro: string;
    all_filtered: boolean;
    changes: Record<string, any>;
    generar_zip?: boolean;
    tipo_servicio?: string;
  }): Observable<any> {
    const generarZip = payload.generar_zip ?? true;
    if (generarZip) {
      return this.http.post(`${this.baseUrl}/avaluos/bulk-update-compact`, payload, {
        responseType: 'blob'
      });
    }

    return this.http.post(`${this.baseUrl}/avaluos/bulk-update-compact`, payload);
  }

  bulkImportCompact(file: File, metodo: 'comercial' | 'jans'): Observable<Blob> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('metodo', metodo);

    return this.http.post(`${this.baseUrl}/avaluos/bulk-import-compact`, formData, {
      responseType: 'blob'
    });
  }

// En tu IngresoService (Angular)
// En tu servicio
generarPdf(id: number): Observable<HttpResponse<Blob>> {
  return this.http.get(`${this.baseUrl}/avaluos/${id}/pdf`, {
    responseType: 'blob',
    observe: 'response' // Esto es importante para obtener los headers
  });
}

// Método para ver PDF (abrir en nueva pestaña)
// Método para ver PDF (abrir en nueva pestaña)
verPdfEnNavegador(id: number): Observable<any> {
  const url = `${this.baseUrl}/avaluos/${id}/pdf?action=view`;
  // Abrir en nueva pestaña
  window.open(url, '_blank');
  return new Observable(observer => {
    observer.next(true); // Enviar un valor (puede ser cualquier cosa)
    observer.complete();
  });
}
  // Método para descargar PDF
  descargarPdf(id: number): Observable<{blob: Blob, nombre: string}> {
    return new Observable(observer => {
      this.http.get(`${this.baseUrl}/avaluos/${id}/pdf?action=download`, {
        responseType: 'blob',
        observe: 'response'
      }).subscribe({
        next: (response: HttpResponse<Blob>) => {
          // Extraer el nombre del archivo del header
          const contentDisposition = response.headers.get('Content-Disposition');
          let nombre = `avaluo-${id}.pdf`;
          
          if (contentDisposition) {
            const match = contentDisposition.match(/filename="([^"]+)"/);
            if (match && match[1]) {
              nombre = match[1];
            }
          }
          
          observer.next({
            blob: response.body as Blob,
            nombre: nombre
          });
          observer.complete();
        },
        error: (error) => observer.error(error)
      });
    });
  }
}
