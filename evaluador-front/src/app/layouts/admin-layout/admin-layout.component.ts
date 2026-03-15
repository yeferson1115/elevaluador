import { Component,AfterViewInit  } from '@angular/core';
import { RouterOutlet,RouterModule } from '@angular/router';
import { HasPermissionDirective } from '../../core/directives/has-permission.directive';
import { Permissions } from '../../core/constants/permissions.const';
declare var $: any;
@Component({
  standalone: true,
  selector: 'app-admin-layout',
  imports: [RouterOutlet,RouterModule,HasPermissionDirective],
  templateUrl: './admin-layout.component.html',
})
export class AdminLayoutComponent implements AfterViewInit{
  Permissions = Permissions;
  user: any = null;

  constructor() {
    const userJson = localStorage.getItem('user');
    this.user = userJson ? JSON.parse(userJson) : null;
  }
 ngAfterViewInit(): void {
 setTimeout(() => {
      if (typeof $ !== 'undefined' && $.app && $.app.nav && $.app.nav.init) {
        $.app.nav.init(); // Inicializa el menú lateral
      } else {
        console.warn('El menú no pudo inicializarse: $.app.nav no está definido');
      }
    }, 0);
    (window as any).feather?.replace(); // para los íconos feather
    
  }

  logout(event: Event) {
    event.preventDefault(); // evitar que cargue el href antes de limpiar
    localStorage.clear(); // elimina todo del localStorage
    // Si solo quieres borrar el user y token puedes hacer:
    // localStorage.removeItem('user');
    // localStorage.removeItem('token');

    window.location.href = '/login'; // redirige al login
  }

  
}