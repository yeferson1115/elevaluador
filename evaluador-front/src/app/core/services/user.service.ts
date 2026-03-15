import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Role {
  id: number;
  name: string;
  guard_name?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  document: string;
  phone: string;
  tarjeta_profecional:string;
  r_aa:string;
  profesion:string;
  roles: Role[];
  permissions: string[];
}

export interface PaginatedUsers {
  data: User[];
  current_page: number;
  last_page: number;
  total: number;
}

@Injectable({ providedIn: 'root' })
export class UserService {
  private baseUrl = environment.apiUrl+'/users';

  constructor(private http: HttpClient) {}

  getUsers(page = 1, search = '', perPage = 10): Observable<PaginatedUsers> {
  let params = new HttpParams()
    .set('page', page.toString())
    .set('per_page', perPage.toString());

  if (search.trim().length) {
    params = params.set('search', search.trim());
  }

  return this.http.get<PaginatedUsers>(this.baseUrl, { params });
}

  getUser(id: number): Observable<User> {
    return this.http.get<User>(`${this.baseUrl}/${id}`);
  }

  createUser(data: Partial<User> & { password: string }): Observable<User> {
    return this.http.post<User>(this.baseUrl, data);
  }

  updateUser(id: number, data: Partial<User>): Observable<User> {
    return this.http.put<User>(`${this.baseUrl}/${id}`, data);
  }

  deleteUser(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/${id}`);
  }

   getRoles(): Observable<Role[]> {
    return this.http.get<Role[]>(this.baseUrl);
  }
}
