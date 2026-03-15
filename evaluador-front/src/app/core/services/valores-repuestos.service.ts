import { Injectable } from '@angular/core';
import { HttpClient,HttpParams  } from '@angular/common/http';
import { Observable,map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ValoresRepuesto, PaginatedResponse, ApiResponseWrapper, ValoresRepuestoListResponse  } from '../models/valores-repuesto.model';



@Injectable({
  providedIn: 'root'
})
export class ValoresRepuestosService {
  private apiUrl = `${environment.apiUrl}/valores-repuestos`;
    private baseUrl = `${environment.apiUrl}/valores-repuesto`;
  private baseUrlAlternativa = `${environment.apiUrl}/valores-repuestos`;

  constructor(private http: HttpClient) {}



  // Obtener todos los registros con paginación y filtros
  getValoresRepuestos(params?: any): Observable<PaginatedResponse> {
    let httpParams = new HttpParams();
    
    if (params) {
      Object.keys(params).forEach(key => {
        if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
          httpParams = httpParams.set(key, params[key]);
        }
      });
    }
    
    // Extraemos el data.data para devolver directamente el PaginatedResponse
    return this.http.get<ValoresRepuestoListResponse>(this.baseUrl, { params: httpParams })
      .pipe(
        map(response => {
          if (response.success && response.data) {
            return response.data; // ← Esto es el PaginatedResponse
          }
          throw new Error(response.message || 'Error al cargar datos');
        })
      );
  }

  // Obtener un registro por ID
  getValoresRepuestoById(id: number): Observable<ValoresRepuesto> {
    return this.http.get<ApiResponseWrapper<ValoresRepuesto>>(`${this.baseUrl}/${id}`)
      .pipe(
        map(response => {
          if (response.success && response.data) {
            return response.data;
          }
          throw new Error(response.message || 'Error al cargar el registro');
        })
      );
  }

  // Crear un nuevo registro
  createValoresRepuesto(data: ValoresRepuesto): Observable<ApiResponseWrapper<any>> {
    return this.http.post<ApiResponseWrapper<any>>(this.baseUrl, data);
  }

  // Actualizar un registro
  updateValoresRepuesto(id: number, data: Partial<ValoresRepuesto>): Observable<ApiResponseWrapper<any>> {
    return this.http.put<ApiResponseWrapper<any>>(`${this.baseUrl}/${id}`, data);
  }

  // Eliminar un registro
  deleteValoresRepuesto(id: number): Observable<ApiResponseWrapper<any>> {
    return this.http.delete<ApiResponseWrapper<any>>(`${this.baseUrl}/${id}`);
  }

  // Obtener todos los tipos únicos
  getTipos(): Observable<string[]> {
    return this.http.get<ApiResponseWrapper<string[]>>(`${this.baseUrlAlternativa}/tipos`)
      .pipe(
        map(response => {
          if (response.success && Array.isArray(response.data)) {
            return response.data;
          }
          return [];
        })
      );
  }

  buscarPorCilindraje(clase: string, cilindraje: number, especial: boolean = false): Observable<any> {
    return this.http.get(`${this.apiUrl}/buscar`, {
      params: {
        clase,
        cilindraje: cilindraje.toString(),
        especial: especial ? '1' : '0'
      }
    });
  }

  /**
   * Buscar valor por rango (método alternativo)
   */
  buscarPorRango(clase: string, cilindraje: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/buscar-rango`, {
      params: {
        clase,
        cilindraje: cilindraje.toString()
      }
    });
  }

  /**
   * Obtener todos los valores para una clase
   */
  obtenerPorClase(clase: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/clase/${clase}`);
  }
}