import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface FasecoldaMemoria {
  codigo_fasecolda: string;
  registros: number;
  updated_at: string | null;
}

export interface FasecoldaMemoriaResponse {
  current_page: number;
  data: FasecoldaMemoria[];
  last_page: number;
  per_page: number;
  total: number;
}

@Injectable({
  providedIn: 'root'
})
export class FasecoldaService {
  private apiUrl = environment.apiUrl+'/fasecolda';

  constructor(private http: HttpClient) {}

  importarExcel(formData: FormData): Observable<any> {
    return this.http.post(`${this.apiUrl}/import`, formData);
  }

  getValores(codigo: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/${codigo}`);
  }

  buscarPorModelo(codigo: string, modelo: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${codigo}/modelo/${modelo}`);
  }


  getMemorias(params?: { page?: number; per_page?: number; codigo?: string }): Observable<FasecoldaMemoriaResponse> {
    return this.http.get<FasecoldaMemoriaResponse>(`${this.apiUrl}`, { params: params as any });
  }

  actualizarCodigo(codigoActual: string, codigoNuevo: string): Observable<any> {
    return this.http.put(`${this.apiUrl}/${codigoActual}`, {
      codigo_fasecolda: codigoNuevo
    });
  }

  eliminarMemoria(codigo: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${codigo}`);
  }
}