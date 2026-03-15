import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

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
}