import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class PermissionGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): boolean | UrlTree {
    const permission = route.data['permission'] as string;
    console.log('Permission requerida:', permission);

    const hasPerm = this.authService.hasPermission(permission);
    console.log('Permiso permitido:', hasPerm);

    if (hasPerm) {
      return true;
    }
    return this.router.parseUrl('/');  // o a donde quieras redirigir
  }
}
