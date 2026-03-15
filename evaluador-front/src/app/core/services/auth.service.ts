import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private baseUrl = environment.apiUrl;

  constructor(private http: HttpClient,private router: Router) {}

  login(data: any): Observable<any> {
  return this.http.post(`${this.baseUrl}/login`, data).pipe(
    tap((res: any) => {
      const expiresAt = Date.now() + res.expires_in * 1000; // Guardar la hora de expiración

      localStorage.setItem('token', res.access_token);
      localStorage.setItem('user', JSON.stringify(res.user));
      localStorage.setItem('permissions', JSON.stringify(res.permissions || res.user?.permissions || []));
      localStorage.setItem('expires_at', expiresAt.toString());
    })
  );
}

  isAuthenticated(): boolean {
    const token = localStorage.getItem('token');
    const expiresAt = localStorage.getItem('expires_at');

    if (!token || !expiresAt) return false;

    return Date.now() < parseInt(expiresAt); // true si aún no ha expirado
  }

  getProfile(): Observable<any> {
    const token = localStorage.getItem('token');
    const headers = token ? new HttpHeaders().set('Authorization', `Bearer ${token}`) : undefined;
    return this.http.get(`${this.baseUrl}/profile`, { headers });
  }

  isLoggedIn(): boolean {
    return !!localStorage.getItem('token');
  }

  logout(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('permissions');
    localStorage.removeItem('expires_at');
    this.router.navigate(['/']);
  }

  hasPermission(permission: string): boolean {
    console.log(permission);
    const permissionsStr = localStorage.getItem('permissions');
    if (!permissionsStr) return false;
    const permissions: Array<{name: string}> = JSON.parse(permissionsStr);
    return permissions.some(p => p.name === permission);
  }

refreshToken(): Promise<void> {
  const token = localStorage.getItem('token');
  if (!token) return Promise.reject();

  return this.http.post(`${this.baseUrl}/refresh`, {}, {
    headers: { Authorization: `Bearer ${token}` }
  }).toPromise().then((res: any) => {
    const expiresAt = Date.now() + res.expires_in * 1000;
    localStorage.setItem('token', res.access_token);
    localStorage.setItem('user', JSON.stringify(res.user));
    localStorage.setItem('permissions', JSON.stringify(res.permissions || []));
    localStorage.setItem('expires_at', expiresAt.toString());
  }).catch(() => {
    this.logout();
  });
}

}
