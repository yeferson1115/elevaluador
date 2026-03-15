// src/app/services/alert.service.ts
import { Injectable } from '@angular/core';
import Swal, { SweetAlertIcon } from 'sweetalert2';

@Injectable({
  providedIn: 'root'
})
export class AlertService {

  success(message: string, title: string = 'Éxito') {
    Swal.fire(title, message, 'success');
  }

  error(message: string, title: string = 'Error') {
    Swal.fire(title, message, 'error');
  }

  info(message: string, title: string = 'Información') {
    Swal.fire(title, message, 'info');
  }

  warning(message: string, title: string = 'Advertencia') {
    Swal.fire(title, message, 'warning');
  }

  confirm(options: {
  title?: string,
  text?: string,
  confirmButtonText?: string,
  cancelButtonText?: string,
  icon?: SweetAlertIcon
} = {}): Promise<any> {
  return Swal.fire({
    title: options.title || '¿Estás seguro?',
    text: options.text || 'Esta acción no se puede deshacer.',
    icon: options.icon || 'warning',
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText || 'Sí',
    cancelButtonText: options.cancelButtonText || 'Cancelar'
  });
}
}
