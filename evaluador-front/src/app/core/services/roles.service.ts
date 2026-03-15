import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError } from 'rxjs/operators';
import { Observable } from 'rxjs';
import { BaseService } from './base.service'; 
import { environment } from '../../../environments/environment';

export interface Role {
  id: number;
  name: string;
  guard_name?: string;
}

@Injectable({ providedIn: 'root' })
export class RolesService extends BaseService{
  private baseUrl = environment.apiUrl;

  

  constructor(private http: HttpClient) {
    super(); // necesario al extender
  }

  getRoles(): Observable<any> {
    return this.http.get(`${this.baseUrl}/roles`).pipe(
      catchError(this.handleError)
    );
  }

  getRole(id: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/roles/${id}`).pipe(
      catchError(this.handleError)
    );
  }

  createRole(data: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/roles`, data).pipe(
      catchError(this.handleError)
    );
  }

  updateRole(id: number, data: any): Observable<any> {
    return this.http.put(`${this.baseUrl}/roles/${id}`, data).pipe(
      catchError(this.handleError)
    );
  }

  deleteRole(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/roles/${id}`).pipe(
      catchError(this.handleError)
    );
  }

  getPermissions(): Observable<any> {
    return this.http.get(`${this.baseUrl}/permissions`).pipe(
      catchError(this.handleError)
    );
  }

  getRolesPaginated(params: any): Observable<any> {
    return this.http.get<any>(`${this.baseUrl}/roles/datatables`, { params });
  }

   getallRoles(): Observable<Role[]> {
    return this.http.get<Role[]>(`${this.baseUrl}/getroles`);
  }
}
