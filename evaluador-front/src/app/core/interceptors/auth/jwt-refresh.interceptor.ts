import { Injectable } from '@angular/core';
import {
  HttpInterceptorFn,
  HttpRequest,
  HttpHandlerFn,
  HttpEvent,
  HttpErrorResponse,
} from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { Observable, from, throwError } from 'rxjs';
import { catchError, switchMap } from 'rxjs/operators';

export const jwtRefreshInterceptor: HttpInterceptorFn = (
  req: HttpRequest<any>,
  next: HttpHandlerFn
): Observable<HttpEvent<any>> => {
  const authService = inject(AuthService);
  const expiresAt = Number(localStorage.getItem('expires_at'));
  const token = localStorage.getItem('token');

  // Si el token está por expirar en menos de 60 segundos, refrescarlo
  if (token && Date.now() > (expiresAt - 60000)) {
    return from(authService.refreshToken()).pipe(
      switchMap(() => {
        const newToken = localStorage.getItem('token');
        const cloned = req.clone({
          setHeaders: { Authorization: `Bearer ${newToken}` },
        });
        return next(cloned);
      }),
      catchError((error: HttpErrorResponse) => {
        authService.logout();
        return throwError(() => error);
      })
    );
  }

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req);
};
